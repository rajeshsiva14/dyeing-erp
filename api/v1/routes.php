<?php
/**
 * REST API v1 Master Router & Controller Dispatcher
 * File: api/v1/routes.php
 */

use Core\Database\Database;
use Core\Services\InventoryService;
use Core\Services\AccountingService;
use Core\Services\CostingService;

function jsonResponse(mixed $data, int $status = 200, array $meta = []): void {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($status);
    $meta['request_id'] = 'req_' . bin2hex(random_bytes(6));
    $meta['timestamp'] = date('c');
    echo json_encode([
        'success' => $status >= 200 && $status < 300,
        'data'    => $data,
        'meta'    => $meta
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $code, string $message, int $status = 400, array $fields = []): void {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($status);
    $res = [
        'success' => false,
        'error' => [
            'code'    => $code,
            'message' => $message,
        ],
        'meta' => [
            'request_id' => 'err_' . bin2hex(random_bytes(6)),
            'timestamp'  => date('c')
        ]
    ];
    if (!empty($fields)) {
        $res['error']['fields'] = $fields;
    }
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

// -------------------------------------------------------------
// 1. Auth & Me
// -------------------------------------------------------------
\Core\Routing\Router::post('/api/v1/auth/login', function() {
    $input = getJsonInput();
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($username === '') {
        jsonError('VALIDATION_ERROR', 'Username is required.', 422, ['username' => ['Username is required']]);
    }

    $user = Database::fetch("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
    if (!$user || $user['status'] !== 'active') {
        jsonError('UNAUTHORIZED', 'Invalid username or password.', 401);
    }

    // Check password
    if (!password_verify($password, $user['password_hash'])) {
        jsonError('UNAUTHORIZED', 'Invalid username or password.', 401);
    }

    // Set session user
    $profile = [
        'id'          => $user['id'],
        'company_id'  => $user['company_id'],
        'branch_id'   => $user['branch_id'],
        'username'    => $user['username'],
        'full_name'   => $user['full_name'],
        'email'       => $user['email'],
        'role'        => $user['role'],
        'designation' => $user['designation'],
        'initials'    => strtoupper(substr($user['full_name'], 0, 2)),
    ];
    \Core\Security\SessionManager::setUser($profile);

    jsonResponse([
        'user'  => $profile,
        'token' => bin2hex(random_bytes(24))
    ]);
});

\Core\Routing\Router::get('/api/v1/auth/me', function() {
    $user = \Core\Security\SessionManager::getUser();
    if (!$user) {
        jsonError('UNAUTHORIZED', 'Not authenticated.', 401);
    }
    jsonResponse($user);
});

// -------------------------------------------------------------
// 2. Masters APIs
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/masters/customers', function() {
    $customers = Database::fetchAll("SELECT id, code, name, category, gstin, phone, email, city, credit_limit, current_balance, status FROM customers ORDER BY name ASC");
    jsonResponse($customers);
});

\Core\Routing\Router::get('/api/v1/masters/suppliers', function() {
    $suppliers = Database::fetchAll("SELECT id, code, name, supplier_type, gstin, phone, email, city, current_balance, status FROM suppliers ORDER BY name ASC");
    jsonResponse($suppliers);
});

\Core\Routing\Router::get('/api/v1/masters/products', function() {
    $products = Database::fetchAll("SELECT p.*, u.code as uom_code FROM products p JOIN uoms u ON p.base_uom_id = u.id ORDER BY p.name ASC");
    jsonResponse($products);
});

\Core\Routing\Router::get('/api/v1/masters/machines', function() {
    $machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC");
    jsonResponse($machines);
});

// -------------------------------------------------------------
// 3. Sales Orders API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/sales-orders', function() {
    $orders = Database::fetchAll("SELECT so.*, c.name as customer_name, c.code as customer_code FROM sales_orders so JOIN customers c ON so.customer_id = c.id ORDER BY so.id DESC");
    jsonResponse($orders);
});

