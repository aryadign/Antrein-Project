<?php

namespace Tests\Feature\Display;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicQueueDisplayLayoutTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private string $displayToken;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik',
            'is_active' => true,
        ]);

        $owner = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Klinik Renon',
            'slug' => 'klinik-renon',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->displayToken = $this->business->generateDisplayToken();
    }

    public function test_display_renders_take_queue_qr_card(): void
    {
        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertOk()
            ->assertSee('Scan untuk Ambil Antrean dari HP')
            ->assertSee('data-testid="display-take-queue-qr"', false)
            ->assertSee('href="'.route('customer.queue.create', ['slug' => $this->business->slug]).'"', false)
            ->assertSee('<svg', false);
    }

    public function test_display_has_no_pulsing_dots_or_bullet_separators(): void
    {
        $response = $this->get(route('display.show', ['token' => $this->displayToken]));

        $response->assertOk()
            ->assertDontSee('•')
            ->assertDontSee('animate-ping', false)
            ->assertDontSee('animate-pulse', false);
    }

    public function test_display_data_endpoint_does_not_expose_qr_payload(): void
    {
        $response = $this->getJson(route('display.data', ['token' => $this->displayToken]));

        $response->assertOk()
            ->assertJsonMissingPath('data.qr')
            ->assertJsonStructure(['success', 'data' => ['business', 'server_time', 'counters']]);
    }
}
