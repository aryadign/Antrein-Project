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
        'public_key' => env('VAPID_PUBLIC_KEY', 'BCd0k9zLqP7wN3mK8vT2xR5yJ4hG6fD1sA9bC8eF7gH6jK5lM4nO3pQ2rS1tU0vW9xY8z_A7bC6dE5fG4hI3jK2l'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'xY9zA8bC7dE6fG5hI4jK3lM2nO1pQ0rS9tU8vW7xY6z'),
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

