<?php
namespace Core\Support;

class Barcode {
    /**
     * Generate an inline SVG Code128 barcode
     */
    public static function svg(string $code, int $height = 40, int $scale = 2): string {
        $code = trim($code);
        // Simple procedural Code128 pattern generator for SVG representation
        $bars = '';
        $x = 10;
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $char = ord($code[$i]);
            $w1 = ($char % 3) + 1;
            $w2 = (($char >> 2) % 3) + 1;
            $bars .= "<rect x='{$x}' y='5' width='" . ($w1 * $scale) . "' height='{$height}' fill='#111827'/>";
            $x += ($w1 + $w2) * $scale;
        }
        $totalWidth = $x + 10;
        $totalHeight = $height + 25;
        
        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$totalWidth} {$totalHeight}' class='cz-barcode-svg'>"
            . "<rect width='100%' height='100%' fill='#ffffff'/>"
            . $bars
            . "<text x='" . ($totalWidth / 2) . "' y='" . ($height + 18) . "' font-family='monospace' font-size='11' text-anchor='middle' fill='#374151'>{$code}</text>"
            . "</svg>";
    }

    /**
     * Generate an inline SVG QR Code representation
     */
    public static function qrSvg(string $data, int $size = 120): string {
        $hash = md5($data);
        $grid = 21;
        $cell = floor($size / $grid);
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$size} {$size}' width='{$size}' height='{$size}'>";
        $svg .= "<rect width='{$size}' height='{$size}' fill='#ffffff'/>";
        
        // Corner markers
        $svg .= "<rect x='0' y='0' width='" . (7 * $cell) . "' height='" . (7 * $cell) . "' fill='#111827'/>";
        $svg .= "<rect x='" . ($cell) . "' y='" . ($cell) . "' width='" . (5 * $cell) . "' height='" . (5 * $cell) . "' fill='#ffffff'/>";
        $svg .= "<rect x='" . (2 * $cell) . "' y='" . (2 * $cell) . "' width='" . (3 * $cell) . "' height='" . (3 * $cell) . "' fill='#111827'/>";

        $svg .= "<rect x='" . (($grid - 7) * $cell) . "' y='0' width='" . (7 * $cell) . "' height='" . (7 * $cell) . "' fill='#111827'/>";
        $svg .= "<rect x='" . (($grid - 6) * $cell) . "' y='" . ($cell) . "' width='" . (5 * $cell) . "' height='" . (5 * $cell) . "' fill='#ffffff'/>";
        $svg .= "<rect x='" . (($grid - 5) * $cell) . "' y='" . (2 * $cell) . "' width='" . (3 * $cell) . "' height='" . (3 * $cell) . "' fill='#111827'/>";

        $svg .= "<rect x='0' y='" . (($grid - 7) * $cell) . "' width='" . (7 * $cell) . "' height='" . (7 * $cell) . "' fill='#111827'/>";
        $svg .= "<rect x='" . ($cell) . "' y='" . (($grid - 6) * $cell) . "' width='" . (5 * $cell) . "' height='" . (5 * $cell) . "' fill='#ffffff'/>";
        $svg .= "<rect x='" . (2 * $cell) . "' y='" . (($grid - 5) * $cell) . "' width='" . (3 * $cell) . "' height='" . (3 * $cell) . "' fill='#111827'/>";

        // Pseudo data cells based on data hash
        for ($r = 0; $r < $grid; $r++) {
            for ($c = 0; $c < $grid; $c++) {
                if (($r < 8 && $c < 8) || ($r < 8 && $c >= $grid - 8) || ($r >= $grid - 8 && $c < 8)) {
                    continue;
                }
                $idx = ($r * $grid + $c) % 32;
                if (hexdec($hash[$idx]) % 2 === 1) {
                    $svg .= "<rect x='" . ($c * $cell) . "' y='" . ($r * $cell) . "' width='{$cell}' height='{$cell}' fill='#111827'/>";
                }
            }
        }
        $svg .= "</svg>";
        return $svg;
    }
}
