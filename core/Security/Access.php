<?php
namespace Core\Security;

class Access {
    public static function isSuperAdmin(?array $user = null): bool {
        $user = $user ?? SessionManager::getUser();
        return !empty($user) && in_array(strtolower($user['role'] ?? ''), ['admin', 'superadmin'], true);
    }

    public static function can(?array $user, string $permission, string $action = 'view'): bool {
        $user = $user ?? SessionManager::getUser();
        if (!$user) {
            return false;
        }
        if (self::isSuperAdmin($user)) {
            return true;
        }

        $role = strtolower($user['role'] ?? '');
        // Standard role permission mappings
        $matrix = [
            'sales' => ['commercial', 'customers', 'sales_orders', 'quotations', 'enquiries', 'reports'],
            'production' => ['planning', 'production', 'spinning', 'knitting', 'dyeing', 'garments', 'bom'],
            'qc' => ['quality', 'lab', 'inspections', 'dyeing', 'ncr'],
            'stores' => ['inventory', 'procurement', 'grn', 'lots', 'rolls', 'gate', 'adjustments'],
            'accounts' => ['accounts', 'invoices', 'receipts', 'payments', 'journals', 'reports', 'finance'],
            'hr' => ['hr', 'employees', 'attendance', 'payroll', 'wages']
        ];

        $allowedModules = $matrix[$role] ?? [];
        return in_array($permission, $allowedModules, true);
    }
}
