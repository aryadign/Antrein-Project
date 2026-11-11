<?php

namespace Tests\Feature\Display;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop & Salon',
            'slug' => 'barbershop-salon',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'name' => 'Gentlemen Barber',
            'slug' => 'gentlemen-barber',
            'phone' => '081234567890',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
            'timezone' => 'Asia/Makassar',
        ]);

        Service::create([
            'business_id' => $this->business->id,
            'name' => 'Haircut',
            'prefix' => 'B',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        BusinessMembership::create([
            'business_id' => $this->business->id,
            'user_id' => $this->operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    public function test_owner_can_access_display_management_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('merchant.display.index'));

        $response->assertOk();
        $response->assertViewIs('merchant.display.index');
        $response->assertSee('Layar Antrean Publik (TV Display)');
    }

    public function test_operator_cannot_access_display_management_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('merchant.display.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_accessing_display_management(): void
    {
        $response = $this->get(route('merchant.display.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_generate_display_token_and_enable_display(): void
    {
        $this->assertNull($this->business->display_token_hash);
        $this->assertFalse((bool) $this->business->is_display_enabled);

        $response = $this->actingAs($this->owner)->post(route('merchant.display.generate'));

        $response->assertRedirect(route('merchant.display.index'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('one_time_display_token');

        $rawToken = session('one_time_display_token');
        $this->assertNotEmpty($rawToken);

        $this->business->refresh();
        $this->assertTrue($this->business->is_display_enabled);
        $this->assertNotNull($this->business->display_token_hash);
        $this->assertSame(hash('sha256', $rawToken), $this->business->display_token_hash);

        // Verify database does not store plaintext display token
        $this->assertArrayNotHasKey('display_token', $this->business->getAttributes());

        // Assert one-time token notice is shown when flashed in session
        $indexResponse = $this->actingAs($this->owner)
            ->withSession(['one_time_display_token' => $rawToken])
            ->get(route('merchant.display.index'));
        $indexResponse->assertSee('Salin & Simpan Tautan Layar Display Sekarang', false);

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->business->id,
            'action' => 'display_token_generated',
        ]);
    }

    public function test_owner_can_toggle_display_status(): void
    {
        // First enable
        $response = $this->actingAs($this->owner)->post(route('merchant.display.toggle'), [
            'is_display_enabled' => '1',
        ]);

        $response->assertRedirect(route('merchant.display.index'));
        $response->assertSessionHas('one_time_display_token');

        $this->business->refresh();
        $this->assertTrue($this->business->is_display_enabled);
        $this->assertNotNull($this->business->display_token_hash);

        // Then disable
        $response = $this->actingAs($this->owner)->post(route('merchant.display.toggle'), [
            'is_display_enabled' => '0',
        ]);

        $response->assertRedirect(route('merchant.display.index'));
        $this->business->refresh();
        $this->assertFalse($this->business->is_display_enabled);
    }

    public function test_owner_can_regenerate_display_token_and_old_token_is_invalidated(): void
    {
        $oldToken = $this->business->generateDisplayToken();
        $oldTokenHash = $this->business->display_token_hash;

        $response = $this->actingAs($this->owner)->post(route('merchant.display.generate'));

        $response->assertRedirect(route('merchant.display.index'));
        $response->assertSessionHas('one_time_display_token');

        $this->business->refresh();
        $newToken = session('one_time_display_token');
        $newTokenHash = $this->business->display_token_hash;

        $this->assertNotSame($oldToken, $newToken);
        $this->assertNotSame($oldTokenHash, $newTokenHash);
        $this->assertSame(hash('sha256', $newToken), $newTokenHash);

        // Verify activity log for rotation
        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->business->id,
            'action' => 'display_token_regenerated',
        ]);
    }

    public function test_owner_can_revoke_display_access(): void
    {
        $this->business->generateDisplayToken();
        $this->assertTrue($this->business->isDisplayActive());

        $response = $this->actingAs($this->owner)->delete(route('merchant.display.revoke'));

        $response->assertRedirect(route('merchant.display.index'));

        $this->business->refresh();
        $this->assertFalse($this->business->is_display_enabled);
        $this->assertNull($this->business->display_token_hash);
        $this->assertFalse($this->business->isDisplayActive());

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->business->id,
            'action' => 'display_access_revoked',
        ]);
    }

    public function test_operator_cannot_perform_display_management_actions(): void
    {
        $response1 = $this->actingAs($this->operator)->post(route('merchant.display.generate'));
        $response1->assertForbidden();

        $response2 = $this->actingAs($this->operator)->post(route('merchant.display.toggle'), [
            'is_display_enabled' => '1',
        ]);
        $response2->assertForbidden();

        $response3 = $this->actingAs($this->operator)->delete(route('merchant.display.revoke'));
        $response3->assertForbidden();
    }
}
