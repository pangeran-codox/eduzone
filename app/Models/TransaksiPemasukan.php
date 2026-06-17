<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiPemasukan extends Model
{
    use BelongsToSchool;

    protected $table = 'transaksi_pemasukan';

    protected $primaryKey = 'id_pemasukan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'school_id',
        'no_transaksi',
        'tanggal_transaksi',
        'id_kategori',
        'keterangan',
        'sumber',
        'jumlah',
        'metode_pembayaran',
        'no_bukti',
        'file_bukti',
        'tahun_ajaran',
        'semester',
        'status',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah'            => 'decimal:2',
        'verified_at'       => 'datetime',
    ];

    // Relationships
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPemasukan::class, 'id_kategori', 'id_kategori_pemasukan');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
