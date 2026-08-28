<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Absensi\StoreDeviceRequest;
use App\Http\Requests\Superadmin\Absensi\UpdateDeviceRequest;
use App\Models\Absensi\Device;
use App\Models\Absensi\SchoolRef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        $devices = Device::query()
            ->when($request->filled('school'), fn ($q) => $q->where('school_id', $request->input('school')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'ilike', $term)
                        ->orWhere('device_code', 'ilike', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $schools = SchoolRef::orderBy('name')->pluck('name', 'school_id');

        return view('superadmin.absensi.devices.index', [
            'devices' => $devices,
            'schools' => $schools,
            'search' => $request->input('search', ''),
            'schoolFilter' => $request->input('school', ''),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.absensi.devices.create', [
            'schools' => SchoolRef::orderBy('name')->pluck('name', 'school_id'),
            'capabilityOptions' => Device::CAPABILITIES,
        ]);
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['device_type'] = Device::deriveDeviceType($data['capabilities']);

        $rawKey = Str::random(32);
        $data['api_key_hash'] = hash('sha256', $rawKey);
        $data['is_active'] = true;

        $device = Device::create($data);

        // Key mentah CUMA ditampilkan sekali di sini (flash session), tidak
        // pernah disimpan plain di database.
        return redirect()
            ->route('superadmin.absensi.devices.index')
            ->with('generated_key', $rawKey)
            ->with('generated_device_code', $device->device_code);
    }

    public function edit(Device $device): View
    {
        return view('superadmin.absensi.devices.edit', [
            'device' => $device,
            'schools' => SchoolRef::orderBy('name')->pluck('name', 'school_id'),
            'capabilityOptions' => Device::CAPABILITIES,
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $data = $request->validated();
        $data['device_type'] = Device::deriveDeviceType($data['capabilities']);

        $device->update($data);

        return redirect()
            ->route('superadmin.absensi.devices.index')
            ->with('success', "Device \"{$device->name}\" berhasil diperbarui.");
    }

    /**
     * Generate ulang device key — dipakai kalau key lama dicurigai bocor,
     * atau device fisik diganti/di-reset. Key lama otomatis tidak berlaku
     * lagi begitu ini dijalankan (overwrite api_key_hash).
     */
    public function regenerateKey(Device $device): RedirectResponse
    {
        $rawKey = Str::random(32);

        $device->update([
            'api_key_hash' => hash('sha256', $rawKey),
        ]);

        return redirect()
            ->route('superadmin.absensi.devices.index')
            ->with('generated_key', $rawKey)
            ->with('generated_device_code', $device->device_code);
    }

    /**
     * Hapus device. Kalau ternyata device ini sudah punya riwayat
     * attendance_events (foreign key constraint), hapus permanen akan
     * gagal — arahkan ke nonaktifkan saja lewat pesan error, bukan crash
     * dengan stack trace SQL mentah ke superadmin.
     */
    public function destroy(Device $device): RedirectResponse
    {
        try {
            $device->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('superadmin.absensi.devices.index')
                ->with('error', "Device \"{$device->name}\" tidak bisa dihapus permanen karena sudah punya riwayat absensi. Nonaktifkan saja lewat tombol Edit (set Status ke Nonaktif).");
        }

        return redirect()
            ->route('superadmin.absensi.devices.index')
            ->with('success', "Device \"{$device->name}\" berhasil dihapus.");
    }
}