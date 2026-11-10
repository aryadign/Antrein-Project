<?php

namespace App\Providers;

use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
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
    }
}
