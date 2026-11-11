<?php

namespace Tests\Feature\Privacy;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnonymizeQueuePiiCommandTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Service $service;

    private DailyQueue $dailyQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Bengkel & Servis',
            'slug' => 'bengkel-servis',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Garasi Sejati',
            'slug' => 'bengkel-garasi-sejati',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Servis Ringan',
            'duration_minutes' => 30,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
    }

    /**
     * Create a synthetic terminal ticket carrying customer PII.
     */
    protected function createTicket(array $attributes = []): QueueTicket
    {
        return QueueTicket::create(array_merge([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(40),
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'notes' => 'Pelanggan membawa motor plat D 1234 AB, minta dicek rantai.',
            'created_at' => now()->subDays(31),
            'completed_at' => now()->subDays(30),
        ], $attributes));
    }

    public function test_anonymizes_terminal_tickets_past_retention_window(): void
    {
        $ticket = $this->createTicket();

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $ticket->refresh();

        $this->assertSame('Pelanggan #A-001', $ticket->customer_name);
        $this->assertSame('0812****0000', $ticket->customer_phone);
        $this->assertNull($ticket->notes);
    }

    public function test_anonymization_preserves_operational_and_analytics_fields(): void
    {
        $ticket = $this->createTicket([
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
        ]);

        $history = QueueStatusHistory::create([
            'queue_ticket_id' => $ticket->id,
            'event_type' => 'status_changed',
            'from_status' => QueueTicket::STATUS_DIPANGGIL,
            'to_status' => QueueTicket::STATUS_SELESAI,
            'actor_type' => 'merchant',
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $ticket->refresh();

        $this->assertSame('A-002', $ticket->ticket_number);
        $this->assertSame(2, $ticket->sequence_number);
        $this->assertSame(QueueTicket::STATUS_SELESAI, $ticket->status);
        $this->assertSame($this->business->id, $ticket->business_id);
        $this->assertSame($this->service->id, $ticket->service_id);
        $this->assertSame($this->dailyQueue->id, $ticket->daily_queue_id);
        $this->assertNotNull($ticket->created_at);
        $this->assertNotNull($ticket->completed_at);

        // Historical status log and relations remain intact and queryable.
        $this->assertSame(1, $ticket->statusHistories()->count());
        $this->assertTrue($history->refresh()->queueTicket->is($ticket));
        $this->assertTrue($ticket->business->is($this->business));
        $this->assertTrue($ticket->service->is($this->service));
        $this->assertTrue($ticket->dailyQueue->is($this->dailyQueue));
    }

    public function test_anonymization_never_deletes_queue_records(): void
    {
        $this->createTicket(['ticket_number' => 'A-001', 'sequence_number' => 1]);
        $this->createTicket(['ticket_number' => 'A-002', 'sequence_number' => 2]);

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $this->assertSame(2, QueueTicket::count());
        $this->assertSame(2, QueueTicket::terminal()->count());
    }

    public function test_terminal_tickets_within_retention_window_are_untouched(): void
    {
        $ticket = $this->createTicket([
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'created_at' => now()->subDays(10),
            'completed_at' => now()->subDays(9),
        ]);

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $ticket->refresh();

        $this->assertSame('Budi Santoso', $ticket->customer_name);
        $this->assertSame('081234567890', $ticket->customer_phone);
        $this->assertSame('Pelanggan membawa motor plat D 1234 AB, minta dicek rantai.', $ticket->notes);
    }

    public function test_active_tickets_are_never_anonymized(): void
    {
        foreach ([
            QueueTicket::STATUS_MENUNGGU,
            QueueTicket::STATUS_DIPANGGIL,
            QueueTicket::STATUS_SEDANG_DILAYANI,
        ] as $index => $status) {
            $this->createTicket([
                'ticket_number' => sprintf('A-%03d', $index + 1),
                'sequence_number' => $index + 1,
                'status' => $status,
                'completed_at' => null,
            ]);
        }

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        foreach (QueueTicket::all() as $ticket) {
            $this->assertSame('Budi Santoso', $ticket->customer_name);
            $this->assertSame('081234567890', $ticket->customer_phone);
            $this->assertSame('Pelanggan membawa motor plat D 1234 AB, minta dicek rantai.', $ticket->notes);
        }
    }

    public function test_command_is_idempotent_when_run_repeatedly(): void
    {
        $ticket = $this->createTicket();

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $firstRun = $ticket->refresh()->only(['customer_name', 'customer_phone', 'notes', 'ticket_number', 'status']);

        // Second run must not corrupt data or duplicate work.
        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $secondRun = $ticket->refresh()->only(['customer_name', 'customer_phone', 'notes', 'ticket_number', 'status']);

        $this->assertSame($firstRun, $secondRun);
        $this->assertSame(1, QueueTicket::count());
    }

    public function test_dry_run_does_not_modify_the_database(): void
    {
        $ticket = $this->createTicket();

        $this->artisan('queues:anonymize-pii', ['--dry-run' => true])->assertSuccessful();

        $ticket->refresh();

        $this->assertSame('Budi Santoso', $ticket->customer_name);
        $this->assertSame('081234567890', $ticket->customer_phone);
        $this->assertSame('Pelanggan membawa motor plat D 1234 AB, minta dicek rantai.', $ticket->notes);
    }

    public function test_retention_window_is_configurable_via_environment_config(): void
    {
        config(['app.queue_pii_retention_days' => 5]);

        $expired = $this->createTicket([
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'created_at' => now()->subDays(6),
            'completed_at' => now()->subDays(5),
        ]);

        $recent = $this->createTicket([
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'created_at' => now()->subDays(4),
            'completed_at' => now()->subDays(3),
        ]);

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $this->assertSame('Pelanggan #A-001', $expired->refresh()->customer_name);
        $this->assertSame('Budi Santoso', $recent->refresh()->customer_name);
    }

    public function test_short_or_missing_phone_values_do_not_leak_digits(): void
    {
        $shortPhone = $this->createTicket([
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_phone' => '1234',
        ]);

        $noPhone = $this->createTicket([
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_phone' => null,
        ]);

        $this->artisan('queues:anonymize-pii')->assertSuccessful();

        $this->assertSame('****', $shortPhone->refresh()->customer_phone);
        $this->assertNull($noPhone->refresh()->customer_phone);
    }

    public function test_invalid_retention_configuration_fails_without_modifying_data(): void
    {
        config(['app.queue_pii_retention_days' => 0]);

        $ticket = $this->createTicket();

        $this->artisan('queues:anonymize-pii')->assertFailed();

        $this->assertSame('Budi Santoso', $ticket->refresh()->customer_name);
    }
}
