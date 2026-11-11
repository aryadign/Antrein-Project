<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\BusinessOrganization;
use App\Models\DailyQueue;
use App\Models\OrganizationMembership;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;
    private BusinessOrganization $orgA;
    private Business $branchA1;
    private BusinessCategory $category;
    private User $operatorA1;

    private User $ownerB;
    private BusinessOrganization $orgB;
    private Business $branchB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = BusinessCategory::create([
            'name' => 'Kecantikan & Barbershop',
            'slug' => 'kecantikan-barbershop',
            'is_active' => true,
        ]);

        // Organization A & Owner A
        $this->ownerA = User::factory()->merchant()->create([
            'name' => 'Owner Garasi Group',
            'email' => 'owner.garasi@example.com',
        ]);

        $this->branchA1 = Business::create([
            'user_id' => $this->ownerA->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop Pusat',
            'slug' => 'garasi-barbershop-pusat',
            'address' => 'Jl. Pengayoman No. 1, Makassar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        // Branch creation hook creates organization automatically
        $this->branchA1->refresh();
        $this->orgA = $this->branchA1->organization;

        // Create active service for Branch A1 so onboarding is complete
        Service::create([
            'business_id' => $this->branchA1->id,
            'name' => 'Layanan Utama Pusat',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        // Operator for Branch A1
        $this->operatorA1 = User::factory()->merchant()->create([
            'name' => 'Operator Loket Pusat',
            'email' => 'operator.pusat@example.com',
        ]);

        BusinessMembership::create([
            'organization_id' => $this->orgA->id,
            'business_id' => $this->branchA1->id,
            'user_id' => $this->operatorA1->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        // Organization B & Owner B (different tenant)
        $this->ownerB = User::factory()->merchant()->create([
            'name' => 'Owner Klinik Sehat Group',
            'email' => 'owner.klinik@example.com',
        ]);

        $this->branchB1 = Business::create([
            'user_id' => $this->ownerB->id,
            'category_id' => $this->category->id,
            'name' => 'Klinik Sehat Utama',
            'slug' => 'klinik-sehat-utama',
            'address' => 'Jl. Veteran No. 5, Makassar',
            'phone' => '081298765432',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        $this->branchB1->refresh();
        $this->orgB = $this->branchB1->organization;

        Service::create([
            'business_id' => $this->branchB1->id,
            'name' => 'Layanan Utama Klinik',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
    }

    /**
     * Test Organization: Organization is created and has valid relationship with branch.
     */
    public function test_organization_is_automatically_created_for_business(): void
    {
        $this->assertNotNull($this->orgA);
        $this->assertEquals($this->ownerA->id, $this->orgA->owner_user_id);
        $this->assertTrue($this->orgA->branches()->where('id', $this->branchA1->id)->exists());
        $this->assertTrue($this->orgA->isOwner($this->ownerA));
        $this->assertTrue($this->orgA->canManageBranches($this->ownerA));
    }

    /**
     * Test Branch Listing: Owner can view organization branch list.
     */
    public function test_owner_can_view_branch_listing(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('merchant.branches.index'));

        $response->assertOk();
        $response->assertSee('Garasi Barbershop Pusat');
        $response->assertSee('Daftar Cabang');
    }

    /**
     * Test Branch Creation: Owner can create a new branch under organization.
     */
    public function test_owner_can_create_new_branch_under_organization(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('merchant.branches.store'), [
            'name' => 'Garasi Barbershop Cabang Antang',
            'slug' => 'garasi-antang',
            'category_id' => $this->category->id,
            'address' => 'Jl. Antang Raya No. 88, Makassar',
            'phone' => '081234567899',
            'timezone' => 'Asia/Makassar',
            'status' => 'tutup',
        ]);

        $response->assertRedirect(route('merchant.branches.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('businesses', [
            'name' => 'Garasi Barbershop Cabang Antang',
            'slug' => 'garasi-antang',
            'organization_id' => $this->orgA->id,
            'is_active' => true,
        ]);

        $newBranch = Business::where('slug', 'garasi-antang')->first();
        $this->assertNotNull($newBranch);
        $this->assertEquals($this->orgA->id, $newBranch->organization_id);
    }

    /**
     * Test Branch Slug: Unique branch slug generation prevents collision.
     */
    public function test_branch_slug_must_be_unique(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('merchant.branches.store'), [
            'name' => 'Cabang Duplikat',
            'slug' => 'garasi-barbershop-pusat', // Already used by branchA1
            'category_id' => $this->category->id,
            'address' => 'Jl. Urip No. 1',
            'phone' => '081234567891',
            'timezone' => 'Asia/Makassar',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    /**
     * Test Branch Update: Owner can update branch profile.
     */
    public function test_owner_can_update_branch(): void
    {
        $response = $this->actingAs($this->ownerA)->put(route('merchant.branches.update', $this->branchA1), [
            'name' => 'Garasi Barbershop Pusat Renovasi',
            'category_id' => $this->category->id,
            'address' => 'Jl. Pengayoman No. 1A, Makassar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
        ]);

        $response->assertRedirect(route('merchant.branches.index'));
        $this->branchA1->refresh();
        $this->assertEquals('Garasi Barbershop Pusat Renovasi', $this->branchA1->name);
        $this->assertEquals('Jl. Pengayoman No. 1A, Makassar', $this->branchA1->address);
    }

    /**
     * Test Branch Deactivation: Branch can be deactivated, but at least one must remain active.
     */
    public function test_branch_deactivation_rules(): void
    {
        // Attempting to deactivate sole active branch fails
        $response = $this->actingAs($this->ownerA)->patch(route('merchant.branches.toggle-active', $this->branchA1));
        $response->assertSessionHasErrors('general');
        $this->assertTrue($this->branchA1->fresh()->is_active);

        // Create second branch
        $branchA2 = Business::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->ownerA->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Cabang 2',
            'slug' => 'garasi-cabang-2',
            'address' => 'Jl. Hertasning No. 10',
            'phone' => '081234567888',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        // Now deactivating branch A2 succeeds
        $response = $this->actingAs($this->ownerA)->patch(route('merchant.branches.toggle-active', $branchA2));
        $response->assertRedirect(route('merchant.branches.index'));
        $this->assertFalse($branchA2->fresh()->is_active);
    }

    /**
     * Test Tenant Isolation: Owner A cannot edit or toggle branches belonging to Organization B.
     */
    public function test_cross_tenant_branch_tampering_is_forbidden(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('merchant.branches.edit', $this->branchB1))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->put(route('merchant.branches.update', $this->branchB1), [
                'name' => 'Tampered Branch',
                'category_id' => $this->category->id,
                'address' => 'Jl. Hacker No. 1',
                'phone' => '081234567899',
                'timezone' => 'Asia/Makassar',
            ])
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->patch(route('merchant.branches.toggle-active', $this->branchB1))
            ->assertStatus(403);
    }

    /**
     * Test Role Boundaries: Operator cannot access branch creation, editing or organization settings.
     */
    public function test_operator_cannot_access_branch_management(): void
    {
        $this->actingAs($this->operatorA1)
            ->get(route('merchant.branches.create'))
            ->assertStatus(403);

        $this->actingAs($this->operatorA1)
            ->post(route('merchant.branches.store'), [
                'name' => 'Operator Branch',
                'category_id' => $this->category->id,
                'address' => 'Jl. Operator No. 1',
                'phone' => '081234567899',
                'timezone' => 'Asia/Makassar',
            ])
            ->assertStatus(403);

        $this->actingAs($this->operatorA1)
            ->get(route('merchant.organization.edit'))
            ->assertStatus(403);
    }

    /**
     * Test Active Branch Switching: User can switch active branch in session.
     */
    public function test_user_can_switch_active_branch(): void
    {
        $branchA2 = Business::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->ownerA->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Cabang BTP',
            'slug' => 'garasi-cabang-btp',
            'address' => 'Jl. BTP Blok M, Makassar',
            'phone' => '081234567777',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        Service::create([
            'business_id' => $branchA2->id,
            'name' => 'Potong Rambut BTP',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->post(route('merchant.branches.switch'), [
            'business_id' => $branchA2->id,
        ]);

        $response->assertRedirect(route('merchant.dashboard'));
        $response->assertSessionHas('active_business_id', $branchA2->id);

        // Next request uses branchA2 as active business
        $dashboardResponse = $this->actingAs($this->ownerA)->get(route('merchant.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Garasi Cabang BTP');
    }

    /**
     * Test Unauthorized Branch Switching: Cannot switch to an unauthorized branch.
     */
    public function test_cannot_switch_to_unauthorized_branch(): void
    {
        $this->actingAs($this->ownerA)->post(route('merchant.branches.switch'), [
            'business_id' => $this->branchB1->id,
        ])->assertStatus(403);
    }

    /**
     * Test Queue Engine Branch Isolation: Queues and ticket numbering are independent per branch.
     */
    public function test_queue_numbering_is_independent_per_branch(): void
    {
        $branchA2 = Business::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->ownerA->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Cabang Panakkukang',
            'slug' => 'garasi-panakkukang',
            'address' => 'Jl. Boulevard No. 12',
            'phone' => '081234567666',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        $service1 = Service::create([
            'business_id' => $this->branchA1->id,
            'name' => 'Potong Rambut',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $service2 = Service::create([
            'business_id' => $branchA2->id,
            'name' => 'Potong Rambut',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $queueService = app(QueueService::class);

        // Generate Ticket in Branch 1
        $ticket1 = $queueService->generateOnlineTicket(
            business: $this->branchA1,
            service: $service1,
            customerName: 'Budi Branch 1'
        );

        // Generate Ticket in Branch 2
        $ticket2 = $queueService->generateOnlineTicket(
            business: $branchA2,
            service: $service2,
            customerName: 'Iwan Branch 2'
        );

        // Both tickets start at sequence 1 in their respective branch
        $this->assertEquals(1, $ticket1->sequence_number);
        $this->assertEquals(1, $ticket2->sequence_number);
        $this->assertEquals('A-001', $ticket1->ticket_number);
        $this->assertEquals('A-001', $ticket2->ticket_number);
        $this->assertEquals($this->branchA1->id, $ticket1->business_id);
        $this->assertEquals($branchA2->id, $ticket2->business_id);
    }

    /**
     * Test Organization Aggregated Reports: Owner can view aggregated analytics.
     */
    public function test_owner_can_view_aggregated_organization_reports(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('merchant.organization.reports'));

        $response->assertOk();
        $response->assertSee('Laporan Agregat Organisasi');
        $response->assertSee('Garasi Barbershop Pusat');
        $response->assertSee('Total Tiket Organisasi');
    }

    /**
     * Test Cross-Tenant Report Isolation: Owner B cannot see Organization A reports.
     */
    public function test_cross_tenant_organization_report_isolation(): void
    {
        $response = $this->actingAs($this->ownerB)->get(route('merchant.organization.reports'));

        $response->assertOk();
        $response->assertSee('Klinik Sehat Utama');
        $response->assertDontSee('Garasi Barbershop Pusat');
    }
}
