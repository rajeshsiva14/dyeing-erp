<?php
/**
 * Production Orders & Floor Scheduling - KARCEM Textile ERP
 * File: modules/planning/views/production-orders.php
 */

use Core\Database\Database;
use Core\Support\Url;

$pageTitle = 'Production Orders & Scheduling';
$activeModule = 'planning';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$orders = Database::fetchAll("SELECT po.*, p.name as product_name, p.sku, u.code as uom_code, so.order_no as sales_order_no FROM production_orders po JOIN products p ON po.product_id = p.id JOIN uoms u ON po.uom_id = u.id LEFT JOIN sales_orders so ON po.sales_order_id = so.id ORDER BY po.id DESC");
$products = Database::fetchAll("SELECT id, sku, name FROM products WHERE is_active = 1 ORDER BY name ASC");
$salesOrders = Database::fetchAll("SELECT id, order_no FROM sales_orders WHERE status = 'confirmed' ORDER BY id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/planning') ?>">Planning</a></li>
            <li class="breadcrumb-item active">Production Orders</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Production Orders & Floor Release</h1>
        <p class="text-secondary small mb-0">Order release, material demand calculations, routing steps, and machine allocation.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newProdOrderModal">
          <i class="bi bi-plus-lg me-1"></i> Plan Production Order
        </button>
      </div>
    </div>

    <!-- Production Orders Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-calendar2-check text-primary me-2"></i>Production Schedule Register</h2>
        <span class="small text-secondary"><?= count($orders) ?> Orders Scheduled</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Order Number</th>
              <th>Product / Fabric</th>
              <th>Sales Order Ref</th>
              <th>Planned Qty</th>
              <th>Actual Output</th>
              <th>Stage</th>
              <th>Start / Due Date</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($orders as $po): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($po['order_no']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($po['product_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;">SKU: <?= htmlspecialchars($po['sku']) ?></span>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($po['sales_order_no'] ?? 'Replenishment') ?></span></td>
                <td class="fw-bold font-monospace"><?= number_format($po['planned_qty'], 1) ?> <?= $po['uom_code'] ?></td>
                <td class="fw-bold text-success font-monospace"><?= number_format($po['actual_output_qty'], 1) ?> <?= $po['uom_code'] ?></td>
                <td><span class="badge bg-secondary-subtle text-secondary text-uppercase"><?= htmlspecialchars($po['stage']) ?></span></td>
                <td>
                  <span class="text-dark fw-semibold"><?= date('d M', strtotime($po['start_date'])) ?></span> &rarr;
                  <span class="text-muted"><?= date('d M Y', strtotime($po['due_date'])) ?></span>
                </td>
                <td><span class="badge-status status-<?= htmlspecialchars($po['status']) ?>"><?= htmlspecialchars($po['status']) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <?php if ($po['status'] === 'planned'): ?>
                      <button class="btn btn-outline-success" onclick="releaseProdOrder(<?= $po['id'] ?>)">
                        <i class="bi bi-play-circle me-1"></i> Release
                      </button>
                    <?php endif; ?>
                    <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-dark" title="Load Vessel">
                      <i class="bi bi-droplet-half me-1"></i> Vessel
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- New Production Order Modal -->
<div class="modal fade" id="newProdOrderModal" tabindex="-1" aria-labelledby="newProdOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newProdOrderModalLabel"><i class="bi bi-calendar-plus me-2"></i>Create Production Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="prodOrderForm" onsubmit="submitProdOrder(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold">Link to Confirmed Sales Order (Optional)</label>
              <select class="form-select" id="po_sales_order_id">
                <option value="">Stock Replenishment (No SO)</option>
                <?php foreach ($salesOrders as $so): ?>
                  <option value="<?= $so['id'] ?>"><?= htmlspecialchars($so['order_no']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Product to Manufacture <span class="text-danger">*</span></label>
              <select class="form-select" id="po_product_id" required>
                <option value="">Select Product...</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['sku'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Planned Target Qty (Kg/Pcs) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="po_qty" value="1000" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Manufacturing Route Stage</label>
              <select class="form-select" id="po_stage">
                <option value="dyeing">Soft Flow Dyeing & Finishing</option>
                <option value="knitting">Circular Knitting</option>
                <option value="spinning">Spinning Mill</option>
                <option value="garmenting">Garment Cutting & Sewing</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Start Date</label>
              <input type="date" class="form-control" id="po_start" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Due Delivery Date</label>
              <input type="date" class="form-control" id="po_due" value="<?= date('Y-m-d', strtotime('+2 weeks')) ?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="savePoBtn"><i class="bi bi-save me-1"></i> Plan Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  async function submitProdOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('savePoBtn');
    btn.disabled = true;

    const payload = {
      sales_order_id: document.getElementById('po_sales_order_id').value ? parseInt(document.getElementById('po_sales_order_id').value) : null,
      product_id: parseInt(document.getElementById('po_product_id').value),
      planned_qty: parseFloat(document.getElementById('po_qty').value),
      stage: document.getElementById('po_stage').value,
      start_date: document.getElementById('po_start').value,
      due_date: document.getElementById('po_due').value
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/production-orders') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Production Order Created', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to create order');
        btn.disabled = false;
      }
    } catch (err) {
      alert('Error: ' + err.message);
      btn.disabled = false;
    }
  }

  async function releaseProdOrder(id) {
    if (!confirm('Release this Production Order to the shop floor?')) return;
    try {
      const res = await fetch('<?= Url::to('/api/v1/production-orders/') ?>' + id + '/release', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Order Released', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to release order');
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }
</script>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
