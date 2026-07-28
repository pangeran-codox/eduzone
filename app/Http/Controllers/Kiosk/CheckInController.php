<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Absensi\Device;
use Illuminate\View\View;

/**
 * Halaman kiosk fisik di device absensi (RFID/QR/Face/manual).
 * SENGAJA TANPA middleware auth/tenant standar (lihat routes/kiosk.php) - ini
 * device publik yang berdiri di lokasi fisik sekolah, bukan halaman user login.
 * Identitas & scope sekolah ditentukan dari device_code di URL, bukan dari sesi user.
 *
 * KEPUTUSAN FINAL (lihat ARCHITECTURE.md §2.6): endpoint check-in device
 * dipegang PENUH oleh `absensi-gateway` (Go) - controller ini cuma render
 * halaman Blade-nya. kiosk.js manggil langsung ke gateway (lewat proxy NPM
 * /gateway), BUKAN ke Laravel. Method store()/deviceKeyValid() yang dulu ada
 * di sini sudah dihapus karena redundant dengan checkin_device.go di gateway.
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
}