<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRecord extends Model
{
    use BelongsToSchool, HasUuids;

    public $timestamps = false; // tabel ini cuma punya created_at (useCurrent)

    protected $table = 'student_records';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'student_id',
        'activity',
        'date',
        'description',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'description' => EncryptedAttribute::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}