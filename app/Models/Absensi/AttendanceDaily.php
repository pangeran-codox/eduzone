<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Rekap HARIAN per orang, hasil UPSERT dari attendance_events (lihat
 * App\Console\Commands\Absensi\AggregateAttendanceDaily). Struktur sengaja
 * mirip student_attendance/teacher_attendance di DB utama supaya
 * sinkronisasi via sync_log jadi mapping 1:1 (lihat SyncAttendanceDailyToMain).
 *
 * Migration set default id via DB::raw('gen_random_uuid()'), tapi kita
 * tetap pakai HasUuids di sini supaya Eloquent generate UUID di sisi
 * client SEBELUM insert — kalau mengandalkan DB default doang, ->id tidak
 * otomatis ke-fetch balik setelah create() (gotcha umum UUID PK di
 * Eloquent), padahal ->id dibutuhkan langsung buat sync_log.source_id.
 */
class AttendanceDaily extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'attendance_daily';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // pakai updated_at manual (DB default useCurrent)

    protected $fillable = [
        'school_id',
        'person_id',
        'person_type',
        'date',
        'first_check_in',
        'last_check_out',
        'status',
        'primary_method',
        'total_events',
        'has_anomaly',
    ];

    protected $casts = [
        'date' => 'date',
        'total_events' => 'integer',
        'has_anomaly' => 'boolean',
    ];
}