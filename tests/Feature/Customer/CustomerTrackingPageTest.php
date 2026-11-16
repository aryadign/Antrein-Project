<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerTrackingPageTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;

    private Business $business;

    private Service $service;

    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik Mata',
            'slug' => 'klinik-mata',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Bali Eye Center',
            'slug' => 'bali-eye-center',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Periksa Mata Lengkap',
            'duration_minutes' => 20,
            'price' => 175000,
            'daily_quota' => 30,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_access_tracking_page_with_valid_token(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('TKT-001');
        $response->assertSee('Bali Eye Center');
        $response->assertSee('Periksa Mata Lengkap');
        $response->assertSee('Gusti Ngurah Jaya');
        $response->assertSee('081***890');
        $response->assertDontSee('081234567890'); // Unmasked phone must not be rendered
        $response->assertSee('Menunggu');
    }

    public function test_customer_can_access_tracking_page_via_ticket_alias_route(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $response = $this->get(route('customer.ticket.show', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('TKT-001');
        $response->assertSee('Bali Eye Center');
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get(route('customer.queue.track', ['token' => Str::random(40)]));

        $response->assertNotFound();
    }

    public function test_tracking_page_displays_dipanggil_status_and_callout(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Sedang Dipanggil');
        $response->assertSee('NOMOR ANDA SEDANG DIPANGGIL!');
    }

    public function test_tracking_page_displays_sedang_dilayani_status(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Sedang Dilayani');
        $response->assertSee('Pelayanan Sedang Berlangsung');
    }

    public function test_tracking_page_displays_selesai_status(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Selesai');
        $response->assertSee('Pelayanan Selesai');
    }

    public function test_tracking_page_displays_dilewati_status(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->skipTicket($ticket, $this->merchant);

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Dilewati');
        $response->assertSee('Antrean Dilewati');
    }

    public function test_tracking_page_displays_dibatalkan_status_with_reason(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->cancelTicket($ticket, $this->merchant, 'Pasien berhalangan hadir karena macet');

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Dibatalkan');
        $response->assertSee('Pasien berhalangan hadir karena macet');
    }

    public function test_terminal_ticket_past_24_hours_returns_http_410_expired_without_pii(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);

        // Advance 25 hours
        Carbon::setTestNow(now()->addHours(25));

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertStatus(410);
        $response->assertSee('Tiket Pelacakan Kedaluwarsa');
        $response->assertDontSee('Gusti Ngurah Jaya');
        $response->assertDontSee('081234567890');

        Carbon::setTestNow(); // reset
    }

    public function test_waiting_tracking_page_shows_cancel_button_and_qr_without_copy_link(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890',
            customer: $customer
        );

        $this->actingAs($customer)->get(route('customer.queue.track', ['token' => $ticket->access_token]))
            ->assertOk()
            ->assertSee('Batalkan Antrean')
            ->assertSee('QR Code Tiket')
            ->assertSee('Unduh QR Tiket')
            ->assertSee('<svg', false)
            ->assertDontSee('Salin Link Tiket')
            ->assertDontSee('Link tiket Anda');
    }

    public function test_customer_can_cancel_waiting_ticket_with_custom_reason(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customerPhone: '081234567890',
            customer: $customer
        );

        $response = $this->actingAs($customer)->post(route('customer.queue.cancel', ['token' => $ticket->access_token]), [
            'cancellation_reason' => 'Waktu tunggu terlalu lama',
        ]);

        $response->assertRedirect(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertSessionHas('status', 'Antrean '.$ticket->ticket_number.' berhasil dibatalkan.');

        $ticket->refresh();
        $this->assertSame(QueueTicket::STATUS_DIBATALKAN, $ticket->status);
        $this->assertSame('Waktu tunggu terlalu lama', $ticket->cancellation_reason);

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'from_status' => QueueTicket::STATUS_MENUNGGU,
            'to_status' => QueueTicket::STATUS_DIBATALKAN,
            'actor_type' => QueueStatusHistory::ACTOR_CUSTOMER,
            'actor_id' => null,
        ]);

        $this->actingAs($customer)->get(route('customer.queue.track', ['token' => $ticket->access_token]))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Waktu tunggu terlalu lama')
            ->assertDontSee('Batalkan Antrean');
    }

    public function test_customer_cancel_uses_default_reason_when_blank(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customer: $customer
        );

        $this->actingAs($customer)->post(route('customer.queue.cancel', ['token' => $ticket->access_token]), [
            'cancellation_reason' => '',
        ])->assertRedirect(route('customer.queue.track', ['token' => $ticket->access_token]));

        $this->assertSame(QueueTicket::STATUS_DIBATALKAN, $ticket->fresh()->status);
        $this->assertSame('Dibatalkan oleh pelanggan', $ticket->fresh()->cancellation_reason);
    }

    public function test_customer_cannot_cancel_ticket_after_being_called_or_served(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customer: $customer
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();

        $this->actingAs($customer)->postJson(route('customer.queue.cancel', ['token' => $ticket->access_token]), [
            'cancellation_reason' => 'Berubah pikiran',
        ])->assertStatus(422)->assertJsonStructure(['message']);

        $this->assertSame(QueueTicket::STATUS_DIPANGGIL, $ticket->fresh()->status);

        $this->queueService->startService($ticket, $this->merchant);

        $this->actingAs($customer)->post(route('customer.queue.cancel', ['token' => $ticket->access_token]))
            ->assertSessionHasErrors('queue_error');

        $this->assertSame(QueueTicket::STATUS_SEDANG_DILAYANI, $ticket->fresh()->status);
    }

    public function test_customer_cancel_returns_410_for_expired_terminal_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Gusti Ngurah Jaya',
            customer: $customer
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);

        Carbon::setTestNow(now()->addHours(25));

        $this->actingAs($customer)->post(route('customer.queue.cancel', ['token' => $ticket->access_token]))
            ->assertStatus(410);

        Carbon::setTestNow();
    }
}
