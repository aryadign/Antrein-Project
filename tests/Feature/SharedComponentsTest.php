<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SharedComponentsTest extends TestCase
{
    public function test_queue_ticket_exposes_a_label_and_tone_for_every_status(): void
    {
        $expected = [
            QueueTicket::STATUS_MENUNGGU => ['Menunggu', 'warning'],
            QueueTicket::STATUS_DIPANGGIL => ['Dipanggil', 'info'],
            QueueTicket::STATUS_SEDANG_DILAYANI => ['Sedang dilayani', 'brand'],
            QueueTicket::STATUS_SELESAI => ['Selesai', 'success'],
            QueueTicket::STATUS_DILEWATI => ['Dilewati', 'neutral'],
            QueueTicket::STATUS_TIDAK_HADIR => ['Tidak hadir', 'caution'],
            QueueTicket::STATUS_DIBATALKAN => ['Dibatalkan', 'danger'],
        ];

        $this->assertSame(array_keys($expected), array_keys(QueueTicket::statusPresentation()));

        foreach ($expected as $status => [$label, $tone]) {
            $ticket = new QueueTicket(['status' => $status]);

            $this->assertSame($label, $ticket->statusLabel());
            $this->assertSame($tone, $ticket->statusTone());
            $this->assertSame($label, QueueTicket::labelForStatus($status));
            $this->assertSame($tone, QueueTicket::toneForStatus($status));
        }
    }

    public function test_queue_ticket_status_helpers_accept_english_aliases_and_unknown_values(): void
    {
        $this->assertSame('Sedang dilayani', QueueTicket::labelForStatus('serving'));
        $this->assertSame('caution', QueueTicket::toneForStatus('no_show'));
        $this->assertTrue(QueueTicket::isTicketStatus('Cancelled'));
        $this->assertFalse(QueueTicket::isTicketStatus('buka'));
        $this->assertSame('Lainnya', QueueTicket::labelForStatus('lainnya'));
        $this->assertSame('neutral', QueueTicket::toneForStatus('lainnya'));
    }

    public function test_status_badge_renders_ticket_status_from_the_model_map(): void
    {
        $fromString = Blade::render('<x-status-badge status="tidak_hadir" />');
        $fromModel = Blade::render('<x-status-badge :status="$ticket" />', [
            'ticket' => new QueueTicket(['status' => QueueTicket::STATUS_SEDANG_DILAYANI]),
        ]);
        $businessStatus = Blade::render('<x-status-badge status="buka" size="sm" />');

        $this->assertStringContainsString('Tidak hadir', $fromString);
        $this->assertStringContainsString('bg-orange-50', $fromString);
        $this->assertStringContainsString('Sedang dilayani', $fromModel);
        $this->assertStringContainsString('Buka', $businessStatus);
        $this->assertStringNotContainsString('text-[11px]', $businessStatus);
    }

    public function test_stat_tile_is_calm_and_stat_card_is_an_alias(): void
    {
        $tile = Blade::render('<x-stat-tile label="Selesai" value="18" hint="Hari ini" tone="success" />');
        $card = Blade::render('<x-stat-card title="Total Antrean" value="48" description="Dari kuota 60" trend="+12%" />');

        $this->assertStringContainsString('data-stat-tile', $tile);
        $this->assertStringContainsString('data-tone="success"', $tile);
        $this->assertStringContainsString('shadow-card', $tile);
        $this->assertStringNotContainsString('bg-linear-to-br', $tile);

        $this->assertStringContainsString('data-stat-tile', $card);
        $this->assertStringContainsString('Total Antrean', $card);
        $this->assertStringContainsString('Dari kuota 60', $card);
        $this->assertStringContainsString('+12%', $card);
    }
}
