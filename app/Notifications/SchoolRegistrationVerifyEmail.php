<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Dikirim ke pendaftar lewat on-demand notification (belum punya akun):
 * Notification::route('mail', $email)->notify(new SchoolRegistrationVerifyEmail(...)).
 * Sengaja hanya menerima nilai scalar/Carbon, bukan model, supaya payload queue ringan.
 */
// NotTenantAware: dikirim dari konteks TANPA tenant (form publik / superadmin). Tanpa ini, worker
// menolak job dengan CurrentTenantCouldNotBeDeterminedInTenantAwareJob dan email tidak pernah terkirim.
class SchoolRegistrationVerifyEmail extends Notification implements ShouldQueue, NotTenantAware
{
    use Queueable;

    public function __construct(
        public string $applicantName,
        public string $schoolName,
        public string $verifyUrl,
        public CarbonInterface $expiresAt,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Pendaftaran Sekolah di EduZone')
            ->greeting("Halo, {$this->applicantName}!")
            ->line("Terima kasih telah mendaftarkan {$this->schoolName} di EduZone.")
            ->line('Klik tombol di bawah untuk memverifikasi alamat email Anda. Setelah itu, pendaftaran akan ditinjau oleh tim kami.')
            ->action('Verifikasi Email', $this->verifyUrl)
            ->line('Tautan berlaku sampai ' . $this->expiresAt->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') . '.')
            ->line('Jika Anda tidak merasa mendaftar, abaikan email ini.');
    }
}
