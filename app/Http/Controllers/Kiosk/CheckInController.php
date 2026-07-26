<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Absensi\Device;
use App\Services\Absensi\AttendanceRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman & endpoint untuk layar kiosk fisik di device absensi (RFID/QR/manual).
 * SENGAJA TANPA middleware auth/tenant standar (lihat routes/kiosk.php) - ini
 * device publik yang berdiri di lokasi fisik sekolah, bukan halaman user login.
 * Identitas & scope sekolah ditentukan dari device_code di URL, bukan dari sesi user.
 *
 * CATATAN OPEN ITEM (lihat ARCHITECTURE.md §2.5): ada repo Go terpisah
 * (`absensi-gateway`) yang juga punya file checkin_device.go/checkin_teacher.go -
 * pembagian endpoint mana yang dipegang Go vs Laravel belum dipetakan. Controller
 * ini dibuat supaya modul Absensi punya SATU halaman kerja end-to-end dulu;
 * kalau nanti diputuskan endpoint check-in dipegang Go, halaman Blade ini bisa
 * tetap dipakai sebagai UI-nya, cuma target fetch() di kiosk.js diarahkan ke
 * absensi-gateway, bukan endpoint Laravel ini.
 */
class CheckInController extends Controller
{
    public function show(string $deviceCode): View
    {
        $device = Device::where('device_code', $deviceCode)
            ->where('is_active', true)
            ->firstOrFail();

        $school = $device->school; // SchoolRef, lewat relasi belongsTo

        return view('kiosk.checkin', [
            'device' => $device,
            'school' => $school,
        ]);
    }

    public function store(Request $request, string $deviceCode, AttendanceRecorder $recorder): JsonResponse
    {
        $device = Device::where('device_code', $deviceCode)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $this->deviceKeyValid($request, $device)) {
            return response()->json(['message' => 'Device key tidak valid.'], 401);
        }

        $data = $request->validate([
            'method' => ['required', 'in:rfid,qr,manual'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        // Aman diupdate langsung (bukan attendance_events) - device boleh punya
        // last_seen_at yang mutable, beda dengan raw log yang insert-only.
        $device->forceFill(['last_seen_at' => now()])->save();

        $result = $recorder->record($device, $data['method'], $data['value']);

        return response()->json($result);
    }

    /**
     * Validasi header X-Device-Key terhadap devices.api_key_hash (sha256 hex,
     * konsisten dengan pola di 02_seed.sql absensi-gateway). Raw key TIDAK
     * pernah disimpan di database (cuma hash-nya) - key mentah cuma ada di
     * kertas/dokumen provisioning device & di localStorage browser kiosk
     * (lihat kiosk.js). Ini keamanan level dasar untuk MVP, BUKAN pengganti
     * device signing Ed25519 (device_keys, masih dorman - lihat SKILL.md).
     */
    private function deviceKeyValid(Request $request, Device $device): bool
    {
        $providedKey = $request->header('X-Device-Key');

        if (! $providedKey || ! $device->api_key_hash) {
            return false;
        }

        return hash_equals($device->api_key_hash, hash('sha256', $providedKey));
    }
}
