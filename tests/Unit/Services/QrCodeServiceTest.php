<?php

namespace Tests\Unit\Services;

use App\Services\QrCodeService;
use PHPUnit\Framework\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_it_generates_valid_svg_qr_code(): void
    {
        $service = new QrCodeService();
        $svg = $service->generateSvg('https://antrein.id/q/barbershop-garasi');

        $this->assertNotEmpty($svg);
        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('width="256"', $svg);
        $this->assertStringContainsString('height="256"', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertStringEndsWith('</svg>', trim($svg));
    }

    public function test_it_generates_matrix_with_consistent_dimensions(): void
    {
        $service = new QrCodeService();
        $matrix = $service->encodeToMatrix('https://antrein.id/q/barbershop-garasi');

        $this->assertIsArray($matrix);
        $size = count($matrix);
        $this->assertGreaterThan(20, $size);

        foreach ($matrix as $row) {
            $this->assertCount($size, $row);
            foreach ($row as $cell) {
                $this->assertContains($cell, [0, 1]);
            }
        }
    }
}

