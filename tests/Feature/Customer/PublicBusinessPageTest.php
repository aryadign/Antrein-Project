<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBusinessPageTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private BusinessCategory $category;
    private Service $activeService;
    private Service $inactiveService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = BusinessCategory::create([
            'name' => 'Klinik Kecantikan',
            'slug' => 'klinik-kecantikan',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Aura Aesthetic Clinic',
            'slug' => 'aura-aesthetic',
            'description' => 'Klinik perawatan kulit terpercaya di Denpasar.',
            'address' => 'Jl. Gatot Subroto No. 45, Denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
        ]);

        // Weekly schedule
        for ($i = 0; $i <= 6; $i++) {
            BusinessHour::create([
                'business_id' => $this->business->id,
                'day_of_week' => $i,
                'open_time' => '09:00:00',
                'close_time' => '21:00:00',
                'is_closed' => false,
            ]);
        }

        $this->activeService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Facial Deep Cleansing',
            'description' => 'Pembersihan mendalam komedo dan jerawat.',
            'duration_minutes' => 45,
            'price' => 150000,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $this->inactiveService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Laser Treatment Promo',
            'description' => 'Promo laser sudah berakhir.',
            'duration_minutes' => 60,
            'price' => 500000,
            'daily_quota' => 5,
            'is_active' => false,
        ]);
    }

    public function test_public_business_page_loads_with_valid_slug(): void
    {
        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Aura Aesthetic Clinic');
        $response->assertSee('Klinik Kecantikan');
        $response->assertSee('Jl. Gatot Subroto No. 45, Denpasar');
        $response->assertSee('081234567890');
        $response->assertSee('Facial Deep Cleansing');
        $response->assertSee('Rp 150.000');
        $response->assertSee('±45 menit');
        $response->assertSee('Ambil Antrean');
    }

    public function test_public_business_page_returns_404_for_invalid_slug(): void
    {
        $response = $this->get(route('customer.queue', ['slug' => 'non-existent-umkm']));

        $response->assertNotFound();
    }

    public function test_inactive_services_are_not_displayed(): void
    {
        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Facial Deep Cleansing');
        $response->assertDontSee('Laser Treatment Promo');
    }

    public function test_services_of_other_tenants_are_not_leaked(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $this->category->id,
            'name' => 'Barbershop Sebelah',
            'slug' => 'barbershop-sebelah',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Potong Rambut Rahasia',
            'duration_minutes' => 30,
            'daily_quota' => 10,
            'is_active' => true,
        ]);

        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertDontSee('Potong Rambut Rahasia');
        $response->assertDontSee('Barbershop Sebelah');
    }

    public function test_business_status_jeda_disables_queue_taking_with_notice(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Pendaftaran Antrean Sedang Dijeda');
        $response->assertSee('Antrean Dijeda');
        $response->assertDontSee('Ambil Antrean');
    }

    public function test_business_status_tutup_shows_closed_banner_and_disables_intake(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('UMKM Sedang Tutup');
        $response->assertSee('Sedang Tutup');
        $response->assertDontSee('Ambil Antrean');
    }

    public function test_suspended_business_shows_suspended_alert(): void
    {
        $this->business->update(['is_suspended' => true]);

        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Layanan Antrean Sementara Tidak Tersedia');
        $response->assertDontSee('Ambil Antrean');
    }

    public function test_empty_state_shown_when_business_has_no_active_services(): void
    {
        $this->activeService->update(['is_active' => false]);

        $response = $this->get(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertSee('Belum Ada Layanan Tersedia');
    }

    public function test_public_business_page_json_contract(): void
    {
        $response = $this->getJson(route('customer.queue', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'business' => [
                    'id',
                    'name',
                    'slug',
                    'status',
                    'is_open',
                ],
                'services',
                'now_serving',
                'waiting_count',
            ],
        ]);
        $this->assertEquals('Aura Aesthetic Clinic', $response->json('data.business.name'));
    }
}

