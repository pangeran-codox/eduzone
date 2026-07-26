<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STATUS: DORMAN. Kunci Ed25519 per device untuk device signing - tabel &
 * model sudah disiapkan tapi logika signing/verifikasi belum diaktifkan.
 * Jangan bikin controller/endpoint untuk ini kecuali diminta eksplisit.
 */
class DeviceKey extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'device_keys';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // registered_at, bukan created_at/updated_at

    protected $fillable = [
        'device_id',
        'public_key',
        'algorithm',
        'is_active',
        'registered_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'registered_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
