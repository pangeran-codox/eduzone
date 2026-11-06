<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan pendaftaran sekolah (tenant).
 * TIDAK memakai BelongsToSchool: baris ini ada sebelum tenant ada.
 */
class SchoolRegistration extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_PENDING_EMAIL = 'pending_email';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'school_name', 'npsn', 'level', 'address', 'village', 'district',
        'city', 'province', 'postal_code', 'school_phone',
        'applicant_name', 'applicant_email', 'applicant_phone', 'applicant_position',
        'status', 'email_verification_token_hash', 'email_verification_expires_at',
        'email_verified_at', 'reviewed_by', 'reviewed_at', 'rejection_reason',
        'school_id', 'ip_address',
        'user_id', 'password_setup_token_hash', 'password_setup_expires_at', 'password_setup_used_at',
    ];

    protected $hidden = ['email_verification_token_hash', 'password_setup_token_hash'];

    protected function casts(): array
    {
        return [
            'email_verification_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'password_setup_expires_at' => 'datetime',
            'password_setup_used_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Akun Tata Usaha yang dibuat saat approve. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }
}
