<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectAttendance extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'student_subject_attendance';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'teaching_attendance_id',
        'student_id',
        'schedule_id',
        'date',
        'status',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'date'       => 'date',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function teachingAttendance(): BelongsTo
    {
        return $this->belongsTo(TeachingAttendance::class, 'teaching_attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
