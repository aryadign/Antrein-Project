<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

class WebPushService
{
    protected ?WebPush $webPush = null;

    protected bool $missingVapidWarningLogged = false;

    public function __construct()
    {
        $this->ensureOpenSslConfig();
        $this->initializeWebPush();
    }

    /**
     * Configure OpenSSL on Windows if not set in environment.
     */
    protected function ensureOpenSslConfig(): void
    {
        if (PHP_OS_FAMILY === 'Windows' && empty(getenv('OPENSSL_CONF'))) {
            $candidates = [
                'C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64\extras\ssl\openssl.cnf',
                'C:\laragon\bin\php\php-8.3.29-nts-Win32-vs16-x64\extras\ssl\openssl.cnf',
                'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\extras\ssl\openssl.cnf',
            ];

            foreach ($candidates as $path) {
                if (file_exists($path)) {
                    putenv("OPENSSL_CONF={$path}");
                    break;
                }
            }
        }
    }

    /**
     * Initialize the WebPush instance with VAPID credentials.
     */
    protected function initializeWebPush(): void
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject', 'mailto:support@antrein.id');

        if (! empty($publicKey) && ! empty($privateKey)) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => $subject,
                        'publicKey' => $publicKey,
                        'privateKey' => $privateKey,
                    ],
                ];

                $options = config('webpush.client_options', []);
                $this->webPush = new WebPush($auth, $options);
            } catch (\Throwable $e) {
                Log::warning('Failed to initialize WebPush service: '.$e->getMessage());
                $this->webPush = null;
            }
        }
    }

    /**
     * Determine whether VAPID keys are configured for web push delivery.
     */
    public function isVapidConfigured(): bool
    {
        return ! empty(config('webpush.vapid.public_key')) && ! empty(config('webpush.vapid.private_key'));
    }

    /**
     * Send a web push notification to a specific subscription.
     *
     * @param  PushSubscription  $subscription
     * @param  array{title: string, body: string, icon?: string, data?: array<string, mixed>}  $payload
     * @return bool
     */
    public function send(PushSubscription $subscription, array $payload): bool
    {
        if (! $this->webPush) {
            if (app()->isProduction()) {
                // Security Review (Pre-M18 P0-03): fail fast when the push
                // feature is used in production without VAPID credentials.
                // The message never reveals any secret values.
                throw new RuntimeException(
                    'Web Push VAPID keys are not configured. Run "php artisan webpush:vapid" and set '
                    .'VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY in the environment before using push notifications.'
                );
            }

            if (! $this->missingVapidWarningLogged) {
                $this->missingVapidWarningLogged = true;
                Log::warning('Web push notification skipped: VAPID keys are not configured. Run "php artisan webpush:vapid" to generate them.');
            }

            return false;
        }

        try {
            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?? 'aes128gcm',
            ]);

            // Ensure payload is JSON encoded and sanitised
            $jsonPayload = json_encode([
                'title' => $payload['title'] ?? 'AntreIn Notification',
                'body' => $payload['body'] ?? '',
                'icon' => $payload['icon'] ?? '/images/icon-192.png',
                'badge' => '/images/badge-72.png',
                'data' => $payload['data'] ?? [],
            ]);

            /** @var MessageSentReport $report */
            $report = $this->webPush->sendOneNotification($sub, $jsonPayload);

            if ($report->isSuccess()) {
                return true;
            }

            // Clean up permanently dead or expired endpoints (404 Not Found, 410 Gone)
            if ($report->isSubscriptionExpired()) {
                $subscription->delete();
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('Web push delivery failed: '.$e->getMessage(), [
                'subscription_id' => $subscription->id,
            ]);

            return false;
        }
    }

    /**
     * Send web push notification to all active subscriptions of a merchant user.
     *
     * @param  User  $user
     * @param  array{title: string, body: string, icon?: string, data?: array<string, mixed>}  $payload
     * @return int Number of successfully delivered push notifications
     */
    public function sendToUser(User $user, array $payload): int
    {
        $delivered = 0;
        $subscriptions = $user->pushSubscriptions()->get();

        foreach ($subscriptions as $subscription) {
            if ($this->send($subscription, $payload)) {
                $delivered++;
            }
        }

        return $delivered;
    }

    /**
     * Send web push notification to all active subscriptions of a customer queue ticket.
     *
     * @param  QueueTicket  $ticket
     * @param  array{title: string, body: string, icon?: string, data?: array<string, mixed>}  $payload
     * @return int Number of successfully delivered push notifications
     */
    public function sendToTicket(QueueTicket $ticket, array $payload): int
    {
        $delivered = 0;
        $subscriptions = $ticket->pushSubscriptions()->get();

        foreach ($subscriptions as $subscription) {
            if ($this->send($subscription, $payload)) {
                $delivered++;
            }
        }

        return $delivered;
    }
}

