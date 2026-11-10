<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Configuration for Web Push
    |--------------------------------------------------------------------------
    |
    | VAPID (Voluntary Application Server Identification) keys are used to
    | identify your application server to web push notification services
    | (such as Google FCM, Mozilla autopush, Apple Web Push).
    |
    */

    'vapid' => [
        // Security Review (Pre-M18 P0-03): VAPID keys are read from the
        // environment only. Never add fallback values here — generate a key
        // pair with `php artisan webpush:vapid` and set it in `.env`.
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:support@antrein.id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guzzle Client Options
    |--------------------------------------------------------------------------
    */

    'client_options' => [
        'timeout' => 15,
        'connect_timeout' => 5,
    ],

];

