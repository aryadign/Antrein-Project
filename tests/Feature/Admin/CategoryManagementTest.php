<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories_list(): void
    {
        $admin = User::factory()->admin()->create();

        BusinessCategory::create([
            'name' => 'Klinik Pratama',
            'slug' => 'klinik-pratama',
            'description' => 'Layanan medis dan dokter umum',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertSee('Klinik Pratama');
        $response->assertSee('klinik-pratama');
    }

    public function test_admin_can_create_category_with_auto_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Cuci Mobil & Motor',
            'description' => 'Jasa cuci kendaraan bermotor',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('business_categories', [
            'name' => 'Cuci Mobil & Motor',
            'slug' => 'cuci-mobil-motor',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_slug_is_rejected_on_explicit_creation(): void
    {
        $admin = User::factory()->admin()->create();

        BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Barbershop Lain',
            'slug' => 'barbershop',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_admin_can_edit_existing_category(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Salon',
            'slug' => 'salon',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Salon & Spa Modern',
            'slug' => 'salon-spa-modern',
            'description' => 'Perawatan rambut dan tubuh',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('business_categories', [
            'id' => $category->id,
            'name' => 'Salon & Spa Modern',
            'slug' => 'salon-spa-modern',
        ]);
    }

    public function test_admin_can_toggle_category_active_status(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Bengkel',
            'slug' => 'bengkel',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.categories.toggle-status', $category));

        $response->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($admin)->patch(route('admin.categories.toggle-status', $category));
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_referenced_category_cannot_be_destructively_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Klinik Gigi',
            'slug' => 'klinik-gigi',
            'is_active' => true,
        ]);

        $owner = User::factory()->merchant()->create();
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Dental Care Bali',
            'slug' => 'dental-care-bali',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        // Attempt deletion of referenced category
        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertSessionHasErrors(['delete_error']);
        $this->assertDatabaseHas('business_categories', [
            'id' => $category->id,
            'name' => 'Klinik Gigi',
        ]);
    }

    public function test_unreferenced_category_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Kategori Kosong',
            'slug' => 'kategori-kosong',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('business_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_merchant_cannot_access_admin_category_routes(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin_categories(): void
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('login'));
    }
}

