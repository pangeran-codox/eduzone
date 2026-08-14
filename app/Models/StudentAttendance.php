<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Absensi harian siswa di DB UTAMA — target sinkronisasi dari
 * App\Models\Absensi\AttendanceDaily (pgsql_absensi), lihat
 * App\Console\Commands\Absensi\SyncAttendanceDailyToMain.
 *
 * JANGAN diisi manual dari controller check-in device — satu-satunya
 * penulis baris di sini adalah job sync ini. Kalau nanti ada fitur
 * "input manual Izin/Sakit oleh TU", itu juga harus lewat model ini
 * (bukan tabel baru), supaya tidak ada dua sumber kebenaran.
 */
class StudentAttendance extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'student_attendance';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // migration cuma punya created_at (DB default useCurrent)

    protected $fillable = [
        'school_id',
        'student_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}