<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessHour;
use App\Models\BusinessMembership;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MerchantConsoleMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;

    private User $operator;

    private Business $business;

    private BusinessCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Barber Kuta',
            'slug' => 'barber-kuta',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->business->id,
            'user_id' => $this->operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        Service::create([
            'business_id' => $this->business->id,
            'name' => 'Potong Rambut',
            'duration_minutes' => 30,
            'price' => 50000,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'business_id' => $this->business->id,
            'day_of_week' => 1,
            'open_time' => '08:00',
            'close_time' => '20:00',
            'is_closed' => false,
        ]);

        $this->counter = BusinessCounter::query()->where('business_id', $this->business->id)->firstOrFail();
    }

    public function test_queue_console_renders_mobile_thumb_dock_with_call_next_action(): void
    {
        $response = $this->actingAs($this->operator)->get(route('merchant.queues'));

        $response->assertOk()
            ->assertSee('data-testid="queue-thumb-dock"', false)
            ->assertSee('Panggil Berikutnya')
            ->assertSee('Datang langsung');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function merchantConsoleRoutes(): array
    {
        return [
            'queue console' => ['merchant.queues'],
            'dashboard' => ['merchant.dashboard'],
            'services' => ['merchant.services.index'],
            'counters' => ['merchant.counters.index'],
            'staff' => ['merchant.staff.index'],
            'display' => ['merchant.display.index'],
            'reports' => ['merchant.reports'],
        ];
    }

    #[DataProvider('merchantConsoleRoutes')]
    public function test_merchant_console_pages_have_no_decorative_bullets_or_pulsing_dots(string $routeName): void
    {
        $actor = in_array($routeName, ['merchant.queues', 'merchant.display.index'], true) ? $this->operator : $this->merchant;
        $response = $this->actingAs($actor)->get(route($routeName));

        $response->assertOk()
            ->assertDontSee('•')
            ->assertDontSee('·')
            ->assertDontSee('animate-ping', false)
            ->assertDontSee('animate-pulse', false);
    }

    public function test_merchant_page_titles_do_not_duplicate_brand_suffix(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.services.index'));

        $response->assertOk()
            ->assertSee('<title>Manajemen Layanan — AntreIn UMKM</title>', false);
    }

    public function test_queue_console_counter_filter_reads_counter_id_query(): void
    {
        $response = $this->actingAs($this->operator)->get(route('merchant.queues', ['counter_id' => $this->counter->id]));

        $response->assertOk()
            ->assertSee('<option value="'.$this->counter->id.'" selected', false);
    }

    public function test_report_custom_range_inputs_are_disabled_for_quick_periods(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.reports', ['period' => '30_days']));

        $response->assertOk()
            ->assertSee('<input type="hidden" name="period" value="custom" disabled', false);
    }

    public function test_input_error_component_renders_messages_array(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag);

        $html = Blade::render(
            '<x-input-error :messages="$messages" />',
            ['messages' => ['Nama loket wajib diisi.'], 'errors' => $errors],
        );

        $this->assertStringContainsString('Nama loket wajib diisi.', $html);
    }
}
