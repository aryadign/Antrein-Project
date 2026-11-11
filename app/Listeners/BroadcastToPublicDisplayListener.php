<?php

namespace App\Listeners;

use App\Events\CounterStatusChanged;
use App\Events\PublicQueueDisplayUpdated;
use App\Events\QueueTicketCounterAssigned;
use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;

class BroadcastToPublicDisplayListener
{
    /**
     * Handle queue ticket created events.
     */
    public function handleTicketCreated(QueueTicketCreated $event): void
    {
        $business = $event->ticket->business;
        if ($business && $business->isDisplayActive()) {
            event(new PublicQueueDisplayUpdated($business, 'ticket_created'));
        }
    }

    /**
     * Handle queue ticket status changed events.
     */
    public function handleTicketStatusChanged(QueueTicketStatusChanged $event): void
    {
        $business = $event->ticket->business;
        if ($business && $business->isDisplayActive()) {
            event(new PublicQueueDisplayUpdated($business, 'status_changed'));
        }
    }

    /**
     * Handle queue ticket counter assigned events.
     */
    public function handleTicketCounterAssigned(QueueTicketCounterAssigned $event): void
    {
        $business = $event->ticket->business;
        if ($business && $business->isDisplayActive()) {
            event(new PublicQueueDisplayUpdated($business, 'counter_assigned'));
        }
    }

    /**
     * Handle counter status changed events.
     */
    public function handleCounterStatusChanged(CounterStatusChanged $event): void
    {
        $business = $event->counter->business;
        if ($business && $business->isDisplayActive()) {
            event(new PublicQueueDisplayUpdated($business, 'counter_status_changed'));
        }
    }
}

