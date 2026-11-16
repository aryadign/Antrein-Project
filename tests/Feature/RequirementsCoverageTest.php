<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessMembership;
use App\Models\CustomerReview;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DevelopmentMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequirementsCoverageTest extends TestCase
{
    use RefreshDatabase;

    private BusinessCategory $category;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = BusinessCategory::create([
            'name' => 'Kuliner & Kafe',
            'slug' => 'kuliner-kafe',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin-test@antrein.id',
        ]);
    }

    public function test_landing_page_exposes_customer_login_and_registration_without_guest_queue_copy(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Masuk Pelanggan');
        $response->assertSee('Daftar Pelanggan');
        $response->assertSee(route('register', ['type' => 'customer']), false);
        $response->assertDontSee('tanpa login');
        $response->assertDontSee('tanpa akun');
        $response->assertDontSee('Salin Link');
        $response->assertDontSee('Salin Tautan');
    }

    public function test_customer_can_register_and_login_to_take_queue(): void
    {
        $owner = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $this->category->id,
            'name' => 'Kopi Harmoni',
            'slug' => 'kopi-harmoni',
            'phone' => '081234567890',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'timezone' => 'Asia/Makassar',
        ]);
        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Espresso Tonic',
            'duration_minutes' => 10,
            'daily_quota' => 30,
            'is_active' => true,
        ]);

        // 1. Register as customer
        $registerResponse = $this->post(route('register'), [
            'account_type' => 'customer',
            'name' => 'Sari Wulandari',
            'email' => 'sari.customer@example.com',
            'phone' => '081299990000',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $registerResponse->assertRedirect(route('customer.dashboard'));

        $customer = User::where('email', 'sari.customer@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_CUSTOMER, $customer->role);

        // 2. Guest cannot access take-queue, my-tickets, cancel, or review
        auth()->logout();
        $this->get(route('customer.queue.create', $business->slug))->assertRedirect(route('login'));
        $this->post(route('customer.queue.take', $business->slug), [
            'service_id' => $service->id,
            'customer_name' => 'Tamu',
        ])->assertRedirect(route('login'));
        $this->get(route('customer.my-tickets'))->assertRedirect(route('login'));

        // 3. Authenticated customer takes a ticket and has no link-sharing UI
        $takeResponse = $this->actingAs($customer)->post(route('customer.queue.take', $business->slug), [
            'service_id' => $service->id,
            'customer_name' => 'Sari Wulandari',
            'customer_phone' => '081299990000',
        ]);
        $takeResponse->assertRedirect();

        $ticket = QueueTicket::where('user_id', $customer->id)->firstOrFail();
        $this->actingAs($customer)
            ->get(route('customer.queue.track', $ticket->access_token))
            ->assertOk()
            ->assertSee('QR Code Tiket')
            ->assertDontSee('Salin Link')
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('Tautan Publik');
    }

    public function test_customer_cannot_hold_more_than_three_active_tickets_simultaneously_and_canceling_frees_slot(): void
    {
        $customer = User::factory()->customer()->create();

        $businesses = [];
        $services = [];
        for ($i = 1; $i <= 4; $i++) {
            $owner = User::factory()->merchant()->create();
            $biz = Business::create([
                'user_id' => $owner->id,
                'category_id' => $this->category->id,
                'name' => "Toko Uji {$i}",
                'slug' => "toko-uji-{$i}",
                'phone' => '08120000000'.$i,
                'status' => Business::STATUS_BUKA,
                'approval_status' => Business::APPROVAL_APPROVED,
                'timezone' => 'Asia/Makassar',
            ]);
            $srv = Service::create([
                'business_id' => $biz->id,
                'name' => "Layanan {$i}",
                'duration_minutes' => 15,
                'daily_quota' => 50,
                'is_active' => true,
            ]);
            $businesses[] = $biz;
            $services[] = $srv;
        }

        // Take 3 active tickets across 3 different stores
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($customer)->postJson(route('customer.queue.take', $businesses[$i]->slug), [
                'service_id' => $services[$i]->id,
                'customer_name' => $customer->name,
                'customer_phone' => '081234567890',
            ])->assertCreated();
        }

        $this->assertSame(3, $customer->activeTicketsCount());

        // 4th active ticket on another store is rejected
        $fourth = $this->actingAs($customer)->postJson(route('customer.queue.take', $businesses[3]->slug), [
            'service_id' => $services[3]->id,
            'customer_name' => $customer->name,
            'customer_phone' => '081234567890',
        ]);
        $fourth->assertStatus(422);
        $this->assertStringContainsString('maksimal 3 tiket antrean aktif', (string) $fourth->json('message'));

        // Cancel one ticket -> frees slot -> 4th ticket now succeeds
        $firstTicket = QueueTicket::where('user_id', $customer->id)->firstOrFail();
        $this->actingAs($customer)->postJson(route('customer.queue.cancel', $firstTicket->access_token), [
            'reason' => 'Berubah pikiran',
        ])->assertOk();

        $this->assertSame(2, $customer->activeTicketsCount());

        $this->actingAs($customer)->postJson(route('customer.queue.take', $businesses[3]->slug), [
            'service_id' => $services[3]->id,
            'customer_name' => $customer->name,
            'customer_phone' => '081234567890',
        ])->assertCreated();

        $this->assertSame(3, $customer->activeTicketsCount());
    }

    public function test_umkm_onboarding_approval_waiting_and_admin_accept_reject_workflow(): void
    {
        $owner = User::factory()->merchant()->create([
            'name' => 'Pemilik Baru',
            'email' => 'pemilik-baru@example.com',
        ]);

        // Step 1 -> creates pending business
        $this->actingAs($owner)->post(route('merchant.onboarding.step1'), [
            'name' => 'Kedai Maju Jaya',
            'category_id' => $this->category->id,
            'description' => 'Kedai kopi nusantara',
            'address' => 'Jl. Merdeka No. 12',
            'phone' => '081234561111',
            'timezone' => 'Asia/Makassar',
        ])->assertRedirect(route('merchant.onboarding', ['step' => 2]));

        $owner = $owner->fresh();
        $business = Business::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame(Business::APPROVAL_PENDING, $business->approval_status);

        // Step 3 -> add initial service
        $this->actingAs($owner)->post(route('merchant.onboarding.step3'), [
            'name' => 'Kopi Tubruk Gula Aren',
            'duration_minutes' => 10,
            'price' => 18000,
            'daily_quota' => 40,
        ])->assertRedirect(route('merchant.onboarding', ['step' => 4]));

        // Step 4 onboarding has QR without Salin Tautan
        $this->actingAs($owner)->get(route('merchant.onboarding', ['step' => 4]))
            ->assertOk()
            ->assertSee('Verifikasi Admin AntreIn')
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('Salin Link');

        // Complete onboarding -> goes to waiting approval page
        $this->actingAs($owner)->post(route('merchant.onboarding.complete'))
            ->assertRedirect(route('merchant.approval.status'));

        $this->actingAs($owner)->get(route('merchant.approval.status'))
            ->assertOk()
            ->assertSee('Pendaftaran UMKM Sedang Diproses Admin');

        // Pending business cannot access dashboard and is hidden from public catalog/store page
        $this->actingAs($owner)->get(route('merchant.dashboard'))
            ->assertRedirect(route('merchant.approval.status'));
        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee('Kedai Maju Jaya');
        $this->get(route('customer.queue', $business->slug))
            ->assertNotFound();

        // Admin rejects first with reason -> owner sees reason and can resubmit
        $this->actingAs($this->admin)->post(route('admin.businesses.reject', $business), [
            'rejection_reason' => 'Foto dan deskripsi usaha belum lengkap.',
        ])->assertRedirect();

        $business->refresh();
        $this->assertSame(Business::APPROVAL_REJECTED, $business->approval_status);

        $this->actingAs($owner->fresh())->get(route('merchant.approval.status'))
            ->assertOk()
            ->assertSee('Pendaftaran UMKM Ditolak Admin')
            ->assertSee('Foto dan deskripsi usaha belum lengkap.');

        $this->actingAs($owner->fresh())->post(route('merchant.approval.resubmit'))
            ->assertRedirect(route('merchant.approval.status'));
        $this->assertSame(Business::APPROVAL_PENDING, $business->fresh()->approval_status);

        // Admin approves -> owner unlocks dashboard & store appears publicly
        $this->actingAs($this->admin)->post(route('admin.businesses.approve', $business))
            ->assertRedirect();

        $this->assertSame(Business::APPROVAL_APPROVED, $business->fresh()->approval_status);

        $this->actingAs($owner->fresh())->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertDontSee('Aksi Cepat');

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Kedai Maju Jaya');

        $this->get(route('customer.queue', $business->slug))
            ->assertOk()
            ->assertSee('Kedai Maju Jaya');
    }

    public function test_owner_is_blocked_from_operator_queue_operations_and_aksi_cepat_is_removed(): void
    {
        $owner = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $this->category->id,
            'name' => 'Salon Cempaka',
            'slug' => 'salon-cempaka',
            'phone' => '081234567777',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'timezone' => 'Asia/Makassar',
        ]);
        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Creambath',
            'duration_minutes' => 30,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $operator = User::factory()->merchant()->create();
        $operatorMembership = BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);
        $counter = $business->counters()->firstOrFail();
        $counter->assignedMemberships()->syncWithoutDetaching([$operatorMembership->id]);

        // Owner dashboard & QR page render cleanly without Aksi Cepat or Salin Link
        $this->actingAs($owner)->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertDontSee('Aksi Cepat')
            ->assertDontSee('Panggil Berikutnya')
            ->assertDontSee('Salin Link')
            ->assertDontSee('Salin Tautan');

        // Owner gets 403 on operator queue routes
        $this->actingAs($owner)->get(route('merchant.queues'))->assertForbidden();
        $this->actingAs($owner)->post(route('merchant.queues.call-next'))->assertForbidden();
        $this->actingAs($owner)->post(route('merchant.queues.walk-in'), [
            'service_id' => $service->id,
            'customer_name' => 'Tamu',
        ])->assertForbidden();

        // Operator can access queue desk without Aksi Cepat or Salin Tautan
        $this->actingAs($operator)->get(route('merchant.queues'))
            ->assertOk()
            ->assertDontSee('Aksi Cepat')
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('Salin Link');

        $this->actingAs($operator)->post(route('merchant.queues.walk-in'), [
            'service_id' => $service->id,
            'customer_name' => 'Walk In Pelanggan',
        ])->assertRedirect();

        $this->actingAs($operator)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counter->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('queue_tickets', [
            'business_id' => $business->id,
            'customer_name' => 'Walk In Pelanggan',
            'status' => QueueTicket::STATUS_DIPANGGIL,
        ]);
    }

    public function test_store_wait_time_range_scales_with_active_queue_depth(): void
    {
        $owner = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $this->category->id,
            'name' => 'Klinik Ceria',
            'slug' => 'klinik-ceria',
            'phone' => '081234568888',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'timezone' => 'Asia/Makassar',
        ]);
        Service::create([
            'business_id' => $business->id,
            'name' => 'Poli Umum',
            'duration_minutes' => 15,
            'daily_quota' => 50,
            'is_active' => true,
        ]);

        $rangeEmpty = $business->customerWaitRange(0);
        $rangeMedium = $business->customerWaitRange(4);
        $rangeBusy = $business->customerWaitRange(10);

        $this->assertLessThan($rangeMedium['min_minutes'], $rangeEmpty['min_minutes']);
        $this->assertLessThan($rangeBusy['min_minutes'], $rangeMedium['min_minutes']);
        $this->assertLessThan($rangeBusy['max_minutes'], $rangeMedium['max_minutes']);
        $this->assertStringContainsString('mnt', $rangeBusy['label']);
    }

    public function test_development_seeder_populates_diverse_umkms_customers_reviews_and_is_idempotent(): void
    {
        $this->seed(DevelopmentMerchantSeeder::class);
        $this->seed(DevelopmentMerchantSeeder::class);

        $this->assertGreaterThanOrEqual(7, User::where('role', User::ROLE_CUSTOMER)->count());
        $this->assertGreaterThanOrEqual(8, Business::where('approval_status', Business::APPROVAL_APPROVED)->count());
        $this->assertSame(1, Business::where('approval_status', Business::APPROVAL_PENDING)->count());
        $this->assertSame(1, Business::where('approval_status', Business::APPROVAL_REJECTED)->count());
        $this->assertGreaterThanOrEqual(15, CustomerReview::count());
        $this->assertGreaterThanOrEqual(10, QueueTicket::count());

        // Diverse categories visible on customer search catalog
        $this->get(route('customer.search'))
            ->assertOk()
            ->assertSee('Barbershop Garasi')
            ->assertSee('Kopi Senja &amp; Roastery', false)
            ->assertSee('Klinik Medika Utama')
            ->assertDontSee('Warung Teh Rimbun') // pending UMKM hidden from customers
            ->assertDontSee('Salin Link')
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('tanpa login');

        // Dashboard renders cleanly without forbidden copy
        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee('Warung Teh Rimbun')
            ->assertDontSee('Salin Link')
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('tanpa login');
    }
}
