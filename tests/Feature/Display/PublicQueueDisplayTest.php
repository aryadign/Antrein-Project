<?php

namespace Tests\Feature\Display;

use App\Events\CounterStatusChanged;
use App\Events\PublicQueueDisplayUpdated;
use App\Events\QueueTicketCounterAssigned;
use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicQueueDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Service $service;
    protected DailyQueue $dailyQueue;
    protected BusinessCounter $counter1;
    protected BusinessCounter $counter2;
    protected string $displayToken;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik Dokter',
            'slug' => 'klinik-dokter',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'name' => 'Klinik Medika Pratama',
            'slug' => 'klinik-medika-pratama',
            'phone' => '081234567890',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
            'timezone' => 'Asia/Makassar',
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Poli Umum',
            'prefix' => 'A',
            'duration_minutes' => 15,
            'daily_quota' => 50,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 0,
            'total_tickets' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        // Default counter L-01 was provisioned via booted hook, create L-02
        $this->counter1 = BusinessCounter::where('business_id', $this->business->id)->first();
        if (! $this->counter1) {
            $this->counter1 = BusinessCounter::create([
                'business_id' => $this->business->id,
                'name' => 'Loket 1',
                'code' => 'L-01',
                'is_active' => true,
            ]);
        }

        $this->counter2 = BusinessCounter::create([
            'business_id' => $this->business->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        $this->displayToken = $this->business->generateDisplayToken();
    }

    // =========================================================================
    // 1. Access & Security Tests
    // =========================================================================

    public function test_guest_can_view_public_queue_display_with_valid_token(): void
    {
        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertOk();
        $response->assertViewIs('display.show');
        $response->assertSee('Klinik Medika Pratama');
        $response->assertSee('Loket 1');
        $response->assertSee('Loket 2');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
    }

    public function test_public_display_returns_404_for_invalid_token(): void
    {
        $response = $this->get(route('display.show', ['token' => 'invalid-token-sample-123456']));

        $response->assertNotFound();
    }

    public function test_public_display_returns_404_when_display_is_disabled(): void
    {
        $this->business->update(['is_display_enabled' => false]);

        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertNotFound();
    }

    public function test_public_display_returns_404_when_business_is_suspended(): void
    {
        $this->business->update(['is_suspended' => true]);

        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertNotFound();
    }

    public function test_public_display_returns_404_after_token_is_revoked(): void
    {
        $this->business->revokeDisplayAccess();

        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertNotFound();
    }

    public function test_public_display_returns_404_for_old_token_after_regeneration(): void
    {
        $oldToken = $this->displayToken;
        $this->business->generateDisplayToken(); // Generates new token

        $response = $this->get(route('display.show', ['token' => $oldToken]));

        $response->assertNotFound();
    }

    // =========================================================================
    // 2. Data Endpoint & PII Protection Tests
    // =========================================================================

    public function test_display_data_endpoint_returns_sanitized_json_without_pii(): void
    {
        // Create an active ticket at counter 1
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter1->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Secret Customer Name',
            'customer_phone' => '081299887766',
            'customer_email' => 'secret.customer@example.com',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'called_at' => now(),
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $response = $this->getJson(route('display.data', ['token' => $this->displayToken]));

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $response->assertJson([
            'success' => true,
            'data' => [
                'business' => [
                    'name' => 'Klinik Medika Pratama',
                    'timezone' => 'Asia/Makassar',
                ],
                'counters' => [
                    [
                        'name' => 'Loket 1',
                        'code' => 'L-01',
                        'has_active_ticket' => true,
                        'active_ticket' => [
                            'ticket_number' => 'A-001',
                            'status' => 'dipanggil',
                            'status_label' => 'Dipanggil',
                            'service_name' => 'Poli Umum',
                        ],
                    ],
                    [
                        'name' => 'Loket 2',
                        'code' => 'L-02',
                        'has_active_ticket' => false,
                        'active_ticket' => null,
                    ],
                ],
            ],
        ]);

        // CRITICAL PRIVACY CHECKS: Ensure ZERO PII or private tokens are leaked
        $content = $response->getContent();
        $this->assertStringNotContainsString('Secret Customer Name', $content);
        $this->assertStringNotContainsString('081299887766', $content);
        $this->assertStringNotContainsString('secret.customer@example.com', $content);
        $this->assertStringNotContainsString($ticket->access_token, $content);
        $this->assertStringNotContainsString($this->displayToken, $content);
    }

    public function test_display_data_endpoint_returns_404_for_invalid_token(): void
    {
        $response = $this->getJson(route('display.data', ['token' => 'invalid-token-xyz']));

        $response->assertNotFound();
        $response->assertJson(['success' => false]);
    }

    // =========================================================================
    // 3. Multi-Counter Lifecycle Transitions
    // =========================================================================

    public function test_multi_counter_display_reflects_independent_counter_ticket_states(): void
    {
        // Counter 1: Ticket A-001 is DIPANGGIL
        $ticket1 = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter1->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pasien Satu',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'called_at' => now(),
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        // Counter 2: Ticket A-002 is SEDANG_DILAYANI
        $ticket2 = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter2->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pasien Dua',
            'status' => QueueTicket::STATUS_SEDANG_DILAYANI,
            'called_at' => now()->subMinutes(5),
            'service_started_at' => now()->subMinutes(3),
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $response = $this->getJson(route('display.data', ['token' => $this->displayToken]));

        $response->assertOk();
        $data = $response->json('data.counters');

        $this->assertCount(2, $data);
        $this->assertSame('A-001', $data[0]['active_ticket']['ticket_number']);
        $this->assertSame('dipanggil', $data[0]['active_ticket']['status']);
        $this->assertSame('A-002', $data[1]['active_ticket']['ticket_number']);
        $this->assertSame('sedang_dilayani', $data[1]['active_ticket']['status']);

        // Now ticket 1 completes
        $ticket1->update([
            'status' => QueueTicket::STATUS_SELESAI,
            'completed_at' => now(),
        ]);

        $response2 = $this->getJson(route('display.data', ['token' => $this->displayToken]));
        $data2 = $response2->json('data.counters');

        // Counter 1 should now be empty (active_ticket = null), Counter 2 remains active
        $this->assertNull($data2[0]['active_ticket']);
        $this->assertSame('A-002', $data2[1]['active_ticket']['ticket_number']);
    }

    public function test_inactive_counter_is_excluded_from_display(): void
    {
        $this->counter2->update(['is_active' => false]);

        $response = $this->getJson(route('display.data', ['token' => $this->displayToken]));

        $response->assertOk();
        $counters = $response->json('data.counters');

        $this->assertCount(1, $counters);
        $this->assertSame('Loket 1', $counters[0]['name']);
    }

    // =========================================================================
    // 4. Real-time Broadcasting & Listener Tests
    // =========================================================================

    public function test_public_queue_display_updated_event_broadcasts_on_public_channel(): void
    {
        $event = new PublicQueueDisplayUpdated($this->business, 'ticket_called');

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertSame('display.' . $this->business->display_token_hash, $channels[0]->name);
        $this->assertSame('PublicQueueDisplayUpdated', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame('ticket_called', $payload['action']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertCount(2, $payload); // Only action and timestamp, zero PII
    }

    public function test_listener_dispatches_display_updated_when_ticket_status_changes(): void
    {
        Event::fake([PublicQueueDisplayUpdated::class]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 3,
            'customer_name' => 'Pasien Tiga',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL));

        Event::assertDispatched(PublicQueueDisplayUpdated::class, function (PublicQueueDisplayUpdated $e) {
            return $e->business->id === $this->business->id
                && $e->action === 'status_changed';
        });
    }

    public function test_listener_does_not_broadcast_if_display_is_disabled(): void
    {
        $this->business->update(['is_display_enabled' => false]);

        Event::fake([PublicQueueDisplayUpdated::class]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-004',
            'sequence_number' => 4,
            'customer_name' => 'Pasien Empat',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL));

        Event::assertNotDispatched(PublicQueueDisplayUpdated::class);
    }
}
