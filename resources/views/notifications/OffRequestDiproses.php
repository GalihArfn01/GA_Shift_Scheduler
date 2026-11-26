<?php

namespace App\Notifications;

use App\Models\OffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OffRequestDiproses extends Notification
{
    use Queueable;

    public function __construct(public OffRequest $offRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $diterima = $this->offRequest->status_approval === 'diterima';
        $tanggal  = \Carbon\Carbon::parse($this->offRequest->tanggal)
            ->translatedFormat('d M Y');

        return (new MailMessage)
            ->subject($diterima ? 'Off Request Disetujui' : 'Off Request Ditolak')
            ->greeting('Halo ' . ($notifiable->name ?? 'Agent') . ',')
            ->line($diterima
                ? 'Off Request Anda pada ' . $tanggal . ' telah disetujui oleh PIC.'
                : 'Off Request Anda pada ' . $tanggal . ' ditolak. Alasan: ' . ($this->offRequest->catatan_pic ?? 'Tidak ada catatan.'))
            ->action('Lihat Off Request Saya', route('agent.off-requests.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $diterima = $this->offRequest->status_approval === 'diterima';
        $tanggal  = \Carbon\Carbon::parse($this->offRequest->tanggal)
            ->translatedFormat('d M Y');

        return [
            'type'    => 'off_request_diproses',
            'title'   => $diterima ? 'Off Request Disetujui' : 'Off Request Ditolak',
            'message' => $diterima
                ? "Off Request Anda pada {$tanggal} telah disetujui oleh PIC."
                : "Off Request Anda pada {$tanggal} ditolak. Alasan: " . ($this->offRequest->catatan_pic ?? 'Tidak ada catatan.'),
            'url'     => route('agent.off-requests.index'),
            'icon'    => $diterima ? 'check' : 'x',
        ];
    }
}
