<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\CustomerReview;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private Business $cafe;

    private Business $barbershop;

    protected function setUp(): void
    {
        parent::setUp();

        $cafeCategory = BusinessCategory::create(['name' => 'Kuliner & Kafe', 'slug' => 'kuliner-kafe', 'is_active' => true]);
        $barberCategory = BusinessCategory::create(['name' => 'Barbershop & Pangkas Rambut', 'slug' => 'barbershop-pangkas-rambut', 'is_active' => true]);
        BusinessCategory::create(['name' => 'Klinik & Praktik Dokter', 'slug' => 'klinik-praktik-dokter', 'is_active' => true]);

        $this->cafe = $this->createBusiness($cafeCategory, 'Kedai Kopi Senja', 'kedai-kopi-senja');
        $this->barbershop = $this->createBusiness($barberCategory, 'Barbershop Garasi', 'barbershop-garasi');
    }

    public function test_portal_pages_render_and_require_auth_for_personal_tabs(): void
    {
        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Cara pakai AntreIn')
            ->assertSee('Mau antre di mana?')
            ->assertSee('Kedai Kopi Senja')
            ->assertSee('Barbershop Garasi');

        $this->get(route('customer.my-tickets'))->assertRedirect(route('login'));
        $this->get(route('customer.history'))->assertRedirect(route('login'));
        $this->get(route('customer.profile'))->assertRedirect(route('login'));

        $customer = User::factory()->customer()->create(['name' => 'Raka Pratama']);
        $this->actingAs($customer)->get(route('customer.my-tickets'))->assertOk()->assertSee('Belum ada antrean aktif');
        $this->actingAs($customer)->get(route('customer.history'))->assertOk()->assertSee('Belum ada riwayat antrean');
        $this->actingAs($customer)->get(route('customer.profile'))->assertOk()->assertSee('Simpan Profil Saya');
    }

    public function test_search_lists_stores_with_all_chip_and_every_active_category(): void
    {
        BusinessCategory::create(['name' => 'Kategori Nonaktif', 'slug' => 'kategori-nonaktif', 'is_active' => false]);

        $this->get(route('customer.search', ['kategori' => 'kuliner-kafe']))
            ->assertOk()
            ->assertSee('Semua')
            ->assertSee('Kuliner')
            ->assertSee('Barbershop')
            ->assertSee('Klinik')
            ->assertDontSee('Kategori Nonaktif')
            ->assertSee('Kedai Kopi Senja')
            ->assertSee('Barbershop Garasi');
    }

    public function test_each_category_gets_its_own_icon_instead_of_the_all_icon(): void
    {
        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('images/icons/scissors.svg', false)
            ->assertSee('images/icons/stethoscope.svg', false)
            ->assertSee('images/figma/utensils.svg', false)
            ->assertSee('Segera hadir');
    }

    public function test_search_ignores_unknown_category(): void
    {
        $this->get(route('customer.search', ['kategori' => 'tidak-ada']))
            ->assertOk()
            ->assertViewHas('selectedCategory', 'semua');
    }

    public function test_customer_tickets_appear_on_dashboard_my_tickets_and_history(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $otherCustomer = User::factory()->customer()->create(['name' => 'Pelanggan Lain']);
        $queueService = app(QueueService::class);
        $service = $this->cafe->services()->first();

        $activeTicket = $queueService->generateOnlineTicket($this->cafe, $service, 'Budi Santoso', customer: $customer);
        $finishedTicket = $queueService->generateOnlineTicket($this->barbershop, $this->barbershop->services()->first(), 'Budi Santoso', customer: $customer);
        $finishedTicket->update(['status' => QueueTicket::STATUS_SELESAI]);
        $otherGuestTicket = $queueService->generateOnlineTicket($this->cafe, $service, 'Pelanggan Lain', customer: $otherCustomer);

        $this->actingAs($customer);

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($activeTicket->ticket_number)
            ->assertSee('Antrean aktif Anda')
            ->assertDontSee('Cara pakai AntreIn');

        $this->get(route('customer.my-tickets'))
            ->assertOk()
            ->assertSee('1 / 3 tiket aktif')
            ->assertSee(route('customer.queue.track', $activeTicket->access_token), false)
            ->assertDontSee('Pelanggan Lain');

        $this->get(route('customer.history'))
            ->assertOk()
            ->assertSee($finishedTicket->ticket_number)
            ->assertSee('Barbershop Garasi')
            ->assertDontSee(route('customer.queue.track', $otherGuestTicket->access_token), false);
    }

    public function test_returning_customer_without_active_ticket_sees_last_store(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $ticket = app(QueueService::class)->generateOnlineTicket($this->cafe, $this->cafe->services()->first(), 'Budi Santoso', customer: $customer);
        $ticket->update(['status' => QueueTicket::STATUS_SELESAI]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada antrean aktif')
            ->assertSee('KEDAI PILIHAN ANDA')
            ->assertDontSee('Cara pakai AntreIn');
    }

    public function test_authenticated_customer_can_save_profile_and_it_prefills_queue_form(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Raka Lama']);

        $this->actingAs($customer)->post(route('customer.profile.update'), [
            'name' => 'Budi Santoso',
            'phone' => '0812 3456 7890',
            'email' => 'budi@example.com',
        ])->assertRedirect(route('customer.profile'))->assertSessionHasNoErrors();

        $this->actingAs($customer)->get(route('customer.profile'))->assertSee('BS')->assertSee('Profil berhasil disimpan.');
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertSee('Halo, Budi');
        $this->actingAs($customer)->get(route('customer.queue.create', $this->cafe->slug))->assertSee('value="Budi Santoso"', false);
    }

    public function test_profile_requires_a_name(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post(route('customer.profile.update'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_store_cards_highlight_hours_rating_and_stock_photos_without_middle_dots(): void
    {
        BusinessHour::create([
            'business_id' => $this->cafe->id,
            'day_of_week' => (int) now('Asia/Jakarta')->dayOfWeek,
            'open_time' => '00:00',
            'close_time' => '23:59',
            'is_closed' => false,
        ]);
        $this->reviewBusiness($this->cafe, 5, 'Kopinya mantap.');
        $this->reviewBusiness($this->cafe, 4, 'Pelayanan cepat');

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Buka s/d 23.59')
            ->assertSee('4.5')
            ->assertSee('(2 ulasan)')
            ->assertSee('images/stores/kopi.jpg', false)
            ->assertSee('images/stores/barbershop.jpg', false)
            ->assertSeeInOrder(['Pilih Toko', 'Ambil Tiket', 'Pantau Giliran'])
            ->assertDontSee('·')
            ->assertDontSee('Pantau Live');
    }

    public function test_search_supports_sort_options(): void
    {
        $queueService = app(QueueService::class);
        $queueService->generateOnlineTicket($this->barbershop, $this->barbershop->services()->first(), 'Pelanggan Satu');
        $queueService->generateOnlineTicket($this->barbershop, $this->barbershop->services()->first(), 'Pelanggan Dua');
        $this->reviewBusiness($this->barbershop, 5, 'Rapi sekali.');

        $firstStoreFor = fn (string $sort): string => $this->get(route('customer.search', ['urut' => $sort]))
            ->assertOk()
            ->viewData('businesses')
            ->first()
            ->name;

        $this->assertSame('Barbershop Garasi', $firstStoreFor('rekomendasi'));
        $this->assertSame('Kedai Kopi Senja', $firstStoreFor('antrean'));
        $this->assertSame('Barbershop Garasi', $firstStoreFor('nama'));

        $this->get(route('customer.search', ['urut' => 'acak']))
            ->assertOk()
            ->assertViewHas('selectedSort', 'rekomendasi')
            ->assertSee('Urutkan')
            ->assertSee('Antrean Terpendek')
            ->assertSee('Nama A-Z');
    }

    public function test_store_page_shows_rating_summary_and_recent_reviews(): void
    {
        $this->reviewBusiness($this->cafe, 5, 'Baristanya ramah sekali.');
        $this->reviewBusiness($this->cafe, 3, null);

        $this->get(route('customer.queue', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Ulasan Pelanggan')
            ->assertSee('(2 ulasan)')
            ->assertSee('Baristanya ramah sekali.')
            ->assertSee('Sisa kuota 48')
            ->assertDontSee('·');
    }

    public function test_store_page_without_reviews_says_so(): void
    {
        $this->get(route('customer.queue', $this->cafe->slug))
            ->assertOk()
            ->assertSee('Belum ada ulasan')
            ->assertDontSee('Ulasan Pelanggan');
    }

    public function test_tracking_page_renders_qr_ticket_without_copy_link_and_shows_quick_feedback_chips(): void
    {
        $ticket = app(QueueService::class)->generateOnlineTicket($this->cafe, $this->cafe->services()->first(), 'Budi Santoso');
        $ticket->update(['status' => QueueTicket::STATUS_SELESAI, 'completed_at' => now()]);

        $this->get(route('customer.queue.track', $ticket->access_token))
            ->assertOk()
            ->assertSee('QR Code Tiket')
            ->assertSee('<svg', false)
            ->assertDontSee('Salin Link Tiket')
            ->assertDontSee('Bagikan link tiket')
            ->assertDontSee('Link tiket Anda')
            ->assertSee('Pelayanan cepat')
            ->assertSee('Staf ramah')
            ->assertSee('Antrean tertib')
            ->assertSee('Tempat nyaman')
            ->assertDontSee('Pantau Live');
    }

    public function test_visual_fallbacks_and_hours_badge(): void
    {
        $croissant = new Service(['name' => 'Croissant Mentega', 'description' => null]);
        $haircut = new Service(['name' => 'Potong Rambut', 'description' => null]);
        $generic = new Service(['name' => 'Layanan Umum', 'description' => null]);

        $this->assertSame('images/stores/pastry.jpg', $croissant->fallbackImagePath());
        $this->assertSame('images/stores/barbershop.jpg', $haircut->fallbackImagePath());
        $this->assertSame('images/stores/kopi.jpg', $generic->fallbackImagePath($this->cafe->category));
        $this->assertNull($generic->fallbackImagePath());

        $this->cafe->update(['status' => Business::STATUS_JEDA]);
        $this->assertSame(['label' => 'Antrean dijeda', 'tone' => 'paused'], $this->cafe->fresh()->customerHoursBadge());

        $this->barbershop->update(['status' => Business::STATUS_TUTUP]);
        $this->assertSame('closed', $this->barbershop->fresh()->customerHoursBadge()['tone']);
    }

    private function reviewBusiness(Business $business, int $rating, ?string $comment): CustomerReview
    {
        $service = $business->services()->first();
        $dailyQueue = app(QueueService::class)->getOrCreateDailyQueue($business);
        $nextNumber = ((int) $dailyQueue->current_number) + 1;
        $dailyQueue->update(['current_number' => $nextNumber]);

        $ticket = QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => sprintf('TKT-%03d', $nextNumber),
            'sequence_number' => $nextNumber,
            'customer_name' => 'Pengulas',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(64),
            'completed_at' => now(),
        ]);

        return CustomerReview::create([
            'business_id' => $business->id,
            'queue_ticket_id' => $ticket->id,
            'service_id' => $service->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }

    private function createBusiness(BusinessCategory $category, string $name, string $slug): Business
    {
        $business = Business::create([
            'user_id' => User::factory()->merchant()->create()->id,
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'address' => 'Jl. Braga No. 1, Bandung',
            'timezone' => 'Asia/Jakarta',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
        ]);

        Service::create([
            'business_id' => $business->id,
            'name' => 'Layanan Utama',
            'duration_minutes' => 10,
            'daily_quota' => 50,
            'is_active' => true,
        ]);

        return $business;
    }
}