\Core\Routing\Router::post('/api/v1/sales-orders', function() {
    $input = getJsonInput();
    if (empty($input['customer_id'])) {
        jsonError('VALIDATION_ERROR', 'Customer is required.', 422, ['customer_id' => ['Customer must be selected']]);
    }
    if (empty($input['items']) || !is_array($input['items'])) {
        jsonError('VALIDATION_ERROR', 'At least one order line item is required.', 422);
    }

    Database::beginTransaction();
    try {
        $orderNo = Database::getNextDocumentNumber('sales_order');
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($input['items'] as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            $rate = (float)($it['unit_price'] ?? 0);
            $taxPct = (float)($it['tax_pct'] ?? 5);
            $lineTotal = $qty * $rate;
            $lineTax = ($lineTotal * $taxPct) / 100;
            $subtotal += $lineTotal;
            $taxTotal += $lineTax;
        }

        $orderId = Database::insert('sales_orders', [
            'company_id'             => 1,
            'order_no'               => $orderNo,
            'customer_po_no'         => $input['customer_po_no'] ?? null,
            'customer_id'            => $input['customer_id'],
            'order_date'             => $input['order_date'] ?? date('Y-m-d'),
            'promised_delivery_date' => $input['promised_delivery_date'] ?? date('Y-m-d', strtotime('+3 weeks')),
            'order_type'             => $input['order_type'] ?? 'domestic',
            'subtotal'               => $subtotal,
            'tax_total'              => $taxTotal,
            'grand_total'            => $subtotal + $taxTotal,
            'shipping_address'       => $input['shipping_address'] ?? 'Tirupur Hub',
            'status'                 => 'draft',
            'credit_approved'        => 1,
            'created_by'             => 1
        ]);

        foreach ($input['items'] as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            $rate = (float)($it['unit_price'] ?? 0);
            $taxPct = (float)($it['tax_pct'] ?? 5);
            $lineTotal = $qty * $rate;
            $lineTax = ($lineTotal * $taxPct) / 100;

            Database::insert('sales_order_items', [
                'sales_order_id' => $orderId,
                'product_id'     => $it['product_id'],
                'shade_code'     => $it['shade_code'] ?? null,
                'shade_name'     => $it['shade_name'] ?? null,
                'ordered_qty'    => $qty,
                'uom_id'         => $it['uom_id'] ?? 1,
                'unit_price'     => $rate,
                'tax_amount'     => $lineTax,
                'total_amount'   => $lineTotal + $lineTax,
                'delivery_date'  => $input['promised_delivery_date'] ?? date('Y-m-d', strtotime('+3 weeks'))
            ]);
        }

        Database::commit();
        jsonResponse([
            'id'       => $orderId,
            'order_no' => $orderNo,
            'status'   => 'draft',
            'message'  => "Sales Order {$orderNo} created successfully."
        ], 201);
    } catch (\Throwable $e) {
        Database::rollBack();
        jsonError('SERVER_ERROR', $e->getMessage(), 500);
    }
});

\Core\Routing\Router::post('/api/v1/sales-orders/{id}/confirm', function() {
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('#/sales-orders/([0-9]+)/confirm#', $uri, $m);
    $orderId = (int)($m[1] ?? 0);

    $so = Database::fetch("SELECT * FROM sales_orders WHERE id = ?", [$orderId]);
    if (!$so) {
        jsonError('NOT_FOUND', 'Sales Order not found.', 404);
    }

    Database::update('sales_orders', [
        'status'      => 'confirmed',
        'approved_by' => 1,
        'approved_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $orderId]);

    jsonResponse([
        'id'       => $orderId,
        'order_no' => $so['order_no'],
        'status'   => 'confirmed',
        'message'  => "Sales Order {$so['order_no']} has been confirmed and released for planning."
    ]);
});

// -------------------------------------------------------------
// 4. Production Orders API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/production-orders', function() {
    $pos = Database::fetchAll("SELECT po.*, p.name as product_name, p.sku, u.code as uom_code FROM production_orders po JOIN products p ON po.product_id = p.id JOIN uoms u ON po.uom_id = u.id ORDER BY po.id DESC");
    jsonResponse($pos);
});

\Core\Routing\Router::post('/api/v1/production-orders', function() {
    $input = getJsonInput();
    if (empty($input['product_id']) || empty($input['planned_qty'])) {
        jsonError('VALIDATION_ERROR', 'Product and Planned Quantity are required.', 422);
    }

    $orderNo = Database::getNextDocumentNumber('production_order');
    $poId = Database::insert('production_orders', [
        'company_id'      => 1,
        'order_no'        => $orderNo,
        'sales_order_id'  => $input['sales_order_id'] ?? null,
        'product_id'      => $input['product_id'],
        'planned_qty'     => $input['planned_qty'],
        'uom_id'          => $input['uom_id'] ?? 1,
        'start_date'      => $input['start_date'] ?? date('Y-m-d'),
        'due_date'        => $input['due_date'] ?? date('Y-m-d', strtotime('+2 weeks')),
        'stage'           => $input['stage'] ?? 'dyeing',
        'status'          => 'planned'
    ]);

    jsonResponse([
        'id'       => $poId,
        'order_no' => $orderNo,
        'status'   => 'planned',
        'message'  => "Production Order {$orderNo} generated."
    ], 201);
});

