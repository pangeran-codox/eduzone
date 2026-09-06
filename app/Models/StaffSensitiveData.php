<?php

namespace App\Models;

use App\Models\Concerns\EncryptsViaGrpcService;
use App\Models\Concerns\LogsSensitiveDataChanges;
use App\Multitenancy\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSensitiveData extends Model
{
    use BelongsToSchool, HasUuids, LogsSensitiveDataChanges, EncryptsViaGrpcService;

    protected string $auditableForeignKey = 'staff_id';
    protected string $auditableType = 'Staff';

    protected $table = 'staff_sensitive_data';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'staff_id',
        'nip_encrypted',
        'birth_place_encrypted',
        'birth_date_encrypted',
        'religion_encrypted',
        'address_encrypted',
        'phone_encrypted',
    ];

    protected static array $encryptedFields = [
        'nip',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'phone',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}