<?php

namespace App\Notifications;

use App\Models\QueueTicket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerTicketStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public QueueTicket $ticket,
        public string $status
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
        $businessName = $business?->name ?? 'Unit Usaha Mitra AntreIn';
        $timezone = $business?->timezone ?: 'Asia/Makassar';
        $currentTime = Carbon::now($timezone)->translatedFormat('H:i, d F Y');
        $trackUrl = route('customer.queue.track', ['token' => $this->ticket->access_token]);
        $counterName = $this->ticket->counter?->name;

        $subjectStatus = match ($this->status) {
            QueueTicket::STATUS_DIPANGGIL => 'Dipanggil',
            QueueTicket::STATUS_SEDANG_DILAYANI => 'Sedang Dilayani',
            QueueTicket::STATUS_SELESAI => 'Pelayanan Selesai',
            QueueTicket::STATUS_DILEWATI => 'Dilewati',
            QueueTicket::STATUS_DIBATALKAN => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };

        $subject = sprintf('[AntreIn] Antrean %s %s — %s', $this->ticket->ticket_number, $subjectStatus, $businessName);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo, ' . $this->ticket->customer_name . '!')
            ->line(sprintf('Pembaruan status untuk nomor antrean Anda di **%s**:', $businessName));

        match ($this->status) {
            QueueTicket::STATUS_DIPANGGIL => $mail
                ->line(sprintf('📢 Nomor antrean Anda **%s** telah **DIPANGGIL**%s.', 
                    $this->ticket->ticket_number, 
                    $counterName ? ' ke **' . $counterName . '**' : ''))
                ->line('Silakan segera menuju meja pelayanan/loket yang ditentukan.'),

            QueueTicket::STATUS_SEDANG_DILAYANI => $mail
                ->line(sprintf('Nomor antrean Anda **%s** saat ini **SEDANG DILAYANI**%s.', 
                    $this->ticket->ticket_number, 
                    $counterName ? ' di **' . $counterName . '**' : '')),

            QueueTicket::STATUS_SELESAI => $mail
                ->line(sprintf('Pelayanan untuk nomor antrean **%s** telah **SELESAI**.', $this->ticket->ticket_number))
                ->line('Terima kasih telah berkunjung dan memanfaatkan sistem antrean digital AntreIn.'),

            QueueTicket::STATUS_DILEWATI => $mail
                ->line(sprintf('Nomor antrean Anda **%s** telah **DILEWATI** karena Anda belum hadir saat dipanggil.', $this->ticket->ticket_number))
                ->line('Silakan hubungi staf/operator di lokasi jika Anda memerlukan bantuan lebih lanjut.'),

            QueueTicket::STATUS_DIBATALKAN => $mail
                ->line(sprintf('Nomor antrean Anda **%s** telah **DIBATALKAN**.', $this->ticket->ticket_number))
                ->line(sprintf('Alasan: *%s*', $this->ticket->cancellation_reason ?: 'Pembatalan operasional antrean.')),

            default => $mail->line(sprintf('Status antrean Anda saat ini: **%s**.', $subjectStatus)),
        };

        return $mail
            ->line(sprintf('Layanan: **%s** | Waktu: **%s** (%s)', 
                $this->ticket->service?->name ?? 'Layanan', 
                $currentTime, 
                $timezone))
            ->action('Lihat Detail & Lacak Antrean', $trackUrl)
            ->line('Anda menerima email ini karena menyetujui pemberitahuan perkembangan status antrean AntreIn.');
    }
}