\Core\Routing\Router::post('/api/v1/production-orders/{id}/release', function() {
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('#/production-orders/([0-9]+)/release#', $uri, $m);
    $poId = (int)($m[1] ?? 0);

    $po = Database::fetch("SELECT * FROM production_orders WHERE id = ?", [$poId]);
    if (!$po) {
        jsonError('NOT_FOUND', 'Production order not found.', 404);
    }

    Database::update('production_orders', [
        'status'      => 'released',
        'released_by' => 1,
        'released_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $poId]);

    jsonResponse([
        'id'       => $poId,
        'order_no' => $po['order_no'],
        'status'   => 'released',
        'message'  => "Production Order {$po['order_no']} released to floor."
    ]);
});

// -------------------------------------------------------------
// 5. Stores & GRN API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/goods-receipts', function() {
    $grns = Database::fetchAll("SELECT grn.*, s.name as supplier_name, w.name as warehouse_name FROM goods_receipts grn JOIN suppliers s ON grn.supplier_id = s.id JOIN warehouses w ON grn.warehouse_id = w.id ORDER BY grn.id DESC");
    jsonResponse($grns);
});

\Core\Routing\Router::post('/api/v1/goods-receipts', function() {
    $input = getJsonInput();
    if (empty($input['supplier_id']) || empty($input['items'])) {
        jsonError('VALIDATION_ERROR', 'Supplier and items are required.', 422);
    }

    Database::beginTransaction();
    try {
        $grnNo = Database::getNextDocumentNumber('goods_receipt');
        $grnId = Database::insert('goods_receipts', [
            'company_id'          => 1,
            'grn_no'              => $grnNo,
            'purchase_order_id'   => $input['purchase_order_id'] ?? null,
            'supplier_id'         => $input['supplier_id'],
            'supplier_challan_no' => $input['supplier_challan_no'] ?? null,
            'vehicle_no'          => $input['vehicle_no'] ?? null,
            'receipt_date'        => $input['receipt_date'] ?? date('Y-m-d'),
            'warehouse_id'        => $input['warehouse_id'] ?? 1,
            'status'              => 'draft'
        ]);

        foreach ($input['items'] as $it) {
            Database::insert('goods_receipt_items', [
                'goods_receipt_id' => $grnId,
                'product_id'       => $it['product_id'],
                'received_qty'     => $it['received_qty'],
                'accepted_qty'     => $it['accepted_qty'] ?? $it['received_qty'],
                'uom_id'           => $it['uom_id'] ?? 1,
                'supplier_lot_no'  => $it['supplier_lot_no'] ?? null,
                'internal_lot_no'  => 'LOT-' . date('ymd') . '-' . rand(100, 999),
                'unit_rate'        => $it['unit_rate'] ?? 320.00
            ]);
        }

        Database::commit();
        jsonResponse([
            'id'      => $grnId,
            'grn_no'  => $grnNo,
            'status'  => 'draft',
            'message' => "GRN {$grnNo} recorded successfully."
        ], 201);
    } catch (\Throwable $e) {
        Database::rollBack();
        jsonError('SERVER_ERROR', $e->getMessage(), 500);
    }
});

