<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * STATUS: DORMAN. Registry "Local Presence Verifier" untuk sekolah dengan
 * ISP residensial ber-CGNAT. Tahap awal cukup GPS + IP publik stabil
 * (school_networks) - jangan bangun logic verifier ini kecuali diminta.
 */
class LocalVerifier extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'local_verifiers';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma created_at, tanpa updated_at

    protected $fillable = [
        'school_id',
        'internal_hostname',
        'public_key',
        'algorithm',
        'is_active',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_heartbeat_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    public function presenceTickets(): HasMany
    {
        return $this->hasMany(PresenceTicket::class, 'verifier_id');
    }
}
