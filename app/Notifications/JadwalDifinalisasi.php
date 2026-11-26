<?php

namespace App\Notifications;

use App\Models\SchedulePeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JadwalDifinalisasi extends Notification
{
    use Queueable;

    public function __construct(public SchedulePeriod $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
