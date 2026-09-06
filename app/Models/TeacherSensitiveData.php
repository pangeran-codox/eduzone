<?php

namespace App\Models;

use App\Models\Concerns\EncryptsViaGrpcService;
use App\Models\Concerns\LogsSensitiveDataChanges;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSensitiveData extends Model
{
    use BelongsToSchool, HasUuids, LogsSensitiveDataChanges, EncryptsViaGrpcService;

    protected string $auditableForeignKey = 'teacher_id';
    protected string $auditableType = 'Teacher';

    protected $table = 'teacher_sensitive_data';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'nip_encrypted',
        'nuptk_encrypted',
        'birth_place_encrypted',
        'birth_date_encrypted',
        'religion_encrypted',
        'address_encrypted',
        'phone_encrypted',
    ];

    protected static array $encryptedFields = [
        'nip',
        'nuptk',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}