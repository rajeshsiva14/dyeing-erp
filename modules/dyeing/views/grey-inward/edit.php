<?php
/**
 * Dedicated Update Form: Grey Fabric Inward & Tare Weighment
 * File: modules/dyeing/views/grey-inward-edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Grey Fabric Inward';
$activeModule = 'grey-inward';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$inwardId = (int)($_GET['id'] ?? ($_POST['inward_id'] ?? 0));

if ($inwardId <= 0) {
    header('Location: ' . Url::to('/dyeing/grey-inward'));
    exit;
}

$errors = [];
$successMsg = '';

// Handle POST to save the update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_inward') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $supplierName = trim($_POST['supplier_name'] ?? '');
    $sourceType   = trim($_POST['source_type'] ?? 'knitting');
    $challanNo    = trim($_POST['delivery_challan_no'] ?? '');
    $vehicleNo    = trim($_POST['vehicle_no'] ?? '');
    $poRef        = trim($_POST['po_or_order_ref'] ?? '');
    $warehouse    = trim($_POST['warehouse_location'] ?? 'Grey Fabric Store - Bay A');
    $receivedBy   = trim($_POST['received_by'] ?? 'Stores Officer');
    $fabricCode   = trim($_POST['fabric_code'] ?? '');
    $fabricDesc   = trim($_POST['fabric_description'] ?? '');
    $construction = trim($_POST['construction'] ?? 'knitted');
    $composition  = trim($_POST['composition'] ?? '');
    $rolls        = max(1, (int)($_POST['received_rolls'] ?? 1));
    $grossKg      = round((float)($_POST['gross_weight_kg'] ?? 0), 2);
    $tareKg       = round((float)($_POST['tare_weight_kg'] ?? 0), 2);
    $netKg        = round(max(0, $grossKg - $tareKg), 2);
    $widthInches  = (float)($_POST['width_inches'] ?? 0);
    $gsm          = (int)($_POST['gsm'] ?? 0);
    $remarks      = trim($_POST['remarks'] ?? '');

    if ($supplierName === '') $errors[] = 'Supplier / Source unit is required.';
    if ($challanNo === '')    $errors[] = 'Delivery challan number is required.';
    if ($fabricCode === '')   $errors[] = 'Fabric code is required.';
    if ($grossKg <= 0)        $errors[] = 'Gross weight must be greater than 0.';
    if ($netKg <= 0)          $errors[] = 'Tare weight must be less than gross weight.';

    if (!$errors) {
        Database::execute("
            UPDATE grey_fabric_inwards 
            SET supplier_name = ?, source_type = ?, delivery_challan_no = ?, vehicle_no = ?,
                po_or_order_ref = ?, warehouse_location = ?, received_by = ?,
                fabric_code = ?, fabric_description = ?, construction = ?, composition = ?,
                received_rolls = ?, gross_weight_kg = ?, tare_weight_kg = ?, net_weight_kg = ?,
                width_inches = ?, gsm = ?, remarks = ?
            WHERE id = ? AND company_id = ?
        ", [
            $supplierName, $sourceType, $challanNo, $vehicleNo,
            $poRef, $warehouse, $receivedBy,
            $fabricCode, $fabricDesc, $construction, $composition,
            $rolls, $grossKg, $tareKg, $netKg,
            $widthInches, $gsm, $remarks,
            $inwardId, $companyId
        ]);

        header('Location: ' . Url::to('/dyeing/grey-inward?updated=' . $inwardId));
        exit;
    }
}

$inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inwardId, $companyId]);

if (!$inward) {
    header('Location: ' . Url::to('/dyeing/grey-inward'));
    exit;
}

$csrfToken = CSRF::getToken();

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Document Header Bar -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Grey Fabric Inward</h1>
            <span class="badge bg-primary font-monospace fs-6"><?= htmlspecialchars($inward['inward_no']) ?></span>
            <code class="code-pill">Lot: <?= htmlspecialchars($inward['grey_lot_no']) ?></code>
          </div>
          <p class="text-secondary small mb-0 mt-1">Full-page editor for delivery challan specifications, tare weighment deductions, storage location, and lot details.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/print/grey-inward?id=' . $inward['id'] . '&format=pdf') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-file-earmark-pdf me-1"></i> Download TCPDF Slip
        </a>
        <a href="<?= Url::to('/dyeing/grey-inspection?inward_id=' . $inward['id']) ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-search me-1"></i> QC Inspection
        </a>
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editInwardForm" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Inward
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</div>
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Full Document Form -->
    <form id="editInwardForm" method="POST" action="<?= Url::to('/dyeing/grey-inward-edit?id=' . $inward['id']) ?>">
      <input type="hidden" name="action" value="update_inward">
      <input type="hidden" name="inward_id" value="<?= $inward['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <!-- Section A: Source & Transport Details -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-truck text-primary me-2"></i>Section A — Source & Supplier Details</h2>
            <p class="cz-doc-section-desc">Knitting source mill, delivery challan details, carrier registration, and storage bay.</p>
          </div>
          <span class="badge badge-bw font-monospace">SOURCE SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Source Type <span class="text-danger">*</span></label>
            <select name="source_type" class="form-select form-select-sm" required>
              <?php
                $stOptions = [
                  'knitting' => 'Knitting Unit',
                  'weaving' => 'Weaving Unit',
                  'external_supplier' => 'External Yarn / Fabric Supplier',
                  'transfer' => 'Inter-Unit Transfer',
                  'customer_jobwork' => 'Customer-Owned Jobwork'
                ];
                foreach ($stOptions as $k => $lbl):
              ?>
                <option value="<?= $k ?>" <?= ($inward['source_type'] === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Supplier / Knitting Source Unit <span class="text-danger">*</span></label>
            <input type="text" name="supplier_name" value="<?= htmlspecialchars($inward['supplier_name']) ?>" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Delivery Challan No <span class="text-danger">*</span></label>
            <input type="text" name="delivery_challan_no" value="<?= htmlspecialchars($inward['delivery_challan_no']) ?>" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">PO / Order Ref</label>
            <input type="text" name="po_or_order_ref" value="<?= htmlspecialchars($inward['po_or_order_ref'] ?? '') ?>" class="form-control form-control-sm" placeholder="PO-2026-XXXX">
          </div>
          <div class="col-md-4">
            <label class="form-label">Vehicle / Carrier Registration No</label>
            <input type="text" name="vehicle_no" value="<?= htmlspecialchars($inward['vehicle_no'] ?? '') ?>" class="form-control form-control-sm font-monospace" placeholder="e.g. TN-39-AA-1234">
          </div>
          <div class="col-md-6">
            <label class="form-label">Warehouse Storage Location <span class="text-danger">*</span></label>
            <select name="warehouse_location" class="form-select form-select-sm" required>
              <?php
                $whOptions = [
                  'Grey Fabric Store - Bay A',
                  'Grey Fabric Store - Bay B',
                  'Customer Material Hold - Bay C',
                  'Quarantine Holding Area'
                ];
                foreach ($whOptions as $wh):
              ?>
                <option value="<?= $wh ?>" <?= ($inward['warehouse_location'] === $wh) ? 'selected' : '' ?>><?= $wh ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Received By (Stores Officer) <span class="text-danger">*</span></label>
            <input type="text" name="received_by" class="form-control form-control-sm" value="<?= htmlspecialchars($inward['received_by'] ?? 'P. Murugan (Stores)') ?>" required>
          </div>
        </div>
      </div>

      <!-- Section B: Fabric Specifications & Lot Allocation -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-box-seam text-primary me-2"></i>Section B — Fabric Specifications & Lot Allocation</h2>
            <p class="cz-doc-section-desc">Fabric code, structure, composition, tare weighment deductions, and allocated lot number.</p>
          </div>
          <span class="badge badge-bw font-monospace"><?= htmlspecialchars($inward['grey_lot_no']) ?></span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Fabric Code <span class="text-danger">*</span></label>
            <input type="text" name="fabric_code" value="<?= htmlspecialchars($inward['fabric_code']) ?>" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
            <input type="text" name="fabric_description" value="<?= htmlspecialchars($inward['fabric_description']) ?>" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Construction</label>
            <select name="construction" class="form-select form-select-sm">
              <option value="knitted" <?= ($inward['construction'] === 'knitted') ? 'selected' : '' ?>>Knitted</option>
              <option value="woven" <?= ($inward['construction'] === 'woven') ? 'selected' : '' ?>>Woven</option>
              <option value="other" <?= ($inward['construction'] === 'other') ? 'selected' : '' ?>>Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Composition <span class="text-danger">*</span></label>
            <input type="text" name="composition" value="<?= htmlspecialchars($inward['composition']) ?>" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Width (Inches) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="width_inches" value="<?= $inward['width_inches'] ?>" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Target GSM <span class="text-danger">*</span></label>
            <input type="number" name="gsm" value="<?= $inward['gsm'] ?>" class="form-control form-control-sm font-monospace" required>
          </div>

          <div class="col-12"><hr class="my-2"></div>

          <div class="col-md-3">
            <label class="form-label">Received Rolls <span class="text-danger">*</span></label>
            <input type="number" name="received_rolls" id="upd_rolls" value="<?= $inward['received_rolls'] ?>" class="form-control form-control-sm font-monospace js-upd-calc" min="1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Gross Weight (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="gross_weight_kg" id="upd_gross" value="<?= $inward['gross_weight_kg'] ?>" class="form-control form-control-sm font-monospace js-upd-calc" min="0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tare Deduction (Kg)</label>
            <input type="number" step="0.01" name="tare_weight_kg" id="upd_tare" value="<?= $inward['tare_weight_kg'] ?>" class="form-control form-control-sm font-monospace js-upd-calc" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Certified Net Wt (Kg)</label>
            <input type="text" id="upd_net" class="form-control form-control-sm font-monospace fw-bold bg-light" readonly value="<?= number_format($inward['net_weight_kg'], 2) ?>">
          </div>

          <div class="col-md-12">
            <label class="form-label">Inspection Notes / Receiving Remarks</label>
            <input type="text" name="remarks" value="<?= htmlspecialchars($inward['remarks'] ?? '') ?>" class="form-control form-control-sm" placeholder="e.g. Clean rolls, moisture within 6.5%, intact loop structure">
          </div>
        </div>
      </div>

      <!-- Live Telemetry Card -->
      <div class="border rounded-3 p-3 bg-white mt-3 shadow-sm mb-4">
        <div class="row text-center g-2">
          <div class="col-3 border-end">
            <div class="text-secondary small">Received Rolls</div>
            <div class="fw-bold font-monospace fs-5 text-dark" id="dispRolls"><?= $inward['received_rolls'] ?></div>
          </div>
          <div class="col-3 border-end">
            <div class="text-secondary small">Gross Weight</div>
            <div class="fw-bold font-monospace fs-5 text-dark" id="dispGross"><?= number_format($inward['gross_weight_kg'], 2) ?> <span class="fs-6 fw-normal text-muted">Kg</span></div>
          </div>
          <div class="col-3 border-end">
            <div class="text-secondary small">Tare Deduction</div>
            <div class="fw-bold font-monospace fs-5 text-muted" id="dispTare"><?= number_format($inward['tare_weight_kg'], 2) ?> <span class="fs-6 fw-normal text-muted">Kg</span></div>
          </div>
          <div class="col-3">
            <div class="text-secondary small">Certified Net Weight</div>
            <div class="fw-bold font-monospace fs-5 text-primary" id="dispNet"><?= number_format($inward['net_weight_kg'], 2) ?> <span class="fs-6 fw-normal text-muted">Kg</span></div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Inward
        </button>
      </div>
    </form>

  </div>
</main>

<script>
(function() {
  function recalcUpdate() {
    const g = parseFloat(document.getElementById('upd_gross')?.value) || 0;
    const t = parseFloat(document.getElementById('upd_tare')?.value) || 0;
    const r = parseInt(document.getElementById('upd_rolls')?.value, 10) || 0;
    const net = Math.max(0, g - t);
    document.getElementById('upd_net').value = net.toFixed(2);
    document.getElementById('dispRolls').textContent = r;
    document.getElementById('dispGross').textContent = g.toFixed(2) + ' Kg';
    document.getElementById('dispTare').textContent = t.toFixed(2) + ' Kg';
    document.getElementById('dispNet').textContent = net.toFixed(2) + ' Kg';
  }
  document.querySelectorAll('.js-upd-calc').forEach(el => el.addEventListener('input', recalcUpdate));
})();
</script>

<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
