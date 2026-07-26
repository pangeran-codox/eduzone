<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * STATUS: DORMAN. Alur approval koreksi data absensi - tabel & model sudah
 * ada tapi belum diaktifkan logikanya. Koreksi versi awal masih langsung/
 * manual (lihat ARCHITECTURE.md §2.4). Jangan bangun UI approval untuk ini
 * kecuali diminta eksplisit.
 *
 * source_table/source_id nunjuk ke AttendanceDaily ATAU AttendancePeriod -
 * tidak dibuatkan relasi Eloquent otomatis karena bukan polymorphic
 * standar (dua model berbeda tanpa kolom *_type yang konsisten dengan
 * konvensi morphTo Laravel). Resolve manual sesuai source_table kalau
 * fitur ini mulai dikerjakan.
 */
class AttendanceCorrectionLog extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'attendance_correction_log';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // cuma created_at

    protected $fillable = [
        'source_table',
        'source_id',
        'requested_by',
        'reason',
        'data_lama',
        'data_baru',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
        'reviewed_at' => 'datetime',
    ];
}
