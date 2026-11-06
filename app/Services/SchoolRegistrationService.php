<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SchoolRegistrationApproved;
use App\Notifications\SchoolRegistrationRejected;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Logika approve/reject pendaftaran sekolah. Lihat PERENCANAAN_PENDAFTARAN_TENANT.md bagian 3 dan 10.
 *
 * Catatan desain (semua sudah diverifikasi dari kode, lihat dokumen perencanaan bagian 7):
 * - Dipanggil dari konteks superadmin tanpa tenant aktif: school_id SELALU diset eksplisit
 *   (trait BelongsToSchool tidak menimpa school_id yang sudah ada).
 * - Query READ pada model bertrait BelongsToSchool terkena SchoolScope, jadi idempotensi dijaga
 *   lewat baris SchoolRegistration (tanpa scope) dengan lockForUpdate().
 * - Tidak ada field sensitif Staff yang diisi, jadi tidak ada panggilan gRPC enkripsi.
 * - Email dikirim SETELAH transaksi commit supaya tidak ada email untuk transaksi yang di-rollback.
 */
class SchoolRegistrationService
{
    // NILAI SEMENTARA: durasi trial perlu dikonfirmasi pemilik (pertanyaan terbuka 1 di dokumen perencanaan).
    public const TRIAL_DAYS = 30;

    // Sama dengan sekolah trial yang sudah ada di database (max_users = 100).
    public const TRIAL_MAX_USERS = 100;

    public const PLAN_TRIAL = 'trial';

    public const SUBSCRIPTION_ACTIVE = 'active';

    // Usulan: masa berlaku link set-password (pertanyaan terbuka 2).
    public const PASSWORD_SETUP_TTL_HOURS = 72;

    public const TU_ROLE = 'tu';

    /**
     * @throws DomainException jika status pendaftaran bukan pending_approval
     */
    public function approve(SchoolRegistration $registration, User $reviewer): SchoolRegistration
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(self::PASSWORD_SETUP_TTL_HOURS);

        [$locked, $username] = DB::transaction(function () use ($registration, $reviewer, $token, $expiresAt) {
            $locked = $this->lockOrFail($registration);

            if ($locked->status !== SchoolRegistration::STATUS_PENDING_APPROVAL) {
                throw new DomainException('Pendaftaran ini sudah diproses atau emailnya belum diverifikasi.');
            }

            // Validasi form hanya berlaku saat mendaftar. Antara daftar dan approve, sekolah/akun dengan
            // NPSN atau email yang sama bisa saja dibuat lewat jalur lain (mis. CRUD sekolah superadmin).
            if ($locked->npsn && School::query()->where('npsn', $locked->npsn)->exists()) {
                throw new DomainException('NPSN ini sudah dipakai sekolah lain. Periksa data sekolah sebelum menyetujui.');
            }

            if (User::query()->where('email', $locked->applicant_email)->exists()) {
                throw new DomainException('Email pendaftar sudah dipakai akun lain. Tolak pendaftaran ini dan minta pendaftar mendaftar ulang dengan email berbeda.');
            }

            $today = today();
            $trialUntil = $today->copy()->addDays(self::TRIAL_DAYS);

            // 1. Sekolah. slug, subscription_plan, max_users, is_active semuanya NOT NULL di tabel schools.
            $school = School::create([
                'name' => $locked->school_name,
                'slug' => $this->uniqueSlug($locked->school_name),
                'npsn' => $locked->npsn,
                'level' => $locked->level,
                'address' => $locked->address,
                'village' => $locked->village,
                'district' => $locked->district,
                'city' => $locked->city,
                'province' => $locked->province,
                'postal_code' => $locked->postal_code,
                'phone' => $locked->school_phone,
                'subscription_plan' => self::PLAN_TRIAL,
                'subscription_until' => $trialUntil,
                'max_users' => self::TRIAL_MAX_USERS,
                'is_active' => true,
            ]);

            // 2. Riwayat langganan. Kolom cache di schools sudah diisi di atas, sama seperti yang
            //    dilakukan SubscriptionController saat menyimpan langganan berstatus active.
            Subscription::create([
                'school_id' => $school->id,
                'plan' => self::PLAN_TRIAL,
                'started_at' => $today,
                'expired_at' => $trialUntil,
                'amount' => 0,
                'status' => self::SUBSCRIPTION_ACTIVE,
                'note' => 'Trial dari pendaftaran mandiri',
                'created_at' => now(), // $timestamps = false di model Subscription
            ]);

            // 3. Akun Tata Usaha. Pola sama dengan StaffController: hanya string `role` yang diisi.
            $username = $this->uniqueUsername($locked->applicant_email, $locked->applicant_name);

            $user = User::create([
                'school_id' => $school->id,
                'username' => $username,
                'email' => $locked->applicant_email,
                'role' => self::TU_ROLE,
                'password' => Str::random(40), // placeholder (cast hashed); TU menetapkan sendiri lewat link
                'is_active' => true,
                'email_verified_at' => $locked->email_verified_at,
            ]);

            // 4. Profil staff (nama lengkap ada di sini, bukan di users). Tanpa field sensitif.
            Staff::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'full_name' => $locked->applicant_name,
                'email' => $locked->applicant_email,
                'position' => $locked->applicant_position ?: 'Tata Usaha',
                'is_active' => true,
            ]);

