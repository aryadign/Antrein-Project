<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentMerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Security Review (Pre-M18 P0-02): the demo merchant account must never
        // be created in production environments (mirrors AdminUserSeeder guard).
        if (app()->isProduction()) {
            $this->command?->warn('DevelopmentMerchantSeeder: environment production terdeteksi. Akun demo development dilewati demi keamanan.');

            return;
        }

        // 1. Create Demo Merchant User
        $merchantUser = User::firstOrCreate(
            ['email' => 'owner@garasi.id'],
            [
                'name' => 'Made Aryawan (Demo Owner)',
                'password' => Hash::make('merchant12345'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $category = BusinessCategory::where('slug', 'barbershop-pangkas-rambut')->first();

        // 2. Create Demo Business
        $business = Business::firstOrCreate(
            ['user_id' => $merchantUser->id],
            [
                'category_id' => $category?->id,
                'name' => 'Barbershop Garasi (Demo Development)',
                'slug' => 'barbershop-garasi-demo',
                'description' => 'Demo usaha potong rambut modern pria untuk pengujian fitur antrean digital.',
                'address' => 'Jl. Teuku Umar No. 88, Denpasar Barat, Bali',
                'phone' => '081234567890',
                'timezone' => 'Asia/Makassar',
                'status' => Business::STATUS_BUKA,
                'is_suspended' => false,
            ]
        );

        // 3. Ensure Owner Membership exists
        \App\Models\BusinessMembership::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $merchantUser->id,
            ],
            [
                'role' => \App\Models\BusinessMembership::ROLE_OWNER,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        // 4. Create Operating Hours (Mon-Sun, 10:00 - 21:00 WITA)
        for ($day = 0; $day <= 6; $day++) {
            BusinessHour::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'day_of_week' => $day,
                ],
                [
                    'open_time' => '10:00:00',
                    'close_time' => '21:00:00',
                    'is_closed' => false,
                ]
            );
        }

        // 4. Create Initial Services
        $services = [
            [
                'name' => 'Potong Rambut Dewasa',
                'description' => 'Cuci rambut, pangkas model pria, pijat kepala ringan, dan pomade.',
                'duration_minutes' => 30,
                'price' => 45000.00,
                'daily_quota' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Cukur Jenggot & Kumis',
                'description' => 'Shaving razor higienis dengan handuk hangat.',
                'duration_minutes' => 15,
                'price' => 25000.00,
                'daily_quota' => 20,
                'is_active' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $serviceData['name'],
                ],
                $serviceData
            );
        }

        // 5. Create Demo Operator User & Assign to Counter (M12 & M13 RBAC)
        $operatorUser = User::firstOrCreate(
            ['email' => 'operator@garasi.id'],
            [
                'name' => 'Wayan Sudirga (Demo Operator)',
                'password' => Hash::make('operator12345'),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $membership = \App\Models\BusinessMembership::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $operatorUser->id,
            ],
            [
                'role' => \App\Models\BusinessMembership::ROLE_OPERATOR,
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
    }
}

