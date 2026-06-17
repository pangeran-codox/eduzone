<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'staff';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'user_id',
        'full_name',
        'nip',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
        'email',
        'position',
        'joined_date',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'birth_date'  => 'date',
        'joined_date' => 'date',
        'is_active'   => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'staff_id');
    }

    public function toolmanReports(): HasMany
    {
        return $this->hasMany(ToolmanReport::class, 'staff_id');
    }
}
