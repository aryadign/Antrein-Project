<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $merchantA;
    private Business $businessA;
    private Service $serviceA;
    private DailyQueue $queueA;

    private User $merchantB;
    private Business $businessB;
    private Service $serviceB;
    private DailyQueue $queueB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kecantikan & Barbershop',
            'slug' => 'kecantikan-barbershop',
            'is_active' => true,
        ]);

        // Merchant A Setup
        $this->merchantA = User::factory()->merchant()->create();
        $this->businessA = Business::create([
            'user_id' => $this->merchantA->id,
            'category_id' => $category->id,
            'name' => 'Barber AntreIn Sejahtera',
            'slug' => 'barber-antrein-sejahtera',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->serviceA = Service::create([
            'business_id' => $this->businessA->id,
            'name' => 'Gentleman Haircut',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->queueA = DailyQueue::create([
            'business_id' => $this->businessA->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 2,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        // Create completed ticket
        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Pertama',
            'customer_phone' => '081111111111',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'token-secret-alpha-123456',
            'created_at' => Carbon::now('Asia/Makassar')->subMinutes(60),
            'called_at' => Carbon::now('Asia/Makassar')->subMinutes(40),
            'service_started_at' => Carbon::now('Asia/Makassar')->subMinutes(35),
            'completed_at' => Carbon::now('Asia/Makassar')->subMinutes(10),
        ]);

        // Create ticket with formula injection risk characters
        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => '=cmd|"/C calc"!A0',
            'customer_phone' => '+628222222222',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'token-secret-beta-789012',
            'created_at' => Carbon::now('Asia/Makassar')->subMinutes(15),
        ]);

        // Merchant B Setup (Tenant Isolation)
        $this->merchantB = User::factory()->merchant()->create();
        $this->businessB = Business::create([
            'user_id' => $this->merchantB->id,
            'category_id' => $category->id,
            'name' => 'Klinik Cantik Merchant B',
            'slug' => 'klinik-cantik-merchant-b',
            'phone' => '089999999999',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->serviceB = Service::create([
            'business_id' => $this->businessB->id,
            'name' => 'Facial Treatment B',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->queueB = DailyQueue::create([
            'business_id' => $this->businessB->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        QueueTicket::create([
            'business_id' => $this->businessB->id,
            'daily_queue_id' => $this->queueB->id,
            'service_id' => $this->serviceB->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Rahasia B',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'token-secret-merchant-b',
            'created_at' => Carbon::now('Asia/Makassar')->subMinutes(5),
        ]);

        // Admin User Setup
        $this->admin = User::factory()->admin()->create();
    }

    // ==========================================
    // CSV EXPORT TESTS (P1-06)
    // ==========================================

    public function test_merchant_can_export_csv_successfully(): void
    {
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', ['period' => 'today']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // Must start with UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Contains business and report metadata
        $this->assertStringContainsString('Barber AntreIn Sejahtera', $content);
        $this->assertStringContainsString('Gentleman Haircut', $content);
        $this->assertStringContainsString('Pelanggan Pertama', $content);
        $this->assertStringContainsString('A-001', $content);
        $this->assertStringContainsString('Asia/Makassar', $content);
    }

    public function test_csv_export_prevents_formula_injection(): void
    {
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', ['period' => 'today']));

        $content = $response->streamedContent();

        // The malicious string `=cmd|"/C calc"!A0` must be prepended with a single quote
        $this->assertStringContainsString("'=cmd|", $content);
        $this->assertStringNotContainsString("\n=cmd", $content);
    }

    public function test_csv_export_does_not_leak_sensitive_tokens(): void
    {
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', ['period' => 'today']));

        $content = $response->streamedContent();

        // Must NOT expose internal tokens or credentials
        $this->assertStringNotContainsString('token-secret-alpha-123456', $content);
        $this->assertStringNotContainsString('token-secret-beta-789012', $content);
    }

    public function test_csv_export_respects_custom_date_range(): void
    {
        $today = Carbon::today('Asia/Makassar')->toDateString();

        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', [
            'period' => 'custom',
            'start_date' => $today,
            'end_date' => $today,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString($today . ' s/d ' . $today, $content);
    }

    public function test_csv_export_cross_tenant_isolation(): void
    {
        // Merchant A exports report: MUST NOT contain Merchant B's data
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', [
            'period' => 'today',
            'business_id' => $this->businessB->id, // Tampering attempt
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Barber AntreIn Sejahtera', $content);
        $this->assertStringNotContainsString('Klinik Cantik Merchant B', $content);
        $this->assertStringNotContainsString('Facial Treatment B', $content);
        $this->assertStringNotContainsString('Pelanggan Rahasia B', $content);
        $this->assertStringNotContainsString('B-001', $content);
    }

    public function test_unauthenticated_user_cannot_export_csv(): void
    {
        $response = $this->get(route('merchant.reports.export.csv'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_merchant_csv_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('merchant.reports.export.csv'));
        $response->assertStatus(403);
    }

    public function test_csv_export_handles_invalid_date_range_gracefully(): void
    {
        // start_date greater than end_date
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv', [
            'period' => 'custom',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
        ]));

        $response->assertRedirect(route('merchant.reports', ['period' => '7_days']));
        $response->assertSessionHasErrors('date_range');
    }

    // ==========================================
    // PDF EXPORT TESTS (P1-07)
    // ==========================================

    public function test_merchant_can_export_pdf_successfully(): void
    {
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.pdf', ['period' => 'today']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));

        // Content must be a valid PDF binary
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_pdf_export_cross_tenant_isolation(): void
    {
        // Merchant A requests PDF with parameter tampering
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.pdf', [
            'period' => 'today',
            'business_id' => $this->businessB->id,
        ]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('barber-antrein-sejahtera', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('klinik-cantik-merchant-b', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_unauthenticated_user_cannot_export_pdf(): void
    {
        $response = $this->get(route('merchant.reports.export.pdf'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_merchant_pdf_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('merchant.reports.export.pdf'));
        $response->assertStatus(403);
    }

    public function test_pdf_export_handles_empty_dataset_gracefully(): void
    {
        // Clean queue for Merchant B and test PDF generation with 0 tickets
        QueueTicket::where('business_id', $this->businessB->id)->delete();

        $response = $this->actingAs($this->merchantB)->get(route('merchant.reports.export.pdf', ['period' => 'today']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_export_renders_long_report_with_many_tickets(): void
    {
        // Create 25 additional tickets to test multi-page table handling
        for ($i = 3; $i <= 25; $i++) {
            QueueTicket::create([
                'business_id' => $this->businessA->id,
                'daily_queue_id' => $this->queueA->id,
                'service_id' => $this->serviceA->id,
                'ticket_number' => sprintf('A-%03d', $i),
                'sequence_number' => $i,
                'customer_name' => 'Pelanggan Uji ' . $i,
                'source' => QueueTicket::SOURCE_WALK_IN,
                'status' => QueueTicket::STATUS_SELESAI,
                'access_token' => 'token-test-' . $i,
                'created_at' => Carbon::now('Asia/Makassar')->subMinutes(50),
                'service_started_at' => Carbon::now('Asia/Makassar')->subMinutes(30),
                'completed_at' => Carbon::now('Asia/Makassar')->subMinutes(10),
            ]);
        }

        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.pdf', ['period' => 'today']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_export_defaults_to_seven_days_when_period_is_omitted_or_invalid(): void
    {
        // Omitted period
        $responseCsv = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.csv'));
        $responseCsv->assertOk();
        $this->assertStringContainsString('7 Hari Terakhir', $responseCsv->streamedContent());

        // Invalid period
        $responsePdf = $this->actingAs($this->merchantA)->get(route('merchant.reports.export.pdf', ['period' => 'unknown_period']));
        $responsePdf->assertOk();
        $this->assertEquals('application/pdf', $responsePdf->headers->get('Content-Type'));
    }

    public function test_merchant_reports_web_view_displays_export_buttons(): void
    {
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports', ['period' => '30_days']));

        $response->assertOk();
        $response->assertSee('Ekspor CSV');
        $response->assertSee('Ekspor PDF');
        $response->assertSee(route('merchant.reports.export.csv', ['period' => '30_days']));
        $response->assertSee(route('merchant.reports.export.pdf', ['period' => '30_days']));
    }
}
