<?php

namespace App\Services;

class QrCodeService
{
    /**
     * Galois Field 256 tables
     *
     * @var list<int>
     */
    private static array $exp = [];

    /**
     * @var list<int>
     */
    private static array $log = [];

    /**
     * QR Code Version capacities for Byte Mode with EC level M
     *
     * @var array<int, array{total: int, ec: int, remainder: int, blocks: list<array{0: int, 1: int}>}>
     */
    private static array $versionDataM = [
        1 => ['total' => 26, 'ec' => 10, 'remainder' => 0, 'blocks' => [[1, 16]]],
        2 => ['total' => 44, 'ec' => 16, 'remainder' => 7, 'blocks' => [[1, 28]]],
        3 => ['total' => 70, 'ec' => 26, 'remainder' => 7, 'blocks' => [[1, 44]]],
        4 => ['total' => 100, 'ec' => 18, 'remainder' => 7, 'blocks' => [[2, 32]]],
        5 => ['total' => 134, 'ec' => 24, 'remainder' => 7, 'blocks' => [[2, 43]]],
        6 => ['total' => 172, 'ec' => 16, 'remainder' => 7, 'blocks' => [[4, 27]]],
        7 => ['total' => 196, 'ec' => 18, 'remainder' => 0, 'blocks' => [[4, 31]]],
        8 => ['total' => 242, 'ec' => 22, 'remainder' => 0, 'blocks' => [[2, 38], [2, 39]]],
        9 => ['total' => 292, 'ec' => 22, 'remainder' => 0, 'blocks' => [[3, 36], [2, 37]]],
        10 => ['total' => 346, 'ec' => 26, 'remainder' => 0, 'blocks' => [[4, 43], [1, 44]]],
    ];

    /**
     * Alignment pattern coordinates for each version
     *
     * @var array<int, list<int>>
     */
    private static array $alignmentCoords = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Format information strings for Level M (mask 0 to 7)
     *
     * @var array<int, list<int>>
     */
    private static array $formatBits = [
        0 => [1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0],
        1 => [1, 0, 1, 0, 0, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0],
        2 => [1, 0, 1, 1, 1, 1, 0, 1, 0, 1, 0, 1, 0, 0, 1],
        3 => [1, 0, 1, 1, 0, 0, 1, 1, 1, 0, 0, 0, 1, 1, 1],
        4 => [1, 1, 0, 0, 1, 1, 0, 0, 0, 1, 0, 1, 1, 1, 1],
        5 => [1, 1, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1],
        6 => [1, 1, 0, 1, 1, 0, 0, 1, 0, 0, 1, 0, 1, 0, 0],
        7 => [1, 1, 0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0],
    ];

