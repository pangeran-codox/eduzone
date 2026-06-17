<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeConfig extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'grade_configs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'class_id',
        'academic_year',
        'semester',
        'kurikulum',
        'kkm',
        'is_finalized',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'kkm'          => 'integer',
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
    ];

    // Relationships
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
