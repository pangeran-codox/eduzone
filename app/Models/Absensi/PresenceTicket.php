<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STATUS: DORMAN. Tiket presence sekali-pakai yang diterbitkan LocalVerifier
 * untuk membuktikan HP guru ada di LAN sekolah (solusi CGNAT). Belum
 * diaktifkan - tahap awal cukup school_networks + GPS.
 *
 * Catatan schema: used_by_event_id SENGAJA TANPA foreign key di database
 * (beda dengan QrToken::usedByEvent() yang FK-nya ada) - relasi Eloquent di
 * bawah ini tetap jalan sebagai query biasa, cuma tidak ada constraint
 * integritas di level DB untuk kolom ini.
 */
class PresenceTicket extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'presence_tickets';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma issued_at

    protected $fillable = [
        'verifier_id',
        'nonce',
        'signature',
        'issued_at',
        'expires_at',
        'used_at',
        'used_by_event_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(LocalVerifier::class, 'verifier_id');
    }

    public function usedByEvent(): BelongsTo
    {
        return $this->belongsTo(AttendanceEvent::class, 'used_by_event_id');
    }
}
