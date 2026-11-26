<?php

namespace App\Notifications;

use App\Models\OffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OffRequestBaruMasuk extends Notification
{
    use Queueable;

    public function __construct(public OffRequest $offRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $namaAgent = $this->offRequest->agent->nama_agent ?? 'Agent';
        $tanggal   = \Carbon\Carbon::parse($this->offRequest->tanggal)
            ->translatedFormat('d M Y');

        return (new MailMessage)
            ->subject('Off Request Baru Masuk')
            ->line("{$namaAgent} mengajukan Off Request Prioritas Tinggi untuk tanggal {$tanggal}. Perlu persetujuan Anda.")
            ->action('Lihat Off Request', route('off-requests.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $namaAgent = $this->offRequest->agent->nama_agent ?? 'Agent';
        $tanggal   = \Carbon\Carbon::parse($this->offRequest->tanggal)
            ->translatedFormat('d M Y');

        return [
            'type'    => 'off_request_baru',
            'title'   => 'Off Request Baru Masuk',
            'message' => "{$namaAgent} mengajukan Off Request Prioritas Tinggi untuk tanggal {$tanggal}. Perlu persetujuan Anda.",
            'url'     => route('off-requests.index'),
            'icon'    => 'inbox',
        ];
    }
}
