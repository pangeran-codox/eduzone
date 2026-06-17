<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'teacher_attendance';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'recorded_by',
        'created_at',
    ];

    protected $casts = [
        'date'       => 'date',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
