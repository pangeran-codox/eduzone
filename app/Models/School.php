<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant;

class School extends Tenant
{
    use HasUuids;

    protected $table = 'schools';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'npsn',
        'nss',
        'level',
        'status',
        'accreditation',
        'address',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'website',
        'logo',
        'principal_name',
        'principal_nip',
        'vision',
        'mission',
        'motto',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'subscription_plan',
        'subscription_until',
        'max_users',
        'is_active',
        'onboarded_at',
    ];

    protected $casts = [
        'subscription_until' => 'date',
        'max_users'          => 'integer',
        'is_active'          => 'boolean',
        'onboarded_at'       => 'datetime',

        // Data sensitif - dienkripsi langsung di kolomnya (1 row per sekolah, tidak butuh tabel terpisah)
        'principal_nip'       => EncryptedAttribute::class,
        'bank_account_number' => EncryptedAttribute::class,
        'bank_account_name'   => EncryptedAttribute::class,
    ];

    // ── Relationships ──────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'school_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'school_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'school_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'school_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'school_id');
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class, 'school_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'school_id');
    }
}