<?php

namespace Tests\Feature\Reviews;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\CustomerReview;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Service $service;
    protected DailyQueue $dailyQueue;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik & Kesehatan',
            'slug' => 'klinik-kesehatan',
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
            'name' => 'Klinik Pratama Sehat',
            'slug' => 'klinik-pratama-sehat',
            'phone' => '081234567890',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
            'timezone' => 'Asia/Makassar',
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Konsultasi Dokter Umum',
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

        $this->operator = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        BusinessMembership::create([
            'business_id' => $this->business->id,
            'user_id' => $this->operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    /**
     * Helper to create a ticket in a specific lifecycle state.
     */
    protected function createTicket(string $status = QueueTicket::STATUS_SELESAI): QueueTicket
    {
        return QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Ahmad Santoso',
            'customer_phone' => '081298765432',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => $status,
            'access_token' => Str::random(40),
            'qr_code_path' => 'qr/test.png',
            'completed_at' => $status === QueueTicket::STATUS_SELESAI ? now() : null,
        ]);
    }

    // =========================================================================
    // 1. Customer Review Submission & Intake
    // =========================================================================

    public function test_customer_can_submit_review_for_completed_ticket(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        $response = $this->post(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
            'comment' => 'Pelayanan sangat ramah dan ruang tunggu nyaman!',
        ]);

        $response->assertRedirect(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertSessionHas('status', 'Terima kasih atas ulasan dan masukan Anda!');

        $this->assertDatabaseHas('customer_reviews', [
            'business_id' => $this->business->id,
            'queue_ticket_id' => $ticket->id,
            'service_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Pelayanan sangat ramah dan ruang tunggu nyaman!',
        ]);

        // Audit log created
        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->business->id,
            'action' => 'customer_review_submitted',
        ]);
    }

    public function test_customer_can_submit_review_via_json_request(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 4,
            'comment' => 'Dokter sangat informatif.',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Terima kasih atas ulasan dan masukan Anda!',
            'review' => [
                'rating' => 4,
                'comment' => 'Dokter sangat informatif.',
            ],
        ]);
    }

    public function test_customer_track_page_displays_submitted_review(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        CustomerReview::create([
            'business_id' => $this->business->id,
            'queue_ticket_id' => $ticket->id,
            'service_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Sangat memuaskan.',
        ]);

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Penilaian Anda:');
        $response->assertSee('Sangat memuaskan.');
        $response->assertSee('Ulasan Anda telah tersimpan');
    }

    // =========================================================================
    // 2. Lifecycle State Enforcement
    // =========================================================================

    public function test_customer_cannot_review_ticket_with_status_menunggu(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_MENUNGGU);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Ulasan hanya dapat diberikan setelah pelayanan antrean selesai.',
        ]);
    }

    public function test_customer_cannot_review_ticket_with_status_dipanggil(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_DIPANGGIL);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_review_ticket_with_status_sedang_dilayani(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SEDANG_DILAYANI);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_review_ticket_with_status_dilewati(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_DILEWATI);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_review_ticket_with_status_dibatalkan(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_DIBATALKAN);

        $response = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 1,
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 3. Idempotency & Validation Enforcement
    // =========================================================================

    public function test_cannot_submit_duplicate_review_for_same_ticket(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        // First review
        $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
            'comment' => 'Pertama kali ulas',
        ])->assertStatus(201);

        // Second review attempt
        $res = $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 4,
            'comment' => 'Coba ulas lagi',
        ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Ulasan untuk tiket antrean ini sudah pernah dikirimkan.',
        ]);

        $this->assertSame(1, CustomerReview::where('queue_ticket_id', $ticket->id)->count());
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        // Rating 0
        $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Rating 6
        $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 6,
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Null rating
        $this->postJson(route('customer.review.store', ['token' => $ticket->access_token]), [])
            ->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    public function test_comment_is_sanitized_against_xss(): void
    {
        $ticket = $this->createTicket(QueueTicket::STATUS_SELESAI);

        $this->post(route('customer.review.store', ['token' => $ticket->access_token]), [
            'rating' => 5,
            'comment' => '<script>alert("XSS")</script><b>Pelayanan Bagus!</b>',
        ]);

        $review = CustomerReview::where('queue_ticket_id', $ticket->id)->first();
        $this->assertNotNull($review);
        $this->assertStringNotContainsString('<script>', $review->comment);
        $this->assertStringNotContainsString('<b>', $review->comment);
        $this->assertSame('alert("XSS")Pelayanan Bagus!', $review->comment);
    }

    public function test_submitting_with_invalid_token_returns_404(): void
    {
        $response = $this->postJson(route('customer.review.store', ['token' => 'invalid-token-12345']), [
            'rating' => 5,
        ]);

        $response->assertNotFound();
    }

    // =========================================================================
    // 4. Merchant Reviews Monitoring & Tenant Isolation
    // =========================================================================

    public function test_owner_can_view_reviews_page(): void
    {
        CustomerReview::create([
            'business_id' => $this->business->id,
            'queue_ticket_id' => $this->createTicket(QueueTicket::STATUS_SELESAI)->id,
            'service_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Luar biasa pelayanannya.',
        ]);

        $response = $this->actingAs($this->owner)->get(route('merchant.reviews.index'));

        $response->assertOk();
        $response->assertViewIs('merchant.reviews.index');
        $response->assertSee('Ulasan &amp; Masukan Pelanggan', false);
        $response->assertSee('Luar biasa pelayanannya.');
        $response->assertSee('5.0');
    }

    public function test_operator_can_view_reviews_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('merchant.reviews.index'));

        $response->assertOk();
        $response->assertViewIs('merchant.reviews.index');
    }

    public function test_guest_is_redirected_to_login_when_accessing_reviews(): void
    {
        $response = $this->get(route('merchant.reviews.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_merchant_can_only_see_their_own_reviews_strict_tenant_isolation(): void
    {
        // Business B
        $otherOwner = User::factory()->create(['role' => User::ROLE_MERCHANT, 'email_verified_at' => now()]);
        $otherBusiness = Business::create([
            'user_id' => $otherOwner->id,
            'category_id' => $this->business->category_id,
            'name' => 'Barbershop Bintang',
            'slug' => 'barbershop-bintang',
            'phone' => '089999999999',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
            'timezone' => 'Asia/Makassar',
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Cukur Rambut',
            'prefix' => 'B',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $otherQueue = DailyQueue::create([
            'business_id' => $otherBusiness->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 0,
            'total_tickets' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
        $otherTicket = QueueTicket::create([
            'business_id' => $otherBusiness->id,
            'daily_queue_id' => $otherQueue->id,
            'service_id' => $otherService->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi Gunawan',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(40),
            'qr_code_path' => 'qr/test2.png',
            'completed_at' => now(),
        ]);

        // Review for Business B
        CustomerReview::create([
            'business_id' => $otherBusiness->id,
            'queue_ticket_id' => $otherTicket->id,
            'service_id' => $otherService->id,
            'rating' => 2,
            'comment' => 'Komentar rahasia bisnis sebelah yang tidak boleh bocor',
        ]);

        // Review for Business A
        $ticketA = $this->createTicket(QueueTicket::STATUS_SELESAI);
        CustomerReview::create([
            'business_id' => $this->business->id,
            'queue_ticket_id' => $ticketA->id,
            'service_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Ulasan klinik sehat kami yang asli',
        ]);

        $response = $this->actingAs($this->owner)->get(route('merchant.reviews.index'));

        $response->assertOk();
        $response->assertSee('Ulasan klinik sehat kami yang asli');
        $response->assertDontSee('Komentar rahasia bisnis sebelah yang tidak boleh bocor');
    }

    public function test_reviews_can_be_filtered_by_rating(): void
    {
        $ticket1 = $this->createTicket(QueueTicket::STATUS_SELESAI);
        CustomerReview::create([
            'business_id' => $this->business->id,
            'queue_ticket_id' => $ticket1->id,
            'service_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Bintang lima mantap',
        ]);

        $ticket2 = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Siti Nurhaliza',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(40),
            'qr_code_path' => 'qr/test3.png',
            'completed_at' => now(),
        ]);
        CustomerReview::create([
            'business_id' => $this->business->id,
            'queue_ticket_id' => $ticket2->id,
            'service_id' => $this->service->id,
            'rating' => 2,
            'comment' => 'Bintang dua kurang puas',
        ]);

        // Filter for rating 5
        $res5 = $this->actingAs($this->owner)->get(route('merchant.reviews.index', ['rating' => 5]));
        $res5->assertOk();
        $res5->assertSee('Bintang lima mantap');
        $res5->assertDontSee('Bintang dua kurang puas');

        // Filter for rating 2
        $res2 = $this->actingAs($this->owner)->get(route('merchant.reviews.index', ['rating' => 2]));
        $res2->assertOk();
        $res2->assertSee('Bintang dua kurang puas');
        $res2->assertDontSee('Bintang lima mantap');
    }
}

