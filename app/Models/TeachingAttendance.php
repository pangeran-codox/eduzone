<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAttendance extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'teaching_attendance';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'schedule_id',
        'teacher_id',
        'date',
        'start_time',
        'end_time',
        'topic',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'date'       => 'date',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function studentSubjectAttendances(): HasMany
    {
        return $this->hasMany(StudentSubjectAttendance::class, 'teaching_attendance_id');
    }
}
