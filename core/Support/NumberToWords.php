<?php
namespace Core\Support;

class NumberToWords {
    private static array $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    private static array $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];

    public static function inWords(float $amount, string $currency = 'INR'): string {
        $amount = round($amount, 2);
        $whole = (int)floor($amount);
        $fraction = (int)round(($amount - $whole) * 100);

        if ($whole === 0 && $fraction === 0) {
            return 'Zero Rupees Only';
        }

        $words = self::convertIndianWhole($whole);
        $result = $words ? $words . ' Rupees' : '';

        if ($fraction > 0) {
            $fractionWords = self::convertGroup($fraction);
            $result .= ($result ? ' and ' : '') . $fractionWords . ' Paise';
        }

        return trim($result) . ' Only';
    }

    public static function convert(float $amount, string $currency = 'INR'): string {
        return self::inWords($amount, $currency);
    }

    private static function convertIndianWhole(int $number): string {
        if ($number === 0) {
            return '';
        }

        $crores = (int)floor($number / 10000000);
        $number %= 10000000;

        $lakhs = (int)floor($number / 100000);
        $number %= 100000;

        $thousands = (int)floor($number / 1000);
        $number %= 1000;

        $hundreds = (int)floor($number / 100);
        $remainder = $number % 100;

        $parts = [];
        if ($crores > 0) {
            $parts[] = self::convertIndianWhole($crores) . ' Crore';
        }
        if ($lakhs > 0) {
            $parts[] = self::convertGroup($lakhs) . ' Lakh';
        }
        if ($thousands > 0) {
            $parts[] = self::convertGroup($thousands) . ' Thousand';
        }
        if ($hundreds > 0) {
            $parts[] = self::$ones[$hundreds] . ' Hundred';
        }
        if ($remainder > 0) {
            $parts[] = self::convertGroup($remainder);
        }

        return implode(' ', $parts);
    }

    private static function convertGroup(int $n): string {
        if ($n < 20) {
            return self::$ones[$n];
        }
        $tens = (int)floor($n / 10);
        $units = $n % 10;
        return self::$tens[$tens] . ($units > 0 ? ' ' . self::$ones[$units] : '');
    }

    public static function formatIndian(float $amount, int $decimals = 2): string {
        $neg = $amount < 0 ? '-' : '';
        $amount = abs($amount);
        $parts = explode('.', number_format($amount, $decimals, '.', ''));
        $whole = $parts[0];
        $dec = isset($parts[1]) ? '.' . $parts[1] : '';

        if (strlen($whole) <= 3) {
            return $neg . $whole . $dec;
        }

        $last3 = substr($whole, -3);
        $rest = substr($whole, 0, -3);
        $restFormatted = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);

        return $neg . $restFormatted . ',' . $last3 . $dec;
    }
}
