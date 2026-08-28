<?php

namespace App\Http\Controllers\Tenant\Absensi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Absensi\StoreDeviceRequest;
use App\Http\Requests\Tenant\Absensi\UpdateDeviceRequest;
use App\Models\Absensi\Device;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD device absensi di level TENANT — TU/Kepsek kelola device milik
 * sekolah mereka sendiri. Terpisah dari App\Http\Controllers\Superadmin\
 * DeviceController (yang lintas sekolah, superadmin only).
 *
 * school_id SELALU dipaksa dari auth()->user()->school_id, TIDAK PERNAH
 * dari input form — kalau tidak, TU sekolah A bisa (sengaja/tidak
 * sengaja) membuat device atas nama sekolah lain.
 *
 * PENTING: Device hidup di connection pgsql_absensi, TERPISAH dari
 * mekanisme BelongsToSchool/tenant middleware yang bekerja di DB utama.
 * Route-model-binding {device} otomatis resolve device dari UUID di URL
 * TANPA peduli sekolah mana — makanya setiap action selain index()/
 * create()/store() WAJIB panggil authorizeSchool() secara eksplisit,
 * kalau tidak jadi celah IDOR (TU sekolah A edit/hapus device sekolah B
 * cukup ganti UUID di URL).
 */
class DeviceController extends Controller
{
    public const DEVICE_TYPES = [
        'face_camera' => 'Kamera Wajah',
        'rfid_reader' => 'RFID Reader',
        'qr_scanner' => 'QR Scanner',
        'hybrid' => 'Hybrid (RFID + QR)',
        'manual_kiosk' => 'Kiosk Manual',
    ];

    public function index(Request $request): View
    {
        $devices = Device::where('school_id', $request->user()->school_id)
            ->orderBy('name')
            ->paginate(15);

        return view('tenant.absensi.devices.index', [
            'devices' => $devices,
            'deviceTypes' => self::DEVICE_TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tenant.absensi.devices.create', [
            'capabilityOptions' => Device::CAPABILITIES,
            'classes' => $this->classOptions($request),
        ]);
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->user()->school_id; // dipaksa, bukan dari input form
        $data['is_active'] = true;
        $data['device_type'] = Device::deriveDeviceType($data['capabilities']);

        [$rawKey, $hash] = $this->generateApiKey();
        $data['api_key_hash'] = $hash;

        $device = Device::create($data);

        return redirect()
            ->route('absensi.devices.index')
            ->with('success', "Device \"{$device->name}\" berhasil ditambahkan.")
            ->with('raw_api_key', $rawKey)
            ->with('raw_api_key_device', $device->name);
    }

    public function edit(Request $request, Device $device): View
    {
        $this->authorizeSchool($request, $device);

        return view('tenant.absensi.devices.edit', [
            'device' => $device,
            'capabilityOptions' => Device::CAPABILITIES,
            'classes' => $this->classOptions($request),
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $this->authorizeSchool($request, $device);

        $data = $request->validated();
        $data['device_type'] = Device::deriveDeviceType($data['capabilities']);

        $device->update($data);

        return redirect()
            ->route('absensi.devices.index')
            ->with('success', "Device \"{$device->name}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeSchool($request, $device);

        try {
            $device->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('absensi.devices.index')
                ->with('error', "Device \"{$device->name}\" tidak bisa dihapus permanen karena sudah punya riwayat absensi. Nonaktifkan saja lewat Edit.");
        }

        return redirect()
            ->route('absensi.devices.index')
            ->with('success', "Device \"{$device->name}\" berhasil dihapus.");
    }

    public function regenerateKey(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeSchool($request, $device);

        [$rawKey, $hash] = $this->generateApiKey();
        $device->update(['api_key_hash' => $hash]);

        return redirect()
            ->route('absensi.devices.index')
            ->with('success', "API key untuk \"{$device->name}\" berhasil di-regenerate. Key lama otomatis tidak berlaku.")
            ->with('raw_api_key', $rawKey)
            ->with('raw_api_key_device', $device->name);
    }

    private function authorizeSchool(Request $request, Device $device): void
    {
        abort_unless($device->school_id === $request->user()->school_id, 403);
    }

    private function classOptions(Request $request)
    {
        return SchoolClass::where('school_id', $request->user()->school_id)
            ->where('is_active', true)
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas', 'id');
    }

    /**
     * @return array{0: string, 1: string} [raw_key, sha256_hash]
     */
    private function generateApiKey(): array
    {
        $raw = Str::random(32);

        return [$raw, hash('sha256', $raw)];
    }
}