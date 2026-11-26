<?php

namespace App\Notifications;

use App\Models\ShiftSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TukarJadwalDiprosesPic extends Notification
{
    use Queueable;

    public function __construct(public ShiftSwap $swap) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $diterima = $this->swap->status === 'disetujui_pic';

        return (new MailMessage)
            ->subject($diterima ? 'Tukar Jadwal Disetujui PIC' : 'Tukar Jadwal Ditolak PIC')
            ->greeting('Halo ' . ($notifiable->name ?? 'Agent') . ',')
            ->line($diterima
                ? 'Pengajuan tukar jadwal Anda telah disetujui oleh PIC.'
                : 'Pengajuan tukar jadwal Anda ditolak oleh PIC. Alasan: ' . ($this->swap->catatan_pic ?? 'Tidak ada catatan.'))
            ->action('Lihat Tukar Jadwal', route('agent.shift-swaps.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $diterima = $this->swap->status === 'disetujui_pic';
        
        return [
            'type'    => 'tukar_jadwal_diproses_pic',
            'title'   => $diterima ? 'Tukar Jadwal Disetujui PIC' : 'Tukar Jadwal Ditolak PIC',
            'message' => $diterima 
                ? "Pengajuan tukar jadwal Anda telah disetujui oleh PIC." 
                : "Pengajuan tukar jadwal Anda ditolak oleh PIC. Alasan: " . ($this->swap->catatan_pic ?? 'Tidak ada catatan.'),
            'url'     => route('agent.shift-swaps.index'),
            'icon'    => $diterima ? 'check' : 'x',
        ];
    }
}
