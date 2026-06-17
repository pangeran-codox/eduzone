<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiBos extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'realisasi_bos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'id_bos',
        'id_pengeluaran',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function danaBos(): BelongsTo
    {
        return $this->belongsTo(DanaBos::class, 'id_bos', 'id_bos');
    }

    public function transaksiPengeluaran(): BelongsTo
    {
        return $this->belongsTo(TransaksiPengeluaran::class, 'id_pengeluaran', 'id_pengeluaran');
    }
}
