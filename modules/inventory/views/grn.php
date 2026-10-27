<?php
/**
 * Goods Receipt Notes (GRN) & Stores Inward - KARCEM Textile ERP
 * File: modules/inventory/views/grn.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'Goods Receipt Notes (GRN)';
$activeModule = 'inventory';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$grns = Database::fetchAll("SELECT grn.*, s.name as supplier_name, s.code as supplier_code, w.name as warehouse_name FROM goods_receipts grn JOIN suppliers s ON grn.supplier_id = s.id JOIN warehouses w ON grn.warehouse_id = w.id ORDER BY grn.id DESC");
$suppliers = Database::fetchAll("SELECT id, code, name FROM suppliers WHERE status = 'active' ORDER BY name ASC");
$products = Database::fetchAll("SELECT id, sku, name, standard_cost FROM products WHERE is_active = 1 ORDER BY name ASC");
$warehouses = Database::fetchAll("SELECT id, code, name FROM warehouses WHERE status = 'active' ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/inventory') ?>">Inventory</a></li>
            <li class="breadcrumb-item active">GRN</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Goods Receipt Notes (GRN) & Stores Inward</h1>
        <p class="text-secondary small mb-0">Record incoming raw cotton, combed yarn, grey fabric, dyes and chemicals with inspection and lot assignments.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newGrnModal">
          <i class="bi bi-plus-lg me-1"></i> New GRN Inward
        </button>
      </div>
    </div>

    <!-- GRN List -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Goods Receipt Register</h2>
        <span class="small text-secondary"><?= count($grns) ?> GRNs Logged</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>GRN Number</th>
              <th>Supplier</th>
              <th>Receipt Date</th>
              <th>Destination Warehouse</th>
              <th>Vehicle Number</th>
              <th>Supplier Challan / Inv</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($grns as $g): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($g['grn_no']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($g['supplier_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($g['supplier_code']) ?></span>
                </td>
                <td><?= date('d M Y', strtotime($g['receipt_date'])) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($g['warehouse_name']) ?></span></td>
                <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($g['vehicle_no'] ?? '-') ?></span></td>
                <td class="text-muted"><?= htmlspecialchars($g['supplier_challan_no'] ?? '-') ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($g['status']) ?>"><?= htmlspecialchars($g['status']) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <?php if ($g['status'] === 'draft'): ?>
                      <button class="btn btn-success" onclick="postGrn(<?= $g['id'] ?>)" title="Post to Immutable Stock Movement Ledger">
                        <i class="bi bi-check-circle me-1"></i> Post GRN
                      </button>
                    <?php endif; ?>
                    <a href="<?= Url::to('/print/grn?id=' . $g['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P15 Goods Receipt Note">
                      <i class="bi bi-printer"></i>
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

<!-- New GRN Modal -->
<div class="modal fade" id="newGrnModal" tabindex="-1" aria-labelledby="newGrnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newGrnModalLabel"><i class="bi bi-box-arrow-in-down me-2"></i>Record New Goods Receipt (GRN)</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="newGrnForm" onsubmit="submitNewGrn(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Supplier <span class="text-danger">*</span></label>
              <select class="form-select" id="grn_supplier_id" required>
                <option value="">Select Supplier...</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['code'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Destination Warehouse <span class="text-danger">*</span></label>
              <select class="form-select" id="grn_warehouse_id" required>
                <?php foreach ($warehouses as $w): ?>
                  <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= $w['code'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Receipt Date</label>
              <input type="date" class="form-control" id="grn_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Supplier Challan / Invoice No</label>
              <input type="text" class="form-control" id="grn_challan_no" placeholder="e.g. DC-99881">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Vehicle Number</label>
              <input type="text" class="form-control" id="grn_vehicle_no" placeholder="TN 38 AA 1234">
            </div>

            <!-- Material Line Item -->
            <div class="col-12 border-top pt-3">
              <h6 class="fw-bold small text-dark mb-2">Received Material Details</h6>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small">Product / Raw Material <span class="text-danger">*</span></label>
                  <select class="form-select" id="grn_item_product_id" required>
                    <option value="">Select Material...</option>
                    <?php foreach ($products as $p): ?>
                      <option value="<?= $p['id'] ?>" data-cost="<?= $p['standard_cost'] ?>"><?= htmlspecialchars($p['name']) ?> (Cost: ₹<?= $p['standard_cost'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Received Qty (Kg) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="grn_item_qty" value="1000" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Supplier Lot / Bale No</label>
                  <input type="text" class="form-control" id="grn_item_lot" placeholder="e.g. VARD-LOT-99">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveGrnBtn"><i class="bi bi-save me-1"></i> Save GRN Draft</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  async function submitNewGrn(e) {
    e.preventDefault();
    const btn = document.getElementById('saveGrnBtn');
    btn.disabled = true;

    const payload = {
      supplier_id: parseInt(document.getElementById('grn_supplier_id').value),
      warehouse_id: parseInt(document.getElementById('grn_warehouse_id').value),
      receipt_date: document.getElementById('grn_date').value,
      supplier_challan_no: document.getElementById('grn_challan_no').value,
      vehicle_no: document.getElementById('grn_vehicle_no').value,
      items: [{
        product_id: parseInt(document.getElementById('grn_item_product_id').value),
        received_qty: parseFloat(document.getElementById('grn_item_qty').value),
        accepted_qty: parseFloat(document.getElementById('grn_item_qty').value),
        supplier_lot_no: document.getElementById('grn_item_lot').value,
        unit_rate: 320.00
      }]
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/goods-receipts') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('GRN Created', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to save GRN');
        btn.disabled = false;
      }
    } catch (err) {
      alert('Error: ' + err.message);
      btn.disabled = false;
    }
  }

  async function postGrn(id) {
    if (!confirm('Post this GRN? This will assign immutable lot numbers and update live stock balances.')) {
      return;
    }
    try {
      const res = await fetch('<?= Url::to('/api/v1/goods-receipts/') ?>' + id + '/post', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('GRN Posted', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to post GRN');
      }
    } catch (err) {
      alert('Error: ' + err.message);
    }
  }
</script>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
