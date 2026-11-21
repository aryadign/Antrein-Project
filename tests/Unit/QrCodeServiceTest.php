<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QrCodeServiceTest extends TestCase
{
    /**
     * Valid 15-bit format strings for error correction level M, indexed by mask.
     *
     * @var list<string>
     */
    private const FORMAT_M = [
        '101010000010010', '101000100100101', '101111001111100', '101101101001011',
        '100010111111001', '100000011001110', '100111110010111', '100101010100000',
    ];

    public function test_matrix_matches_reference_encoder_module_for_module(): void
    {
        $matrix = (new QrCodeService)->encodeToMatrix('http://localhost:8000/q/kopi-senja-roastery');

        // Golden matrix produced by the reference `qrcode` library (level M, mask 0) and decoded by jsQR.
        $expected = [
            '111111100110000100111011001111111',
            '100000101101010111010101001000001',
            '101110100110000000001000101011101',
            '101110100010110001010100001011101',
            '101110101100000001000100001011101',
            '100000100110000000100010001000001',
            '111111101010101010101010101111111',
            '000000000011011011101110100000000',
            '101010100111110111010101100010010',
            '011010011100100000100010111000011',
            '110010100101010001001100101100111',
            '111000010110100100010001101010011',
            '100001101000100100000001111101000',
            '011101011001110101010101001001001',
            '111010111011101000100110010111111',
            '100001011011001101110111010101010',
            '110010110111001011100101111000000',
            '111000011111001011000110111100011',
            '011001110110101010101010011100111',
            '111111010010001101110111010010011',
            '000000111011111010101010011001010',
            '001111001100111110111110111000111',
            '100110111110000111011000110101111',
            '010011000111100011101100001010001',
            '100101100101000101010110111111000',
            '000000001101011010000011100011011',
            '111111100111010001001101101010111',
            '100000100011000010010000100011000',
            '101110101110111100010000111111011',
            '101110100110111100010000100111011',
            '101110101111101001100110100010001',
            '100000100010100101110100100110010',
            '111111101111101011000111111000011',
        ];

        $this->assertSame($expected, array_map(fn (array $row): string => implode('', $row), $matrix));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function textLengths(): array
    {
        return ['version 2' => [20], 'version 4' => [50], 'version 6' => [100], 'version 7' => [107], 'version 10' => [200]];
    }

    #[DataProvider('textLengths')]
    public function test_both_format_copies_are_valid_and_dark_module_is_kept(int $length): void
    {
        $matrix = (new QrCodeService)->encodeToMatrix('https://a.id/q/'.str_repeat('x', $length - 15));
        $size = count($matrix);

        $firstCopy = $matrix[8][0].$matrix[8][1].$matrix[8][2].$matrix[8][3].$matrix[8][4].$matrix[8][5].$matrix[8][7].$matrix[8][8]
            .$matrix[7][8].$matrix[5][8].$matrix[4][8].$matrix[3][8].$matrix[2][8].$matrix[1][8].$matrix[0][8];

        $secondCopy = '';
        for ($row = $size - 1; $row >= $size - 7; $row--) {
            $secondCopy .= $matrix[$row][8];
        }
        for ($col = $size - 8; $col < $size; $col++) {
            $secondCopy .= $matrix[8][$col];
        }

        $this->assertContains($firstCopy, self::FORMAT_M);
        $this->assertSame($firstCopy, $secondCopy);
        $this->assertSame(1, $matrix[$size - 8][8], 'Dark module must stay dark.');
    }

    public function test_version_seven_and_up_carry_version_information(): void
    {
        $matrix = (new QrCodeService)->encodeToMatrix('https://a.id/q/'.str_repeat('x', 92));
        $size = count($matrix);
        $this->assertSame(45, $size);

        $bits = 0;
        for ($i = 17; $i >= 0; $i--) {
            $bits = ($bits << 1) | $matrix[intdiv($i, 3)][$size - 11 + ($i % 3)];
        }

        $this->assertSame(0x07C94, $bits);
    }
}
