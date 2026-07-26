<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Embedding wajah TERENKRIPSI, dipisah dari `credentials` karena data
 * biometrik butuh perlakuan khusus (mengikuti pola *_sensitive_data di DB
 * utama). embedding_encrypted HARUS sudah terenkripsi di application layer
 * SEBELUM di-assign ke model ini - model ini tidak melakukan enkripsi apapun.
 */
class FaceTemplate extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'face_templates';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma created_at

    protected $fillable = [
        'credential_id',
        'embedding_encrypted',
        'model_version',
        'quality_score',
    ];

    protected $casts = [
        'quality_score' => 'decimal:2',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'credential_id');
    }
}
