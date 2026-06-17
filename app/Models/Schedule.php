<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'schedules';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject',
        'grade',
        'major',
        'class_group',
        'class_id',
        'day',
        'start_time',
        'end_time',
        'room',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function teachingAttendances(): HasMany
    {
        return $this->hasMany(TeachingAttendance::class, 'schedule_id');
    }

    public function lessonJournals(): HasMany
    {
        return $this->hasMany(LessonJournal::class, 'schedule_id');
    }

    public function studentSubjectAttendances(): HasMany
    {
        return $this->hasMany(StudentSubjectAttendance::class, 'schedule_id');
    }
}
