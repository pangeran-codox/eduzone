<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'classes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'grade',
        'major_id',
        'class_group',
        'academic_year',
        'nama_kelas',
        'kapasitas',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function gradeConfigs(): HasMany
    {
        return $this->hasMany(GradeConfig::class, 'class_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    public function lessonJournals(): HasMany
    {
        return $this->hasMany(LessonJournal::class, 'class_id');
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'class_id');
    }

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class, 'class_id');
    }

    public function studentSikap(): HasMany
    {
        return $this->hasMany(StudentSikap::class, 'class_id');
    }
}