\Core\Routing\Router::post('/api/v1/goods-receipts/{id}/post', function() {
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('#/goods-receipts/([0-9]+)/post#', $uri, $m);
    $grnId = (int)($m[1] ?? 0);

    $grn = Database::fetch("SELECT * FROM goods_receipts WHERE id = ?", [$grnId]);
    if (!$grn) {
        jsonError('NOT_FOUND', 'GRN not found.', 404);
    }
    if ($grn['status'] === 'posted') {
        jsonError('CONFLICT', 'GRN has already been posted.', 409);
    }

    $items = Database::fetchAll("SELECT * FROM goods_receipt_items WHERE goods_receipt_id = ?", [$grnId]);

    Database::beginTransaction();
    try {
        foreach ($items as $it) {
            $accepted = (float)$it['accepted_qty'];
            if ($accepted <= 0) continue;

            // 1. Create or update inventory lot
            $lotNumber = $it['internal_lot_no'] ?: ('LOT-GRN-' . $grn['grn_no']);
            $lotId = Database::insert('inventory_lots', [
                'company_id'          => 1,
                'lot_number'          => $lotNumber,
                'product_id'          => $it['product_id'],
                'supplier_lot_number' => $it['supplier_lot_no'],
                'owner_type'          => 'company',
                'origin_type'         => 'purchase',
                'origin_ref'          => $grn['grn_no'],
                'total_qty'           => $accepted,
                'available_qty'       => $accepted,
                'uom_id'              => $it['uom_id'],
                'unit_cost'           => $it['unit_rate'],
                'status'              => 'accepted'
            ]);

            // 2. Post immutable inventory movement
            InventoryService::postMovement([
                'company_id'        => 1,
                'warehouse_id'      => $grn['warehouse_id'],
                'product_id'        => $it['product_id'],
                'lot_id'            => $lotId,
                'movement_type'     => 'grn_receipt',
                'quantity_base_uom' => $accepted,
                'unit_cost'         => $it['unit_rate'],
                'source_type'       => 'goods_receipts',
                'source_id'         => $grnId,
                'source_line_id'    => $it['id'],
                'posted_by'         => 1
            ]);
        }

        Database::update('goods_receipts', [
            'status'    => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => 1
        ], 'id = :id', ['id' => $grnId]);

        Database::commit();
        jsonResponse([
            'id'      => $grnId,
            'grn_no'  => $grn['grn_no'],
            'status'  => 'posted',
            'message' => "GRN {$grn['grn_no']} posted to stock ledger successfully."
        ]);
    } catch (\Throwable $e) {
        Database::rollBack();
        jsonError('SERVER_ERROR', $e->getMessage(), 500);
    }
});

// -------------------------------------------------------------
// 6. Dyeing Batches & Process Logging API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/dye-batches', function() {
    $batches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC");
    jsonResponse($batches);
});

\Core\Routing\Router::post('/api/v1/dye-batches', function() {
    $input = getJsonInput();
    if (empty($input['dye_recipe_id']) || empty($input['machine_id']) || empty($input['planned_weight_kg'])) {
        jsonError('VALIDATION_ERROR', 'Recipe, Machine, and Planned Weight (Kg) are required.', 422);
    }

    $batchNo = Database::getNextDocumentNumber('dye_batch');
    $batchId = Database::insert('dye_batches', [
        'company_id'          => 1,
        'batch_no'            => $batchNo,
        'production_order_id' => $input['production_order_id'] ?? null,
        'dye_recipe_id'       => $input['dye_recipe_id'],
        'machine_id'          => $input['machine_id'],
        'planned_weight_kg'   => $input['planned_weight_kg'],
        'actual_loaded_kg'    => $input['planned_weight_kg'],
        'liquor_ratio'        => $input['liquor_ratio'] ?? '1:5',
        'operator_name'       => $input['operator_name'] ?? 'M. Kandasamy',
        'status'              => 'loaded',
        'process_start_time'  => date('Y-m-d H:i:s')
    ]);

    jsonResponse([
        'id'       => $batchId,
        'batch_no' => $batchNo,
        'status'   => 'loaded',
        'message'  => "Dyeing Batch {$batchNo} loaded successfully."
    ], 201);
});

\Core\Routing\Router::post('/api/v1/dye-batches/{id}/parameters', function() {
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('#/dye-batches/([0-9]+)/parameters#', $uri, $m);
    $batchId = (int)($m[1] ?? 0);
    $input = getJsonInput();

    if (empty($input['process_step'])) {
        jsonError('VALIDATION_ERROR', 'Process step is required.', 422);
    }

    $paramId = Database::insert('dye_batch_parameters', [
        'dye_batch_id'         => $batchId,
        'process_step'         => $input['process_step'],
        'set_temperature_c'    => $input['set_temperature_c'] ?? 60.0,
        'actual_temperature_c' => $input['actual_temperature_c'] ?? 60.0,
        'set_ph'               => $input['set_ph'] ?? 7.00,
        'actual_ph'            => $input['actual_ph'] ?? 7.00,
        'cycle_time_minutes'   => $input['cycle_time_minutes'] ?? 30,
        'operator_notes'       => $input['operator_notes'] ?? null
    ]);

    jsonResponse([
        'id'       => $paramId,
        'batch_id' => $batchId,
        'message'  => 'Process parameter reading recorded successfully.'
    ], 201);
});

