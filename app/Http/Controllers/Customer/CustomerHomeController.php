<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\QueueService;
use App\Services\WaitingTimeEstimationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Guests have no account, so "their" tickets are the ones stored in the session
 * when they take a queue, and their profile is kept in the session as well.
 */
class CustomerHomeController extends Controller
{
    /**
     * @var list<string>
     */
    private const array ACTIVE_STATUSES = [
        QueueTicket::STATUS_MENUNGGU,
        QueueTicket::STATUS_DIPANGGIL,
        QueueTicket::STATUS_SEDANG_DILAYANI,
    ];

    /**
     * @var list<string>
     */
    private const array FINISHED_STATUSES = [
        QueueTicket::STATUS_SELESAI,
        QueueTicket::STATUS_DIBATALKAN,
        QueueTicket::STATUS_DILEWATI,
        QueueTicket::STATUS_TIDAK_HADIR,
    ];

    /**
     * Sort options on Cari Toko, keyed by the `urut` query value.
     *
     * @var array<string, string>
     */
    public const array SORT_OPTIONS = [
        'rekomendasi' => 'Rekomendasi',
        'antrean' => 'Antrean Terpendek',
        'nama' => 'Nama A-Z',
    ];

    public function __construct(
        protected QueueService $queueService,
        protected WaitingTimeEstimationService $waitingTimeEstimationService,
    ) {}

    /**
     * Beranda: greeting, featured store, active ticket and category shortcuts.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeTickets = $this->activeTickets($user);
        $activeTicket = $activeTickets->first();

        $featuredStore = $activeTicket?->business
            ?? $this->finishedTickets($user)->first()?->business;

        if ($featuredStore) {
            $featuredStore->loadMissing('hours');
        }

        $stores = $this->sortStores($this->hydrateWaitingCounts($this->storesQuery()->get()), 'rekomendasi');

        return view('customer.dashboard', [
            'customerName' => $user?->name ?? $this->guestProfile()['name'],
            'activeTicket' => $activeTicket,
            'activeTickets' => $activeTickets,
            'activeTicketCount' => $activeTickets->count(),
            'maxActiveTickets' => 3,
            'featuredStore' => $featuredStore,
            'isFirstVisit' => $featuredStore === null,
            'categories' => $this->customerCategories(),
            'areas' => Business::AREAS,
            'stores' => $stores->take(6),
            'storeCount' => $stores->count(),
        ]);
    }

    /**
     * Cari Toko & Antrean: every open store, filtered by area, category, and search query.
     */
    public function search(Request $request): View
    {
        $categories = $this->customerCategories();
        $requestedCategory = (string) $request->query('kategori', '');
        $requestedArea = trim((string) $request->query('daerah', ''));
        $selectedArea = in_array($requestedArea, Business::AREAS, true) ? $requestedArea : 'semua';
        $requestedSort = (string) $request->query('urut', '');
        $selectedSort = array_key_exists($requestedSort, self::SORT_OPTIONS) ? $requestedSort : 'rekomendasi';

        $query = $this->storesQuery();
        if ($selectedArea !== 'semua') {
            $query->where('area', $selectedArea);
        }

        $businesses = $this->sortStores($this->hydrateWaitingCounts($query->get()), $selectedSort);

        return view('customer.search', [
            'businesses' => $businesses,
            'categories' => $categories,
            'selectedCategory' => $categories->contains('slug', $requestedCategory) ? $requestedCategory : 'semua',
            'areas' => Business::AREAS,
            'selectedArea' => $selectedArea,
            'sortOptions' => self::SORT_OPTIONS,
            'selectedSort' => $selectedSort,
        ]);
    }

    /**
     * Antrean Saya: the customer's active tickets plus finished/cancelled ones, filtered on the client.
     */
    public function myTickets(Request $request): View
    {
        $user = $request->user();
        $activeTickets = $this->activeTickets($user);

        return view('customer.my-tickets', [
            'activeTickets' => $activeTickets,
            'finishedTickets' => $this->finishedTickets($user),
            'activeTicketCount' => $activeTickets->count(),
            'maxActiveTickets' => 3,
        ]);
    }

