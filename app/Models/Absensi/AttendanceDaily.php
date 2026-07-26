<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agregat HARIAN per orang, hasil UPSERT dari attendance_events (bukan
 * ditulis manual). Struktur sengaja mirip student_attendance/
 * teacher_attendance di DB utama supaya sync via sync_log jadi mapping 1:1.
 */
class AttendanceDaily extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'attendance_daily';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma updated_at, tanpa created_at

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
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_events' => 'integer',
        'has_anomaly' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }
}
