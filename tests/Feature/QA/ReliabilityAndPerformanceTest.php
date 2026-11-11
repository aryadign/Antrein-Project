<?php

namespace Tests\Feature\QA;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use DomainException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReliabilityAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;
    private QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Apotek Sehat Kuat',
            'slug' => 'apotek-sehat-kuat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Tebus Resep',
            'duration_minutes' => 10,
            'daily_quota' => 2, // Strict low quota for testing
            'is_active' => true,
        ]);

        $this->queueService = app(QueueService::class);
    }

    public function test_daily_quota_exhaustion_blocks_further_tickets(): void
    {
        // 1st Ticket: OK
        $ticket1 = $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pasien 1');
        $this->assertEquals(1, $ticket1->sequence_number);

        // 2nd Ticket: OK (hits limit of 2)
        $ticket2 = $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pasien 2');
        $this->assertEquals(2, $ticket2->sequence_number);

        // 3rd Ticket: Should throw DomainException for quota exhausted
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Kuota harian untuk layanan ini telah habis');

        $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pasien 3');
    }

    public function test_closed_business_rejects_ticket_generation(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pendaftaran antrean online tidak dapat dilakukan karena usaha sedang tutup.');

        $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pasien Baru');
    }

    public function test_inactive_service_rejects_ticket_generation(): void
    {
        $this->service->update(['is_active' => false]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Layanan yang dipilih saat ini sedang tidak aktif');

        $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pasien Baru');
    }

    public function test_idempotency_key_prevents_duplicate_ticket_generation(): void
    {
        $idempotencyKey = 'idem-unique-key-12345';

        $ticket1 = $this->queueService->generateOnlineTicket(
            $this->business,
            $this->service,
            'Pasien Idemp',
            '081234567890',
            $idempotencyKey
        );

        // Second request with exact same idempotency key and data
        $ticket2 = $this->queueService->generateOnlineTicket(
            $this->business,
            $this->service,
            'Pasien Idemp',
            '081234567890',
            $idempotencyKey
        );

        $this->assertEquals($ticket1->id, $ticket2->id);
        $this->assertEquals($ticket1->sequence_number, $ticket2->sequence_number);
        $this->assertEquals(1, QueueTicket::count());
    }

    public function test_database_transaction_rollbacks_on_unexpected_failure(): void
    {
        $dailyQueue = $this->queueService->getOrCreateDailyQueue($this->business);
        $initialNumber = $dailyQueue->current_number;

        try {
            DB::transaction(function () use ($dailyQueue) {
                $dailyQueue->current_number = 999;
                $dailyQueue->save();

                throw new Exception('Simulated unexpected system failure during transaction');
            });
        } catch (Exception $e) {
            // caught expected simulated exception
        }

        $this->assertEquals($initialNumber, $dailyQueue->fresh()->current_number);
    }
}
