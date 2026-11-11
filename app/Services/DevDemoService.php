<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DevDemoService
{
    public const string ADMIN_EMAIL = 'admin@antrein.id';
    public const string OWNER_EMAIL = 'owner@garasi.id';
    public const string OPERATOR_EMAIL = 'operator@garasi.id';
    public const string DEMO_SLUG = 'barbershop-garasi-demo';

    /**
     * Determine whether the current environment allows developer demo features.
     */
    public function isDevAllowed(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    /**
     * Resolve or provision the requested demo persona user.
     *
     * @return User|null (Null for customer guest persona)
     */
    public function resolveOrCreatePersonaUser(string $persona): ?User
    {
        return match ($persona) {
            'admin' => $this->ensureAdminUser(),
            'owner' => $this->ensureOwnerUser(),
            'operator' => $this->ensureOperatorUser(),
            'customer' => null,
            default => null,
        };
    }

    /**
     * Ensure the dedicated demo admin user exists.
     */
    public function ensureAdminUser(): User
    {
        $adminEmail = (string) env('INITIAL_ADMIN_EMAIL', self::ADMIN_EMAIL);
        $adminPassword = env('INITIAL_ADMIN_PASSWORD') ?: 'AdminAntreIn#2026';

        return User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator AntreIn (Demo)',
                'password' => Hash::make((string) $adminPassword),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Ensure the dedicated demo business and owner exist.
     */
    public function ensureOwnerUser(): User
    {
        $category = BusinessCategory::firstOrCreate(
            ['slug' => 'barbershop-pangkas-rambut'],
            [
                'name' => 'Barbershop & Pangkas Rambut',
                'description' => 'Layanan pangkas rambut pria dan perawatan wajah',
                'is_active' => true,
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Made Aryawan (Demo Owner)',
                'password' => Hash::make('merchant12345'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $business = Business::firstOrCreate(
            ['user_id' => $owner->id],
            [
                'category_id' => $category->id,
                'name' => 'Barbershop Garasi (Demo Development)',
                'slug' => self::DEMO_SLUG,
                'description' => 'Demo usaha potong rambut modern pria untuk pengujian fitur antrean digital.',
                'address' => 'Jl. Teuku Umar No. 88, Denpasar Barat, Bali',
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'is_suspended' => false,
            ]
        );

        // Ensure owner membership exists
        BusinessMembership::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $owner->id,
            ],
            [
                'role' => BusinessMembership::ROLE_OWNER,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        // Ensure at least one service exists for onboarding completeness
        Service::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => 'Potong Rambut Dewasa',
            ],
            [
                'description' => 'Cuci rambut, pangkas model pria, pijat kepala ringan, dan pomade.',
                'duration_minutes' => 30,
                'price' => 45000.00,
                'daily_quota' => 30,
                'is_active' => true,
            ]
        );

        return $owner;
    }

    /**
     * Ensure the dedicated demo operator user exists and is assigned to counter.
     */
    public function ensureOperatorUser(): User
    {
        $this->ensureOwnerUser(); // Guarantees business exists

        $business = Business::where('slug', self::DEMO_SLUG)->firstOrFail();

        $operator = User::firstOrCreate(
            ['email' => self::OPERATOR_EMAIL],
            [
                'name' => 'Wayan Sudirga (Demo Operator)',
                'password' => Hash::make('operator12345'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $membership = BusinessMembership::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $operator->id,
            ],
            [
                'role' => BusinessMembership::ROLE_OPERATOR,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        $counter = $business->counters()->firstOrCreate(
            ['code' => 'L-01'],
            [
                'name' => 'Loket 1',
                'is_active' => true,
            ]
        );

        if ($counter && method_exists($counter, 'assignedMemberships')) {
            $counter->assignedMemberships()->syncWithoutDetaching([$membership->id]);
        }

        return $operator;
    }
}

