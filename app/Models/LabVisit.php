<?php

namespace App\Models;

use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabVisit extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'lab_visits';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'lab_booking_id',
        'student_count',
        'activity',
        'created_at',
    ];

    protected $casts = [
        'student_count' => 'integer',
        'created_at'    => 'datetime',
    ];

    // Relationships
    public function labBooking(): BelongsTo
    {
        return $this->belongsTo(LabBooking::class, 'lab_booking_id');
    }
}
