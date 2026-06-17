<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'slug',
        'name',
        'color_primary',
        'color_secondary',
        'icon',
    ];

    protected $casts = [];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
