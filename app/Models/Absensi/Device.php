<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Terminal absensi fisik: kamera face-recognition, RFID reader, QR scanner,
 * hybrid, atau manual kiosk. id di-generate lokal (bukan sync dari DB utama).
 */
class Device extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'devices';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true; // devices punya created_at & updated_at standar

    protected $fillable = [
        'school_id',
        'device_code',
        'name',
        'device_type',
        'location',
        'default_class_id',
        'ip_address',
        'api_key_hash',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(DeviceKey::class, 'device_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'device_id');
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(QrToken::class, 'device_id');
    }
}
