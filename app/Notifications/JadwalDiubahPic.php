<?php

namespace App\Notifications;

use App\Models\SchedulePeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke agent ketika PIC mengubah jadwal mereka secara manual
 * lewat grid editor (Ubah Grid) SETELAH periode berstatus final.
 * Edit pada periode draft tidak mengirim notifikasi ini -- jadwal draft
 * memang belum final, agent belum pernah melihatnya, jadi tidak relevan.
 */
class JadwalDiubahPic extends Notification
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
            ->subject('Jadwal Anda Diubah oleh PIC')
            ->line("Jadwal Anda pada periode \"{$this->period->nama_periode}\" telah diubah secara manual oleh PIC setelah jadwal ini difinalisasi.")
            ->line('Silakan cek kembali jadwal terbaru Anda untuk memastikan tidak ada yang terlewat.')
            ->action('Lihat Jadwal Saya', route('agent.schedule.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'jadwal_diubah_pic',
            'title'   => 'Jadwal Anda Diubah oleh PIC',
            'message' => "Jadwal Anda pada periode \"{$this->period->nama_periode}\" telah diubah secara manual oleh PIC setelah difinalisasi. Klik untuk melihat jadwal terbaru.",
            'url'     => route('agent.schedule.index'),
            'icon'    => 'calendar',
        ];
    }
}
