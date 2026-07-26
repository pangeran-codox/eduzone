<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cache read-only dari `schools` di DB utama. Disinkron SATU ARAH
 * (utama -> absensi) - jangan tulis balik ke sini secara langsung
 * kecuali dari job sinkronisasi. school_id SAMA PERSIS dengan id
 * di tabel schools DB utama, bukan UUID yang di-generate lokal.
 */
class SchoolRef extends Model
{
    protected $connection = 'pgsql_absensi';
    protected $table = 'schools_ref';
    protected $primaryKey = 'school_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // pakai synced_at, bukan created_at/updated_at

    protected $fillable = [
        'school_id',
        'name',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'geofence_radius_meters' => 'integer',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(PeopleRef::class, 'school_id', 'school_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'school_id', 'school_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SchedulesRef::class, 'school_id', 'school_id');
    }

    public function networks(): HasMany
    {
        return $this->hasMany(SchoolNetwork::class, 'school_id', 'school_id');
    }

    public function localVerifiers(): HasMany
    {
        return $this->hasMany(LocalVerifier::class, 'school_id', 'school_id');
    }
}
