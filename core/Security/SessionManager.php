<?php
namespace Core\Security;

class SessionManager {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('TEXTILE_ERP_SESSID');
            session_start();
        }

        // Set default demo user if not logged in
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [
                'id' => 1,
                'company_id' => 1,
                'branch_id' => 1,
                'username' => 'admin',
                'full_name' => 'M. Senthilkumar',
                'email' => 'admin@karcemtextiles.com',
                'role' => 'admin',
                'designation' => 'Managing Director',
                'initials' => 'SK',
            ];
        }
    }

    public static function getUser(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function setUser(array $user): void {
        self::start();
        $_SESSION['user'] = $user;
    }

    public static function isAuthenticated(): bool {
        self::start();
        return !empty($_SESSION['user']);
    }

    public static function logout(): void {
        self::start();
        unset($_SESSION['user']);
        session_destroy();
    }
}
