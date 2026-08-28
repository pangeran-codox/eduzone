<?php

namespace App\Http\Controllers\Tenant\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceEvent;
use App\Models\Absensi\SchoolRef;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Absensi\GatewayTokenIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Absen mandiri via HP dengan validasi geofencing.
 *
 * DUA JALUR BEDA tergantung role, SENGAJA tidak disatukan:
 *
 * 1. GURU (guru_mapel/wali_kelas) -> lewat absensi-gateway (Go) via
 *    endpoint POST /api/v1/checkin/teacher, diautentikasi JWT dari
 *    GatewayTokenIssuer. Ini jalur RESMI sesuai arsitektur (gateway =
 *    authoritative write path) — gateway yang urus geofencing GPS,
 *    validasi jaringan WiFi sekolah (school_networks), dan insert ke
 *    attendance_events dengan hash-chaining/signing kalau nanti
 *    diaktifkan. Laravel TIDAK menulis attendance_events sendiri untuk
 *    jalur ini.
 *
 * 2. SISWA & STAFF NON-GURU (kepsek/tu/kurikulum/kesiswaan/bk/toolman) ->
 *    TETAP direct-write ke attendance_events dari Laravel. Gateway BELUM
 *    punya endpoint setara buat role ini (cuma checkin/teacher yang ada)
 *    — begitu gateway sudah punya endpoint untuk siswa/staff, jalur ini
 *    harus dipindah juga, ini bukan keputusan desain permanen, cuma
 *    keterbatasan gateway saat ini.
 */
class AbsenHpController extends Controller
{
    public function show(Request $request)
    {
        $school = SchoolRef::find($request->user()->school_id);

        return view('tenant.absensi.absen-hp', [
            'school' => $school,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'event_type' => ['required', Rule::in(['check_in', 'check_out'])],
        ]);

        $user = $request->user();

        if (in_array($user->role, ['guru_mapel', 'wali_kelas'], true)) {
            return $this->storeViaGateway($request, $validated);
        }

        return $this->storeDirect($request, $validated);
    }

    /**
     * Jalur guru — lewat absensi-gateway, bukan tulis langsung.
     */
    private function storeViaGateway(Request $request, array $validated)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $teacherId = Teacher::where('user_id', $user->id)->value('id');

        if (! $teacherId) {
            return back()->withErrors([
                'location' => 'Akun Anda belum terhubung ke data guru. Hubungi admin.',
            ]);
        }

        $token = (new GatewayTokenIssuer())->issueForTeacher($teacherId, $schoolId);
        $baseUrl = config('services.absensi_gateway.base_url');

        try {
            $response = Http::withToken($token)
                ->timeout(8)
                ->post("{$baseUrl}/api/v1/checkin/teacher", [
                    'event_type' => $validated['event_type'],
                    'latitude' => (float) $validated['latitude'],
                    'longitude' => (float) $validated['longitude'],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gagal konek ke absensi-gateway saat checkin/teacher', [
                'error' => $e->getMessage(),
                'teacher_id' => $teacherId,
            ]);

            return back()->withErrors([
                'location' => 'Layanan absensi sedang tidak bisa dihubungi. Coba lagi sebentar, atau hubungi admin kalau berlanjut.',
            ]);
        }

        if ($response->failed()) {
            Log::error('absensi-gateway menolak checkin/teacher', [
                'status' => $response->status(),
                'body' => $response->body(),
                'teacher_id' => $teacherId,
            ]);

            $message = $response->json('message') ?? 'Absen ditolak oleh layanan absensi.';

            return back()->withErrors(['location' => $message]);
        }

        $body = $response->json();
        $label = $validated['event_type'] === 'check_in' ? 'Absen masuk' : 'Absen pulang';

        if (($body['status'] ?? null) === 'accepted_with_flag') {
            $reasons = implode(', ', $body['anomaly_reasons'] ?? []);

            return back()->with(
                'success',
                "{$label} tercatat, tapi ditandai untuk verifikasi ({$reasons}). Tetap dihitung hadir, TU/wali kelas mungkin akan menghubungi Anda untuk konfirmasi."
            );
        }

        return back()->with('success', "{$label} berhasil dicatat.");
    }

    /**
     * Jalur siswa & staff non-guru — direct-write, gateway belum punya
     * endpoint setara. Lihat catatan class-level di atas.
     */
    private function storeDirect(Request $request, array $validated)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $school = SchoolRef::find($schoolId);

        if (! $school) {
            return back()->withErrors([
                'location' => 'Data lokasi sekolah belum tersedia. Hubungi admin.',
            ]);
        }

        $distance = $this->haversineDistanceMeters(
            (float) $school->latitude,
            (float) $school->longitude,
            $validated['latitude'],
            $validated['longitude']
        );

        if ($distance > $school->geofence_radius_meters) {
            return back()->withErrors([
                'location' => sprintf(
                    'Anda berada %d meter dari sekolah (radius maksimal %d meter). Absen ditolak — pastikan Anda berada di area sekolah.',
                    round($distance),
                    $school->geofence_radius_meters
                ),
            ]);
        }

        [$personId, $personType] = $this->resolvePerson($user);

        if (! $personId) {
            return back()->withErrors([
                'location' => 'Akun Anda belum terhubung ke data staff/siswa. Hubungi admin.',
            ]);
        }

        $accuracy = $validated['accuracy'] ?? null;
        $poorAccuracy = $accuracy !== null && $accuracy > $school->geofence_radius_meters;

        AttendanceEvent::create([
            'school_id' => $schoolId,
            'device_id' => null,
            'schedule_id' => null,
            'person_id' => $personId,
            'person_type' => $personType,
            'method' => 'mobile_gps',
            'event_type' => $validated['event_type'],
            'confidence_score' => null,
            'is_valid' => true,
            'flagged_reason' => $poorAccuracy
                ? sprintf('Akurasi GPS rendah (±%dm), melebihi radius geofence (%dm)', round($accuracy), $school->geofence_radius_meters)
                : null,
            'raw_payload' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy_meters' => $accuracy,
                'distance_from_school_meters' => round($distance, 1),
                'user_agent' => $request->userAgent(),
            ],
            'recorded_at' => now(),
        ]);

        $label = $validated['event_type'] === 'check_in' ? 'Absen masuk' : 'Absen pulang';

        return back()->with('success', "{$label} berhasil dicatat.");
    }

    /**
     * @return array{0: ?string, 1: ?string} [person_id, person_type]
     */
    private function resolvePerson($user): array
    {
        return match ($user->role) {
            'siswa' => [
                Student::where('user_id', $user->id)->value('id'),
                'student',
            ],
            'kepsek', 'tu', 'kurikulum', 'kesiswaan', 'bk', 'toolman' => [
                Staff::where('user_id', $user->id)->value('id'),
                'staff',
            ],
            default => [null, null],
        };
    }

    private function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }
}