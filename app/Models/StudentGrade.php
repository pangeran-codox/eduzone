<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGrade extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'student_grades';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'teacher_id',
        'subject',
        'academic_year',
        'semester',
        'nilai_harian',
        'nilai_tugas',
        'nilai_akhir',
        'predikat',
        'catatan',
    ];

    protected $casts = [
        'nilai_harian' => 'decimal:2',
        'nilai_tugas'  => 'decimal:2',
        'nilai_akhir'  => 'decimal:2',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
