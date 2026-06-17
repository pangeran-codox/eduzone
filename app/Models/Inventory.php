<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'inventory';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'item_name',
        'quantity',
        'condition',
        'location',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];
}
