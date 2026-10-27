<?php
namespace Core\Security;

use Core\Support\Url;

class SessionManager {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $params = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => 86400,
                'path'     => $params['path'] ?: '/',
                'domain'   => $params['domain'],
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_name('TEXTILE_ERP_SESSID');
            session_start();
        }
    }

    public static function getUser(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function setUser(array $user): void {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = $user;
    }

    public static function isAuthenticated(): bool {
        self::start();
        return !empty($_SESSION['user']['id']);
    }

    public static function requireLogin(): void {
        self::start();
        if (!self::isAuthenticated()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']]);
                exit;
            }
            header('Location: ' . Url::to('/login'));
            exit;
        }
    }

    public static function logout(): void {
        self::start();
        unset($_SESSION['user']);
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
