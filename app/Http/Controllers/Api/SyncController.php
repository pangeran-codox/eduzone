<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 3 endpoint internal untuk di-jemput (pull) berkala oleh absensi-gateway
 * (Go), mengisi schools_ref/people_ref/schedules_ref. Kontrak lengkap ada
 * di docs/laravel-sync-contract.md (repo absensi-gateway) - JANGAN ubah
 * bentuk response di sini tanpa update dokumen itu juga.
 *
 * Semua endpoint: response SELALU array JSON polos (bukan {"data":[...]}),
 * urut ASC by updated_at (penting untuk konsistensi pagination), dan
 * PERSIS per_page baris per halaman kecuali halaman terakhir.
 */
class SyncController extends Controller
{
    public function schools(Request $request): JsonResponse
    {
        $query = School::query();

        $this->applyUpdatedSince($query, $request);

        // Sekolah tanpa koordinat GPS SENGAJA di-skip - kontrak mewajibkan
        // latitude/longitude/geofence_radius_meters ada nilainya, dan kolom
        // ini baru ditambahkan (nullable) jadi sekolah lama mungkin belum
        // diisi. Skip di sini, bukan kirim 0/0 yang salah dan bisa lolos
        // validasi geofencing secara keliru.
        $query->whereNotNull('latitude')->whereNotNull('longitude');

        $schools = $query
            ->orderBy('updated_at')
            ->forPage(
                (int) $request->query('page', 1),
                (int) $request->query('per_page', 500)
            )
            ->get();

        return response()->json(
            $schools->map(fn ($s) => [
                'school_id' => $s->id,
                'name' => $s->name,
                'latitude' => (float) $s->latitude,
                'longitude' => (float) $s->longitude,
                'geofence_radius_meters' => (int) ($s->geofence_radius_meters ?? 150),
                // null kalau admin belum atur jam masuk - JANGAN kasih default
                // di sini, gateway sengaja tidak menandai Terlambat sampai
                // field ini terisi (lihat laravel-sync-contract.md).
                'late_cutoff_time' => $s->late_cutoff_time,
                'is_active' => (bool) $s->is_active,
                'updated_at' => $this->toRfc3339($s->updated_at),
            ])->values()
        );
    }

    public function people(Request $request): JsonResponse
    {
        $updatedSince = $request->filled('updated_since')
            ? Carbon::parse($request->query('updated_since'))
            : null;

        $students = DB::table('students')
            ->select([
                'id as person_id',
                'school_id',
                DB::raw("'student' as person_type"),
                'full_name',
                'photo',
                'class_id',
                'grade',
                DB::raw("(status = 'aktif') as is_active"),
                'updated_at',
            ])
            ->when($updatedSince, fn ($q) => $q->where('updated_at', '>', $updatedSince));

        $teachers = DB::table('teachers')
            ->select([
                'id as person_id',
                'school_id',
                DB::raw("'teacher' as person_type"),
                'full_name',
                'photo',
                DB::raw('NULL as class_id'),
                DB::raw('NULL as grade'),
                'is_active',
                'updated_at',
            ])
            ->when($updatedSince, fn ($q) => $q->where('updated_at', '>', $updatedSince));

        $staff = DB::table('staff')
            ->select([
                'id as person_id',
                'school_id',
                DB::raw("'staff' as person_type"),
                'full_name',
                'photo',
                DB::raw('NULL as class_id'),
                DB::raw('NULL as grade'),
                'is_active',
                'updated_at',
            ])
            ->when($updatedSince, fn ($q) => $q->where('updated_at', '>', $updatedSince));

        $rows = $students
            ->unionAll($teachers)
            ->unionAll($staff)
            ->orderBy('updated_at')
            ->forPage(
                (int) $request->query('page', 1),
                (int) $request->query('per_page', 500)
            )
            ->get();

        return response()->json(
            $rows->map(fn ($p) => [
                'person_id' => $p->person_id,
                'school_id' => $p->school_id,
                'person_type' => $p->person_type,
                'full_name' => $p->full_name,
                'photo_url' => $this->resolvePhotoUrl($p->photo),
                'class_id' => $p->class_id,
                'grade' => $p->grade,
                'is_active' => (bool) $p->is_active,
                'updated_at' => $this->toRfc3339($p->updated_at),
            ])->values()
        );
    }

    public function schedules(Request $request): JsonResponse
    {
        // Peta nama hari Indonesia -> ISO 8601 (1=Senin .. 7=Minggu), sesuai
        // yang diwajibkan kontrak. HARUS persis 7 kunci ini - kalau ada
        // variasi penulisan lain di data (mis. "senin" huruf kecil), tambah
        // normalisasi di sini, jangan di tempat lain.
        $dayMap = [
            'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4,
            'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7,
        ];

        $query = Schedule::query()
            // Jadwal yang belum lengkap (belum ada guru/kelas ditentukan)
            // di-skip - schedules_ref mewajibkan keduanya NOT NULL.
            ->whereNotNull('teacher_id')
            ->whereNotNull('class_id');

        $this->applyUpdatedSince($query, $request);

        $schedules = $query
            ->orderBy('updated_at')
            ->forPage(
                (int) $request->query('page', 1),
                (int) $request->query('per_page', 500)
            )
            ->get();

        $mapped = $schedules->map(function ($sch) use ($dayMap) {
            $dayOfWeek = $dayMap[$sch->day] ?? null;

            // Nama hari yang tidak dikenali (typo data, dsb) - skip baris
            // ini daripada kirim day_of_week yang salah/null ke gateway.
            if ($dayOfWeek === null) {
                return null;
            }

            return [
                'schedule_id' => $sch->id,
                'school_id' => $sch->school_id,
                'class_id' => $sch->class_id,
                'subject_name' => $sch->subject,
                'teacher_id' => $sch->teacher_id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $sch->start_time,
                'end_time' => $sch->end_time,
                'is_active' => (bool) $sch->is_active,
                'updated_at' => $this->toRfc3339($sch->updated_at),
            ];
        })->filter()->values();

        return response()->json($mapped);
    }

    private function applyUpdatedSince($query, Request $request): void
    {
        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', Carbon::parse($request->query('updated_since')));
        }
    }

    private function toRfc3339($value): string
    {
        return Carbon::parse($value)->toRfc3339String();
    }

    /**
     * ASUMSI (belum ada konvensi lain yang ada di project ini untuk
     * dicontoh): kolom `photo` di students/teachers/staff berisi path
     * relatif di disk 'public' (hasil Storage::disk('public')->put(...)),
     * diakses lewat symlink `php artisan storage:link`. Kalau ternyata
     * project pakai disk lain (S3, dll) atau path absolut, sesuaikan
     * method ini SAJA - tidak ada tempat lain yang perlu diubah.
     *
     * Sengaja balikin null (bukan string kosong) kalau tidak ada foto,
     * sesuai kontrak - person_type dari gateway generate avatar inisial
     * SVG sendiri untuk kasus ini.
     */
    private function resolvePhotoUrl(?string $photo): ?string
    {
        if (! $photo) {
            return null;
        }

        return Storage::disk('public')->url($photo);
    }
}