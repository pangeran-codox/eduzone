<?php

namespace App\Console\Commands;

use App\Models\SchoolRegistration;
use Illuminate\Console\Command;

/**
 * Tanpa command ini, pendaftar yang tidak pernah klik link verifikasi akan
 * "memblokir" NPSN/email-nya selamanya (partial unique index hanya mengecualikan
 * status expired/rejected/approved).
 */
class ExpireStaleSchoolRegistrations extends Command
{
    protected $signature = 'registrations:expire-stale';

    protected $description = 'Tandai pendaftaran sekolah yang link verifikasi emailnya sudah kedaluwarsa sebagai expired';

    public function handle(): int
    {
        $count = SchoolRegistration::query()
            ->where('status', SchoolRegistration::STATUS_PENDING_EMAIL)
            ->where('email_verification_expires_at', '<', now())
            ->update([
                'status' => SchoolRegistration::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);

        $this->info("{$count} pendaftaran ditandai expired.");

        return self::SUCCESS;
    }
}
