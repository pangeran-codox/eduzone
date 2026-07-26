<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Metode absensi tiap orang (hash UID RFID / token QR / dll).
 * Satu orang bisa punya beberapa metode sekaligus.
 */
class Credential extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'credentials';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // enrolled_at, bukan created_at/updated_at

    protected $fillable = [
        'school_id',
        'person_id',
        'person_type',
        'method',
        'credential_hash',
        'is_active',
        'enrolled_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enrolled_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    /**
     * Catatan: sama seperti PeopleRef::credentials(), relasi ini cuma filter
     * person_id. Tambahkan ->where('person_type', ...) saat query kalau
     * butuh presisi penuh sesuai composite key people_ref.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PeopleRef::class, 'person_id', 'person_id');
    }

    public function faceTemplates(): HasMany
    {
        return $this->hasMany(FaceTemplate::class, 'credential_id');
    }
}
