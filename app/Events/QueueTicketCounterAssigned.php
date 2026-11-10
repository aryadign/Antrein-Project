<?php

namespace App\Events;

use App\Models\BusinessCounter;
use App\Models\QueueTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTicketCounterAssigned implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public QueueTicket $ticket,
        public BusinessCounter $counter
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
            new PrivateChannel('counter.'.$this->counter->id),
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
        return 'QueueTicketCounterAssigned';
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
            'customer_name' => $this->ticket->customer_name,
            'service_name' => $this->ticket->service?->name ?? 'Layanan',
            'status' => $this->ticket->status,
            'counter_id' => $this->counter->id,
            'counter_name' => $this->counter->name,
            'counter_code' => $this->counter->code,
        ];
    }
}
