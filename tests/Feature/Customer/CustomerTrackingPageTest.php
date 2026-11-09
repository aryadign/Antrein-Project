<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
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
        $response->assertSee('A-001');
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
        $response->assertSee('A-001');
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
        $response->assertSee('Tautan Pelacakan Kedaluwarsa');
        $response->assertDontSee('Gusti Ngurah Jaya');
        $response->assertDontSee('081234567890');

        Carbon::setTestNow(); // reset
    }
}