    /**
     * Riwayat Antrean: finished, cancelled and skipped tickets grouped per month.
     */
    public function history(Request $request): View
    {
        $historyByMonth = $this->finishedTickets($request->user())
            ->groupBy(fn (QueueTicket $ticket) => $ticket->created_at->translatedFormat('F Y'));

        return view('customer.history', [
            'historyByMonth' => $historyByMonth,
        ]);
    }

    /**
     * Profil Saya: customer contact details used to prefill the queue form.
     */
    public function profile(Request $request): View
    {
        $user = $request->user();
        $sessionProfile = $this->guestProfile();

        return view('customer.profile', [
            'profile' => [
                'name' => $user?->name ?? $sessionProfile['name'],
                'phone' => $sessionProfile['phone'],
                'email' => $user?->email ?? $sessionProfile['email'],
            ],
            'user' => $user,
        ]);
    }

    /**
     * Save the authenticated customer profile details.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = $request->user();
        if ($user) {
            $user->update(['name' => trim($validated['name'])]);
        }

        $request->session()->put('customer_profile', [
            'name' => trim($validated['name']),
            'phone' => isset($validated['phone']) ? trim($validated['phone']) : null,
            'email' => isset($validated['email']) ? trim($validated['email']) : ($user?->email),
        ]);

        return redirect()->route('customer.profile')->with('status', 'Profil berhasil disimpan.');
    }

    /**
     * Active tickets for the authenticated customer (plus any session-bound tickets),
     * with live dynamic schedule estimates. Strictly never leaks other customers' now_serving numbers.
     *
     * @return Collection<int, QueueTicket>
     */
    protected function activeTickets(?User $user = null): Collection
    {
        $tickets = $this->customerTickets(self::ACTIVE_STATUSES, $user);

        // Sweep stale 15-minute called tickets for these businesses first
        $tickets->pluck('business')->filter()->unique('id')->each(function (Business $business): void {
            $this->queueService->expireStaleCalledTickets($business);
        });

        // Re-fetch only still-active tickets in case any transitioned to tidak_hadir
        return $this->customerTickets(self::ACTIVE_STATUSES, $user)->each(function (QueueTicket $ticket): void {
            $ticket->queue_position = $this->queueService->calculateQueuePosition($ticket);
            $ticket->wait_estimate = $this->waitingTimeEstimationService->estimateForTicket($ticket, $ticket->queue_position);
            $ticket->schedule_estimate = $this->waitingTimeEstimationService->estimateScheduleForTicket($ticket, $ticket->queue_position);
        });
    }

    /**
     * Finished, cancelled or skipped tickets for the customer, newest first.
     *
     * @return Collection<int, QueueTicket>
     */
    protected function finishedTickets(?User $user = null): Collection
    {
        return $this->customerTickets(self::FINISHED_STATUSES, $user);
    }

    /**
     * @param  list<string>  $statuses
     * @return Collection<int, QueueTicket>
     */
    protected function customerTickets(array $statuses, ?User $user = null): Collection
    {
        $ticketIds = array_filter((array) session('customer_ticket_ids', []), 'is_numeric');

        if (! $user && $ticketIds === []) {
            return collect();
        }

        return QueueTicket::query()
            ->where(function (Builder $query) use ($user, $ticketIds): void {
                if ($user) {
                    $query->where('user_id', $user->id);
                    if ($ticketIds !== []) {
                        $query->orWhereIn('id', $ticketIds);
                    }
                } else {
                    $query->whereIn('id', $ticketIds);
                }
            })
            ->whereIn('status', $statuses)
            ->with(['business.category', 'service'])
            ->latest()
            ->get();
    }

