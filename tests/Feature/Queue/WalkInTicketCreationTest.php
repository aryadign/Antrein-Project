<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInTicketCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Gentleman Cuts Canggu',
            'slug' => 'gentleman-cuts-canggu',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Signature Haircut',
            'duration_minutes' => 30,
            'daily_quota' => 25,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_create_walk_in_ticket(): void
    {
        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Walk-in Guest',
            'notes' => 'Permintaan potong undercut',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'ticket_id',
                'ticket_number',
                'customer_name',
                'status',
                'access_token',
            ],
        ]);

        $this->assertEquals('A-001', $response->json('data.ticket_number'));
        $this->assertNotEmpty($response->json('data.access_token'));

        $this->assertDatabaseHas('queue_tickets', [
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'customer_name' => 'Walk-in Guest',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_WAITING,
            'notes' => 'Permintaan potong undercut',
        ]);
    }

    public function test_walk_in_ticket_creation_allows_optional_phone(): void
    {
        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Guest With Phone',
            'customer_phone' => '081333444555',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('queue_tickets', [
            'customer_name' => 'Guest With Phone',
            'customer_phone' => '081333444555',
        ]);
    }

    public function test_walk_in_creation_when_paused_requires_explicit_confirmation(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        // Attempting without confirmation
        $response1 = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Guest During Pause',
        ]);

        $response1->assertStatus(422);
        $response1->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Jeda Sementara', $response1->json('message'));

        // Attempting with confirmed_paused = true
        $response2 = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Guest During Pause',
            'confirmed_paused' => true,
        ]);

        $response2->assertCreated();
        $this->assertEquals('A-001', $response2->json('data.ticket_number'));
    }

    public function test_walk_in_creation_fails_when_business_is_closed(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Guest After Closing',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('tutup', $response->json('message'));
    }

    public function test_merchant_cannot_create_walk_in_for_another_merchants_service(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $this->business->category_id,
            'name' => 'Other Merchant Salon',
            'slug' => 'other-merchant-salon',
            'phone' => '081234567999',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Merchant Service',
            'duration_minutes' => 20,
            'daily_quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $otherService->id,
            'customer_name' => 'Intruder Guest',
        ]);

        $response->assertStatus(404);
    }
}