// -------------------------------------------------------------
// 7. Quality 4-Point Inspection API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/fabric-inspections', function() {
    $inspections = Database::fetchAll("SELECT fi.*, r.roll_number, u.full_name as inspector_name FROM fabric_inspections fi JOIN inventory_rolls r ON fi.roll_id = r.id JOIN users u ON fi.inspector_id = u.id ORDER BY fi.id DESC");
    jsonResponse($inspections);
});

\Core\Routing\Router::post('/api/v1/fabric-inspections', function() {
    $input = getJsonInput();
    if (empty($input['roll_id']) || empty($input['inspected_meters'])) {
        jsonError('VALIDATION_ERROR', 'Roll and Inspected Meters are required.', 422);
    }

    $meters = (float)$input['inspected_meters'];
    $width = (float)($input['width_inches'] ?? 72);
    $points = (int)($input['total_penalty_points'] ?? 0);
    $pointsPer100 = CostingService::calculateFourPointSystem($points, $meters, $width);
    $grade = ($pointsPer100 <= 20) ? 'A' : (($pointsPer100 <= 28) ? 'B' : 'C');

    $inspNo = 'INSP-' . date('ymd') . '-' . rand(100, 999);
    $inspId = Database::insert('fabric_inspections', [
        'company_id'           => 1,
        'inspection_no'        => $inspNo,
        'roll_id'              => $input['roll_id'],
        'batch_id'             => $input['batch_id'] ?? null,
        'inspected_meters'     => $meters,
        'width_inches'         => $width,
        'gsm'                  => $input['gsm'] ?? 180,
        'total_penalty_points' => $points,
        'points_per_100_sq_yd' => $pointsPer100,
        'quality_grade'        => $grade,
        'fastness_wash'        => $input['fastness_wash'] ?? '4-5',
        'inspector_id'         => 3,
        'inspection_date'      => date('Y-m-d'),
        'disposition'          => ($grade === 'C') ? 'downgrade' : 'accepted',
        'remarks'              => $input['remarks'] ?? '4-Point system inspection verified.'
    ]);

    jsonResponse([
        'id'                   => $inspId,
        'inspection_no'        => $inspNo,
        'points_per_100_sq_yd' => $pointsPer100,
        'quality_grade'        => $grade,
        'message'              => "4-Point Fabric Inspection {$inspNo} recorded. Grade: {$grade}."
    ], 201);
});

// -------------------------------------------------------------
// 8. ETP / ZLD Daily Log API (Tamil Nadu TNPCB Compliance)
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/etp-logs', function() {
    $logs = Database::fetchAll("SELECT * FROM etp_logs ORDER BY log_date DESC, id DESC");
    jsonResponse($logs);
});

\Core\Routing\Router::post('/api/v1/etp-logs', function() {
    $input = getJsonInput();
    if (empty($input['inlet_flow_kld'])) {
        jsonError('VALIDATION_ERROR', 'Inlet flow is required.', 422);
    }

    $logId = Database::insert('etp_logs', [
        'company_id'                   => 1,
        'log_date'                     => $input['log_date'] ?? date('Y-m-d'),
        'shift'                        => $input['shift'] ?? 'shift_1',
        'inlet_flow_kld'               => $input['inlet_flow_kld'],
        'outlet_flow_kld'              => $input['outlet_flow_kld'] ?? $input['inlet_flow_kld'],
        'ro_permeate_recovery_kld'     => $input['ro_permeate_recovery_kld'] ?? ($input['inlet_flow_kld'] * 0.88),
        'mvr_evaporator_condensate_kld'=> $input['mvr_evaporator_condensate_kld'] ?? ($input['inlet_flow_kld'] * 0.10),
        'ph_value'                     => $input['ph_value'] ?? 7.4,
        'cod_ppm'                      => $input['cod_ppm'] ?? 18.0,
        'bod_ppm'                      => $input['bod_ppm'] ?? 5.0,
        'tds_ppm'                      => $input['tds_ppm'] ?? 1400.0,
        'sludge_generated_kg'          => $input['sludge_generated_kg'] ?? 800.0,
        'operator_name'                => $input['operator_name'] ?? 'G. Subramaniam',
        'remarks'                      => $input['remarks'] ?? 'TNPCB ZLD compliant.'
    ]);

    jsonResponse([
        'id'      => $logId,
        'message' => 'ETP / ZLD log registered successfully.'
    ], 201);
});

