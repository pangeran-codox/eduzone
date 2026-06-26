<?php

namespace App\Models;

use App\Models\Concerns\LogsSensitiveDataChanges;
use App\Multitenancy\Concerns\BelongsToSchool;
use App\Services\EncryptionGrpcService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSensitiveData extends Model
{
    use BelongsToSchool, HasUuids, LogsSensitiveDataChanges;

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

    /**
     * Daftar field logis (tanpa suffix _encrypted) yang didukung accessor/mutator otomatis.
     */
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

    /** Cache instance service supaya tidak bikin koneksi gRPC baru tiap akses field */
    protected static ?EncryptionGrpcService $encryptionService = null;

    protected static function encryptionService(): EncryptionGrpcService
    {
        return static::$encryptionService ??= new EncryptionGrpcService();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Magic accessor: $sensitiveData->nisn -> otomatis decrypt dari kolom nisn_encrypted
     */
    public function __get($key)
    {
        if (in_array($key, static::$encryptedFields, true)) {
            return $this->getDecrypted($key);
        }

        return parent::__get($key);
    }

    /**
     * Magic mutator: $sensitiveData->nisn = '0012345678' -> otomatis encrypt ke kolom nisn_encrypted
     */
    public function __set($key, $value)
    {
        if (in_array($key, static::$encryptedFields, true)) {
            $this->setEncrypted($key, $value);
            return;
        }

        parent::__set($key, $value);
    }

    protected function getDecrypted(string $field): ?string
    {
        $column = "{$field}_encrypted";
        $cipherValue = $this->attributes[$column] ?? null;

        if (empty($cipherValue)) {
            return null;
        }

        try {
            return static::encryptionService()->decrypt($cipherValue);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    protected function setEncrypted(string $field, ?string $value): void
    {
        $column = "{$field}_encrypted";

        if ($value === null || $value === '') {
            $this->attributes[$column] = null;
            return;
        }

        $this->attributes[$column] = static::encryptionService()->encrypt($value);
    }
}