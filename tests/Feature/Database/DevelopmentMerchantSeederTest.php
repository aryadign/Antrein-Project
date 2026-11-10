<?php

namespace Tests\Feature\Database;

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DevelopmentMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentMerchantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_demo_merchant_in_non_production_environment(): void
    {
        $this->seed(DevelopmentMerchantSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'owner@garasi.id',
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
        ]);

        $business = Business::where('slug', 'barbershop-garasi-demo')->firstOrFail();

        $this->assertSame('Barbershop Garasi (Demo Development)', $business->name);
        $this->assertSame(7, BusinessHour::where('business_id', $business->id)->count());
        $this->assertSame(2, Service::where('business_id', $business->id)->count());
    }

    public function test_seeder_is_repeatable_without_creating_duplicates(): void
    {
        $this->seed(DevelopmentMerchantSeeder::class);
        $this->seed(DevelopmentMerchantSeeder::class);

        $this->assertSame(1, User::where('email', 'owner@garasi.id')->count());
        $this->assertSame(1, Business::where('slug', 'barbershop-garasi-demo')->count());

        $business = Business::where('slug', 'barbershop-garasi-demo')->firstOrFail();
        $this->assertSame(7, BusinessHour::where('business_id', $business->id)->count());
        $this->assertSame(2, Service::where('business_id', $business->id)->count());
    }

    public function test_production_environment_skips_demo_merchant_seeding(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DevelopmentMerchantSeeder', '--force' => true])
            ->expectsOutputToContain('production')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'owner@garasi.id']);
        $this->assertDatabaseMissing('businesses', ['slug' => 'barbershop-garasi-demo']);
    }

    public function test_database_seeder_does_not_bypass_the_production_guard(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'owner@garasi.id']);
        $this->assertDatabaseMissing('businesses', ['slug' => 'barbershop-garasi-demo']);
    }
}
