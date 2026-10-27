<?php
namespace Core\Security;

class CSRF {
    public static function getToken(): string {
        SessionManager::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): bool {
        SessionManager::start();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Validate token alias used across dyeing forms
     */
    public static function validateToken(?string $token): bool {
        return self::validate($token);
    }

    /**
     * Generate HTML hidden input field for forms
     */
    public static function getField(): string {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
