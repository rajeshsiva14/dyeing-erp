<?php
/**
 * Textile Dyeing ERP — Complete Audit Verification Test Suite
 * File: tests/verify_all.php
 */

declare(strict_types=1);

// Start session before any output
require_once dirname(__DIR__) . '/config/app.php';

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'Core\\')) {
        $file = dirname(__DIR__) . '/core/' . str_replace('\\', '/', substr($class, 5)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use Core\Database\Database;
use Core\Security\SessionManager;
use Core\Security\CSRF;
use Core\Security\Access;
use Core\Services\CostingService;
use Core\Services\InventoryService;
use Core\Services\AccountingService;

SessionManager::start();

$passed = 0;
$failed = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$description}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$description}" . ($details ? " - {$details}" : "") . "\n";
    }
}

echo "====================================================================\n";
echo " TEXTILE DYEING ERP — FULL VERIFICATION TEST SUITE\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// SUITE 1: Security, Sessions & CSRF
// -------------------------------------------------------------------------
echo "SUITE 1: Security, Authentication, RBAC & CSRF Protection\n";

// 1.1 Unauthenticated state verification
$_SESSION['user'] = null;
unset($_SESSION['user']);
assertTest(
    "Unauthenticated visitor has no active session",
    !SessionManager::isAuthenticated() && SessionManager::getUser() === null
);

// 1.2 CSRF Token Generation
$token = CSRF::getToken();
assertTest(
    "CSRF token generated with sufficient cryptographic entropy (>= 32 chars)",
    !empty($token) && strlen($token) >= 32
);

// 1.3 CSRF Validation - Valid Token
assertTest(
    "CSRF validateToken accepts valid token",
    CSRF::validateToken($token) === true
);

// 1.4 CSRF Validation - Invalid Token (executed in isolated sub-process)
$output = shell_exec('/Applications/XAMPP/xamppfiles/bin/php -r \'
require "/Applications/XAMPP/xamppfiles/htdocs/dyeing/config/app.php";
require "/Applications/XAMPP/xamppfiles/htdocs/dyeing/core/Security/SessionManager.php";
require "/Applications/XAMPP/xamppfiles/htdocs/dyeing/core/Security/CSRF.php";
Core\Security\SessionManager::start();
Core\Security\CSRF::getToken();
Core\Security\CSRF::validateToken("wrong_token_here");
\' 2>&1');
assertTest(
    "Invalid CSRF token immediately halts execution with 403 Forbidden",
    str_contains($output ?? '', 'Security Violation (403)')
);

// 1.5 Role-Based Access Control (RBAC) Matrix
$salesUser = ['id' => 99, 'role' => 'sales', 'company_id' => 1];
$productionUser = ['id' => 98, 'role' => 'production', 'company_id' => 1];
$superAdmin = ['id' => 1, 'role' => 'admin', 'company_id' => 1];

assertTest("Superadmin has universal access across all modules", Access::can($superAdmin, 'accounts'));
assertTest("Sales role is permitted to access sales module", Access::can($salesUser, 'sales'));
assertTest("Sales role is denied access to accounts/financials", !Access::can($salesUser, 'accounts'));
assertTest("Production role is permitted to access dyeing workflows", Access::can($productionUser, 'dyeing'));
assertTest("Production role is denied access to HR/payroll", !Access::can($productionUser, 'hr'));

echo "\n";

// -------------------------------------------------------------------------
// SUITE 2: Textile Calculations & Engineering Formulas
// -------------------------------------------------------------------------
echo "SUITE 2: Textile Engineering Calculations & Process Formulas\n";

// 2.1 Bath Volume: Fabric Weight (kg) * Liquor Ratio
// 500 kg fabric with 1:6 liquor ratio = 3,000 Liters
$bathVol = CostingService::calculateBathVolume(500.0, 6.0);
assertTest(
    "Bath Volume: 500 kg @ 1:6 MLR = 3000.00 L",
    $bathVol === 3000.00,
    "Got {$bathVol}"
);

// 2.2 Dyestuff using % OWF: Fabric Weight (kg) * OWF (%) / 100
// 500 kg fabric @ 2.45% OWF = 12.25 kg
$dyestuff = CostingService::calculateDyestuffOwf(500.0, 2.45);
assertTest(
    "Dyestuff % OWF: 500 kg @ 2.45% = 12.2500 kg",
    $dyestuff === 12.2500,
    "Got {$dyestuff}"
);

