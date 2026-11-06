<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SchoolRegistrationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $applicantName,
        public string $schoolName,
        public string $reason,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pendaftaran sekolah belum dapat disetujui')
            ->greeting("Halo, {$this->applicantName}.")
            ->line("Pendaftaran {$this->schoolName} di EduZone belum dapat kami setujui.")
            ->line("Alasan: {$this->reason}")
            ->line('Jika ada pertanyaan, hubungi tim EduZone.');
    }
}