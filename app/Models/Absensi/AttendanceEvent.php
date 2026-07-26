<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RAW LOG - INSERT-ONLY. Setiap tap/scan/deteksi masuk sini apa adanya,
 * termasuk yang gagal/diragukan. Sumber kebenaran mentah untuk audit &
 * anti-fraud. Model ini SENGAJA memblokir update() dan delete() lewat
 * model event - kalau butuh "membatalkan" sebuah event, insert event baru
 * (mis. event_type/flagged_reason yang menjelaskan koreksi), jangan
 * mengubah baris lama.
 *
 * row_hash/prev_hash/signature: kolom hash chaining & device signing sudah
 * ada tapi BELUM DIISI - lapisan keamanan ini belum diaktifkan.
 */
class AttendanceEvent extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'attendance_events';
    public $timestamps = false; // pakai recorded_at

    protected $fillable = [
        'school_id',
        'device_id',
        'schedule_id',
        'person_id',
        'person_type',
        'method',
        'event_type',
        'confidence_score',
        'is_valid',
        'flagged_reason',
        'raw_payload',
        'recorded_at',
        'row_hash',
        'prev_hash',
        'signature',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'is_valid' => 'boolean',
        'raw_payload' => 'array',
        'recorded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException(
                'attendance_events bersifat insert-only. Jangan update baris yang sudah ada - insert event baru sebagai gantinya.'
            );
        });

        static::deleting(function () {
            throw new \RuntimeException(
                'attendance_events bersifat insert-only. Baris tidak boleh dihapus.'
            );
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SchedulesRef::class, 'schedule_id', 'schedule_id');
    }
}