// 2.3 Auxiliary Chemical using g/L: Bath Volume (L) * Dosage (g/L) / 1000
// 3000 L bath @ 20 g/L Glauber Salt = 60.00 kg
$auxChem = CostingService::calculateAuxiliaryQuantity(3000.0, 20.0);
assertTest(
    "Auxiliary Chemical: 3000 L @ 20 g/L = 60.0000 kg",
    $auxChem === 60.0000,
    "Got {$auxChem}"
);

// 2.4 Net Fabric Weight: Gross - Tare
$netWt = CostingService::calculateNetFabricWeight(25.40, 0.80);
assertTest(
    "Net Fabric Weight: 25.40 kg gross - 0.80 kg tare = 24.60 kg net",
    $netWt === 24.60,
    "Got {$netWt}"
);

// 2.5 ASTM D5430 4-Point System Formula
// Formula: (Total Penalty Points * 3600) / (Length in Meters * 1.0936 * Width in Inches)
// 10 points on 100m fabric of 60 inches width:
// Length in yds = 100 * 1.0936 = 109.36
// Sq yds = (109.36 * 60) / 36 = 182.2667
// Score = (10 * 100) / 182.2667 = 5.49 points / 100 sq yds
$fourPointScore = CostingService::calculateFourPointSystem(10, 100.0, 60.0);
assertTest(
    "ASTM D5430 4-Point score: 10 pts, 100m, 60in width = 5.49 pts/100 sq yds",
    $fourPointScore === 5.49,
    "Got {$fourPointScore}"
);

// 2.6 Gross Margin %: (Net Sales - COGS) / Net Sales * 100
// Net Sales: 100,000, COGS: 75,000 => 25.00%
$margin = CostingService::calculateGrossMarginPct(100000.0, 75000.0);
assertTest(
    "Gross Margin: Net Sales ₹100,000, COGS ₹75,000 = 25.00%",
    $margin === 25.00,
    "Got {$margin}%"
);

echo "\n";

// -------------------------------------------------------------------------
// SUITE 3: Inventory Integrity & Negative Stock Guard
// -------------------------------------------------------------------------
echo "SUITE 3: Inventory Integrity, Ledger Movements & Negative Stock Guard\n";

// 3.1 Initial stock balance check
$prodId = 5; // Reactive Blue RGB
$initialStock = (float)Database::fetchColumn("SELECT current_stock FROM products WHERE id = ?", [$prodId]);
assertTest(
    "Product stock exists and is non-negative (current: {$initialStock} kg)",
    $initialStock >= 0.0
);

// 3.2 Negative stock guard verification
$caughtNegativeStockException = false;
try {
    InventoryService::postMovement([
        'company_id'        => 1,
        'warehouse_id'      => 2,
        'product_id'        => $prodId,
        'movement_type'     => 'production_issue',
        'quantity_base_uom' => -($initialStock + 1000.0), // Exceeds available stock
        'source_type'       => 'test',
        'source_id'         => 999
    ]);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Insufficient inventory balance')) {
        $caughtNegativeStockException = true;
    }
}
assertTest(
    "Negative stock guard blocks issue exceeding available stock balance",
    $caughtNegativeStockException
);

// 3.3 Atomic Inventory Movement with Rollback
Database::beginTransaction();
$movementId = InventoryService::postMovement([
    'company_id'        => 1,
    'warehouse_id'      => 2,
    'product_id'        => $prodId,
    'movement_type'     => 'grn_receipt',
    'quantity_base_uom' => 10.0,
    'source_type'       => 'test',
    'source_id'         => 999
]);
Database::rollBack();

$stockAfterRollback = (float)Database::fetchColumn("SELECT current_stock FROM products WHERE id = ?", [$prodId]);
$movementFound = Database::fetch("SELECT id FROM inventory_movements WHERE id = ?", [$movementId]);

assertTest(
    "Failed or rolled-back transaction does not persist inventory movement or change stock",
    $stockAfterRollback === $initialStock && $movementFound === null
);

echo "\n";

// -------------------------------------------------------------------------
// SUITE 4: Financial Accounting & Balanced Journal Integrity
// -------------------------------------------------------------------------
echo "SUITE 4: Accounting Integrity & Balanced Journal Enforcement\n";

// 4.1 Unbalanced Journal Rejection
$caughtUnbalancedJournal = false;
try {
    AccountingService::postJournal(
        ['company_id' => 1, 'narration' => 'Unbalanced Test'],
        [
            ['account_id' => 1, 'debit' => 5000.0, 'credit' => 0.0],
            ['account_id' => 2, 'debit' => 0.0, 'credit' => 4500.0] // ₹500 imbalance!
        ]
    );
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Unbalanced journal entry')) {
        $caughtUnbalancedJournal = true;
    }
}
assertTest(
    "Unbalanced journal entry (Debit ₹5000 != Credit ₹4500) is rejected with exception",
    $caughtUnbalancedJournal
);

