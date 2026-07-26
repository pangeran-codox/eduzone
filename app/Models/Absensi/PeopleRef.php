<?php

namespace App\Models\Absensi;

use App\Models\Absensi\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cache read-only dari `students`/`teachers`/`staff` di DB utama. Disinkron
 * satu arah, dipakai buat validasi cepat tanpa cross-database query.
 * PK gabungan (person_id, person_type) - lihat HasCompositePrimaryKey untuk
 * keterbatasan query (tidak bisa pakai ::find()).
 */
class PeopleRef extends Model
{
    use HasCompositePrimaryKey;

    protected $connection = 'pgsql_absensi';
    protected $table = 'people_ref';
    protected $primaryKey = ['person_id', 'person_type'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // pakai synced_at

    protected $fillable = [
        'person_id',
        'school_id',
        'person_type',
        'full_name',
        'class_id',
        'grade',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRef::class, 'school_id', 'school_id');
    }

    /**
     * Catatan: relasi ini cuma filter person_id. Kalau butuh presisi penuh
     * sesuai composite key, tambahkan ->where('person_type', $this->person_type)
     * saat query, karena Eloquent hasMany tidak native support kondisi
     * tambahan dari kolom non-FK di parent.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'person_id', 'person_id');
    }
}
