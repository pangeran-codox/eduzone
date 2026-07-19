<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselingSession extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'counseling_sessions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'student_id',
        'staff_id',
        'date',
        'topic',
        'result',
    ];

    protected $casts = [
        'date' => 'date',
        'topic' => EncryptedAttribute::class,
        'result' => EncryptedAttribute::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}