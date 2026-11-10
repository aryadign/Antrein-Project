<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use App\Services\WaitingTimeEstimationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M18 / P1-10 — Historical Waiting Time Estimation test suite.
 *
 * The estimate is derived from the business's own historical service durations
 * (median), divided by the number of currently active counters, and applied to
 * the effective number of people ahead (accounting for free counters that call
 * leading tickets almost immediately). When history is insufficient the system
 * must refuse to fabricate a number instead of substituting zero.
 */
class HistoricalWaitingTimeEstimationTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;
    private WaitingTimeEstimationService $estimationService;
    private User $merchant;
    private Business $business;
    private Service $service;
    private DailyQueue $todayQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);
        $this->estimationService = app(WaitingTimeEstimationService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik Estimasi',
            'slug' => 'klinik-estimasi',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Estimasi Waktu',
            'slug' => 'klinik-estimasi-waktu',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Konsultasi Umum',
            'duration_minutes' => 15,
            'is_active' => true,
        ]);

        $this->todayQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);
    }

    /**
     * Seed one completed historical ticket with a known actual service duration.
     * The wait until being called (created_at -> called_at) is kept short and
     * constant so the service duration is the varying input under test.
     */
    private function seedCompletedHistory(
        DailyQueue $queue,
        Carbon $createdAt,
        int $durationMinutes,
        int $sequence,
        string $customerName = 'Pelanggan Histori',
        ?string $customerPhone = '081111111111',
        ?string $customerEmail = null
    ): QueueTicket {
        return QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $queue->id,
            'service_id' => $this->service->id,
            'ticket_number' => sprintf('A-%03d', $sequence),
            'sequence_number' => $sequence,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'hist-m18-'.$queue->id.'-'.$sequence,
            'created_at' => $createdAt,
            'called_at' => $createdAt->copy()->addMinutes(3),
            'service_started_at' => $createdAt->copy()->addMinutes(5),
            'completed_at' => $createdAt->copy()->addMinutes(5 + $durationMinutes),
        ]);
    }

    /**
     * Seed a waiting ticket in today's queue, keeping the daily queue's
     * current_number counter in sync with manual sequence numbering.
     */
    private function seedWaitingTicket(int $sequence, string $customerName = 'Pelanggan Depan'): QueueTicket
    {
        $this->todayQueue->current_number = max($this->todayQueue->current_number, $sequence);
        $this->todayQueue->save();

        return QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->todayQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => sprintf('A-%03d', $sequence),
            'sequence_number' => $sequence,
            'customer_name' => $customerName,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'wait-m18-'.$sequence,
            'created_at' => Carbon::now('Asia/Makassar')->subMinutes(10 - $sequence),
        ]);
    }

    /**
     * Seed the default set of five completed samples (median 10 minutes),
     * spread across different historical days inside the 30-day window.
     */
    private function seedDefaultHistory(array $durationMinutes = [10, 10, 10, 10, 10]): DailyQueue
    {
        $historyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::yesterday('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);

        foreach ($durationMinutes as $index => $duration) {
            $dayOffset = ($index % 3) + 1; // spread samples over different past days
            $this->seedCompletedHistory(
                $historyQueue,
                Carbon::now('Asia/Makassar')->subDays($dayOffset)->setTime(9, $index * 10),
                $duration,
                $index + 1
            );
        }

        return $historyQueue;
    }

    public function test_new_business_without_history_gets_no_estimate_instead_of_zero(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Baru',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertNull($response->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_INSUFFICIENT_HISTORY, $response->json('data.estimated_wait_reason'));
    }

    public function test_tracking_page_shows_unavailable_fallback_when_history_is_missing(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Baru',
            customerPhone: '081234567890'
        );

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Estimasi waktu tunggu belum tersedia');
        $response->assertDontSee('± 0 menit');
    }

    public function test_estimate_stays_unavailable_below_minimum_sample_threshold(): void
    {
        $this->seedDefaultHistory([10, 12, 9, 11]); // only 4 valid samples

        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Ambang',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertNull($response->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_INSUFFICIENT_HISTORY, $response->json('data.estimated_wait_reason'));
    }

    public function test_estimate_available_at_minimum_sample_threshold(): void
    {
        $this->seedDefaultHistory(); // five samples, median 10 minutes, single default counter

        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Minimal',
            customerPhone: '081234567890'
        );

        // Position 1 with a free counter: next in line, no remaining queue to drain.
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(1, $response->json('data.queue_position'));
        $this->assertSame(0, $response->json('data.estimated_wait_minutes'));
        $this->assertNull($response->json('data.estimated_wait_reason'));
    }

    public function test_estimate_uses_median_of_historical_service_durations(): void
    {
        // Median must be 14 (not the mean 30.4): a single slow service must not skew the estimate.
        $this->seedDefaultHistory([10, 12, 14, 16, 100]);

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Median',
            customerPhone: '081234567890'
        );

        // Position 3 with one free default counter: 2 people ahead x 14 min / 1 counter = 28.
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(3, $response->json('data.queue_position'));
        $this->assertSame(28, $response->json('data.estimated_wait_minutes'));
    }

    public function test_estimate_rounds_up_to_whole_minutes(): void
    {
        // Six samples: median (9 + 10) / 2 = 9.5 minutes.
        $this->seedDefaultHistory([5, 6, 9, 10, 10, 11]);

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $this->seedWaitingTicket(3);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Pembulatan',
            customerPhone: '081234567890'
        );

        // Position 4 with one free counter: 3 people ahead x 9.5 = 28.5 -> rounded up to 29.
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(29, $response->json('data.estimated_wait_minutes'));
    }

    public function test_active_counters_divide_the_estimated_wait(): void
    {
        $this->seedDefaultHistory(); // median 10 minutes

        // Open a second active counter.
        $this->business->counters()->create([
            'name' => 'Loket 2',
            'code' => 'L-02',
            'description' => 'Loket pelayanan kedua',
            'is_active' => true,
        ]);

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Paralel',
            customerPhone: '081234567890'
        );

        // Two free counters call the two leading tickets immediately, leaving 1
        // effective person ahead at 10 min / 2 counters = 5 minutes.
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(5, $response->json('data.estimated_wait_minutes'));
    }

    public function test_busy_counter_is_not_counted_as_free(): void
    {
        $this->seedDefaultHistory(); // median 10 minutes, single default counter

        // A called ticket occupies the only counter: the position-1 customer
        // must wait for the remaining serving window instead of getting zero.
        $calledTicket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Dipanggil',
            customerPhone: '081234567891'
        );
        $this->queueService->callNext($this->business, $this->merchant);

        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Menunggu',
            customerPhone: '081234567892'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(1, $response->json('data.queue_position'));
        $this->assertSame(10, $response->json('data.estimated_wait_minutes'));

        // The occupying ticket is not part of the estimate sample set (it is not completed).
        $this->assertSame(QueueTicket::STATUS_DIPANGGIL, $calledTicket->refresh()->status);
    }

    public function test_inactive_counters_do_not_speed_up_the_estimate(): void
    {
        $this->seedDefaultHistory(); // median 10 minutes

        // The extra counter exists but is inactive: only one serving lane counts.
        $this->business->counters()->create([
            'name' => 'Loket Cadangan',
            'code' => 'L-02',
            'description' => 'Loket nonaktif',
            'is_active' => false,
        ]);

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Loket Nonaktif',
            customerPhone: '081234567890'
        );

        // 2 ahead x 10 / 1 active counter = 20 (inactive counter must not divide the rate).
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(20, $response->json('data.estimated_wait_minutes'));
    }

    public function test_estimate_unavailable_when_business_has_no_active_counters(): void
    {
        $this->seedDefaultHistory();
        $this->business->counters()->update(['is_active' => false]);

        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Tanpa Loket',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertNull($response->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_NO_ACTIVE_COUNTERS, $response->json('data.estimated_wait_reason'));
    }

    public function test_no_estimate_for_called_serving_and_terminal_tickets(): void
    {
        $this->seedDefaultHistory();

        // Called ticket
        $calledTicket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Dipanggil',
            customerPhone: '081234567893'
        );
        $this->queueService->callNext($this->business, $this->merchant);

        $responseCalled = $this->getJson(route('customer.queue.track', ['token' => $calledTicket->access_token]));
        $responseCalled->assertOk();
        $this->assertNull($responseCalled->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_NOT_WAITING, $responseCalled->json('data.estimated_wait_reason'));

        // Serving ticket
        $calledTicket->refresh();
        $this->queueService->startService($calledTicket, $this->merchant);
        $responseServing = $this->getJson(route('customer.queue.track', ['token' => $calledTicket->access_token]));
        $responseServing->assertOk();
        $this->assertNull($responseServing->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_NOT_WAITING, $responseServing->json('data.estimated_wait_reason'));

        // Terminal ticket
        $calledTicket->refresh();
        $this->queueService->completeTicket($calledTicket, $this->merchant);
        $responseDone = $this->getJson(route('customer.queue.track', ['token' => $calledTicket->access_token]));
        $responseDone->assertOk();
        $this->assertNull($responseDone->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_NOT_WAITING, $responseDone->json('data.estimated_wait_reason'));
    }

    public function test_estimate_is_tenant_isolated_per_business(): void
    {
        // Second business with a much slower service history (median 40 minutes).
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $this->business->category_id,
            'name' => 'Klinik Lambat',
            'slug' => 'klinik-lambat',
            'phone' => '081999999999',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Lambat',
            'duration_minutes' => 45,
            'is_active' => true,
        ]);
        $otherQueue = DailyQueue::create([
            'business_id' => $otherBusiness->id,
            'queue_date' => Carbon::yesterday('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);
        foreach ([40, 40, 40, 40, 40] as $index => $duration) {
            QueueTicket::create([
                'business_id' => $otherBusiness->id,
                'daily_queue_id' => $otherQueue->id,
                'service_id' => $otherService->id,
                'ticket_number' => sprintf('B-%03d', $index + 1),
                'sequence_number' => $index + 1,
                'customer_name' => 'Pelanggan Klinik Lambat',
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_SELESAI,
                'access_token' => 'hist-m18b-'.($index + 1),
                'created_at' => Carbon::now('Asia/Makassar')->subDays(2),
                'called_at' => Carbon::now('Asia/Makassar')->subDays(2)->addMinutes(3),
                'service_started_at' => Carbon::now('Asia/Makassar')->subDays(2)->addMinutes(5),
                'completed_at' => Carbon::now('Asia/Makassar')->subDays(2)->addMinutes(5 + $duration),
            ]);
        }

        $this->seedDefaultHistory(); // median 10 minutes for the primary business

        // Primary business customer: 2 ahead x 10 = 20 minutes.
        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Utama',
            customerPhone: '081234567890'
        );

        // Other business customer: 2 ahead x 40 = 80 minutes (own history only).
        for ($i = 1; $i <= 2; $i++) {
            $otherQueueToday = app(QueueService::class)->getOrCreateDailyQueue($otherBusiness);
            QueueTicket::create([
                'business_id' => $otherBusiness->id,
                'daily_queue_id' => $otherQueueToday->id,
                'service_id' => $otherService->id,
                'ticket_number' => sprintf('B-%03d', $i),
                'sequence_number' => $i,
                'customer_name' => 'Antrean Klinik Lambat '.$i,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => 'wait-m18b-'.$i,
                'created_at' => Carbon::now('Asia/Makassar')->subMinutes(30),
            ]);
        }
        $otherTicket = QueueTicket::create([
            'business_id' => $otherBusiness->id,
            'daily_queue_id' => app(QueueService::class)->getOrCreateDailyQueue($otherBusiness)->id,
            'service_id' => $otherService->id,
            'ticket_number' => 'B-003',
            'sequence_number' => 3,
            'customer_name' => 'Pelanggan Klinik Lambat Terakhir',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'wait-m18b-3',
            'created_at' => Carbon::now('Asia/Makassar'),
        ]);

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertOk();
        $this->assertSame(20, $response->json('data.estimated_wait_minutes'));

        $responseOther = $this->getJson(route('customer.queue.track', ['token' => $otherTicket->access_token]));
        $responseOther->assertOk();
        $this->assertSame(80, $responseOther->json('data.estimated_wait_minutes'));
    }

    public function test_anonymized_history_tickets_remain_usable_samples(): void
    {
        $historyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::yesterday('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);

        // ADR-13 anonymized shape: masked name/phone, notes removed — timestamps intact.
        foreach ([10, 10, 10, 10, 10] as $index => $duration) {
            $this->seedCompletedHistory(
                $historyQueue,
                Carbon::now('Asia/Makassar')->subDays(1)->setTime(10, $index * 10),
                $duration,
                $index + 1,
                customerName: 'Pelanggan #'.($index + 100),
                customerPhone: '0812****'.sprintf('%04d', $index + 1),
                customerEmail: null
            );
        }

        $this->seedWaitingTicket(1);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Sesudah Anonimisasi',
            customerPhone: '081234567890'
        );

        // Anonymized samples still provide the median: 1 ahead x 10 = 10 minutes.
        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertSame(10, $response->json('data.estimated_wait_minutes'));
    }

    public function test_invalid_and_incomplete_history_samples_are_excluded(): void
    {
        $historyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::yesterday('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);
        $base = Carbon::now('Asia/Makassar')->subDays(1)->setTime(9, 0);

        // Four valid samples.
        foreach ([10, 10, 10, 10] as $index => $duration) {
            $this->seedCompletedHistory($historyQueue, $base->copy()->addMinutes($index * 20), $duration, $index + 1);
        }

        // Negative duration (completed before started) — invalid.
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $historyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-005',
            'sequence_number' => 5,
            'customer_name' => 'Sampel Negatif',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'hist-m18-invalid-1',
            'created_at' => $base->copy(),
            'called_at' => $base->copy()->addMinutes(3),
            'service_started_at' => $base->copy()->addMinutes(30),
            'completed_at' => $base->copy()->addMinutes(20),
        ]);

        // Incomplete service (never completed) — excluded.
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $historyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-006',
            'sequence_number' => 6,
            'customer_name' => 'Sampel Belum Selesai',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SEDANG_DILAYANI,
            'access_token' => 'hist-m18-invalid-2',
            'created_at' => $base->copy(),
            'called_at' => $base->copy()->addMinutes(3),
            'service_started_at' => $base->copy()->addMinutes(5),
        ]);

        // Skipped historical ticket (never served) — excluded from service samples.
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $historyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-007',
            'sequence_number' => 7,
            'customer_name' => 'Sampel Dilewati',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_DILEWATI,
            'access_token' => 'hist-m18-invalid-3',
            'created_at' => $base->copy(),
            'skipped_at' => $base->copy()->addMinutes(10),
        ]);

        // Sample outside the 30-day window — excluded.
        $this->seedCompletedHistory(
            $historyQueue,
            Carbon::now('Asia/Makassar')->subDays(35)->setTime(9, 0),
            600,
            8
        );

        // Only the four valid in-window samples remain: below threshold -> unavailable.
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Sampel Tidak Valid',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $this->assertNull($response->json('data.estimated_wait_minutes'));
        $this->assertSame(WaitingTimeEstimationService::REASON_INSUFFICIENT_HISTORY, $response->json('data.estimated_wait_reason'));

        // Adding one more valid in-window sample (median stays 10) makes it available.
        $this->seedCompletedHistory($historyQueue, $base->copy()->addMinutes(90), 10, 9);

        $response2 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response2->assertOk();
        $this->assertSame(0, $response2->json('data.estimated_wait_minutes')); // position 1, free counter
    }

    public function test_estimate_is_deterministic_and_never_mutates_queue_state(): void
    {
        $this->seedDefaultHistory();

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Deterministik',
            customerPhone: '081234567890'
        );

        $before = QueueTicket::where('daily_queue_id', $this->todayQueue->id)
            ->orderBy('sequence_number')
            ->get(['id', 'sequence_number', 'status', 'called_at', 'counter_id'])
            ->map(fn ($t) => $t->getAttributes())
            ->all();
        $currentNumberBefore = $this->todayQueue->refresh()->current_number;

        $response1 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response2 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $this->assertSame(
            $response1->json('data.estimated_wait_minutes'),
            $response2->json('data.estimated_wait_minutes')
        );
        $this->assertSame(20, $response2->json('data.estimated_wait_minutes'));

        $after = QueueTicket::where('daily_queue_id', $this->todayQueue->id)
            ->orderBy('sequence_number')
            ->get(['id', 'sequence_number', 'status', 'called_at', 'counter_id'])
            ->map(fn ($t) => $t->getAttributes())
            ->all();
        $currentNumberAfter = $this->todayQueue->refresh()->current_number;

        // FIFO sequence, statuses, counter assignments, and daily queue counter untouched.
        $this->assertSame($before, $after);
        $this->assertSame($currentNumberBefore, $currentNumberAfter);
    }

    public function test_track_response_contains_no_other_customer_pii(): void
    {
        $historyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::yesterday('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);
        $base = Carbon::now('Asia/Makassar')->subDays(1)->setTime(9, 0);

        foreach ([10, 10, 10, 10, 10] as $index => $duration) {
            $this->seedCompletedHistory(
                $historyQueue,
                $base->copy()->addMinutes($index * 15),
                $duration,
                $index + 1,
                customerName: 'Histori Rahasia '.($index + 1),
                customerPhone: '08199999'.sprintf('%04d', $index + 1),
                customerEmail: 'rahasia'.$index.'@contoh.id'
            );
        }

        $this->seedWaitingTicket(1, 'Antrean Depan Rahasia');
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Terlacak',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertOk();

        $content = $response->content();
        $this->assertStringNotContainsString('Histori Rahasia', $content);
        $this->assertStringNotContainsString('08199999', $content);
        $this->assertStringNotContainsString('rahasia', $content);
        $this->assertStringNotContainsString('Antrean Depan Rahasia', $content);
        // Aggregated estimate itself is present.
        $this->assertSame(10, $response->json('data.estimated_wait_minutes'));
    }

    public function test_ticket_creation_response_returns_historical_estimate(): void
    {
        $this->seedDefaultHistory(); // median 10 minutes

        $this->seedWaitingTicket(1);
        $this->seedWaitingTicket(2);

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Pelanggan Ambil Baru',
            'customer_phone' => '081234567890',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'estimated_wait_minutes',
                'estimated_wait_reason',
            ],
        ]);
        // New ticket lands at position 3: 2 ahead x 10 / 1 counter = 20 minutes.
        $this->assertSame(3, $response->json('data.queue_position'));
        $this->assertSame(20, $response->json('data.estimated_wait_minutes'));
        $this->assertNull($response->json('data.estimated_wait_reason'));
    }

    public function test_tracking_page_shows_available_estimate_with_disclaimer(): void
    {
        $this->seedDefaultHistory(); // median 10 minutes

        $this->seedWaitingTicket(1);
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Pelanggan Tampilan',
            customerPhone: '081234567890'
        );

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Perkiraan waktu tunggu');
        $response->assertSee('± 10 menit');
        $response->assertSee('bukan janji waktu pelayanan');
        // The old static duration-based estimate line must not appear for the wait.
        $response->assertSee('Estimasi Durasi'); // service metadata row stays (nominal service duration)
    }
}