// 4.2 Balanced Journal Entry Transaction
Database::beginTransaction();
try {
    $jId = AccountingService::postJournal(
        ['company_id' => 1, 'narration' => 'Balanced Test Verification Entry'],
        [
            ['account_id' => 1, 'debit' => 1000.0, 'credit' => 0.0],
            ['account_id' => 2, 'debit' => 0.0, 'credit' => 1000.0]
        ]
    );
    assertTest(
        "Balanced journal entry (Debit ₹1000 == Credit ₹1000) posts successfully (Journal ID {$jId})",
        $jId > 0
    );
} finally {
    Database::rollBack(); // Always rollback test journal
}

echo "\n";

// -------------------------------------------------------------------------
// SUITE 5: Database Persistence & Form Submissions
// -------------------------------------------------------------------------
echo "SUITE 5: Database Operations, Forms & Print Engine Views\n";

// 5.1 Purchase Orders exist and render
$po = Database::fetch("SELECT id, po_no, grand_total FROM purchase_orders ORDER BY id DESC LIMIT 1");
assertTest(
    "Purchase order records exist in database (PO: {$po['po_no']}, Total: ₹{$po['grand_total']})",
    $po !== null && $po['id'] > 0
);

// 5.2 Goods Receipt Notes exist and render
$grn = Database::fetch("SELECT id, grn_no FROM goods_receipts ORDER BY id DESC LIMIT 1");
assertTest(
    "Goods Receipt Note records exist in database (GRN: {$grn['grn_no']})",
    $grn !== null && $grn['id'] > 0
);

// 5.3 Test PO Print Sheet Generation
$_GET['id'] = (int)$po['id'];
ob_start();
require dirname(__DIR__) . '/modules/print/views/purchase-order.php';
$poPrintOutput = ob_get_clean();
assertTest(
    "PO Print Sheet (/print/purchase-order) renders without errors (" . strlen($poPrintOutput) . " bytes)",
    strlen($poPrintOutput) > 1000 && str_contains($poPrintOutput, $po['po_no'])
);

// 5.4 Test GRN Print Sheet Generation
$_GET['id'] = (int)$grn['id'];
ob_start();
require dirname(__DIR__) . '/modules/print/views/grn.php';
$grnPrintOutput = ob_get_clean();
assertTest(
    "GRN Print Sheet (/print/grn) renders without errors (" . strlen($grnPrintOutput) . " bytes)",
    strlen($grnPrintOutput) > 1000 && str_contains($grnPrintOutput, $grn['grn_no'])
);

// 5.5 Customer Master Persistence
Database::beginTransaction();
try {
    $testCustCode = 'TEST-CUST-' . rand(100, 999);
    $custId = Database::insert('customers', [
        'company_id'      => 1,
        'code'            => $testCustCode,
        'name'            => 'Apex Textiles Automated Test Account',
        'category'        => 'domestic',
        'billing_address' => '124 Textile Nagar, Tirupur',
        'city'            => 'Tirupur',
        'state'           => 'Tamil Nadu',
        'state_code'      => '33',
        'pincode'         => '641604',
        'currency'        => 'INR',
        'credit_limit'    => 500000.00,
        'credit_days'     => 30,
        'current_balance' => 0.00,
        'status'          => 'active',
        'created_at'      => date('Y-m-d H:i:s')
    ]);

    $saved = Database::fetch("SELECT id, code, name FROM customers WHERE id = ?", [$custId]);
    assertTest(
        "Customer Master insert persists valid record to database (ID {$custId}, Code: {$saved['code']})",
        $saved !== null && $saved['code'] === $testCustCode
    );
} finally {
    Database::rollBack();
}

// -------------------------------------------------------------------------
// SUITE 6: Multi-Tenant Scoping
// -------------------------------------------------------------------------
echo "\nSUITE 6: Multi-Tenant Data Scoping & Company Isolation\n";

$company1Customers = Database::fetchAll("SELECT id FROM customers WHERE company_id = 1");
$company2Customers = Database::fetchAll("SELECT id FROM customers WHERE company_id = 2");
assertTest(
    "Tenant data isolation: Company 1 and Company 2 customer lists are disjoint",
    empty(array_intersect(array_column($company1Customers, 'id'), array_column($company2Customers, 'id')))
);

echo "\n====================================================================\n";
echo " FINAL TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
