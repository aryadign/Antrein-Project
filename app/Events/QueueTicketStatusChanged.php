<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketStatusChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public QueueTicket $ticket,
        public ?string $previousStatus = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('business.'.$this->ticket->business_id),
        ];

        if (! empty($this->ticket->access_token)) {
            $channels[] = new PrivateChannel('ticket.'.hash('sha256', $this->ticket->access_token));
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'QueueTicketStatusChanged';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'sequence_number' => $this->ticket->sequence_number,
            'service_id' => $this->ticket->service_id,
            'service_name' => $this->ticket->service?->name ?? 'Layanan',
            'customer_name' => $this->ticket->customer_name,
            'status' => $this->ticket->status,
            'previous_status' => $this->previousStatus,
            'counter_id' => $this->ticket->counter_id,
            'counter_name' => $this->ticket->counter?->name,
            'counter_code' => $this->ticket->counter?->code,
            'called_at' => $this->ticket->called_at?->toIso8601String(),
            'recalled_at' => $this->ticket->recalled_at?->toIso8601String(),
            'service_started_at' => $this->ticket->service_started_at?->toIso8601String(),
            'completed_at' => $this->ticket->completed_at?->toIso8601String(),
            'skipped_at' => $this->ticket->skipped_at?->toIso8601String(),
            'cancelled_at' => $this->ticket->cancelled_at?->toIso8601String(),
            'updated_at' => $this->ticket->updated_at?->toIso8601String(),
        ];
    }
}
