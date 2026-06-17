<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPengeluaran extends Model
{
    use BelongsToSchool;

    protected $table = 'kategori_pengeluaran';

    protected $primaryKey = 'id_kategori_pengeluaran';

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
    public function transaksiPengeluaran(): HasMany
    {
        return $this->hasMany(TransaksiPengeluaran::class, 'id_kategori', 'id_kategori_pengeluaran');
    }

    public function pengajuanAnggaran(): HasMany
    {
        return $this->hasMany(PengajuanAnggaran::class, 'kategori_pengeluaran', 'id_kategori_pengeluaran');
    }
}
