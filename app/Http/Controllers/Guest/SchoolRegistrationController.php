<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Superadmin\SchoolController;
use App\Http\Requests\Guest\StoreSchoolRegistrationRequest;
use App\Models\SchoolRegistration;
use App\Notifications\SchoolRegistrationVerifyEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Halaman publik (tanpa login, tanpa tenant) untuk pendaftaran sekolah baru.
 * Lihat PERENCANAAN_PENDAFTARAN_TENANT.md bagian 3.
 */
class SchoolRegistrationController extends Controller
{
    /** Masa berlaku link verifikasi (jam). Angka final: pertanyaan terbuka 2 di dokumen perencanaan. */
    private const VERIFICATION_TTL_HOURS = 24;

    public function create(): View
    {
        return view('guest.school-registration.create', [
            'levels' => SchoolController::LEVELS,
        ]);
    }

    public function store(StoreSchoolRegistrationRequest $request): RedirectResponse
    {
        // Honeypot: bot mengisi field tersembunyi. Pura-pura sukses, jangan simpan apa pun.
        if ($request->filled('hp_field')) {
            return redirect()->route('guest.school-registration.thanks');
        }

        $token = Str::random(64);
        $expiresAt = now()->addHours(self::VERIFICATION_TTL_HOURS);

        try {
            $registration = SchoolRegistration::create(array_merge($request->validated(), [
                'status' => SchoolRegistration::STATUS_PENDING_EMAIL,
                // Simpan HASH token saja; token mentah hanya ada di email.
                'email_verification_token_hash' => hash('sha256', $token),
                'email_verification_expires_at' => $expiresAt,
                'ip_address' => $request->ip(),
            ]));
        } catch (UniqueConstraintViolationException $e) {
            // Balapan dua submit bersamaan: partial unique index di database yang menang.
            return back()->withInput()->withErrors([
                'general' => 'Pendaftaran dengan NPSN atau email ini sedang diproses. Cek email Anda untuk tautan verifikasi.',
            ]);
        }

        Notification::route('mail', $registration->applicant_email)->notify(
            new SchoolRegistrationVerifyEmail(
                applicantName: $registration->applicant_name,
                schoolName: $registration->school_name,
                verifyUrl: route('guest.school-registration.verify', [
                    'registration' => $registration->id,
                    'token' => $token,
                ]),
                expiresAt: $expiresAt,
            )
        );

        return redirect()
            ->route('guest.school-registration.thanks')
            ->with('registration_email', $registration->applicant_email);
    }

    public function thanks(): View
    {
        return view('guest.school-registration.thanks', [
            'email' => session('registration_email'),
            'hours' => self::VERIFICATION_TTL_HOURS,
        ]);
    }

    /**
     * Klik link dari email. Idempoten: membuka link dua kali tidak merusak apa pun.
     */
    public function verify(string $registration, string $token): View
    {
        $model = SchoolRegistration::find($registration);

        $tokenValid = $model
            && $model->email_verification_token_hash
            && hash_equals($model->email_verification_token_hash, hash('sha256', $token));

        if (! $tokenValid) {
            return $this->verifyResult('invalid');
        }

        if ($model->status !== SchoolRegistration::STATUS_PENDING_EMAIL) {
            $alreadyVerified = in_array($model->status, [
                SchoolRegistration::STATUS_PENDING_APPROVAL,
                SchoolRegistration::STATUS_APPROVED,
            ], true);

            return $this->verifyResult($alreadyVerified ? 'already' : 'invalid');
        }

        if ($model->email_verification_expires_at?->isPast()) {
            $model->update(['status' => SchoolRegistration::STATUS_EXPIRED]);

            return $this->verifyResult('expired');
        }

        // Update bersyarat = atomik: hanya satu request yang berhasil mengubah status.
        $updated = SchoolRegistration::query()
            ->where('id', $model->id)
            ->where('status', SchoolRegistration::STATUS_PENDING_EMAIL)
            ->update([
                'status' => SchoolRegistration::STATUS_PENDING_APPROVAL,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->verifyResult($updated ? 'success' : 'already');
    }

    /** $state: success | already | expired | invalid */
    private function verifyResult(string $state): View
    {
        return view('guest.school-registration.verified', ['state' => $state]);
    }
}
