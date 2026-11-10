<?php

namespace Tests\Feature\Notifications;

use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\DailyQueue;
use App\Models\PushSubscription;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\CustomerTicketStatusNotification;
use App\Notifications\MerchantNewOnlineTicketNotification;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class EmailAndBrowserNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Service $service;
    protected DailyQueue $dailyQueue;

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
            'name' => 'Klinik Sehat Sentosa',
            'slug' => 'klinik-sehat-sentosa',
            'phone' => '081234567890',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
            'timezone' => 'Asia/Makassar',
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Umum',
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
    }

    // =========================================================================
    // 1. Customer Email Intake & Consent
    // =========================================================================

    public function test_customer_can_register_online_with_optional_email_and_explicit_consent(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Budi Pratama',
            'customer_phone' => '081299887766',
            'notify_email' => '1',
            'customer_email' => 'budi.pratama@example.com',
        ]);

        $response->assertRedirect();

        $ticket = QueueTicket::where('customer_name', 'Budi Pratama')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('budi.pratama@example.com', $ticket->customer_email);
        $this->assertNotNull($ticket->customer_email_consented_at);
    }

    public function test_customer_registration_without_email_leaves_fields_null(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Siti Aminah',
            'customer_phone' => '081344556677',
        ]);

        $response->assertRedirect();

        $ticket = QueueTicket::where('customer_name', 'Siti Aminah')->first();
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->customer_email);
        $this->assertNull($ticket->customer_email_consented_at);
    }

    public function test_customer_registration_fails_if_notify_email_checked_but_email_is_invalid(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Joko Anwar',
            'customer_phone' => '081344556688',
            'notify_email' => '1',
            'customer_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('customer_email');
        $this->assertDatabaseMissing('queue_tickets', ['customer_name' => 'Joko Anwar']);
    }

    public function test_customer_registration_fails_if_notify_email_checked_but_email_is_blank(): void
    {
        $response = $this->post(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->service->id,
            'customer_name' => 'Joko Anwar',
            'customer_phone' => '081344556688',
            'notify_email' => '1',
            'customer_email' => '',
        ]);

        $response->assertSessionHasErrors('customer_email');
    }

    // =========================================================================
    // 2. Customer Email Notifications Dispatch
    // =========================================================================

    public function test_customer_receives_email_when_ticket_status_changes_to_dipanggil(): void
    {
        Notification::fake();

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Rian Hidayat',
            'customer_phone' => '081122334455',
            'customer_email' => 'rian@example.com',
            'customer_email_consented_at' => now(),
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL));

        Notification::assertSentOnDemand(
            CustomerTicketStatusNotification::class,
            function (CustomerTicketStatusNotification $notification, array $channels, mixed $notifiable) {
                return $notifiable->routes['mail'] === 'rian@example.com'
                    && $notification->status === QueueTicket::STATUS_DIPANGGIL;
            }
        );

        $ticket->refresh();
        $this->assertSame(QueueTicket::STATUS_DIPANGGIL, $ticket->last_notified_status);
    }

    public function test_customer_does_not_receive_email_if_consent_was_not_given(): void
    {
        Notification::fake();

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Dewi Lestari',
            'customer_phone' => '081122334466',
            'customer_email' => null,
            'customer_email_consented_at' => null,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU, QueueTicket::STATUS_DIPANGGIL));

        Notification::assertNothingSent();
    }

    public function test_idempotent_event_does_not_dispatch_duplicate_customer_email(): void
    {
        Notification::fake();

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 3,
            'customer_name' => 'Eko Prasetyo',
            'customer_phone' => '081122334477',
            'customer_email' => 'eko@example.com',
            'customer_email_consented_at' => now(),
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'last_notified_status' => QueueTicket::STATUS_DIPANGGIL, // Already notified
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        // Second firing of the same status
        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_DIPANGGIL));

        Notification::assertNothingSent();
    }

    // =========================================================================
    // 3. Merchant Notifications (Email & Preference Filtering)
    // =========================================================================

    public function test_merchant_owner_and_operator_receive_email_on_new_online_ticket(): void
    {
        Notification::fake();

        // Create an active operator
        $operatorUser = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        BusinessMembership::create([
            'business_id' => $this->business->id,
            'user_id' => $operatorUser->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-004',
            'sequence_number' => 4,
            'customer_name' => 'Fajar Nugraha',
            'customer_phone' => '081122334488',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketCreated($ticket));

        Notification::assertSentTo(
            $this->owner,
            MerchantNewOnlineTicketNotification::class
        );

        Notification::assertSentTo(
            $operatorUser,
            MerchantNewOnlineTicketNotification::class
        );
    }

    public function test_merchant_user_who_disabled_email_preference_does_not_receive_email(): void
    {
        Notification::fake();

        UserNotificationPreference::create([
            'user_id' => $this->owner->id,
            'email_on_new_online_ticket' => false,
            'browser_on_new_online_ticket' => true,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-005',
            'sequence_number' => 5,
            'customer_name' => 'Gita Gutawa',
            'customer_phone' => '081122334499',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketCreated($ticket));

        Notification::assertNotSentTo(
            $this->owner,
            MerchantNewOnlineTicketNotification::class
        );
    }

    public function test_walk_in_ticket_does_not_trigger_merchant_email_notification(): void
    {
        Notification::fake();

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-006',
            'sequence_number' => 6,
            'customer_name' => 'Hendra Setiawan',
            'customer_phone' => '081122334400',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_WALK_IN, // Walk-in
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketCreated($ticket));

        Notification::assertNothingSent();
    }

    public function test_other_tenant_merchant_does_not_receive_notification(): void
    {
        Notification::fake();

        $otherOwner = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-007',
            'sequence_number' => 7,
            'customer_name' => 'Indah Permata',
            'customer_phone' => '081122334411',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        event(new QueueTicketCreated($ticket));

        Notification::assertNotSentTo(
            $otherOwner,
            MerchantNewOnlineTicketNotification::class
        );
    }

    // =========================================================================
    // 4. Web Push Subscriptions (Customer)
    // =========================================================================

    public function test_customer_can_subscribe_to_browser_push(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-008',
            'sequence_number' => 8,
            'customer_name' => 'Kevin Sanjaya',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $response = $this->postJson(route('customer.push.subscribe'), [
            'token' => $ticket->access_token,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-customer-token',
            'public_key' => 'BLz98SamplePublicKey...',
            'auth_token' => 'SampleAuthToken123',
            'content_encoding' => 'aesgcm',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $endpointHash = hash('sha256', 'https://fcm.googleapis.com/fcm/send/sample-customer-token');
        $this->assertDatabaseHas('push_subscriptions', [
            'queue_ticket_id' => $ticket->id,
            'endpoint_hash' => $endpointHash,
        ]);
    }

    public function test_customer_push_subscription_fails_for_invalid_token(): void
    {
        $response = $this->postJson(route('customer.push.subscribe'), [
            'token' => 'non-existent-token',
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-customer-token-2',
            'public_key' => 'BLz98SamplePublicKey...',
            'auth_token' => 'SampleAuthToken123',
        ]);

        $response->assertStatus(404);
    }

    public function test_customer_can_unsubscribe_from_browser_push(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-009',
            'sequence_number' => 9,
            'customer_name' => 'Liliyana Natsir',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/unsub-token-sample';
        $endpointHash = hash('sha256', $endpoint);

        PushSubscription::create([
            'queue_ticket_id' => $ticket->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => $endpointHash,
            'public_key' => 'SampleKey',
            'auth_token' => 'SampleAuth',
        ]);

        $response = $this->deleteJson(route('customer.push.unsubscribe'), [
            'token' => $ticket->access_token,
            'endpoint' => $endpoint,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => $endpointHash,
        ]);
    }

    // =========================================================================
    // 5. Web Push Subscriptions & Preferences (Merchant)
    // =========================================================================

    public function test_authenticated_merchant_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->owner)
            ->put(route('merchant.settings.notifications.update'), [
                'email_on_new_online_ticket' => '0',
                'browser_on_new_online_ticket' => '1',
            ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'notifications']));

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $this->owner->id,
            'email_on_new_online_ticket' => false,
            'browser_on_new_online_ticket' => true,
        ]);
    }

    public function test_authenticated_merchant_can_subscribe_to_browser_push(): void
    {
        $endpoint = 'https://updates.push.services.mozilla.com/wpush/v2/merchant-token';

        $response = $this->actingAs($this->owner)
            ->postJson(route('merchant.settings.push.subscribe'), [
                'endpoint' => $endpoint,
                'public_key' => 'BEl62iFXgSample...',
                'auth_token' => 'AuthSecretSample',
                'content_encoding' => 'aesgcm',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $endpointHash = hash('sha256', $endpoint);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->owner->id,
            'endpoint_hash' => $endpointHash,
        ]);
    }

    public function test_authenticated_merchant_can_unsubscribe_from_browser_push(): void
    {
        $endpoint = 'https://updates.push.services.mozilla.com/wpush/v2/merchant-token-unsub';
        $endpointHash = hash('sha256', $endpoint);

        PushSubscription::create([
            'user_id' => $this->owner->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => $endpointHash,
            'public_key' => 'KeySample',
            'auth_token' => 'AuthSample',
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson(route('merchant.settings.push.unsubscribe'), [
                'endpoint' => $endpoint,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => $endpointHash,
        ]);
    }

    public function test_guest_cannot_access_merchant_push_subscription_endpoints(): void
    {
        $response = $this->postJson(route('merchant.settings.push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/unauth',
            'public_key' => 'Key',
            'auth_token' => 'Auth',
        ]);

        $response->assertStatus(401);
    }

    public function test_terminal_status_cleans_up_customer_push_subscriptions(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-010',
            'sequence_number' => 10,
            'customer_name' => 'Maya Sari',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/terminal-cleanup-test';
        $endpointHash = hash('sha256', $endpoint);

        PushSubscription::create([
            'queue_ticket_id' => $ticket->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => $endpointHash,
            'public_key' => 'SampleKey',
            'auth_token' => 'SampleAuth',
        ]);

        // Event for terminal status change
        event(new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_SEDANG_DILAYANI, QueueTicket::STATUS_SELESAI));

        // Subscriptions linked to this ticket should be pruned
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => $endpointHash,
        ]);
    }

    public function test_customer_email_isolated_from_tracking_api_response(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-011',
            'sequence_number' => 11,
            'customer_name' => 'Nadia Utami',
            'customer_email' => 'nadia.private@example.com',
            'customer_email_consented_at' => now(),
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => Str::random(40),
            'qr_code_hash' => Str::random(64),
        ]);

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertJsonMissing([
            'customer_email' => 'nadia.private@example.com',
        ]);
        $response->assertDontSee('nadia.private@example.com');
    }
}
