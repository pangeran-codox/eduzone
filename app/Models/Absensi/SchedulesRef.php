<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cache read-only jadwal pelajaran dari DB utama (tabel schedules).
 * schedule_id SAMA PERSIS dengan id di DB utama - jangan generate baru di sini.
 */
class SchedulesRef extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'schedules_ref';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // pakai synced_at

    protected $fillable = [
        'schedule_id',
        'school_id',
        'class_id',
        'subject_name',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'schedule_id', 'schedule_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AttendancePeriod::class, 'schedule_id', 'schedule_id');
    }
}
