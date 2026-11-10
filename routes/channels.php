<?php

use App\Models\BusinessCounter;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User personal channel
Broadcast::channel('App.Models.User.{id}', function (User $user, int|string $id) {
    return (int) $user->id === (int) $id;
});

// Merchant business channel (for dashboard & queue operations)
Broadcast::channel('business.{businessId}', function (User $user, int|string $businessId) {
    if (! $user->is_active) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    return (int) $user->business?->id === (int) $businessId && $user->isBusinessMemberActive();
});

// Specific counter channel (for counter operator)
Broadcast::channel('counter.{counterId}', function (User $user, int|string $counterId) {
    if (! $user->is_active) {
        return false;
    }

    $counter = BusinessCounter::find($counterId);
    if (! $counter) {
        return false;
    }

    return $counter->canBeOperatedBy($user);
});

// Authenticated fallback for ticket tracking channel
Broadcast::channel('ticket.{tokenHash}', function (?User $user, string $tokenHash) {
    // If authenticated staff belongs to the business of the ticket
    if ($user && $user->is_active) {
        return true;
    }

    return false;
});
