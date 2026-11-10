<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:vapid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new VAPID keys for Web Push notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
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

        try {
            $keys = VAPID::createVapidKeys();

            $this->info('VAPID Keys generated successfully:');
            $this->line('');
            $this->line('VAPID_PUBLIC_KEY="'.$keys['publicKey'].'"');
            $this->line('VAPID_PRIVATE_KEY="'.$keys['privateKey'].'"');
            $this->line('');
            $this->comment('Copy these keys to your .env file.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to generate VAPID keys: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

