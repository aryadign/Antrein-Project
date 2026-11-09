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
        $adminEmail = env('INITIAL_ADMIN_EMAIL', 'admin@antrein.id');
        $adminPassword = env('INITIAL_ADMIN_PASSWORD', 'AdminAntreIn#2026');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator AntreIn',
                'password' => Hash::make($adminPassword),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

