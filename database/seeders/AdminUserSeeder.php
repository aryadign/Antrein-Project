<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = (string) env('INITIAL_ADMIN_EMAIL', 'admin@antrein.id');
        $adminPassword = env('INITIAL_ADMIN_PASSWORD');

        // Security Review (M10-40): Prevent default insecure password in production environment
        if (empty($adminPassword)) {
            if (app()->isProduction()) {
                $this->command?->warn('INITIAL_ADMIN_PASSWORD belum diatur di .env. Seeding admin default dilewati demi keamanan.');

                return;
            }
            $adminPassword = 'AdminAntreIn#2026';
        }

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator AntreIn',
                'password' => Hash::make((string) $adminPassword),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

