<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Barbershop & Pangkas Rambut',
                'slug' => 'barbershop-pangkas-rambut',
                'description' => 'Layanan cukur, potong rambut pria, styling, dan grooming.',
                'is_active' => true,
            ],
            [
                'name' => 'Salon & Perawatan Kecantikan',
                'slug' => 'salon-perawatan-kecantikan',
                'description' => 'Layanan perawatan rambut, creambath, facial, manicure, dan rias wajah.',
                'is_active' => true,
            ],
            [
                'name' => 'Klinik & Praktik Dokter',
                'slug' => 'klinik-praktik-dokter',
                'description' => 'Praktik dokter umum, dokter gigi, bidan mandiri, dan fisioterapi.',
                'is_active' => true,
            ],
            [
                'name' => 'Kuliner & Kafe',
                'slug' => 'kuliner-kafe',
                'description' => 'Antrean meja restoran, kafe kekinian, dan rumah makan dengan kapasitas terbatas.',
                'is_active' => true,
            ],
            [
                'name' => 'Jasa & Servis Elektronik',
                'slug' => 'jasa-servis-elektronik',
                'description' => 'Perbaikan gadget, laptop, kendaraan bermotor, dan perbengkelan.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            BusinessCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

