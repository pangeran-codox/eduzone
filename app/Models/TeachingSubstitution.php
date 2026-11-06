<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeachingSubstitution extends Model
{
    use HasUuids, BelongsToSchool;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'schedule_id',
        'date',
        'original_teacher_id',
        'substitute_teacher_id',
        'reason',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}