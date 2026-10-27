<?php
namespace Core\Support;

class Barcode {
    private static function load(): void {
        $dir = dirname(__DIR__, 2) . '/assets/TCPDF-main/';
        require_once $dir . 'tcpdf_barcodes_1d.php';
        require_once $dir . 'tcpdf_barcodes_2d.php';
    }

    /**
     * Strip the XML prolog, fix the viewBox so the SVG scales to its container
     * and size it with $style (e.g. "width:100%;height:14mm").
     */
    private static function fit(string $svg, string $class, string $style, bool $stretch): string {
        $svg = preg_replace('/^.*?(?=<svg)/s', '', $svg);
        if (!preg_match('/<svg width="([\d.]+)" height="([\d.]+)"/', $svg, $m)) {
            return $svg;
        }
        $aspect = $stretch ? 'none' : 'xMidYMid meet';
        return preg_replace(
            '/<svg width="[\d.]+" height="[\d.]+"/',
            "<svg class='{$class}' style='{$style}' viewBox='0 0 {$m[1]} {$m[2]}' preserveAspectRatio='{$aspect}' shape-rendering='crispEdges'",
            $svg,
            1
        );
    }

    /**
     * Inline SVG Code 128 barcode (scannable). $height is the printed bar height in px,
     * the SVG fills the width of its container.
     */
    public static function svg(string $code, int $height = 40, int $scale = 2): string {
        $code = trim($code);
        if ($code === '') {
            return '';
        }
        self::load();
        $bc = new \TCPDFBarcode($code, 'C128');
        $svg = $bc->getBarcodeSVGcode(max(1, $scale), 60, '#111827');
        $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $out = self::fit($svg, 'cz-barcode-svg', "width:100%;height:{$height}px;display:block;", true);
        return "<div class='cz-barcode-wrap' style='text-align:center;line-height:1;'>{$out}"
            . "<div style='font-family:monospace;font-size:10px;margin-top:1px;color:#374151;'>{$safe}</div></div>";
    }

    /**
     * Inline SVG QR code (scannable, error-correction M), rendered as a $size px square.
     */
    public static function qrSvg(string $data, int $size = 120): string {
        if ($data === '') {
            return '';
        }
        self::load();
        $qr = new \TCPDF2DBarcode($data, 'QRCODE,M');
        $svg = $qr->getBarcodeSVGcode(2, 2, '#111827');
        return self::fit($svg, 'cz-qr-svg', "width:{$size}px;height:{$size}px;display:block;background:#fff;", false);
    }
}