            // 5. Tandai pendaftaran + simpan HASH token set-password.
            $locked->update([
                'status' => SchoolRegistration::STATUS_APPROVED,
                'school_id' => $school->id,
                'user_id' => $user->id,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'password_setup_token_hash' => hash('sha256', $token),
                'password_setup_expires_at' => $expiresAt,
                'password_setup_used_at' => null,
            ]);

            return [$locked, $username];
        });

        $this->notifyApproved($locked, $username, $token, $expiresAt, resend: false);

        return $locked;
    }

    /**
     * @throws DomainException jika status pendaftaran bukan pending_approval
     */
    public function reject(SchoolRegistration $registration, User $reviewer, string $reason): SchoolRegistration
    {
        $locked = DB::transaction(function () use ($registration, $reviewer, $reason) {
            $locked = $this->lockOrFail($registration);

            if ($locked->status !== SchoolRegistration::STATUS_PENDING_APPROVAL) {
                throw new DomainException('Pendaftaran ini sudah diproses atau emailnya belum diverifikasi.');
            }

            $locked->update([
                'status' => SchoolRegistration::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $locked;
        });

        Notification::route('mail', $locked->applicant_email)->notify(
            new SchoolRegistrationRejected(
                applicantName: $locked->applicant_name,
                schoolName: $locked->school_name,
                reason: $reason,
            )
        );

        return $locked;
    }

    /**
     * Terbitkan token set-password baru (link lama otomatis tidak berlaku) dan kirim ulang email.
     *
     * @throws DomainException jika belum disetujui atau kata sandi sudah dibuat
     */
    public function resendPasswordSetup(SchoolRegistration $registration): SchoolRegistration
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(self::PASSWORD_SETUP_TTL_HOURS);

        $locked = DB::transaction(function () use ($registration, $token, $expiresAt) {
            $locked = $this->lockOrFail($registration);

            if ($locked->status !== SchoolRegistration::STATUS_APPROVED || ! $locked->user_id) {
                throw new DomainException('Tautan hanya bisa dikirim ulang untuk pendaftaran yang sudah disetujui.');
            }

            if ($locked->password_setup_used_at !== null) {
                throw new DomainException('Pendaftar sudah membuat kata sandi, tautan tidak perlu dikirim ulang.');
            }

            $locked->update([
                'password_setup_token_hash' => hash('sha256', $token),
                'password_setup_expires_at' => $expiresAt,
            ]);

            return $locked;
        });

        $username = User::query()->whereKey($locked->user_id)->value('username') ?? '';

        $this->notifyApproved($locked, $username, $token, $expiresAt, resend: true);

        return $locked;
    }

    private function notifyApproved(SchoolRegistration $registration, string $username, string $token, $expiresAt, bool $resend): void
    {
        Notification::route('mail', $registration->applicant_email)->notify(
            new SchoolRegistrationApproved(
                applicantName: $registration->applicant_name,
                schoolName: $registration->school_name,
                username: $username,
                setPasswordUrl: route('guest.password-setup.show', [
                    'registration' => $registration->id,
                    'token' => $token,
                ]),
                expiresAt: $expiresAt,
                loginUrl: url('/login'),
                resend: $resend,
            )
        );
    }

    private function lockOrFail(SchoolRegistration $registration): SchoolRegistration
    {
        return SchoolRegistration::query()
            ->whereKey($registration->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'sekolah', 200, '');
        $slug = $base;
        $suffix = 2;

        while (School::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /** Pola sama dengan StaffController::generateUsername (unik global, maks 50 karakter). */
    private function uniqueUsername(string $email, string $fullName): string
    {
        $base = Str::slug(Str::before($email, '@'), '.')
            ?: Str::slug($fullName, '.')
            ?: 'tu';
        $base = Str::limit($base, 40, '');

        $username = $base;
        $suffix = 1;

        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $username = "{$base}{$suffix}";
            $suffix++;
        }

        return $username;
    }
}