// -------------------------------------------------------------
// 9. Billing & Tax Invoices API
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/sales-invoices', function() {
    $invoices = Database::fetchAll("SELECT inv.*, c.name as customer_name, c.gstin as customer_gstin FROM sales_invoices inv JOIN customers c ON inv.customer_id = c.id ORDER BY inv.id DESC");
    jsonResponse($invoices);
});

\Core\Routing\Router::post('/api/v1/sales-invoices', function() {
    $input = getJsonInput();
    if (empty($input['customer_id']) || empty($input['items'])) {
        jsonError('VALIDATION_ERROR', 'Customer and invoice items are required.', 422);
    }

    Database::beginTransaction();
    try {
        $invNo = Database::getNextDocumentNumber('sales_invoice');
        $subtotal = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        foreach ($input['items'] as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            $rate = (float)($it['rate'] ?? 0);
            $lineTotal = $qty * $rate;
            $taxRate = (float)($it['tax_pct'] ?? 5);
            $lineCgst = ($lineTotal * ($taxRate / 2)) / 100;
            $lineSgst = ($lineTotal * ($taxRate / 2)) / 100;

            $subtotal += $lineTotal;
            $cgst += $lineCgst;
            $sgst += $lineSgst;
        }

        $taxTotal = $cgst + $sgst + $igst;
        $grandTotal = $subtotal + $taxTotal;

        $invId = Database::insert('sales_invoices', [
            'company_id'     => 1,
            'invoice_no'     => $invNo,
            'sales_order_id' => $input['sales_order_id'] ?? null,
            'customer_id'    => $input['customer_id'],
            'invoice_date'   => $input['invoice_date'] ?? date('Y-m-d'),
            'due_date'       => $input['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'subtotal'       => $subtotal,
            'cgst_amount'    => $cgst,
            'sgst_amount'    => $sgst,
            'igst_amount'    => $igst,
            'tax_total'      => $taxTotal,
            'grand_total'    => $grandTotal,
            'amount_paid'    => 0.00,
            'balance_amount' => $grandTotal,
            'status'         => 'posted',
            'posted_at'      => date('Y-m-d H:i:s'),
            'created_by'     => 1
        ]);

        foreach ($input['items'] as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            $rate = (float)($it['rate'] ?? 0);
            $lineTotal = $qty * $rate;
            $taxRate = (float)($it['tax_pct'] ?? 5);
            $lineCgst = ($lineTotal * ($taxRate / 2)) / 100;
            $lineSgst = ($lineTotal * ($taxRate / 2)) / 100;

            Database::insert('sales_invoice_items', [
                'sales_invoice_id' => $invId,
                'product_id'       => $it['product_id'],
                'quantity'         => $qty,
                'uom_id'           => $it['uom_id'] ?? 1,
                'rate'             => $rate,
                'tax_code_id'      => $it['tax_code_id'] ?? 2,
                'hsn_sac'          => $it['hsn_sac'] ?? '6006',
                'cgst_amount'      => $lineCgst,
                'sgst_amount'      => $lineSgst,
                'igst_amount'      => 0.00,
                'total_amount'     => $lineTotal + $lineCgst + $lineSgst
            ]);
        }

        // Post balanced Journal Entry for the invoice
        AccountingService::postJournal([
            'company_id'  => 1,
            'entry_date'  => date('Y-m-d'),
            'source_type' => 'sales_invoice',
            'source_id'   => $invId,
            'narration'   => "GST Tax Invoice {$invNo} posted to ledger"
        ], [
            ['account_id' => 4, 'debit' => $grandTotal, 'credit' => 0.00, 'line_narration' => "Trade Debtors - {$invNo}"],
            ['account_id' => 16, 'debit' => 0.00, 'credit' => $subtotal, 'line_narration' => 'Sales Revenue'],
            ['account_id' => 12, 'debit' => 0.00, 'credit' => $sgst, 'line_narration' => 'Output SGST 2.5%'],
            ['account_id' => 13, 'debit' => 0.00, 'credit' => $cgst, 'line_narration' => 'Output CGST 2.5%']
        ]);

        Database::commit();
        jsonResponse([
            'id'          => $invId,
            'invoice_no'  => $invNo,
            'grand_total' => $grandTotal,
            'status'      => 'posted',
            'message'     => "Tax Invoice {$invNo} generated and ledger updated."
        ], 201);
    } catch (\Throwable $e) {
        Database::rollBack();
        jsonError('SERVER_ERROR', $e->getMessage(), 500);
    }
});

// -------------------------------------------------------------
// 10. Customer Receipts & Collection Allocation
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/customer-receipts', function() {
    $receipts = Database::fetchAll("SELECT r.*, c.name as customer_name FROM customer_receipts r JOIN customers c ON r.customer_id = c.id ORDER BY r.id DESC");
    jsonResponse($receipts);
});

