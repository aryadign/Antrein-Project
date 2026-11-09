<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnlineTicketCreationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Apotek & Klinik Sanur',
            'slug' => 'apotek-klinik-sanur',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Tensi & Gula Darah',
            'duration_minutes' => 15,
            'daily_quota' => 30,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_successfully_take_online_ticket(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Dewi Lestari',
            'customer_phone' => '081234567888',
            'idempotency_key' => $idempotencyKey,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'ticket_number',
                'sequence_number',
                'customer_name',
                'customer_phone_masked',
                'status',
                'queue_position',
                'estimated_wait_minutes',
                'tracking_token',
                'tracking_url',
            ],
        ]);

        $this->assertEquals('A-001', $response->json('data.ticket_number'));
        $this->assertEquals(1, $response->json('data.queue_position'));
        $this->assertEquals(0, $response->json('data.estimated_wait_minutes')); // first in line

        $this->assertDatabaseHas('queue_tickets', [
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'customer_name' => 'Dewi Lestari',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_WAITING,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function test_online_ticket_creation_fails_when_business_is_closed(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Wayan Sukadana',
            'customer_phone' => '081234567899',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('tutup', $response->json('message'));
    }

    public function test_online_ticket_creation_fails_when_business_is_paused(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Made Sudira',
            'customer_phone' => '081234567899',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('dijeda', $response->json('message'));
    }

    public function test_online_ticket_creation_fails_for_inactive_service(): void
    {
        $this->service->update(['is_active' => false]);

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Ketut Ardana',
            'customer_phone' => '081234567899',
        ]);

        $response->assertStatus(422);
    }

    public function test_online_ticket_creation_fails_if_service_belongs_to_another_business(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $this->business->category_id,
            'name' => 'Other Business',
            'slug' => 'other-business',
            'phone' => '081999888777',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Service',
            'duration_minutes' => 10,
            'daily_quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $otherService->id,
            'customer_name' => 'Gede Agus',
            'customer_phone' => '081234567899',
        ]);

        $response->assertStatus(404);
    }

    public function test_idempotent_request_with_same_payload_returns_existing_ticket_without_incrementing(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'service_id' => $this->service->id,
            'customer_name' => 'Kadek Arta',
            'customer_phone' => '081234567111',
            'idempotency_key' => $idempotencyKey,
        ];

        $response1 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), $payload);
        $response1->assertCreated();
        $ticketId1 = $response1->json('data.ticket_number');

        // Retry same request
        $response2 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), $payload);
        $response2->assertSuccessful();
        $ticketId2 = $response2->json('data.ticket_number');

        $this->assertEquals($ticketId1, $ticketId2);
        $this->assertEquals('A-001', $ticketId2);
        $this->assertEquals(1, QueueTicket::count());
    }

    public function test_idempotency_key_reused_with_conflicting_payload_fails(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $response1 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Original Customer',
            'customer_phone' => '081234567111',
            'idempotency_key' => $idempotencyKey,
        ]);
        $response1->assertCreated();

        // Second request with altered payload
        $response2 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Different Customer',
            'customer_phone' => '081234567222',
            'idempotency_key' => $idempotencyKey,
        ]);

        $response2->assertStatus(422);
    }

    public function test_session_cannot_take_duplicate_active_ticket_for_same_service(): void
    {
        $response1 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Nyoman Rai',
            'customer_phone' => '081234567333',
        ]);
        $response1->assertCreated();

        // Attempting another ticket for the same service within the same session
        $response2 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Nyoman Rai 2',
            'customer_phone' => '081234567333',
        ]);

        $response2->assertStatus(422);
        $response2->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('aktif untuk layanan ini', $response2->json('message'));
    }

    public function test_session_cannot_exceed_max_three_active_tickets(): void
    {
        $services = [];
        for ($i = 1; $i <= 4; $i++) {
            $services[] = Service::create([
                'business_id' => $this->business->id,
                'name' => "Service {$i}",
                'duration_minutes' => 10,
                'daily_quota' => 20,
                'is_active' => true,
            ]);
        }

        // Take 3 tickets for 3 different services in the same session
        for ($i = 0; $i < 3; $i++) {
            $res = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
                'service_id' => $services[$i]->id,
                'customer_name' => "Customer {$i}",
                'customer_phone' => '081234567444',
            ]);
            $res->assertCreated();
        }

        // Attempt 4th ticket
        $res4 = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $services[3]->id,
            'customer_name' => 'Customer 4',
            'customer_phone' => '081234567444',
        ]);

        $res4->assertStatus(422);
        $res4->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('maksimal 3 tiket', strtolower($res4->json('message')));
    }
}