    private static function initGf(): void
    {
        if (! empty(self::$exp)) {
            return;
        }

        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$exp[$i + 255] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 256) {
                $x ^= 0x11d;
            }
        }
    }

    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    /**
     * @return list<int>
     */
    private static function rsGeneratorPoly(int $ecCount): array
    {
        $poly = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $next = [1, self::$exp[$i]];
            $res = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $res[$j] ^= self::gfMul($poly[$j], $next[0]);
                $res[$j + 1] ^= self::gfMul($poly[$j], $next[1]);
            }
            $poly = $res;
        }

        return $poly;
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    private static function calculateEc(array $data, int $ecCount): array
    {
        self::initGf();
        $generator = self::rsGeneratorPoly($ecCount);
        $remainder = array_merge($data, array_fill(0, $ecCount, 0));

        $dataCount = count($data);
        $genCount = count($generator);

        for ($i = 0; $i < $dataCount; $i++) {
            $factor = $remainder[$i];
            if ($factor !== 0) {
                for ($j = 0; $j < $genCount; $j++) {
                    $remainder[$i + $j] ^= self::gfMul($generator[$j], $factor);
                }
            }
        }

        return array_values(array_slice($remainder, $dataCount));
    }

    /**
     * Generate an SVG string representation of a QR code for the given text.
     *
     * @param  string  $text Content to encode (e.g. public queue URL)
     * @param  int  $size Width and height of the rendered SVG in pixels
     * @param  string  $darkColor Hex color for dark modules
     * @param  string  $lightColor Hex color for background
     * @return string Valid SVG markup
     */
    public function generateSvg(
        string $text,
        int $size = 256,
        string $darkColor = '#0F172A',
        string $lightColor = '#FFFFFF'
    ): string {
        $matrix = $this->encodeToMatrix($text);
        $moduleCount = count($matrix);
        $quietZone = 4;
        $totalModules = $moduleCount + ($quietZone * 2);
        $viewBox = "0 0 {$totalModules} {$totalModules}";

        $rects = [];
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = $c + $quietZone;
                    $y = $r + $quietZone;
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\" />";
                }
            }
        }

        $rectsStr = implode("\n        ", $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="{$viewBox}" width="{$size}" height="{$size}" fill="none" role="img" aria-label="QR Code">
    <rect width="100%" height="100%" fill="{$lightColor}" />
    <g fill="{$darkColor}">
        {$rectsStr}
    </g>
</svg>
SVG;
    }

    /**
     * Encode text into a 2D binary matrix (0 = light, 1 = dark).
     *
     * @return list<list<int>>
     */
    public function encodeToMatrix(string $text): array
    {
        self::initGf();

        $unpacked = unpack('C*', $text);
        $bytes = is_array($unpacked) ? array_values($unpacked) : [];
        $dataLen = count($bytes);

        $version = null;
        foreach (self::$versionDataM as $ver => $info) {
            $totalDataCw = 0;
            foreach ($info['blocks'] as $b) {
                $totalDataCw += $b[0] * $b[1];
            }
            $headerBits = ($ver <= 9 ? 8 : 16) + 4;
            $maxDataBytes = (int) (($totalDataCw * 8 - $headerBits) / 8);

            if ($dataLen <= $maxDataBytes) {
                $version = $ver;
                break;
            }
        }

        if ($version === null) {
            $version = 10;
            $bytes = array_slice($bytes, 0, 180);
            $dataLen = count($bytes);
        }

        $vInfo = self::$versionDataM[$version];

        $bitStream = [];
        array_push($bitStream, 0, 1, 0, 0);

        $countBitsLen = ($version <= 9) ? 8 : 16;
        for ($i = $countBitsLen - 1; $i >= 0; $i--) {
            $bitStream[] = ($dataLen >> $i) & 1;
        }

        foreach ($bytes as $b) {
            for ($i = 7; $i >= 0; $i--) {
                $bitStream[] = ($b >> $i) & 1;
            }
        }

        $totalDataCw = 0;
        foreach ($vInfo['blocks'] as $b) {
            $totalDataCw += $b[0] * $b[1];
        }
        $totalDataBits = $totalDataCw * 8;

        $termLen = min(4, $totalDataBits - count($bitStream));
        for ($i = 0; $i < $termLen; $i++) {
            $bitStream[] = 0;
        }

        while (count($bitStream) % 8 !== 0) {
            $bitStream[] = 0;
        }

        $dataCodewords = [];
        $streamCount = count($bitStream);
        for ($i = 0; $i < $streamCount; $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bitStream[$i + $j];
            }
            $dataCodewords[] = $val;
        }

        $padBytes = [0xEC, 0x11];
        $p = 0;
        while (count($dataCodewords) < $totalDataCw) {
            $dataCodewords[] = $padBytes[$p % 2];
            $p++;
        }

        $blocks = [];
        $cwOffset = 0;
        foreach ($vInfo['blocks'] as $blockConfig) {
            $numBlocks = $blockConfig[0];
            $blockSize = $blockConfig[1];
            for ($i = 0; $i < $numBlocks; $i++) {
                $blockData = array_slice($dataCodewords, $cwOffset, $blockSize);
                $cwOffset += $blockSize;
                $ec = self::calculateEc($blockData, $vInfo['ec']);
                $blocks[] = [
                    'data' => $blockData,
                    'ec' => $ec,
                ];
            }
        }

        $finalCodewords = [];
        $maxDataLen = 0;
        foreach ($blocks as $bl) {
            $maxDataLen = max($maxDataLen, count($bl['data']));
        }

        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($blocks as $bl) {
                if ($i < count($bl['data'])) {
                    $finalCodewords[] = $bl['data'][$i];
                }
            }
        }

        for ($i = 0; $i < $vInfo['ec']; $i++) {
            foreach ($blocks as $bl) {
                $finalCodewords[] = $bl['ec'][$i];
            }
        }

        $size = 17 + (4 * $version);
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        $placeFinder = function (int $row, int $col) use (&$matrix, &$reserved, $size): void {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $currR = $row + $r;
                    $currC = $col + $c;
                    if ($currR < 0 || $currR >= $size || $currC < 0 || $currC >= $size) {
                        continue;
                    }
                    $reserved[$currR][$currC] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        if ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                            $matrix[$currR][$currC] = 1;
                        } else {
                            $matrix[$currR][$currC] = 0;
                        }
                    } else {
                        $matrix[$currR][$currC] = 0;
                    }
                }
            }
        };

        $placeFinder(0, 0);
        $placeFinder(0, $size - 7);
        $placeFinder($size - 7, 0);

        $coords = self::$alignmentCoords[$version];
        foreach ($coords as $ar) {
            foreach ($coords as $ac) {
                if (($ar < 9 && $ac < 9) || ($ar < 9 && $ac > $size - 10) || ($ar > $size - 10 && $ac < 9)) {
                    continue;
                }
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $reserved[$ar + $r][$ac + $c] = true;
                        if (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) {
                            $matrix[$ar + $r][$ac + $c] = 1;
                        } else {
                            $matrix[$ar + $r][$ac + $c] = 0;
                        }
                    }
                }
            }
        }

        for ($i = 8; $i < $size - 8; $i++) {
            if (! $reserved[6][$i]) {
                $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
                $reserved[6][$i] = true;
            }
            if (! $reserved[$i][6]) {
                $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
                $reserved[$i][6] = true;
            }
        }

        $matrix[(4 * $version) + 9][8] = 1;
        $reserved[(4 * $version) + 9][8] = true;

        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }

        $dataBits = [];
        foreach ($finalCodewords as $cw) {
            for ($i = 7; $i >= 0; $i--) {
                $dataBits[] = ($cw >> $i) & 1;
            }
        }
        for ($i = 0; $i < $vInfo['remainder']; $i++) {
            $dataBits[] = 0;
        }

        $bitIdx = 0;
        $numBits = count($dataBits);
        $up = true;

        for ($rightCol = $size - 1; $rightCol > 0; $rightCol -= 2) {
            if ($rightCol === 6) {
                $rightCol = 5;
            }
            $leftCol = $rightCol - 1;

            $rowRange = $up ? range($size - 1, 0, -1) : range(0, $size - 1);
            foreach ($rowRange as $r) {
                foreach ([$rightCol, $leftCol] as $c) {
                    if (! $reserved[$r][$c]) {
                        $matrix[$r][$c] = ($bitIdx < $numBits) ? $dataBits[$bitIdx++] : 0;
                    }
                }
            }
            $up = ! $up;
        }

        $bestMatrix = $matrix;
        $minPenalty = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $maskedMatrix = $matrix;
            for ($r = 0; $r < $size; $r++) {
                for ($c = 0; $c < $size; $c++) {
                    if (! $reserved[$r][$c]) {
                        $invert = match ($mask) {
                            0 => ($r + $c) % 2 === 0,
                            1 => $r % 2 === 0,
                            2 => $c % 3 === 0,
                            3 => ($r + $c) % 3 === 0,
                            4 => ((int) ($r / 2) + (int) ($c / 3)) % 2 === 0,
                            5 => ($r * $c) % 2 + ($r * $c) % 3 === 0,
                            6 => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0,
                            7 => (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0,
                        };
                        if ($invert) {
                            $maskedMatrix[$r][$c] ^= 1;
                        }
                    }
                }
            }

            $fBits = self::$formatBits[$mask];
            $maskedMatrix[8][0] = $fBits[0];
            $maskedMatrix[8][1] = $fBits[1];
            $maskedMatrix[8][2] = $fBits[2];
            $maskedMatrix[8][3] = $fBits[3];
            $maskedMatrix[8][4] = $fBits[4];
            $maskedMatrix[8][5] = $fBits[5];
            $maskedMatrix[8][7] = $fBits[6];
            $maskedMatrix[8][8] = $fBits[7];
            $maskedMatrix[7][8] = $fBits[8];
            $maskedMatrix[5][8] = $fBits[9];
            $maskedMatrix[4][8] = $fBits[10];
            $maskedMatrix[3][8] = $fBits[11];
            $maskedMatrix[2][8] = $fBits[12];
            $maskedMatrix[1][8] = $fBits[13];
            $maskedMatrix[0][8] = $fBits[14];

            for ($i = 0; $i < 7; $i++) {
                $maskedMatrix[8][$size - 1 - $i] = $fBits[14 - $i];
            }
            for ($i = 0; $i < 8; $i++) {
                $maskedMatrix[$size - 8 + $i][8] = $fBits[7 - $i];
            }

            $penalty = 0;
            for ($r = 0; $r < $size; $r++) {
                $runVal = -1;
                $runLen = 0;
                for ($c = 0; $c < $size; $c++) {
                    if ($maskedMatrix[$r][$c] === $runVal) {
                        $runLen++;
                        if ($runLen === 5) {
                            $penalty += 3;
                        } elseif ($runLen > 5) {
                            $penalty += 1;
                        }
                    } else {
                        $runVal = $maskedMatrix[$r][$c];
                        $runLen = 1;
                    }
                }
            }

            if ($penalty < $minPenalty) {
                $minPenalty = $penalty;
                $bestMatrix = $maskedMatrix;
            }
        }

        return $bestMatrix;
    }
}