    /**
     * Order stores for customers. "rekomendasi": open first, then best rated, then shortest queue;
     * "antrean": open first, then fewest waiting; "nama": alphabetical.
     *
     * @param  Collection<int, Business>  $stores
     * @return Collection<int, Business>
     */
    protected function sortStores(Collection $stores, string $sort): Collection
    {
        $sorted = match ($sort) {
            'antrean' => $stores->sortBy([
                fn (Business $a, Business $b): int => $b->isOpen() <=> $a->isOpen(),
                fn (Business $a, Business $b): int => (int) $a->waiting_count <=> (int) $b->waiting_count,
                fn (Business $a, Business $b): int => strcasecmp($a->name, $b->name),
            ]),
            'nama' => $stores->sortBy(fn (Business $business): string => Str::lower($business->name)),
            default => $stores->sortBy([
                fn (Business $a, Business $b): int => $b->isOpen() <=> $a->isOpen(),
                fn (Business $a, Business $b): int => ($b->customerRating() ?? 0) <=> ($a->customerRating() ?? 0),
                fn (Business $a, Business $b): int => (int) $a->waiting_count <=> (int) $b->waiting_count,
                fn (Business $a, Business $b): int => strcasecmp($a->name, $b->name),
            ]),
        };

        return $sorted->values();
    }

    /**
     * Stores visible to customers, with today's waiting count and review summary, ordered by name.
     *
     * @return Builder<Business>
     */
    protected function storesQuery(): Builder
    {
        return Business::query()
            ->visibleToCustomers()
            ->with(['category', 'hours'])
            ->withAvg('customerReviews as reviews_avg_rating', 'rating')
            ->withCount('customerReviews as reviews_count')
            ->withCount('activeCounters as active_counters_count')
            ->withAvg(['services as avg_service_minutes' => fn (Builder $query) => $query->where('is_active', true)], 'duration_minutes')
            ->withMin(['services as min_service_minutes' => fn (Builder $query) => $query->where('is_active', true)], 'duration_minutes')
            ->withMax(['services as max_service_minutes' => fn (Builder $query) => $query->where('is_active', true)], 'duration_minutes')
            ->orderBy('name');
    }

    /**
     * Hydrate today's waiting ticket count using each business's local timezone.
     *
     * @param  Collection<int, Business>  $stores
     * @return Collection<int, Business>
     */
    protected function hydrateWaitingCounts(Collection $stores): Collection
    {
        if ($stores->isEmpty()) {
            return $stores;
        }

        $dateByTimezone = [];
        foreach ($stores as $store) {
            $tz = $store->timezone ?: 'Asia/Makassar';
            $dateByTimezone[$tz] ??= Carbon::now($tz)->toDateString();
        }

        $uniqueDates = array_values(array_unique($dateByTimezone));

        $dailyQueues = DailyQueue::query()
            ->whereIn('business_id', $stores->pluck('id'))
            ->where(function (Builder $query) use ($uniqueDates): void {
                foreach ($uniqueDates as $dateString) {
                    $query->orWhereDate('queue_date', $dateString);
                }
            })
            ->withCount(['queueTickets as waiting_count' => fn (Builder $query) => $query->where('status', QueueTicket::STATUS_MENUNGGU)])
            ->get()
            ->keyBy(fn (DailyQueue $dq): string => $dq->business_id.':'.$dq->queue_date->toDateString());

        return $stores->each(function (Business $store) use ($dateByTimezone, $dailyQueues): void {
            $tz = $store->timezone ?: 'Asia/Makassar';
            $key = $store->id.':'.$dateByTimezone[$tz];
            $store->setAttribute('waiting_count', (int) ($dailyQueues->get($key)?->waiting_count ?? 0));
        });
    }

    /**
     * Every active category, with how many customer-visible stores it has (stores first).
     *
     * @return Collection<int, BusinessCategory>
     */
    protected function customerCategories(): Collection
    {
        return BusinessCategory::query()
            ->where('is_active', true)
            ->withCount(['businesses as stores_count' => fn (Builder $query) => $query->visibleToCustomers()])
            ->orderByDesc('stores_count')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{name: ?string, phone: ?string, email: ?string}
     */
    protected function guestProfile(): array
    {
        $profile = (array) session('customer_profile', []);

        return [
            'name' => $profile['name'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'email' => $profile['email'] ?? null,
        ];
    }
}
