<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agregat per JAM PELAJARAN (beda dari AttendanceDaily yang per hari) - bisa
 * ada banyak baris per orang per hari, satu per schedule_id. Disync ke
 * student_subject_attendance (siswa) & teaching_attendance (guru) di DB utama.
 */
class AttendancePeriod extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'attendance_period';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma updated_at

    protected $fillable = [
        'school_id',
        'schedule_id',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SchedulesRef::class, 'schedule_id', 'schedule_id');
    }
}
