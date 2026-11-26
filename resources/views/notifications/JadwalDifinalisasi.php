<?php

namespace App\Notifications;

use App\Models\SchedulePeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JadwalDifinalisasi extends Notification
{
    use Queueable;

    public function __construct(public SchedulePeriod $period) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Jadwal Baru Telah Diterbitkan')
            ->greeting('Halo ' . ($notifiable->name ?? 'Agent') . ',')
            ->line('Jadwal periode "' . $this->period->nama_periode . '" telah difinalisasi oleh PIC.')
            ->line('Silakan buka aplikasi untuk melihat jadwal Anda.')
            ->action('Lihat Jadwal Saya', route('agent.schedule.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'jadwal_difinalisasi',
            'title'   => 'Jadwal Baru Telah Diterbitkan',
            'message' => "Jadwal periode \"{$this->period->nama_periode}\" telah difinalisasi oleh PIC. Klik untuk melihat jadwal Anda.",
            'url'     => route('agent.schedule.index'),
            'icon'    => 'calendar',
        ];
    }
}
