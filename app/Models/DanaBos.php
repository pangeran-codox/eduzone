<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanaBos extends Model
{
    use BelongsToSchool;

    protected $table = 'dana_bos';

    protected $primaryKey = 'id_bos';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'tahun_ajaran',
        'semester',
        'triwulan',
        'jumlah_diterima',
        'tanggal_terima',
        'keterangan',
        'created_at',
    ];

    protected $casts = [
        'jumlah_diterima' => 'decimal:2',
        'tanggal_terima'  => 'date',
        'created_at'      => 'datetime',
    ];

    // Relationships
    public function realisasiBos(): HasMany
    {
        return $this->hasMany(RealisasiBos::class, 'id_bos', 'id_bos');
    }
}
