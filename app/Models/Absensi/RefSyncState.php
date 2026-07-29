<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;

/**
 * Status sinkronisasi MASUK (Laravel -> absensi-gateway) untuk
 * schools_ref/people_ref/schedules_ref. Ditulis oleh Go
 * (internal/sync/puller.go) - Laravel TIDAK PERNAH menulis ke tabel ini,
 * model ini cuma buat DIBACA (mis. nanti buat dashboard monitoring status
 * sync: "terakhir sukses kapan", "berapa record", "ada error apa").
 */
class RefSyncState extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'ref_sync_state';
    protected $primaryKey = 'resource';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'last_synced_at' => 'datetime',
        'last_record_count' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Laravel tidak boleh menulis ke tabel ini - kalau ada kode yang
     * mencoba save()/create()/update(), gagalkan eksplisit supaya
     * ketahuan cepat kalau ada yang salah asumsi soal kepemilikan data ini.
     */
    protected static function booted(): void
    {
        static::saving(function () {
            throw new \RuntimeException(
                'ref_sync_state ditulis oleh absensi-gateway (Go), bukan Laravel. Model ini read-only.'
            );
        });
    }
}
