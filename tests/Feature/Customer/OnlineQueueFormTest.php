<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnlineQueueFormTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik Dokter Gigi',
            'slug' => 'klinik-dokter-gigi',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Smile Dental Care',
            'slug' => 'smile-dental',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Cabut Gigi Dewasa',
            'duration_minutes' => 30,
            'price' => 200000,
            'daily_quota' => 10,
            'is_active' => true,
        ]);
    }

    public function test_intake_form_page_renders_successfully(): void
    {
        $response = $this->get(route('customer.queue.create', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Ambil Nomor Antrean');
        $response->assertSee('Smile Dental Care');
        $response->assertSee('Cabut Gigi Dewasa');
        $response->assertSee('Nama Lengkap / Panggilan');
        $response->assertSee('Nomor WhatsApp / HP');
    }

    public function test_form_preselects_service_from_query_parameter(): void
    {
        $response = $this->get(route('customer.queue.create', [
            'slug' => $this->business->slug,
            'service_id' => $this->service->id,
        ]));

        $response->assertOk();
        $response->assertSee('value="'.$this->service->id.'"', false);
    }

    public function test_successful_form_submission_redirects_to_tracking_page_prg(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Wayan Sudarma',
            'customer_phone' => '081234567890',
            'idempotency_key' => $idempotencyKey,
        ]);

        $ticket = QueueTicket::where('customer_name', 'Wayan Sudarma')->firstOrFail();

        $response->assertRedirect(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertSessionHas('status', 'Tiket antrean Anda berhasil dibuat!');

        $this->assertEquals('A-001', $ticket->ticket_number);
        $this->assertEquals(QueueTicket::SOURCE_ONLINE, $ticket->source);
        $this->assertEquals(QueueTicket::STATUS_WAITING, $ticket->status);
    }

    public function test_phone_number_is_strictly_optional(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Tanpa Nomor HP',
            'customer_phone' => null,
        ]);

        $ticket = QueueTicket::where('customer_name', 'Tanpa Nomor HP')->first();
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->customer_phone);
        $response->assertRedirect(route('customer.queue.track', ['token' => $ticket->access_token]));
    }

    public function test_submission_fails_when_customer_name_is_missing(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => '',
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertEquals(0, QueueTicket::count());
    }

    public function test_submission_fails_when_service_is_missing(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => '',
            'customer_name' => 'Budi Santoso',
        ]);

        $response->assertSessionHasErrors('service_id');
        $this->assertEquals(0, QueueTicket::count());
    }

    public function test_submission_blocked_when_business_is_jeda(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Budi Santoso',
        ]);

        $response->assertSessionHasErrors('queue_error');
        $this->assertEquals(0, QueueTicket::count());
    }

    public function test_submission_blocked_when_business_is_tutup(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Budi Santoso',
        ]);

        $response->assertSessionHasErrors('queue_error');
        $this->assertEquals(0, QueueTicket::count());
    }

    public function test_submission_fails_when_quota_exhausted(): void
    {
        $this->service->update(['daily_quota' => 1]);

        // Consume 1 ticket
        $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Patient 1',
        ]);

        // Attempt second ticket
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Patient 2',
        ]);

        $response->assertSessionHasErrors('queue_error');
        $this->assertEquals(1, QueueTicket::count());
    }

    public function test_idempotency_key_prevents_duplicate_tickets_on_retry(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'service_id' => $this->service->id,
            'customer_name' => 'Kadek Arimbawa',
            'customer_phone' => '081234567890',
            'idempotency_key' => $idempotencyKey,
        ];

        // 1st submit
        $res1 = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), $payload);
        $res1->assertRedirect();
        $this->assertEquals(1, QueueTicket::count());
        $ticket1 = QueueTicket::first();

        // 2nd submit (retry)
        $res2 = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), $payload);
        $res2->assertRedirect(route('customer.queue.track', ['token' => $ticket1->access_token]));
        $this->assertEquals(1, QueueTicket::count());
    }
}

