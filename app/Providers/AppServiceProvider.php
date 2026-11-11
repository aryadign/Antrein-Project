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
use RuntimeException;

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

        // Security Review (Pre-M18 P1): production must never serve Reverb
        // WebSockets with a wildcard origin allow-list.
        self::validateReverbAllowedOrigins();

        // Public Queue Display Real-time Event Listeners (Milestone 16 - P1-08)
        Event::listen(QueueTicketCreated::class, [BroadcastToPublicDisplayListener::class, 'handleTicketCreated']);
        Event::listen(QueueTicketStatusChanged::class, [BroadcastToPublicDisplayListener::class, 'handleTicketStatusChanged']);
        Event::listen(QueueTicketCounterAssigned::class, [BroadcastToPublicDisplayListener::class, 'handleTicketCounterAssigned']);
        Event::listen(CounterStatusChanged::class, [BroadcastToPublicDisplayListener::class, 'handleCounterStatusChanged']);
    }

    /**
     * Reject a wildcard Reverb WebSocket origin allow-list in production.
     *
     * Security Review (Pre-M18 P1): the browser Origin check is enforced by
     * the Reverb server, so a wildcard allow-list would let any website open
     * a WebSocket connection to the application's real-time channels. Local
     * development keeps the wildcard default for convenience; production must
     * list explicit origins via REVERB_ALLOWED_ORIGINS.
     *
     * @throws RuntimeException When production resolves Reverb origins to a wildcard.
     */
    public static function validateReverbAllowedOrigins(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $allowedOrigins = (array) config('reverb.apps.apps.0.allowed_origins', []);

        if (in_array('*', $allowedOrigins, true)) {
            throw new RuntimeException(
                'Reverb WebSocket origins must not use a wildcard ("*") in production. Set the '
                .'REVERB_ALLOWED_ORIGINS environment variable to an explicit comma-separated list '
                .'of origins (e.g. https://app.example.com).'
            );
        }
    }
}
