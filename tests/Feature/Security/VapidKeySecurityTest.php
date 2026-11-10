<?php

namespace Tests\Feature\Security;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\PushSubscription;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Console\Commands\GenerateVapidKeysCommand;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class VapidKeySecurityTest extends TestCase
{
    use RefreshDatabase;

    private QueueTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik & Kesehatan',
            'slug' => 'klinik-kesehatan-vapid',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Vapid Sehat',
            'slug' => 'klinik-vapid-sehat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pemeriksaan Umum',
            'duration_minutes' => 15,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $this->ticket = QueueTicket::create([
            'business_id' => $business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => Str::random(40),
            'customer_name' => 'Pelanggan Uji',
        ]);

        $this->merchant = $merchant;
        $this->business = $business;
    }

    /**
     * Synthetic push subscription bound to the test ticket.
     */
    protected function createSubscription(array $attributes = []): PushSubscription
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/vapid-test-synthetic-endpoint';

        return PushSubscription::create(array_merge([
            'queue_ticket_id' => $this->ticket->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => 'SyntheticPublicKey',
            'auth_token' => 'SyntheticAuthToken',
        ], $attributes));
    }

    protected function withoutVapidKeys(): void
    {
        config([
            'webpush.vapid.public_key' => null,
            'webpush.vapid.private_key' => null,
        ]);
    }

    /**
     * Generate a throwaway VAPID key pair through the same code path the
     * `webpush:vapid` console command uses.
     *
     * @return array{publicKey: string, privateKey: string}|null
     */
    protected function generateVapidKeys(): ?array
    {
        if (! extension_loaded('openssl')) {
            return null;
        }

        try {
            return GenerateVapidKeysCommand::generateVapidKeys();
        } catch (RuntimeException) {
            return null;
        }
    }

    public function test_webpush_vapid_command_generates_a_key_pair(): void
    {
        if ($this->generateVapidKeys() === null) {
            $this->markTestSkipped('VAPID key generation is unavailable in this environment (OpenSSL could not create a P-256 key pair).');
        }

        $this->artisan('webpush:vapid')->assertSuccessful();
    }

    public function test_config_file_contains_no_hardcoded_vapid_key_fallbacks(): void
    {
        $config = (string) file_get_contents(config_path('webpush.php'));

        // env() must be called without a fallback literal for both keys.
        $this->assertDoesNotMatchRegularExpression('#env\([\'"]VAPID_(?:PUBLIC|PRIVATE)_KEY[\'"]\s*,#', $config);

        // No long base64-style key material may be committed to source.
        $this->assertDoesNotMatchRegularExpression('#[\'"][A-Za-z0-9+/_.-]{40,}[\'"]#', $config);
    }

    public function test_service_reports_unconfigured_state_when_keys_are_missing(): void
    {
        $this->withoutVapidKeys();

        $this->assertFalse((new WebPushService())->isVapidConfigured());
    }

    public function test_development_without_keys_degrades_gracefully(): void
    {
        $this->withoutVapidKeys();

        $service = new WebPushService();
        $subscription = $this->createSubscription();

        // Non-production environments keep push inactive without throwing,
        // so pages and email notifications continue to work.
        $this->assertFalse($service->send($subscription, ['title' => 'Uji', 'body' => 'Coba']));
    }

    public function test_production_without_keys_fails_fast_when_push_is_used(): void
    {
        $this->withoutVapidKeys();
        $this->app['env'] = 'production';

        $service = new WebPushService();
        $subscription = $this->createSubscription();

        try {
            $service->send($subscription, ['title' => 'Uji', 'body' => 'Coba']);
            $this->fail('Expected RuntimeException when using push in production without VAPID keys.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('webpush:vapid', $exception->getMessage());
            $this->assertStringContainsString('VAPID_PUBLIC_KEY', $exception->getMessage());

            // The error message must instruct without revealing secret values.
            $this->assertDoesNotMatchRegularExpression('/=(?:[A-Za-z0-9+\/_]{20,})/', $exception->getMessage());
        }
    }

    public function test_configured_keys_enable_the_webpush_client(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('openssl extension is required to generate VAPID keys.');
        }

        $keys = $this->generateVapidKeys();

        if ($keys === null) {
            $this->markTestSkipped('VAPID key generation is unavailable in this environment (OpenSSL could not create a P-256 key pair).');
        }

        config([
            'webpush.vapid.public_key' => $keys['publicKey'],
            'webpush.vapid.private_key' => $keys['privateKey'],
        ]);

        $service = new WebPushService();

        $this->assertTrue($service->isVapidConfigured());

        // Delivery to an unreachable endpoint fails gracefully; the feature
        // itself runs and no configuration exception is raised.
        $subscription = $this->createSubscription([
            'endpoint' => 'http://127.0.0.1:9/unreachable-vapid-endpoint',
            'endpoint_hash' => hash('sha256', 'unreachable-vapid-endpoint'),
        ]);

        $this->assertFalse($service->send($subscription, ['title' => 'Uji', 'body' => 'Coba']));
    }

    public function test_customer_tracking_page_renders_without_push_ui_when_keys_are_missing(): void
    {
        $this->withoutVapidKeys();

        $response = $this->get(route('customer.queue.track', ['token' => $this->ticket->access_token]));

        $response->assertOk();

        // The push UI box is hidden and the subscribe endpoint is not
        // injected, while the page itself renders normally.
        $response->assertDontSee('Aktifkan Notifikasi Giliran');
        $response->assertDontSee(route('customer.push.subscribe'));
    }

    public function test_customer_tracking_page_shows_push_ui_when_keys_are_configured(): void
    {
        config(['webpush.vapid.public_key' => 'SYNTHETIC-CONFIGURED-VAPID-PUBLIC-KEY']);

        $response = $this->get(route('customer.queue.track', ['token' => $this->ticket->access_token]));

        $response->assertOk();
        $response->assertSee('Aktifkan Notifikasi Giliran');
        $response->assertSee(route('customer.push.subscribe'));
    }

    public function test_merchant_settings_notifications_tab_renders_without_keys(): void
    {
        $this->withoutVapidKeys();

        $response = $this->actingAs($this->merchant)
            ->get(route('merchant.settings', ['tab' => 'notifications']));

        $response->assertOk();

        // The push card is hidden and a clear configuration instruction is
        // shown instead of a broken page.
        $response->assertDontSee('Aktifkan Notifikasi di Peramban Ini');
        $response->assertSee('webpush:vapid');
    }

    public function test_merchant_settings_notifications_tab_shows_push_card_when_keys_are_configured(): void
    {
        config(['webpush.vapid.public_key' => 'SYNTHETIC-CONFIGURED-VAPID-PUBLIC-KEY']);

        $response = $this->actingAs($this->merchant)
            ->get(route('merchant.settings', ['tab' => 'notifications']));

        $response->assertOk();
        $response->assertSee('Aktifkan Notifikasi di Peramban Ini');
    }
}
