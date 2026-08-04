<?php

namespace App\Http\Controllers\Tenant\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\PeopleRef;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Asumsi yang dipakai di controller ini (sesuaikan ke schema asli):
 *
 * - Model App\Models\Absensi\AttendanceDaily (koneksi `pgsql_absensi`, database terpisah —
 *   lihat SKILL.md) diasumsikan punya kolom kira-kira begini:
 *     school_id, kelas_id, person_id, person_type ('siswa'|'guru'),
 *     tanggal, jam_masuk, metode ('rfid'|'qr'|'wajah'|'manual'), status
 *     ('hadir'|'terlambat'|'izin'|'sakit'|'alpa')
 *   Ganti nama kolom di query bawah kalau beda dari migration yang sebenarnya.
 *
 * - PeopleRef dipakai buat resolve nama siswa/guru (cache lokal, composite PK
 *   person_id+person_type) — SESUAI catatan SKILL.md: JANGAN pakai ::find(),
 *   selalu where('person_id', ...)->where('person_type', ...).
 *
 * - "Belum absen" dihitung dari siswa terdaftar di kelas itu (PeopleRef) yang
 *   TIDAK punya baris AttendanceDaily hari ini — bukan status literal di tabel,
 *   karena attendance_events/daily sifatnya insert-only per kejadian absen.
 *   Kalau ternyata ada kolom status 'belum' yang di-generate scheduler, sesuaikan.
 *
 * - Filtering school_id dilakukan manual (bukan lewat SchoolScope global scope),
 *   karena model Absensi ada di database terpisah dan tidak pakai trait
 *   BelongsToSchool yang berlaku untuk model di database utama.
 *
 * - $kelasId didapat dari data guru yang login (mis. relasi wali_kelas -> kelas_id
 *   di tabel utama). Di sini saya asumsikan itu tersedia lewat auth()->user()->kelas_id
 *   — ganti ke cara resolve yang sebenarnya dipakai di sistem kamu.
 */
class AbsensiController extends Controller
{
    public function dashboard(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $kelasId = auth()->user()->kelas_id;
        $today = Carbon::today();

        $dailyRecords = AttendanceDaily::where('school_id', $schoolId)
            ->where('kelas_id', $kelasId)
            ->where('tanggal', $today->toDateString())
            ->get();

        // Semua siswa terdaftar di kelas ini, buat tau siapa yang "belum absen"
        $studentsInClass = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'siswa')
            ->where('kelas_id', $kelasId)
            ->get();

        $recordedPersonIds = $dailyRecords->pluck('person_id');

        $records = $dailyRecords->map(function ($daily) {
            $person = PeopleRef::where('person_id', $daily->person_id)
                ->where('person_type', $daily->person_type)
                ->first();

            return [
                'nama' => $person->nama ?? '(Data tidak ditemukan)',
                'identitas' => $person->nis ?? $person->nip ?? '-',
                'waktu' => $daily->jam_masuk ? Carbon::parse($daily->jam_masuk)->format('H:i') : null,
                'metode' => $this->formatMetode($daily->metode),
                'status' => $this->normalizeStatus($daily->status),
            ];
        });

        // Tambahin siswa yang belum ada baris attendance_daily hari ini
        $belumAbsen = $studentsInClass->whereNotIn('person_id', $recordedPersonIds)
            ->map(fn ($person) => [
                'nama' => $person->nama,
                'identitas' => $person->nis ?? '-',
                'waktu' => null,
                'metode' => null,
                'status' => 'belum',
            ]);

        $records = $records->concat($belumAbsen)->sortBy('nama')->values();

        $stats = [
            'hadir' => $records->where('status', 'hadir')->count(),
            'terlambat' => $records->where('status', 'terlambat')->count(),
            'izin' => $records->whereIn('status', ['izin', 'sakit'])->count(),
            'alpa' => $records->where('status', 'alpa')->count(),
            'belum' => $records->where('status', 'belum')->count(),
        ];

        return view('tenant.absensi.dashboard', [
            'stats' => $stats,
            'records' => $records,
            'classes' => [], // wali kelas cuma pegang 1 kelas, jadi dropdown filter kelas nggak relevan di sini
        ]);
    }

    private function formatMetode(?string $metode): ?string
    {
        return match ($metode) {
            'rfid' => 'RFID',
            'qr' => 'QR',
            'wajah' => 'Wajah',
            'manual' => 'Manual',
            default => $metode,
        };
    }

    private function normalizeStatus(?string $status): string
    {
        // AttendanceDaily kemungkinan bedain 'izin' & 'sakit' terpisah,
        // tapi Blade dashboard-nya cuma punya satu bucket "Izin/Sakit".
        return in_array($status, ['izin', 'sakit']) ? 'izin' : ($status ?? 'belum');
    }
}