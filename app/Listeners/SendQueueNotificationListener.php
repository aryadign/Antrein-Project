<?php

namespace App\Listeners;

use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Models\BusinessMembership;
use App\Models\QueueTicket;
use App\Notifications\CustomerTicketStatusNotification;
use App\Notifications\MerchantNewOnlineTicketNotification;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendQueueNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected WebPushService $webPushService
    ) {}

    /**
     * Handle queue ticket created events (notify merchant staff for online tickets).
     */
    public function handleTicketCreated(QueueTicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Walk-in tickets are created directly on premise and do not notify merchant via email/push
        if ($ticket->source !== QueueTicket::SOURCE_ONLINE) {
            return;
        }

        $business = $ticket->business;
        if (! $business) {
            return;
        }

        // Resolve active business members (Owner + Active Operators)
        $recipients = collect();

        // 1. Business Owner
        $owner = $business->user ?? $business->owner;
        if ($owner && $owner->is_active) {
            $recipients->push($owner);
        }

        // 2. Active Operators
        $operators = $business->memberships()
            ->where('is_active', true)
            ->where('role', BusinessMembership::ROLE_OPERATOR)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($u) => $u && $u->is_active);

        $recipients = $recipients->concat($operators)->unique('id');

        foreach ($recipients as $user) {
            try {
                // Email Notification
                if ($user->wantsEmailOnNewTicket()) {
                    $user->notify(new MerchantNewOnlineTicketNotification($ticket));
                }

                // Browser Push Notification
                if ($user->wantsBrowserPushOnNewTicket() && $user->pushSubscriptions()->exists()) {
                    $this->webPushService->sendToUser($user, [
                        'title' => sprintf('Antrean Baru Masuk — %s', $ticket->ticket_number),
                        'body' => sprintf('Layanan: %s (%s)', $ticket->service?->name ?? 'Layanan', $business->name),
                        'data' => [
                            'url' => route('merchant.queues'),
                            'tag' => 'merchant-ticket-' . $ticket->id,
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed sending merchant notification: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    /**
     * Handle queue ticket status changed events (notify customer).
     */
    public function handleTicketStatusChanged(QueueTicketStatusChanged $event): void
    {
        $ticket = $event->ticket;
        $status = $ticket->status;

        $notifyStatuses = [
            QueueTicket::STATUS_DIPANGGIL,
            QueueTicket::STATUS_SEDANG_DILAYANI,
            QueueTicket::STATUS_SELESAI,
            QueueTicket::STATUS_DILEWATI,
            QueueTicket::STATUS_DIBATALKAN,
        ];

        if (! in_array($status, $notifyStatuses, true)) {
            return;
        }

        // Idempotency: Prevent duplicate notifications for the exact same status
        if ($ticket->last_notified_status === $status) {
            return;
        }

        $ticket->last_notified_status = $status;
        $ticket->saveQuietly();

        // 1. Customer Email Notification (if email provided and explicit consent given)
        if ($ticket->customer_email && $ticket->customer_email_consented_at) {
            try {
                Notification::route('mail', $ticket->customer_email)
                    ->notify(new CustomerTicketStatusNotification($ticket, $status));
            } catch (\Throwable $e) {
                Log::warning('Failed sending customer email notification: ' . $e->getMessage(), [
                    'ticket_id' => $ticket->id,
                ]);
            }
        }

        // 2. Customer Web Push Notification (if browser push subscription exists)
        if ($ticket->pushSubscriptions()->exists()) {
            try {
                $statusLabel = match ($status) {
                    QueueTicket::STATUS_DIPANGGIL => 'Dipanggil',
                    QueueTicket::STATUS_SEDANG_DILAYANI => 'Sedang Dilayani',
                    QueueTicket::STATUS_SELESAI => 'Pelayanan Selesai',
                    QueueTicket::STATUS_DILEWATI => 'Dilewati',
                    QueueTicket::STATUS_DIBATALKAN => 'Dibatalkan',
                    default => ucfirst($status),
                };

                $counterName = $ticket->counter?->name;
                $counterInfo = $counterName ? ' di ' . $counterName : '';

                $body = match ($status) {
                    QueueTicket::STATUS_DIPANGGIL => sprintf('Nomor antrean Anda telah dipanggil%s. Silakan segera menuju loket.', $counterInfo),
                    QueueTicket::STATUS_SEDANG_DILAYANI => sprintf('Nomor antrean Anda saat ini sedang dilayani%s.', $counterInfo),
                    QueueTicket::STATUS_SELESAI => 'Pelayanan antrean Anda telah selesai. Terima kasih!',
                    QueueTicket::STATUS_DILEWATI => 'Nomor antrean Anda dilewati karena belum hadir saat dipanggil.',
                    QueueTicket::STATUS_DIBATALKAN => sprintf('Nomor antrean dibatalkan: %s', $ticket->cancellation_reason ?: 'Pembatalan'),
                    default => sprintf('Pembaruan status antrean Anda: %s', $statusLabel),
                };

                $this->webPushService->sendToTicket($ticket, [
                    'title' => sprintf('Antrean %s: %s', $ticket->ticket_number, $statusLabel),
                    'body' => $body,
                    'data' => [
                        'url' => route('customer.queue.track', ['token' => $ticket->access_token]),
                        'tag' => 'ticket-status-' . $ticket->id,
                    ],
                ]);

                // When ticket reaches a terminal state, clean up its push subscriptions
                if ($ticket->isTerminal()) {
                    $ticket->pushSubscriptions()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed sending customer push notification: ' . $e->getMessage(), [
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }
}
