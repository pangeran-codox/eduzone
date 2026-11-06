<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * On-demand notification (pendaftar belum bisa login). Hanya membawa nilai scalar/Carbon.
 * $resend = true dipakai saat superadmin mengirim ulang link set-password.
 */
class SchoolRegistrationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $applicantName,
        public string $schoolName,
        public string $username,
        public string $setPasswordUrl,
        public CarbonInterface $expiresAt,
        public string $loginUrl,
        public bool $resend = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->resend
                ? 'Tautan baru untuk membuat kata sandi EduZone'
                : 'Pendaftaran sekolah Anda disetujui')
            ->greeting("Halo, {$this->applicantName}!");

        if (! $this->resend) {
            $mail->line("Pendaftaran {$this->schoolName} di EduZone sudah disetujui. Akun Tata Usaha Anda sudah dibuat.");
        } else {
            $mail->line("Berikut tautan baru untuk membuat kata sandi akun Tata Usaha {$this->schoolName}. Tautan sebelumnya tidak berlaku lagi.");
        }

        return $mail
            ->line("Username Anda: {$this->username}")
            ->line('Klik tombol di bawah untuk membuat kata sandi, lalu Anda bisa masuk.')
            ->action('Buat kata sandi', $this->setPasswordUrl)
            ->line('Tautan berlaku sampai ' . $this->expiresAt->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') . '.')
            ->line("Setelah itu, masuk di {$this->loginUrl}.");
    }
}