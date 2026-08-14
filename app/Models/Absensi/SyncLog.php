<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;

/**
 * Jejak sinkronisasi attendance_daily/attendance_period -> student_attendance/
 * teacher_attendance di DB utama. target_table SENGAJA cuma string biasa
 * (bukan FK) karena nunjuk tabel di database lain (DB utama), bukan di
 * pgsql_absensi.
 */
class SyncLog extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'sync_log';
    public $timestamps = false; // created_at diisi otomatis via DB default useCurrent

    protected $fillable = [
        'source_table',
        'source_id',
        'target_table',
        'status',
        'error_message',
        'attempted_at',
        'synced_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}