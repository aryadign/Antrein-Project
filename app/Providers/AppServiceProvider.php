<?php

namespace App\Providers;

use App\Events\CounterStatusChanged;
use App\Events\QueueTicketCounterAssigned;
use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Listeners\BroadcastToPublicDisplayListener;
use App\Listeners\SendQueueNotificationListener;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        Event::listen(QueueTicketCreated::class, [SendQueueNotificationListener::class, 'handleTicketCreated']);
        Event::listen(QueueTicketStatusChanged::class, [SendQueueNotificationListener::class, 'handleTicketStatusChanged']);

        // Public Queue Display Real-time Event Listeners (Milestone 16 - P1-08)
        Event::listen(QueueTicketCreated::class, [BroadcastToPublicDisplayListener::class, 'handleTicketCreated']);
        Event::listen(QueueTicketStatusChanged::class, [BroadcastToPublicDisplayListener::class, 'handleTicketStatusChanged']);
        Event::listen(QueueTicketCounterAssigned::class, [BroadcastToPublicDisplayListener::class, 'handleTicketCounterAssigned']);
        Event::listen(CounterStatusChanged::class, [BroadcastToPublicDisplayListener::class, 'handleCounterStatusChanged']);
    }
}