\Core\Routing\Router::post('/api/v1/customer-receipts', function() {
    $input = getJsonInput();
    if (empty($input['customer_id']) || empty($input['amount'])) {
        jsonError('VALIDATION_ERROR', 'Customer and amount are required.', 422);
    }

    $amount = (float)$input['amount'];
    $receiptNo = Database::getNextDocumentNumber('customer_receipt');

    Database::beginTransaction();
    try {
        $rcptId = Database::insert('customer_receipts', [
            'company_id'          => 1,
            'receipt_no'          => $receiptNo,
            'customer_id'         => $input['customer_id'],
            'receipt_date'        => $input['receipt_date'] ?? date('Y-m-d'),
            'payment_mode'        => $input['payment_mode'] ?? 'neft',
            'reference_number'    => $input['reference_number'] ?? null,
            'amount'              => $amount,
            'unallocated_amount'  => 0.00,
            'bank_account'        => $input['bank_account'] ?? 'HDFC Bank - Current A/c',
            'status'              => 'posted',
            'created_by'          => 1
        ]);

        if (!empty($input['invoice_id'])) {
            Database::insert('receipt_allocations', [
                'customer_receipt_id' => $rcptId,
                'sales_invoice_id'    => $input['invoice_id'],
                'allocated_amount'    => $amount
            ]);

            Database::getConnection()->prepare(
                "UPDATE sales_invoices SET amount_paid = amount_paid + :amt, balance_amount = balance_amount - :amt, status = IF(balance_amount - :amt <= 0, 'paid', 'partially_paid') WHERE id = :id"
            )->execute(['amt' => $amount, 'id' => $input['invoice_id']]);
        }

        // Post Journal Entry: Debit Bank, Credit Accounts Receivable
        AccountingService::postJournal([
            'company_id'  => 1,
            'entry_date'  => date('Y-m-d'),
            'source_type' => 'customer_receipt',
            'source_id'   => $rcptId,
            'narration'   => "Receipt {$receiptNo} via {$input['payment_mode']}"
        ], [
            ['account_id' => 2, 'debit' => $amount, 'credit' => 0.00, 'line_narration' => 'HDFC Bank'],
            ['account_id' => 4, 'debit' => 0.00, 'credit' => $amount, 'line_narration' => 'Accounts Receivable']
        ]);

        Database::commit();
        jsonResponse([
            'id'         => $rcptId,
            'receipt_no' => $receiptNo,
            'amount'     => $amount,
            'message'    => "Receipt {$receiptNo} posted successfully."
        ], 201);
    } catch (\Throwable $e) {
        Database::rollBack();
        jsonError('SERVER_ERROR', $e->getMessage(), 500);
    }
});

// -------------------------------------------------------------
// 11. Reports APIs
// -------------------------------------------------------------
\Core\Routing\Router::get('/api/v1/reports/order-status', function() {
    $orders = Database::fetchAll("SELECT so.order_no, c.name as customer_name, so.order_date, so.promised_delivery_date, so.grand_total, so.status, COALESCE(SUM(soi.ordered_qty), 0) as total_ordered_qty, COALESCE(SUM(soi.produced_qty), 0) as total_produced_qty, COALESCE(SUM(soi.qc_accepted_qty), 0) as total_accepted_qty FROM sales_orders so JOIN customers c ON so.customer_id = c.id LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id GROUP BY so.id ORDER BY so.id DESC");
    jsonResponse($orders);
});

\Core\Routing\Router::get('/api/v1/reports/trial-balance', function() {
    $accounts = Database::fetchAll("SELECT account_code, account_name, account_type, current_balance FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code ASC");
    jsonResponse($accounts);
});
