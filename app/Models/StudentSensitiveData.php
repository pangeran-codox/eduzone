<?php

namespace App\Models;

use App\Models\Concerns\EncryptsViaGrpcService;
use App\Models\Concerns\LogsSensitiveDataChanges;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSensitiveData extends Model
{
    use BelongsToSchool, HasUuids, LogsSensitiveDataChanges, EncryptsViaGrpcService;

    protected string $auditableForeignKey = 'student_id';
    protected string $auditableType = 'Student';

    protected $table = 'student_sensitive_data';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'student_id',
        'nis_encrypted',
        'nisn_encrypted',
        'birth_place_encrypted',
        'birth_date_encrypted',
        'religion_encrypted',
        'address_encrypted',
        'phone_encrypted',
        'father_name_encrypted',
        'mother_name_encrypted',
        'father_job_encrypted',
        'mother_job_encrypted',
        'parent_address_encrypted',
        'parent_phone_encrypted',
    ];

    protected static array $encryptedFields = [
        'nis',
        'nisn',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
        'father_name',
        'mother_name',
        'father_job',
        'mother_job',
        'parent_address',
        'parent_phone',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}