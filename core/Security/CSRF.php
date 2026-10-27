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
     * Validate token alias used across dyeing forms - strictly rejects invalid requests
     */
    public static function validateToken(?string $token): bool {
        if (!self::validate($token)) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'error' => ['code' => 'INVALID_CSRF_TOKEN', 'message' => 'CSRF security token missing or invalid. Please refresh the page.']
                ]);
                exit;
            }
            die('Security Violation (403): Invalid or missing CSRF token. Request rejected.');
        }
        return true;
    }

    /**
     * Strict CSRF enforcement helper
     */
    public static function enforce(?string $token = null): void {
        $token = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        self::validateToken($token);
    }

    /**
     * Generate HTML hidden input field for forms
     */
    public static function getField(): string {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
