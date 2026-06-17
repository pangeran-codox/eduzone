<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'majors';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'name',
        'abbreviation',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'major_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'major_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'major_id');
    }
}
