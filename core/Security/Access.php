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

        // Map sub-features to overarching domain
        $dyeingSubmodules = [
            'dyeing', 'grey-inward', 'grey-inspection', 'job-cards', 'batch-planning', 
            'machine-allocation', 'lab-dips', 'recipes', 'recipe-calculation', 
            'chemical-requests', 'process-sheets', 'loading-sheets', 'production-entry', 
            'batches', 'shade-checks', 'batch-qc', 'reprocess-requests', 'finishing', 
            'inspection', 'four-point-inspection', 'roll-packing', 'wastage-loss', 
            'batch-costing', 'job-work', 'stock-transfer', 'masters'
        ];

        if (in_array($permission, $dyeingSubmodules, true)) {
            $permission = 'dyeing';
        }

        // Standard role permission mappings
        $matrix = [
            'sales' => ['dashboard', 'commercial', 'customers', 'sales_orders', 'quotations', 'enquiries', 'reports', 'sales', 'print'],
            'production' => ['dashboard', 'planning', 'production', 'spinning', 'knitting', 'dyeing', 'garments', 'bom', 'masters', 'reports', 'inventory', 'print'],
            'qc' => ['dashboard', 'quality', 'lab', 'inspections', 'dyeing', 'ncr', 'masters', 'reports', 'print'],
            'stores' => ['dashboard', 'inventory', 'procurement', 'grn', 'lots', 'rolls', 'gate', 'adjustments', 'masters', 'print'],
            'accounts' => ['dashboard', 'accounts', 'invoices', 'receipts', 'payments', 'journals', 'reports', 'finance', 'print'],
            'hr' => ['dashboard', 'hr', 'employees', 'attendance', 'payroll', 'wages', 'print']
        ];

        $allowedModules = $matrix[$role] ?? [];
        return in_array($permission, $allowedModules, true);
    }
}
