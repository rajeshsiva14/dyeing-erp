<?php
namespace Core\Support;

class Url {
    private static ?string $base = null;

    public static function base(): string {
        if (self::$base !== null) {
            return self::$base;
        }

        $appUrl = Env::get('APP_URL');
        if (!empty($appUrl)) {
            $parsed = parse_url($appUrl, PHP_URL_PATH);
            self::$base = rtrim($parsed ?: '', '/');
            return self::$base;
        }

        // Auto-detect from script path
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($script);
        self::$base = ($dir === '/' || $dir === '\\') ? '' : rtrim(str_replace('\\', '/', $dir), '/');
        return self::$base;
    }

    public static function to(string $path = ''): string {
        $base = self::base();
        $path = '/' . ltrim($path, '/');
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            return $path;
        }
        return $base . $path;
    }

    public static function asset(string $path): string {
        return self::to($path);
    }

    public static function home(): string {
        return self::to('/dashboard');
    }
}
