<?php
/**
 * Purchase Orders & Procure-to-Pay - KARCEM Textile ERP
 * File: modules/procurement/views/purchase-orders.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'Purchase Orders & Procure-to-Pay';
$activeModule = 'procurement';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$pos = Database::fetchAll("SELECT po.*, s.name as supplier_name, s.code as supplier_code FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id ORDER BY po.id DESC");
$suppliers = Database::fetchAll("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name ASC");
$products = Database::fetchAll("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

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
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
