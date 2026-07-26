<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daftar IP/hostname yang diizinkan per sekolah untuk validasi check-in guru
 * via HP. AKTIF dipakai dari awal rilis (bukan fitur dorman).
 */
class SchoolNetwork extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'school_networks';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true; // created_at & updated_at standar

    protected $fillable = [
        'school_id',
        'label',
        'ip_or_hostname',
        'is_dynamic',
        'requires_local_verifier',
        'is_active',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'requires_local_verifier' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }
}
