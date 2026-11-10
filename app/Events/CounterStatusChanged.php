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

class CounterStatusChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BusinessCounter $counter
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->counter->business_id),
            new PrivateChannel('counter.'.$this->counter->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'CounterStatusChanged';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $activeTicket = $this->counter->tickets()
            ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])
            ->latest('called_at')
            ->first();

        return [
            'counter_id' => $this->counter->id,
            'counter_name' => $this->counter->name,
            'counter_code' => $this->counter->code,
            'is_active' => (bool) $this->counter->is_active,
            'active_ticket_id' => $activeTicket?->id,
            'active_ticket_number' => $activeTicket?->ticket_number,
            'active_ticket_status' => $activeTicket?->status,
            'customer_name' => $activeTicket?->customer_name,
        ];
    }
}
