<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use RuntimeException;
use Throwable;

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
        try {
            $keys = self::generateVapidKeys();
        } catch (RuntimeException $exception) {
            $this->error('Failed to generate VAPID keys: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('VAPID Keys generated successfully:');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY="'.$keys['publicKey'].'"');
        $this->line('VAPID_PRIVATE_KEY="'.$keys['privateKey'].'"');
        $this->line('');
        $this->comment('Copy these keys to your .env file.');

        return self::SUCCESS;
    }

    /**
     * Generate a fresh VAPID key pair (RFC 8292, ECDSA P-256).
     *
     * Shared by the console command and the test suite so both exercise the
     * exact same generation path.
     *
     * @return array{publicKey: string, privateKey: string}
     *
     * @throws RuntimeException When no key pair can be created in this environment.
     */
    public static function generateVapidKeys(): array
    {
        $keys = null;

        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable) {
            // Fall through to the explicit OpenSSL config fallback below.
        }

        if (is_array($keys) && isset($keys['publicKey'], $keys['privateKey']) && $keys['publicKey'] !== '' && $keys['privateKey'] !== '') {
            return $keys;
        }

        // Security Review (Pre-M18 P0-03): key generation must never fall back
        // to hardcoded values — it either creates a fresh pair or fails loudly.
        foreach (self::opensslConfigCandidates() as $configPath) {
            $fallbackKeys = self::createVapidKeysUsingConfig($configPath);

            if ($fallbackKeys !== null) {
                return $fallbackKeys;
            }
        }

        throw new RuntimeException(
            'OpenSSL could not create a P-256 key pair. Make sure the openssl extension is enabled and a valid '
            .'openssl.cnf is available (on Windows, set the OPENSSL_CONF environment variable to the openssl.cnf '
            .'shipped with your PHP build before running this command).'
        );
    }

    /**
     * Candidate OpenSSL configuration files for environments where the
     * library cannot locate its default configuration (typically Windows:
     * OpenSSL resolves OPENSSL_CONF when the library initializes, so it cannot
     * be changed once the PHP process has started).
     *
     * @return list<string>
     */
    protected static function opensslConfigCandidates(): array
    {
        $candidates = [];

        $phpIni = (string) php_ini_loaded_file();
        if ($phpIni !== '') {
            $candidates[] = dirname($phpIni).'/extras/ssl/openssl.cnf';
        }

        $candidates[] = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';

        // Laragon PHP builds used by this project's development machines.
        $candidates[] = 'C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64\extras\ssl\openssl.cnf';
        $candidates[] = 'C:\laragon\bin\php\php-8.3.29-nts-Win32-vs16-x64\extras\ssl\openssl.cnf';

        return array_values(array_filter(
            array_unique($candidates),
            static fn (string $path): bool => is_file($path)
        ));
    }

    /**
     * Create a VAPID key pair while pointing OpenSSL explicitly at a
     * configuration file. Derived keys are validated through the web-push
     * library itself so a malformed pair can never be handed out.
     *
     * @return array{publicKey: string, privateKey: string}|null
     */
    protected static function createVapidKeysUsingConfig(string $configPath): ?array
    {
        if (! extension_loaded('openssl')) {
            return null;
        }

        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'config' => $configPath,
        ]);

        if ($key === false) {
            return null;
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            return null;
        }

        $x = self::ecValueToBinary($details['ec']['x'], 32);
        $y = self::ecValueToBinary($details['ec']['y'], 32);
        $d = self::ecValueToBinary($details['ec']['d'], 32);

        if ($x === null || $y === null || $d === null) {
            return null;
        }

        $publicKey = rtrim(strtr(base64_encode("\x04".$x.$y), '+/', '-_'), '=');
        $privateKey = rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

        try {
            VAPID::validate([
                'subject' => 'mailto:vapid@antrein.id',
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ]);
        } catch (Throwable) {
            return null;
        }

        return ['publicKey' => $publicKey, 'privateKey' => $privateKey];
    }

    /**
     * Normalize an EC coordinate from openssl_pkey_get_details() to exactly
     * $length binary bytes. PHP < 8.4 returns hexadecimal strings, while
     * PHP >= 8.4 returns raw binary. Distinguishing by character set is safe:
     * a raw binary P-256 coordinate consisting solely of hexadecimal digit
     * bytes is statistically impossible.
     */
    protected static function ecValueToBinary(mixed $value, int $length): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (ctype_xdigit($value) && strlen($value) !== $length) {
            if (strlen($value) > $length * 2 || strlen($value) % 2 !== 0) {
                return null;
            }

            $binary = hex2bin(str_pad($value, $length * 2, '0', STR_PAD_LEFT));

            return $binary === false ? null : $binary;
        }

        if (strlen($value) > $length) {
            return null;
        }

        return str_pad($value, $length, "\0", STR_PAD_LEFT);
    }
}
