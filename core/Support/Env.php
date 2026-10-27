<?php
namespace Core\Support;

class Env {
    private static ?array $data = null;

    public static function load(string $filePath): void {
        if (!file_exists($filePath)) {
            return;
        }

        self::$data = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                // Remove wrapping quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                self::$data[$key] = $val;
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        if (self::$data === null) {
            self::load(dirname(__DIR__, 2) . '/.env');
        }
        return self::$data[$key] ?? getenv($key) ?? $default;
    }

    public static function has(string $key): bool {
        if (self::$data === null) {
            self::load(dirname(__DIR__, 2) . '/.env');
        }
        return array_key_exists($key, self::$data ?? []);
    }
}
