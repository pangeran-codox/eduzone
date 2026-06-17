<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'teachers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'nip',
        'nuptk',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
        'email',
        'last_education',
        'education_major',
        'employment_status',
        'joined_date',
        'major_id',
        'is_homeroom',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'birth_date'   => 'date',
        'joined_date'  => 'date',
        'is_homeroom'  => 'boolean',
        'is_active'    => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class, 'teacher_id');
    }

    public function labBookings(): HasMany
    {
        return $this->hasMany(LabBooking::class, 'teacher_id');
    }

    public function lessonJournals(): HasMany
    {
        return $this->hasMany(LessonJournal::class, 'teacher_id');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'teacher_id');
    }

    public function teachingAttendances(): HasMany
    {
        return $this->hasMany(TeachingAttendance::class, 'teacher_id');
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'teacher_id');
    }
}
