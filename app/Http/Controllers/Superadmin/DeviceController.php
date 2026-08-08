<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Absensi\StoreDeviceRequest;
use App\Http\Requests\Superadmin\Absensi\UpdateDeviceRequest;
use App\Models\Absensi\Device;
use App\Models\Absensi\SchoolRef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * Tipe device yang diizinkan — HARUS sinkron persis dengan CHECK
     * constraint "devices_device_type_check" di database (lihat SKILL.md
     * gotcha soal ini, 'rfid' bukan value valid, gampang salah tebak).
     */
    private const DEVICE_TYPES = [
        'face_camera' => 'Kamera Wajah',
        'rfid_reader' => 'RFID Reader',
        'qr_scanner' => 'QR Scanner',
        'hybrid' => 'Hybrid (RFID + QR)',
        'manual_kiosk' => 'Kiosk Manual',
    ];

    public function index(): View
    {
        $devices = Device::orderBy('name')->get();
        $schools = SchoolRef::pluck('name', 'school_id');

        return view('superadmin.absensi.devices.index', [
            'devices' => $devices,
            'schools' => $schools,
            'deviceTypes' => self::DEVICE_TYPES,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.absensi.devices.create', [
            'schools' => SchoolRef::orderBy('name')->pluck('name', 'school_id'),
            'deviceTypes' => self::DEVICE_TYPES,
        ]);
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $rawKey = Str::random(32);

        $device = Device::create([
            ...$request->validated(),
            'api_key_hash' => hash('sha256', $rawKey),
            'is_active' => true,
        ]);

        // Key mentah CUMA ditampilkan sekali di sini (flash session), tidak
        // pernah disimpan plain di database — sama seperti generate manual
        // yang sebelumnya dilakukan lewat tinker.
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
            'deviceTypes' => self::DEVICE_TYPES,
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $device->update($request->validated());

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
