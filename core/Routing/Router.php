<?php
namespace Core\Routing;

use Core\Support\Url;
use Core\Security\SessionManager;
use Core\Security\CSRF;
use Exception;

class Router {
    private static array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    private static bool $initialized = false;

    public static function get(string $path, array|callable $handler): void {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, array|callable $handler): void {
        self::$routes['POST'][$path] = $handler;
    }

    public static function any(string $path, array|callable $handler): void {
        foreach (['GET', 'POST'] as $m) {
            self::$routes[$m][$path] = $handler;
        }
    }

    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        SessionManager::start();

        // Register Web routes
        self::registerWebRoutes();

        // Register API routes
        self::registerApiRoutes();
    }

    private static function registerWebRoutes(): void {
        // Root redirect to Dyeing ERP Dashboard
        self::get('/', function() {
            header('Location: ' . Url::to('/dashboard'));
            exit;
        });

        // Login / Logout
        self::any('/login', ['view' => 'login/index.php', 'title' => 'Sign In']);
        self::any('/logout', function() {
            SessionManager::logout();
            header('Location: ' . Url::to('/login'));
            exit;
        });

        // -------------------------------------------------------------
        // KARCEM DYEING ERP — Complete 30-Form Workflow Routes
        // -------------------------------------------------------------
        $dyeingRoutes = [
            ['/dashboard', 'modules/dyeing/views/index.php', 'Dyeing Operations Dashboard', 'dashboard'],
            ['/dyeing', 'modules/dyeing/views/index.php', 'Dyeing Operations Dashboard', 'dashboard'],
            ['/grey-inward', 'modules/dyeing/views/grey-inward.php', 'Grey Fabric Inward', 'grey-inward'],
            ['/grey-inspection', 'modules/dyeing/views/grey-inspection.php', 'Grey Fabric Inspection', 'grey-inspection'],
            ['/job-cards', 'modules/dyeing/views/job-cards.php', 'Dyeing Job Cards', 'job-cards'],
            ['/batch-planning', 'modules/dyeing/views/batch-planning.php', 'Dyeing Batch Planning', 'batch-planning'],
            ['/machine-allocation', 'modules/dyeing/views/machine-allocation.php', 'Machine Allocation', 'machine-allocation'],
            ['/lab-dips', 'modules/dyeing/views/lab-dips.php', 'Lab Dip & Shade Approvals', 'lab-dips'],
            ['/recipes', 'modules/dyeing/views/recipes.php', 'Dye Recipe Master', 'recipes'],
            ['/recipe-calculation', 'modules/dyeing/views/recipe-calculation.php', 'Batch Recipe Calculation', 'recipe-calculation'],
            ['/chemical-requests', 'modules/dyeing/views/chemical-requests.php', 'Chemical Issue & Usage', 'chemical-requests'],
            ['/process-sheets', 'modules/dyeing/views/process-sheets.php', 'Process Sheets & Stages', 'process-sheets'],
            ['/loading-sheets', 'modules/dyeing/views/loading-sheets.php', 'Machine Loading Sheet', 'loading-sheets'],
            ['/production-entry', 'modules/dyeing/views/production-entry.php', 'Live Production Entry', 'production-entry'],
            ['/batches', 'modules/dyeing/views/batches.php', 'Dyeing Batches & Vessels', 'batches'],
            ['/shade-checks', 'modules/dyeing/views/shade-checks.php', 'In-process Shade Check (ΔE)', 'shade-checks'],
            ['/batch-qc', 'modules/dyeing/views/batch-qc.php', 'Dyeing Batch QC', 'batch-qc'],
            ['/reprocess-requests', 'modules/dyeing/views/reprocess-requests.php', 'Reprocess / Re-dye Requests', 'reprocess-requests'],
            ['/finishing', 'modules/dyeing/views/finishing.php', 'Finishing Operations', 'finishing'],
            ['/inspection', 'modules/dyeing/views/four-point-inspection.php', '4-Point Fabric Inspection', 'four-point-inspection'],
            ['/four-point-inspection', 'modules/dyeing/views/four-point-inspection.php', '4-Point Fabric Inspection', 'four-point-inspection'],
            ['/roll-packing', 'modules/dyeing/views/roll-packing.php', 'Roll Formation & Packing', 'roll-packing'],
            ['/wastage-loss', 'modules/dyeing/views/wastage-loss.php', 'Dyeing Wastage & Loss', 'wastage-loss'],
            ['/batch-costing', 'modules/dyeing/views/batch-costing.php', 'Dyeing Batch Costing', 'batch-costing'],
            ['/job-work', 'modules/dyeing/views/job-work.php', 'External Dyeing Job-Work', 'job-work'],
            ['/stock-transfer', 'modules/dyeing/views/stock-transfer.php', 'Dyeing Stock Transfer', 'stock-transfer'],
            ['/reports', 'modules/dyeing/views/reports.php', 'Dyeing Reports & Registers', 'reports'],
            ['/masters', 'modules/dyeing/views/masters.php', 'Dyeing Masters Configuration', 'masters'],
            ['/organization', 'modules/dyeing/views/organization.php', 'Organization & Multi-Unit Setup', 'organization'],
            ['/print', 'modules/print/views/index.php', 'Dyeing Print Forms Catalogue', 'print'],
        ];

        foreach ($dyeingRoutes as $dr) {
            $h = ['view' => $dr[1], 'title' => $dr[2], 'module' => $dr[3]];
            self::any($dr[0], $h);
            if ($dr[0] !== '/dyeing') {
                self::any('/dyeing' . $dr[0], $h);
            }
        }

        // 10. Garments Manufacturing
        self::get('/garments', ['view' => 'modules/garments/views/index.php', 'title' => 'Garments & Apparel Manufacturing', 'module' => 'garments']);
        self::get('/garments/styles', ['view' => 'modules/garments/views/index.php', 'title' => 'Style Master & SAM', 'module' => 'garments']);
        self::get('/garments/cutting', ['view' => 'modules/garments/views/index.php', 'title' => 'Cutting Docket & Marker Planning', 'module' => 'garments']);
        self::get('/garments/bundles', ['view' => 'modules/garments/views/index.php', 'title' => 'Bundle Tracking & QR Codes', 'module' => 'garments']);
        self::get('/garments/sewing', ['view' => 'modules/garments/views/index.php', 'title' => 'Sewing Lines & Hourly Output', 'module' => 'garments']);
        self::get('/garments/packing', ['view' => 'modules/garments/views/index.php', 'title' => 'Finishing, Packing & Cartons', 'module' => 'garments']);

        // 11. Quality Assurance
        self::get('/quality', ['view' => 'modules/quality/views/index.php', 'title' => 'Quality & Testing Lab', 'module' => 'quality']);
        self::get('/quality/inspections', ['view' => 'modules/quality/views/index.php', 'title' => 'Quality Inspections', 'module' => 'quality']);
        self::get('/quality/ncr', ['view' => 'modules/quality/views/index.php', 'title' => 'Non-Conformance Reports (NCR)', 'module' => 'quality']);
        self::get('/quality/lab-tests', ['view' => 'modules/quality/views/index.php', 'title' => 'Fastness & Shrinkage Test Lab', 'module' => 'quality']);

        // 12. Job-Work & Subcontract
        self::get('/jobwork', ['view' => 'modules/jobwork/views/index.php', 'title' => 'Job-Work & Subcontracting', 'module' => 'jobwork']);
        self::get('/jobwork/outward', ['view' => 'modules/jobwork/views/index.php', 'title' => 'Outward Jobwork Challans', 'module' => 'jobwork']);
        self::get('/jobwork/inward', ['view' => 'modules/jobwork/views/index.php', 'title' => 'Jobwork Receipts', 'module' => 'jobwork']);
        self::get('/jobwork/customer-stock', ['view' => 'modules/jobwork/views/index.php', 'title' => 'Customer Material Stock', 'module' => 'jobwork']);
        self::get('/jobwork/reconciliation', ['view' => 'modules/jobwork/views/index.php', 'title' => 'Jobwork Material Reconciliation', 'module' => 'jobwork']);

        // 13. Gate & Logistics
        self::get('/gate', ['view' => 'modules/gate/views/index.php', 'title' => 'Gate, Logistics & Weighbridge', 'module' => 'gate']);
        self::get('/gate/passes', ['view' => 'modules/gate/views/index.php', 'title' => 'Gate Pass (Returnable & Outward)', 'module' => 'gate']);
        self::get('/gate/weighbridge', ['view' => 'modules/gate/views/index.php', 'title' => 'Weighbridge Slips', 'module' => 'gate']);

        // 14. Compliance & ETP (Tamil Nadu / ZLD)
        self::get('/compliance', ['view' => 'modules/compliance/views/etp-zld-log.php', 'title' => 'Compliance, ETP & Zero Liquid Discharge', 'module' => 'compliance']);
        self::get('/compliance/etp-log', ['view' => 'modules/compliance/views/etp-zld-log.php', 'title' => 'TNPCB ETP / ZLD Daily Logs', 'module' => 'compliance']);
        self::get('/compliance/boiler', ['view' => 'modules/compliance/views/etp-zld-log.php', 'title' => 'Boiler & Steam Logs', 'module' => 'compliance']);
        self::get('/compliance/licences', ['view' => 'modules/compliance/views/etp-zld-log.php', 'title' => 'Statutory Licences & CTO', 'module' => 'compliance']);

        // 15. HR & Production Wages
        self::get('/hr', ['view' => 'modules/hr/views/index.php', 'title' => 'Human Resources & Production Wages', 'module' => 'hr']);
        self::get('/hr/employees', ['view' => 'modules/hr/views/index.php', 'title' => 'Employee Directory', 'module' => 'hr']);
        self::get('/hr/attendance', ['view' => 'modules/hr/views/index.php', 'title' => 'Attendance & Shifts', 'module' => 'hr']);
        self::get('/hr/wages', ['view' => 'modules/hr/views/index.php', 'title' => 'Piece-Rate & Operator Wages', 'module' => 'hr']);

        // 16. Billing, Accounts & Financial Statements
        self::get('/accounts', ['view' => 'modules/accounts/views/index.php', 'title' => 'Billing & Financial Accounts', 'module' => 'accounts']);
        self::get('/accounts/invoices', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'GST Tax Invoices & E-Way Bills', 'module' => 'accounts']);
        self::get('/accounts/receipts', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'Customer Receipts & Collections', 'module' => 'accounts']);
        self::get('/accounts/bills', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'Supplier Bills & Payables', 'module' => 'accounts']);
        self::get('/accounts/payments', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'Supplier Payments', 'module' => 'accounts']);
        self::get('/accounts/journals', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'Journal Entries', 'module' => 'accounts']);
        self::get('/accounts/daybook', ['view' => 'modules/accounts/views/invoices.php', 'title' => 'Day Book & Cash/Bank Book', 'module' => 'accounts']);
        self::get('/accounts/trial-balance', ['view' => 'modules/accounts/views/trial-balance.php', 'title' => 'Trial Balance & Financials', 'module' => 'accounts']);

        // 17. Reports & Analytics (Dyeing Reports)
        self::get('/reports', ['view' => 'modules/dyeing/views/reports.php', 'title' => 'Dyeing Reports & Registers', 'module' => 'reports']);
        self::get('/reports/order-lifecycle', ['view' => 'modules/dyeing/views/reports.php', 'title' => 'Order-to-Cash Lifecycle Report', 'module' => 'reports']);
        self::get('/reports/traceability', ['view' => 'modules/dyeing/views/reports.php', 'title' => 'Roll & Batch Traceability', 'module' => 'reports']);
        self::get('/reports/costing', ['view' => 'modules/dyeing/views/batch-costing.php', 'title' => 'Batch Costing & Profitability', 'module' => 'batch-costing']);
        self::get('/reports/dyeing-rft', ['view' => 'modules/dyeing/views/reports.php', 'title' => 'Right-First-Time (RFT) & Yield', 'module' => 'reports']);

        // 18. Print & Document Engine (P01 to P113, L01 to L08)
        self::get('/print', ['view' => 'modules/print/views/index.php', 'title' => 'Print Form Engine', 'module' => 'print']);
        self::get('/print/sales-order', ['view' => 'modules/print/views/sales-order.php', 'title' => 'P03 Sales Order Confirmation', 'module' => 'print']);
        self::get('/print/purchase-order', ['view' => 'modules/print/views/purchase-order.php', 'title' => 'P13 Purchase Order', 'module' => 'print']);
        self::get('/print/grn', ['view' => 'modules/print/views/grn.php', 'title' => 'P15 Goods Receipt Note', 'module' => 'print']);
        self::get('/print/batch-card', ['view' => 'modules/print/views/batch-card.php', 'title' => 'P40 Dyeing Batch Card & Recipe', 'module' => 'print']);
        self::get('/print/fabric-inspection', ['view' => 'modules/print/views/fabric-inspection.php', 'title' => 'P46 4-Point Fabric Inspection Report', 'module' => 'print']);
        self::get('/print/tax-invoice', ['view' => 'modules/print/views/tax-invoice.php', 'title' => 'P74 GST Tax Invoice', 'module' => 'print']);
        self::get('/print/roll-label', ['view' => 'modules/print/views/roll-label.php', 'title' => 'L01 Thermal Fabric Roll Label', 'module' => 'print']);
    }

    private static function registerApiRoutes(): void {
        require_once dirname(__DIR__, 2) . '/api/v1/routes.php';
    }

    public static function dispatch(): void {
        self::init();

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Strip base path
        $base = Url::base();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        // Enforce Authentication for protected routes
        $publicPaths = ['/login', '/logout', '/api/v1/auth/login'];
        $cleanPath = '/' . trim(str_starts_with($path, '/dyeing') ? substr($path, 7) : $path, '/');
        if (!in_array($path, $publicPaths, true) && !in_array($cleanPath, $publicPaths, true) && $path !== '/') {
            if (!SessionManager::isAuthenticated()) {
                if (str_starts_with($path, '/api/')) {
                    header('Content-Type: application/json; charset=UTF-8');
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'error'   => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required. Please sign in.'],
                        'meta'    => ['request_id' => bin2hex(random_bytes(8))]
                    ]);
                    return;
                }
                header('Location: ' . Url::to('/login'));
                exit;
            }
        }

        // Check if route exists
        $handler = self::$routes[$method][$path] 
            ?? self::$routes[$method]['/dyeing' . $path] 
            ?? (str_starts_with($path, '/dyeing') ? (self::$routes[$method][substr($path, 7)] ?? null) : null)
            ?? null;

        // If not found, check wildcards/dynamic or fallback
        if (!$handler) {
            foreach (self::$routes[$method] as $routePath => $routeHandler) {
                if (str_contains($routePath, '{')) {
                    $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_\-]+)', $routePath);
                    if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                        array_shift($matches);
                        $handler = $routeHandler;
                        break;
                    }
                }
            }
        }

        if ($handler) {
            if (is_callable($handler)) {
                $handler();
                return;
            }

            if (is_array($handler)) {
                if (isset($handler['closure']) && is_callable($handler['closure'])) {
                    $handler['closure']();
                    return;
                }

                if (isset($handler['view'])) {
                    $viewFile = dirname(__DIR__, 2) . '/' . ltrim($handler['view'], '/');
                    if (file_exists($viewFile)) {
                        $pageTitle = $handler['title'] ?? 'KARCEM Textile ERP';
                        $activeModule = $handler['module'] ?? 'dashboard';
                        $baseUrl = Url::base();
                        $csrfToken = CSRF::getToken();
                        $currentUser = SessionManager::getUser();
                        require $viewFile;
                        return;
                    }
                }

                if (isset($handler['controller']) && isset($handler['action'])) {
                    $ctrlClass = $handler['controller'];
                    $act = $handler['action'];
                    $instance = new $ctrlClass();
                    $instance->$act();
                    return;
                }
            }
        }

        // Check if API endpoint
        if (str_starts_with($path, '/api/')) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => "API endpoint '{$method} {$path}' not found."
                ],
                'meta' => ['request_id' => bin2hex(random_bytes(8))]
            ]);
            return;
        }

        // Fallback 404
        http_response_code(404);
        $baseUrl = Url::base();
        $pageTitle = 'Page Not Found';
        require_once dirname(__DIR__, 2) . '/includes/header.php';
        require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
        ?>
        <main class="cz-main">
          <div class="cz-content">
            <section class="cz-panel text-center py-5">
              <div class="display-1 fw-bold text-secondary mb-3">404</div>
              <h2 class="h4 fw-bold text-dark">Page Not Found</h2>
              <p class="text-secondary mb-4">The screen or document you are looking for (<code><?= htmlspecialchars($path) ?></code>) does not exist or has been moved.</p>
              <a href="<?= Url::to('/dashboard') ?>" class="btn btn-dark"><i class="bi bi-arrow-left me-1"></i> Return to Dashboard</a>
            </section>
          </div>
        </main>
        <?php
        require_once dirname(__DIR__, 2) . '/includes/footer.php';
    }
}
