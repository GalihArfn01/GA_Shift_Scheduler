<?php

namespace App\Notifications;

use App\Models\ShiftSwap;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PermintaanTukarJadwal extends Notification
{
    use Queueable;

    public function __construct(public ShiftSwap $swap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $namaPeminta = $this->swap->requester->nama_agent ?? 'Rekan Anda';
        $tanggalSwap = Carbon::parse($this->swap->requesterSchedule->tanggal)->translatedFormat('d M Y');

        return (new MailMessage)
            ->subject('Permintaan Tukar Jadwal')
            ->greeting('Halo ' . ($notifiable->name ?? 'Agent') . ',')
            ->line($namaPeminta . ' mengajukan tukar jadwal dengan Anda yang melibatkan jadwal tanggal ' . $tanggalSwap . '.')
            ->line('Silakan buka aplikasi untuk merespons permintaan ini.')
            ->action('Lihat Tukar Jadwal', route('agent.shift-swaps.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $namaPeminta = $this->swap->requester->nama_agent ?? 'Rekan Anda';
        $tanggalSwap = Carbon::parse($this->swap->requesterSchedule->tanggal)->translatedFormat('d M Y');
        
        return [
            'type'    => 'permintaan_tukar_jadwal',
            'title'   => 'Permintaan Tukar Jadwal',
            'message' => "{$namaPeminta} mengajukan tukar jadwal dengan Anda yang melibatkan jadwal tanggal {$tanggalSwap}.",
            'url'     => route('agent.shift-swaps.index'),
            'icon'    => 'swap',
        ];
    }
}
