<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiPengeluaran extends Model
{
    use BelongsToSchool;

    protected $table = 'transaksi_pengeluaran';

    protected $primaryKey = 'id_pengeluaran';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'school_id',
        'no_transaksi',
        'tanggal_transaksi',
        'id_kategori',
        'keterangan',
        'tujuan',
        'jumlah',
        'metode_pembayaran',
        'no_bukti',
        'file_bukti',
        'tahun_ajaran',
        'semester',
        'is_from_bos',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah'            => 'decimal:2',
        'is_from_bos'       => 'boolean',
    ];

    // Relationships
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'id_kategori', 'id_kategori_pengeluaran');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function realisasiBos(): HasMany
    {
        return $this->hasMany(RealisasiBos::class, 'id_pengeluaran', 'id_pengeluaran');
    }
}
