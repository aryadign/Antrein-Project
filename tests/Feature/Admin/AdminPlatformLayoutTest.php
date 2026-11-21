<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPlatformLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $merchant;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->admin()->create();
        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Barber Sanur',
            'slug' => 'barber-sanur',
            'phone' => '081234567890',
            'timezone' => 'Asia/Jakarta',
            'status' => Business::STATUS_BUKA,
        ]);

        BusinessHour::create([
            'business_id' => $this->business->id,
            'day_of_week' => 0,
            'open_time' => '09:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['admin.dashboard', false],
            'businesses' => ['admin.businesses.index', false],
            'business detail' => ['admin.businesses.show', true],
            'users' => ['admin.users.index', false],
            'queues' => ['admin.queues.index', false],
            'categories' => ['admin.categories.index', false],
            'settings' => ['admin.settings', false],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_have_no_decorative_bullets_or_pulsing_dots(string $routeName, bool $needsBusiness): void
    {
        $url = $needsBusiness ? route($routeName, $this->business) : route($routeName);

        $this->actingAs($this->admin)->get($url)
            ->assertOk()
            ->assertDontSee('•')
            ->assertDontSee('·')
            ->assertDontSee('animate-ping', false)
            ->assertDontSee('animate-pulse', false);
    }

    public function test_user_detail_page_has_no_decorative_bullets(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.show', $this->merchant))
            ->assertOk()
            ->assertDontSee('•')
            ->assertDontSee('animate-pulse', false);
    }

    public function test_admin_navigation_stays_active_on_detail_pages(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.businesses.show', $this->business))
            ->assertOk()
            ->getContent();

        $activePattern = '/href="'.preg_quote(route('admin.businesses.index'), '/').'"\s+aria-current="page"/';
        $dashboardPattern = '/href="'.preg_quote(route('admin.dashboard'), '/').'"\s+aria-current="page"/';

        $this->assertMatchesRegularExpression($activePattern, $content);
        $this->assertDoesNotMatchRegularExpression($dashboardPattern, $content);
    }

    public function test_business_detail_uses_business_timezone_and_sunday_label(): void
    {
        $this->actingAs($this->admin)->get(route('admin.businesses.show', $this->business))
            ->assertOk()
            ->assertSee('Minggu')
            ->assertSee('WIB')
            ->assertDontSee('Hari 0');
    }

    public function test_platform_online_badge_uses_static_icon(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('title="Platform aktif"', false)
            ->assertDontSee('animate-pulse', false);
    }
}
