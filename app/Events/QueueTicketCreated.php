<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public QueueTicket $ticket
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->ticket->business_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'QueueTicketCreated';
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
            'source' => $this->ticket->source,
            'status' => $this->ticket->status,
            'counter_id' => $this->ticket->counter_id,
            'counter_name' => $this->ticket->counter?->name,
            'counter_code' => $this->ticket->counter?->code,
            'created_at' => $this->ticket->created_at?->toIso8601String(),
        ];
    }
}
