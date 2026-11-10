<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks — ADR-13 Customer PII Anonymization
|--------------------------------------------------------------------------
|
| Terminal queue tickets past the QUEUE_PII_RETENTION_DAYS window (default:
| 30 days) have their customer PII anonymized once per day. The schedule
| requires an external scheduler runner in the deployment environment,
| e.g. a cron entry invoking `php artisan schedule:run` every minute or a
| long-running `php artisan schedule:work` process. withoutOverlapping()
| relies on the configured cache store for its mutex.
|
*/
Schedule::command('queues:anonymize-pii')->daily()->withoutOverlapping();
