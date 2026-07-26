<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;

/**
 * Jejak sinkronisasi attendance_daily/attendance_period -> student_attendance/
 * teacher_attendance (dkk) di DB utama. Diisi oleh job terjadwal Laravel,
 * bukan ditulis manual dari request user.
 */
class SyncLog extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'sync_log';
    public $timestamps = false; // cuma created_at

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
