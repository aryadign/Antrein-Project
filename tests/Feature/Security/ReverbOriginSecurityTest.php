<?php

namespace Tests\Feature\Security;

use App\Providers\AppServiceProvider;
use RuntimeException;
use Tests\TestCase;

class ReverbOriginSecurityTest extends TestCase
{
    public function test_config_defines_origins_via_environment_without_hardcoded_domains(): void
    {
        $config = (string) file_get_contents(config_path('reverb.php'));

        // Origins must come from REVERB_ALLOWED_ORIGINS, never from literals.
        $this->assertStringContainsString("env('REVERB_ALLOWED_ORIGINS'", $config);
        $this->assertDoesNotMatchRegularExpression('#https?://[a-z0-9.-]+\.[a-z]{2,}#i', $config);
    }

    public function test_local_development_defaults_to_a_wildcard_origin(): void
    {
        if (env('REVERB_ALLOWED_ORIGINS') !== null) {
            $this->markTestSkipped('REVERB_ALLOWED_ORIGINS is set in this environment; cannot assert the default.');
        }

        $this->assertSame(['*'], config('reverb.apps.apps.0.allowed_origins'));
    }

    public function test_production_rejects_wildcard_reverb_origins(): void
    {
        $this->app['env'] = 'production';
        config(['reverb.apps.apps.0.allowed_origins' => ['*']]);

        try {
            AppServiceProvider::validateReverbAllowedOrigins();
            $this->fail('Expected RuntimeException for wildcard Reverb origins in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('REVERB_ALLOWED_ORIGINS', $exception->getMessage());
        }
    }

    public function test_production_accepts_explicit_origin_lists(): void
    {
        $this->app['env'] = 'production';
        config(['reverb.apps.apps.0.allowed_origins' => ['https://app.antrein.id', 'https://tv.antrein.id']]);

        AppServiceProvider::validateReverbAllowedOrigins();

        $this->addToAssertionCount(1);
    }

    public function test_non_production_allows_wildcard_origins(): void
    {
        config(['reverb.apps.apps.0.allowed_origins' => ['*']]);

        AppServiceProvider::validateReverbAllowedOrigins();

        $this->addToAssertionCount(1);
    }
}
