<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Terminal absensi fisik: kamera face-recognition, RFID reader, QR scanner,
 * hybrid, atau manual kiosk. id di-generate lokal (bukan sync dari DB utama).
 *
 * capabilities: array metode check-in yang aktif di device ini (subset dari
 * CAPABILITIES di bawah) — SUMBER KEBENARAN. device_type adalah LABEL yang
 * diturunkan otomatis dari capabilities (lihat deriveDeviceType()), jangan
 * di-set manual dari form lagi supaya tidak ada dua sumber yang bisa
 * tidak sinkron.
 */
class Device extends Model
{
    use HasUuids;

    public const CAPABILITIES = [
        'rfid' => 'RFID',
        'qr' => 'QR Code',
        'face' => 'Wajah',
        'fingerprint' => 'Sidik Jari',
        'manual' => 'Input Manual (NIS/NIP)',
    ];

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
        'capabilities',
        'location',
        'default_class_id',
        'ip_address',
        'api_key_hash',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
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

    /**
     * Turunkan label device_type dari daftar capabilities — dipanggil dari
     * controller saat store()/update(), bukan diisi manual dari form.
     * fingerprint tunggal -> fingerprint_reader (butuh migration yang
     * menambahkan value ini ke CHECK constraint, lihat
     * 2026_08_16_000001_add_capabilities_to_devices_table).
     */
    public static function deriveDeviceType(array $capabilities): string
    {
        sort($capabilities);

        return match (true) {
            count($capabilities) === 0 => 'manual_kiosk',
            $capabilities === ['face'] => 'face_camera',
            $capabilities === ['rfid'] => 'rfid_reader',
            $capabilities === ['qr'] => 'qr_scanner',
            $capabilities === ['fingerprint'] => 'fingerprint_reader',
            $capabilities === ['manual'] => 'manual_kiosk',
            default => 'hybrid',
        };
    }
}