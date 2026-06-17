<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPemasukan extends Model
{
    use BelongsToSchool;

    protected $table = 'kategori_pemasukan';

    protected $primaryKey = 'id_kategori_pemasukan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'nama_kategori',
        'deskripsi',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function transaksiPemasukan(): HasMany
    {
        return $this->hasMany(TransaksiPemasukan::class, 'id_kategori', 'id_kategori_pemasukan');
    }
}
