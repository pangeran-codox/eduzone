<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'subscriptions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'plan',
        'started_at',
        'expired_at',
        'amount',
        'invoice_no',
        'status',
        'note',
        'created_at',
    ];

    protected $casts = [
        'started_at'  => 'date',
        'expired_at'  => 'date',
        'amount'      => 'decimal:2',
        'created_at'  => 'datetime',
    ];
}
