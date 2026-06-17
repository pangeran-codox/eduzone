<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanAnggaran extends Model
{
    use BelongsToSchool;

    protected $table = 'pengajuan_anggaran';

    protected $primaryKey = 'id_pengajuan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'judul',
        'kategori_pengeluaran',
        'jumlah_diajukan',
        'tanggal_pengajuan',
        'keperluan',
        'status',
        'catatan_reviewer',
        'reviewed_by',
        'reviewed_at',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'jumlah_diajukan'   => 'decimal:2',
        'tanggal_pengajuan' => 'date',
        'reviewed_at'       => 'datetime',
        'created_at'        => 'datetime',
    ];

    // Relationships
    public function kategoriPengeluaran(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran', 'id_kategori_pengeluaran');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
