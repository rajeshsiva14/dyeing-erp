<?php
/**
 * Purchase Orders & Procure-to-Pay - KARCEM Textile ERP
 * File: modules/procurement/views/purchase-orders.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Security\SessionManager;
use Core\Security\CSRF;

SessionManager::start();
$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);

$pageTitle = 'Purchase Orders & Procure-to-Pay';
$activeModule = 'procurement';

$msg = '';
$msgType = 'success';

// Handle New PO Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'create_po') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $poDate = trim($_POST['po_date'] ?? date('Y-m-d'));
    $deliveryDate = trim($_POST['delivery_date'] ?? date('Y-m-d', strtotime('+7 days')));
    $productId = (int)($_POST['product_id'] ?? 0);
    $orderedQty = (float)($_POST['ordered_qty'] ?? 0);
    $unitPrice = (float)($_POST['unit_price'] ?? 0);
    $paymentTerms = trim($_POST['payment_terms'] ?? '30 Days Net');
    $deliveryAddress = trim($_POST['delivery_address'] ?? 'Central Warehouse, SIPCOT Industrial Park, Perundurai');

    if ($supplierId <= 0 || $productId <= 0 || $orderedQty <= 0 || $unitPrice <= 0) {
        $msg = 'Please select a valid supplier, product, quantity (>0), and unit price (>0).';
        $msgType = 'danger';
    } else {
        try {
            $product = Database::fetch("SELECT * FROM products WHERE id = ?", [$productId]);
            if (!$product) {
                throw new \Exception('Selected product not found.');
            }

            Database::beginTransaction();

            $subtotal = round($orderedQty * $unitPrice, 2);
            $taxRate = 0.05; // 5% GST for textile/dye goods
            $taxTotal = round($subtotal * $taxRate, 2);
            $grandTotal = $subtotal + $taxTotal;

            $poCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM purchase_orders WHERE company_id = ?", [$companyId]);
            $poNo = sprintf("PO-%s-%04d", date('Y'), $poCount + 1);

            $poId = Database::insert('purchase_orders', [
                'company_id'       => $companyId,
                'po_no'            => $poNo,
                'supplier_id'      => $supplierId,
                'po_date'          => $poDate,
                'delivery_date'    => $deliveryDate,
                'currency'         => 'INR',
                'exchange_rate'    => 1.0000,
                'subtotal'         => $subtotal,
                'tax_total'        => $taxTotal,
                'grand_total'      => $grandTotal,
                'payment_terms'    => $paymentTerms,
                'delivery_address' => $deliveryAddress,
                'status'           => 'approved',
                'approved_by'      => $currentUser['id'] ?? 1,
                'approved_at'      => date('Y-m-d H:i:s'),
                'created_by'       => $currentUser['id'] ?? 1,
                'created_at'       => date('Y-m-d H:i:s')
            ]);

            Database::insert('purchase_order_items', [
                'purchase_order_id' => $poId,
                'product_id'        => $productId,
                'ordered_qty'       => $orderedQty,
                'received_qty'      => 0.00,
                'accepted_qty'      => 0.00,
                'uom_id'            => $product['base_uom_id'] ?? 1,
                'unit_price'        => $unitPrice,
                'tax_code_id'       => $product['tax_code_id'] ?? null,
                'tax_amount'        => $taxTotal,
                'total_amount'      => $grandTotal
            ]);

            Database::commit();
            $msg = "Purchase Order {$poNo} created and released successfully.";
            $msgType = 'success';
        } catch (\Exception $e) {
            Database::rollBack();
            $msg = 'Failed to create Purchase Order: ' . $e->getMessage();
            $msgType = 'danger';
        }
    }
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$pos = Database::fetchAll("SELECT po.*, s.name as supplier_name, s.code as supplier_code FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id ORDER BY po.id DESC");
$suppliers = Database::fetchAll("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name ASC");
$products = Database::fetchAll("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if (!empty($msg)): ?>
      <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/procurement') ?>">Procurement</a></li>
            <li class="breadcrumb-item active">Purchase Orders</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Supplier Purchase Orders (PO)</h1>
        <p class="text-secondary small mb-0">Procure-to-pay workflow: Purchase requisition, RFQ supplier comparison, PO release, GRN inward, and 3-way matching.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newPoModal">
          <i class="bi bi-plus-lg me-1"></i> Create Purchase Order
        </button>
      </div>
    </div>

    <!-- PO Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cart-check text-primary me-2"></i>Purchase Orders Register</h2>
        <span class="small text-secondary"><?= count($pos) ?> Purchase Orders</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>PO Number</th>
              <th>Supplier Name</th>
              <th>PO Date</th>
              <th>Delivery Due</th>
              <th>Subtotal</th>
              <th>Tax Total</th>
              <th>Grand Total</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php if (!empty($pos)): ?>
              <?php foreach ($pos as $po): ?>
                <tr>
                  <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($po['po_no']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($po['supplier_name']) ?></td>
                  <td><?= date('d M Y', strtotime($po['po_date'])) ?></td>
                  <td><span class="text-dark fw-semibold"><?= date('d M Y', strtotime($po['delivery_date'])) ?></span></td>
                  <td>₹<?= NumberToWords::formatIndian($po['subtotal'], 2) ?></td>
                  <td>₹<?= NumberToWords::formatIndian($po['tax_total'], 2) ?></td>
                  <td class="fw-bold text-dark">₹<?= NumberToWords::formatIndian($po['grand_total'], 2) ?></td>
                  <td><span class="badge-status status-<?= htmlspecialchars($po['status']) ?>"><?= htmlspecialchars($po['status']) ?></span></td>
                  <td class="text-end">
                    <a href="<?= Url::to('/print/purchase-order?id=' . $po['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Print PO Sheet">
                      <i class="bi bi-printer me-1"></i> Print
                    </a>
                    <a href="<?= Url::to('/inventory/grn') ?>" class="btn btn-sm btn-outline-dark" title="Receive against PO">
                      <i class="bi bi-box-arrow-in-down me-1"></i> Create GRN
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No purchase orders found. Click "Create Purchase Order" above to release a new supplier order.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- Create Purchase Order Modal -->
<div class="modal fade" id="newPoModal" tabindex="-1" aria-labelledby="newPoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newPoModalLabel"><i class="bi bi-cart-plus me-2"></i>Create Supplier Purchase Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <?= CSRF::getField() ?>
        <input type="hidden" name="form_action" value="create_po">

        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Supplier Vendor <span class="text-danger">*</span></label>
              <select name="supplier_id" class="form-select" required>
                <option value="">-- Select Approved Vendor --</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['code'] . ' - ' . $s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">PO Date <span class="text-danger">*</span></label>
              <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Delivery Due Date <span class="text-danger">*</span></label>
              <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Product / Material Item <span class="text-danger">*</span></label>
              <select name="product_id" id="po_product_id" class="form-select" required onchange="updateProductRate()">
                <option value="">-- Select Material / Dye / Yarn --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= $p['id'] ?>" data-cost="<?= $p['standard_cost'] ?>"><?= htmlspecialchars($p['sku'] . ' - ' . $p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Ordered Qty (Base UOM) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" name="ordered_qty" id="po_qty" class="form-control" placeholder="e.g. 500" required oninput="calcPoTotal()">
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Unit Price (₹) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" name="unit_price" id="po_rate" class="form-control" placeholder="0.00" required oninput="calcPoTotal()">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Terms</label>
              <input type="text" name="payment_terms" class="form-control" value="30 Days PDC from Invoice">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Estimated Total (Incl. 5% GST)</label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="text" id="po_estimated_total" class="form-control font-monospace fw-bold" readonly value="0.00">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Delivery Address</label>
              <textarea name="delivery_address" class="form-control" rows="2">Central Mill Warehouse, SIPCOT Industrial Park, Perundurai, Tamil Nadu 638052</textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark"><i class="bi bi-check2-circle me-1"></i> Release Purchase Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function updateProductRate() {
  const sel = document.getElementById('po_product_id');
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.cost) {
    document.getElementById('po_rate').value = parseFloat(opt.dataset.cost).toFixed(2);
  }
  calcPoTotal();
}

function calcPoTotal() {
  const qty = parseFloat(document.getElementById('po_qty').value) || 0;
  const rate = parseFloat(document.getElementById('po_rate').value) || 0;
  const subtotal = qty * rate;
  const total = subtotal * 1.05; // 5% GST
  document.getElementById('po_estimated_total').value = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
