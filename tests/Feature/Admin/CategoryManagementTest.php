<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('Tema warna');
        $response->assertSee('Ikon kategori');
        $response->assertSee('Foto sampul bawaan');
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

    public function test_admin_can_save_custom_icon_and_color_theme_on_create_and_update(): void
    {
        $admin = User::factory()->admin()->create();

        $createResponse = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Pet Grooming',
            'description' => 'Perawatan hewan peliharaan',
            'icon' => 'paw-print',
            'color_theme' => 'salon',
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.categories.index'));

        $category = BusinessCategory::where('slug', 'pet-grooming')->firstOrFail();
        $this->assertSame('paw-print', $category->icon);
        $this->assertSame('salon', $category->color_theme);
        $this->assertSame('images/icons/paw-print.svg', $category->iconPath());
        $this->assertSame('salon', $category->customerTheme());
        $this->assertSame('bg-pink-100', $category->customerPalette()['background']);

        // Update icon & color theme, then clear back to automatic (empty string)
        $updateResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Pet Grooming',
            'slug' => 'pet-grooming',
            'description' => 'Perawatan hewan peliharaan',
            'icon' => 'camera',
            'color_theme' => 'jasa',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.categories.index'));
        $category->refresh();
        $this->assertSame('camera', $category->icon);
        $this->assertSame('jasa', $category->color_theme);
        $this->assertSame('images/icons/camera.svg', $category->iconPath());
        $this->assertSame('jasa', $category->customerTheme());

        $clearResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Pet Grooming',
            'slug' => 'pet-grooming',
            'icon' => '',
            'color_theme' => '',
            'is_active' => '1',
        ]);

        $clearResponse->assertRedirect(route('admin.categories.index'));
        $category->refresh();
        $this->assertNull($category->icon);
        $this->assertNull($category->color_theme);
    }

    public function test_invalid_icon_or_color_theme_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $storeResponse = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Kategori Tidak Valid',
            'icon' => 'tidak-ada-ikon',
            'color_theme' => 'pelangi',
            'is_active' => '1',
        ]);

        $storeResponse->assertSessionHasErrors(['icon', 'color_theme']);
        $this->assertDatabaseMissing('business_categories', [
            'name' => 'Kategori Tidak Valid',
        ]);

        $category = BusinessCategory::create([
            'name' => 'Studio Foto',
            'slug' => 'studio-foto',
            'is_active' => true,
        ]);

        $updateResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Studio Foto',
            'slug' => 'studio-foto',
            'icon' => '../../etc/passwd',
            'color_theme' => 'invalid-theme',
            'is_active' => '1',
        ]);

        $updateResponse->assertSessionHasErrors(['icon', 'color_theme']);
        $this->assertNull($category->fresh()->icon);
        $this->assertNull($category->fresh()->color_theme);
    }

    public function test_admin_can_upload_replace_and_remove_category_cover_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // Upload on create
        $firstCover = UploadedFile::fake()->image('cover-1.jpg', 1200, 675);
        $createResponse = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Studio Foto',
            'icon' => 'camera',
            'color_theme' => 'jasa',
            'cover' => $firstCover,
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.categories.index'));

        $category = BusinessCategory::where('slug', 'studio-foto')->firstOrFail();
        $this->assertNotNull($category->cover_path);
        $this->assertStringStartsWith('category-covers/', $category->cover_path);
        Storage::disk('public')->assertExists($category->cover_path);
        $this->assertSame(Storage::disk('public')->url($category->cover_path), $category->coverImageUrl());

        $firstCoverPath = $category->cover_path;

        // Replace cover photo on update
        $secondCover = UploadedFile::fake()->image('cover-2.webp', 1200, 675);
        $replaceResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Studio Foto',
            'slug' => 'studio-foto',
            'icon' => 'camera',
            'color_theme' => 'jasa',
            'cover' => $secondCover,
            'is_active' => '1',
        ]);

        $replaceResponse->assertRedirect(route('admin.categories.index'));
        $category->refresh();

        $this->assertNotNull($category->cover_path);
        $this->assertNotSame($firstCoverPath, $category->cover_path);
        Storage::disk('public')->assertMissing($firstCoverPath);
        Storage::disk('public')->assertExists($category->cover_path);

        $secondCoverPath = $category->cover_path;

        // Remove cover photo via remove_cover flag
        $removeResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Studio Foto',
            'slug' => 'studio-foto',
            'icon' => 'camera',
            'color_theme' => 'jasa',
            'remove_cover' => '1',
            'is_active' => '1',
        ]);

        $removeResponse->assertRedirect(route('admin.categories.index'));
        $category->refresh();

        $this->assertNull($category->cover_path);
        $this->assertNull($category->coverImageUrl());
        Storage::disk('public')->assertMissing($secondCoverPath);
    }

    public function test_deleting_unreferenced_category_also_deletes_uploaded_cover_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $coverPath = UploadedFile::fake()->image('cover-delete.png', 800, 450)->store('category-covers', 'public');
        $category = BusinessCategory::create([
            'name' => 'Kategori Sementara',
            'slug' => 'kategori-sementara',
            'cover_path' => $coverPath,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertExists($coverPath);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('business_categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing($coverPath);
    }

    public function test_legacy_category_without_appearance_columns_falls_back_to_slug_guessing(): void
    {
        $barber = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop',
            'is_active' => true,
        ]);

        $klinik = BusinessCategory::create([
            'name' => 'Klinik Kesehatan',
            'slug' => 'klinik-kesehatan',
            'is_active' => true,
        ]);

        $kopi = BusinessCategory::create([
            'name' => 'Kopi & Kafe',
            'slug' => 'kopi-kafe',
            'is_active' => true,
        ]);

        $this->assertSame('images/icons/scissors.svg', $barber->iconPath());
        $this->assertSame('barbershop', $barber->customerTheme());
        $this->assertSame(asset('images/stores/barbershop.jpg'), $barber->coverImageUrl());

        $this->assertSame('images/icons/stethoscope.svg', $klinik->iconPath());
        $this->assertSame('klinik', $klinik->customerTheme());
        $this->assertSame(asset('images/stores/klinik.jpg'), $klinik->coverImageUrl());

        $this->assertSame('images/figma/coffee.svg', $kopi->iconPath());
        $this->assertSame('kuliner', $kopi->customerTheme());
        $this->assertSame(asset('images/stores/kopi.jpg'), $kopi->coverImageUrl());
    }

    public function test_customer_store_page_uses_custom_category_icon_color_and_cover_while_preserving_merchant_cover_priority(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->merchant()->create();

        $categoryCover = UploadedFile::fake()->image('pet-cover.jpg', 1200, 675);
        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Klinik Hewan',
            'slug' => 'pet-care',
            'icon' => 'paw-print',
            'color_theme' => 'salon',
            'cover' => $categoryCover,
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = BusinessCategory::where('slug', 'pet-care')->firstOrFail();

        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Sahabat Satwa',
            'slug' => 'sahabat-satwa',
            'phone' => '081234567899',
            'timezone' => 'Asia/Jakarta',
            'status' => Business::STATUS_BUKA,
            'is_approved' => true,
            'is_active' => true,
            'onboarding_completed_at' => now(),
        ]);

        $categoryCoverUrl = Storage::disk('public')->url($category->cover_path);
        $this->assertSame($categoryCoverUrl, $business->coverImageUrl());

        $storeResponse = $this->get(route('customer.queue', $business->slug));
        $storeResponse->assertOk();
        $storeResponse->assertSee($categoryCoverUrl, false);
        $storeResponse->assertSee('images/icons/paw-print.svg', false);
        $storeResponse->assertSee('bg-pink-100', false);
        $storeResponse->assertSee('bg-pink-500', false);

        // Merchant's own cover photo must still take priority over category cover photo
        $merchantCoverPath = UploadedFile::fake()->image('merchant-cover.jpg', 1200, 675)->store('business-covers', 'public');
        $business->update(['cover_path' => $merchantCoverPath]);

        $merchantCoverUrl = Storage::disk('public')->url($merchantCoverPath);
        $this->assertSame($merchantCoverUrl, $business->fresh()->coverImageUrl());

        $this->get(route('customer.queue', $business->slug))
            ->assertOk()
            ->assertSee($merchantCoverUrl, false)
            ->assertDontSee($categoryCoverUrl, false);
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

        // Attempt deletion of referenced category without replacement
        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertSessionHasErrors(['delete_error']);
        $this->assertDatabaseHas('business_categories', [
            'id' => $category->id,
            'name' => 'Klinik Gigi',
        ]);
    }

    public function test_referenced_category_can_be_deleted_when_reassigning_businesses_to_replacement_category(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $oldCoverPath = UploadedFile::fake()->image('old-cat.jpg', 800, 450)->store('category-covers', 'public');
        $oldCategory = BusinessCategory::create([
            'name' => 'Klinik Gigi Lama',
            'slug' => 'klinik-gigi-lama',
            'cover_path' => $oldCoverPath,
            'is_active' => true,
        ]);

        $replacementCategory = BusinessCategory::create([
            'name' => 'Klinik Kesehatan',
            'slug' => 'klinik-kesehatan',
            'is_active' => true,
        ]);

        $owner = User::factory()->merchant()->create();
        $business1 = Business::create([
            'user_id' => $owner->id,
            'category_id' => $oldCategory->id,
            'name' => 'Dental Care Bali',
            'slug' => 'dental-care-bali',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $business2 = Business::create([
            'user_id' => $owner->id,
            'category_id' => $oldCategory->id,
            'name' => 'Senyum Sehat',
            'slug' => 'senyum-sehat',
            'phone' => '081234567891',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $oldCategory), [
            'replacement_category_id' => $replacementCategory->id,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('business_categories', ['id' => $oldCategory->id]);
        $this->assertSame($replacementCategory->id, $business1->fresh()->category_id);
        $this->assertSame($replacementCategory->id, $business2->fresh()->category_id);
        Storage::disk('public')->assertMissing($oldCoverPath);
    }

    public function test_reassign_delete_rejects_same_category_as_replacement(): void
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

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category), [
            'replacement_category_id' => $category->id,
        ]);

        $response->assertSessionHasErrors(['replacement_category_id']);
        $this->assertDatabaseHas('business_categories', ['id' => $category->id]);
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
