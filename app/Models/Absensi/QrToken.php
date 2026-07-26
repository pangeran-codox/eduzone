<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STATUS: DORMAN. Token QR rotating (anti screenshot-share) - tabel & model
 * sudah ada tapi belum diaktifkan logikanya. Kiosk QR versi awal masih
 * pakai token statis per credential, bukan token yang berotasi lewat tabel
 * ini. Jangan bangun endpoint generate/rotate token kecuali diminta.
 */
class QrToken extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'qr_tokens';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // tidak ada created_at/updated_at

    protected $fillable = [
        'device_id',
        'token',
        'valid_from',
        'valid_until',
        'used_at',
        'used_by_event_id',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function usedByEvent(): BelongsTo
    {
        return $this->belongsTo(AttendanceEvent::class, 'used_by_event_id');
    }
}
