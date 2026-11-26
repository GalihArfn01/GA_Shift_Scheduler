<?php

namespace App\Notifications;

use App\Models\ShiftSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TukarJadwalDisepakati extends Notification
{
    use Queueable;

    public function __construct(public ShiftSwap $swap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $namaA = $this->swap->requester->nama_agent ?? 'Agent A';
        $namaB = $this->swap->responder->nama_agent ?? 'Agent B';

        return (new MailMessage)
            ->subject('Persetujuan Tukar Jadwal (Menunggu PIC)')
            ->line("{$namaA} dan {$namaB} telah sepakat untuk bertukar jadwal. Memerlukan persetujuan Anda.")
            ->action('Lihat Tukar Jadwal', route('pic.shift-swaps.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $namaA = $this->swap->requester->nama_agent ?? 'Agent A';
        $namaB = $this->swap->responder->nama_agent ?? 'Agent B';

        return [
            'type'    => 'tukar_jadwal_disepakati',
            'title'   => 'Persetujuan Tukar Jadwal (Menunggu PIC)',
            'message' => "{$namaA} dan {$namaB} telah sepakat untuk bertukar jadwal. Memerlukan persetujuan Anda.",
            'url'     => route('pic.shift-swaps.index'),
            'icon'    => 'swap',
        ];
    }
}
