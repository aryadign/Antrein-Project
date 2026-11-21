<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessCounter;
use App\Models\QueueTicket;
use Carbon\Carbon;

class DisplayService
{
    /**
     * Resolve an active business by its public display token.
     */
    public function resolveBusiness(string $token): ?Business
    {
        if (empty($token) || strlen($token) > 120) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        /** @var Business|null $business */
        $business = Business::where('display_token_hash', $tokenHash)->first();

        if (! $business || ! $business->isDisplayActive()) {
            return null;
        }

        return $business;
    }

    /**
     * Build the serialized, sanitized read-only display data for public TV/monitor screens.
     *
     * @return array<string, mixed>
     */
    public function formatDisplayData(Business $business): array
    {
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $now = Carbon::now($timezone);

        // Fetch active counters with active ticket (dipanggil or sedang_dilayani) in a single optimized query
        $counters = $business->activeCounters()
            ->with(['activeTicket.service'])
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        if (! $business->usesStations() && $counters->count() > 1) {
            $primary = $counters->first(fn (BusinessCounter $c): bool => $c->activeTicket !== null) ?? $counters->first();
            $counters = $primary ? collect([$primary]) : collect();
        }

        $activeCounterData = $counters->map(function (BusinessCounter $counter) {
            /** @var QueueTicket|null $ticket */
            $ticket = $counter->activeTicket;

            return [
                'id' => $counter->id,
                'name' => $counter->name,
                'code' => $counter->code,
                'description' => $counter->description,
                'is_active' => (bool) $counter->is_active,
                'has_active_ticket' => (bool) $ticket,
                'active_ticket' => $ticket ? [
                    'ticket_number' => $ticket->ticket_number,
                    'sequence_number' => $ticket->sequence_number,
                    'status' => $ticket->status,
                    'status_label' => QueueTicket::labelForStatus($ticket->status),
                    'service_name' => $ticket->service?->name ?? 'Layanan',
                    'booking_slot_window' => $ticket->bookingSlotWindowLabel($ticket->business?->timezone),
                    'called_at' => $ticket->called_at?->toIso8601String(),
                    'called_at_formatted' => $ticket->called_at?->setTimezone($ticket->business->timezone ?? 'Asia/Makassar')->format('H:i'),
                ] : null,
            ];
        });

        // Summary counts
        $callingCount = $counters->filter(fn ($c) => $c->activeTicket?->status === QueueTicket::STATUS_DIPANGGIL)->count();
        $servingCount = $counters->filter(fn ($c) => $c->activeTicket?->status === QueueTicket::STATUS_SEDANG_DILAYANI)->count();

        return [
            'business' => [
                'name' => $business->name,
                'slug' => $business->slug,
                'timezone' => $timezone,
                'logo_url' => $business->getLogoUrl(),
                'use_stations' => $business->usesStations(),
            ],
            'server_time' => [
                'iso' => $now->toIso8601String(),
                'time' => $now->format('H:i'),
                'time_with_seconds' => $now->format('H:i:s'),
                'date' => $now->translatedFormat('l, d F Y'),
            ],
            'counters' => $activeCounterData->values()->all(),
            'meta' => [
                'use_stations' => $business->usesStations(),
                'total_active_counters' => $counters->count(),
                'calling_count' => $callingCount,
                'serving_count' => $servingCount,
                'channel_name' => 'display.'.$business->display_token_hash,
            ],
        ];
    }
}
