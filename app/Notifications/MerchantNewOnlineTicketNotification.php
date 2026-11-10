<?php

namespace App\Notifications;

use App\Models\QueueTicket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantNewOnlineTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public QueueTicket $ticket
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $business = $this->ticket->business;
        $businessName = $business?->name ?? 'Unit Usaha AntreIn';
        $timezone = $business?->timezone ?: 'Asia/Makassar';
        $currentTime = Carbon::now($timezone)->translatedFormat('H:i, d F Y');
        $queueUrl = route('merchant.queues');

        return (new MailMessage)
            ->subject(sprintf('[AntreIn] Tiket Online Baru Masuk: %s — %s', $this->ticket->ticket_number, $businessName))
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line(sprintf('Pelanggan baru telah mendaftar antrean online di unit usaha **%s**:', $businessName))
            ->line(sprintf('• Nomor Antrean: **%s**', $this->ticket->ticket_number))
            ->line(sprintf('• Nama Pelanggan: **%s**', $this->ticket->customer_name))
            ->line(sprintf('• Layanan: **%s**', $this->ticket->service?->name ?? 'Layanan'))
            ->line(sprintf('• Waktu Pendaftaran: **%s** (%s)', $currentTime, $timezone))
            ->action('Buka Meja Operasional Antrean', $queueUrl)
            ->line('Anda menerima email ini sesuai preferensi notifikasi akun staf/pemilik AntreIn Anda.');
    }
}

