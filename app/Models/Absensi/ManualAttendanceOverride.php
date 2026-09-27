<?php

namespace App\Models\Absensi;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ManualAttendanceOverride extends Model
{
    use HasUuids;

    protected $connection = 'pgsql_absensi';
    protected $table = 'manual_attendance_overrides';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'person_id',
        'person_type',
        'date',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}