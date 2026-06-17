<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'students';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'nis',
        'nisn',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
        'email',
        'grade',
        'major_id',
        'class_group',
        'class_id',
        'father_name',
        'mother_name',
        'father_job',
        'mother_job',
        'parent_address',
        'parent_phone',
        'joined_date',
        'status',
        'photo',
    ];

    protected $casts = [
        'birth_date'  => 'date',
        'joined_date' => 'date',
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

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class, 'student_id');
    }

    public function studentSikap(): HasMany
    {
        return $this->hasMany(StudentSikap::class, 'student_id');
    }

    public function studentAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class, 'student_id');
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentRecord::class, 'student_id');
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'student_id');
    }

    public function studentSubjectAttendances(): HasMany
    {
        return $this->hasMany(StudentSubjectAttendance::class, 'student_id');
    }
}
