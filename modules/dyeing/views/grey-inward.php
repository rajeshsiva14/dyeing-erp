<?php
/**
 * Grey Fabric Inward & Verification
 * Receive grey fabric into dyeing unit with gross/tare/net weighment and lot verification
 * File: modules/dyeing/views/grey-inward.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Grey Fabric Inward';
$activeModule = 'grey-inward';

// Handle POST to save new grey fabric inward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inward') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $inwardNo = 'GIN-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $sourceType = $_POST['source_type'] ?? 'knitting';
    $supplierName = trim($_POST['supplier_name'] ?? '');
    $challanNo = trim($_POST['delivery_challan_no'] ?? '');
    $poRef = trim($_POST['po_or_order_ref'] ?? '');
    $receivedBy = trim($_POST['received_by'] ?? 'Stores Receiver');
    $warehouse = trim($_POST['warehouse_location'] ?? 'Grey Fabric Store - Bay A');
    $vehicleNo = trim($_POST['vehicle_no'] ?? '');
    
    $fabricCode = trim($_POST['fabric_code'] ?? 'FAB-SJ-160');
    $fabricDesc = trim($_POST['fabric_description'] ?? '100% Combed Cotton Single Jersey');
    $construction = $_POST['construction'] ?? 'knitted';
    $composition = trim($_POST['composition'] ?? '100% Combed Cotton');
    $greyLotNo = trim($_POST['grey_lot_no'] ?? ('LOT-GRY-' . date('y') . '-' . rand(1000, 9999)));
    
    $receivedRolls = (int)($_POST['received_rolls'] ?? 1);
    $grossWeight = (float)($_POST['gross_weight_kg'] ?? 0);
    $tareWeight = (float)($_POST['tare_weight_kg'] ?? 0);
    $netWeight = max(0, $grossWeight - $tareWeight);
    $widthInches = (float)($_POST['width_inches'] ?? 48);
    $gsm = (int)($_POST['gsm'] ?? 160);
    $remarks = trim($_POST['remarks'] ?? '');

    Database::insert('grey_fabric_inwards', [
        'company_id'          => 1,
        'inward_no'           => $inwardNo,
        'inward_date'         => date('Y-m-d H:i:s'),
        'source_type'         => $sourceType,
        'supplier_name'       => $supplierName,
        'delivery_challan_no' => $challanNo,
        'po_or_order_ref'     => $poRef,
        'vehicle_no'          => $vehicleNo,
        'received_by'         => $receivedBy,
        'warehouse_location'  => $warehouse,
        'fabric_code'         => $fabricCode,
        'fabric_description'  => $fabricDesc,
        'construction'        => $construction,
        'composition'         => $composition,
        'grey_lot_no'         => $greyLotNo,
        'received_rolls'      => $receivedRolls,
        'gross_weight_kg'     => $grossWeight,
        'tare_weight_kg'      => $tareWeight,
        'net_weight_kg'       => $netWeight,
        'width_inches'        => $widthInches,
        'gsm'                 => $gsm,
        'qc_status'           => 'inspection_pending',
        'remarks'             => $remarks
    ]);

    header('Location: ' . Url::to('/dyeing/grey-inward?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$isNewForm = (isset($_GET['action']) && $_GET['action'] === 'new');

$inwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards ORDER BY id DESC");
$totalRolls = (int)Database::fetch("SELECT SUM(received_rolls) as s FROM grey_fabric_inwards")['s'];
$totalNetKg = (float)Database::fetch("SELECT SUM(net_weight_kg) as s FROM grey_fabric_inwards")['s'];
$pendingQc = (int)Database::fetch("SELECT COUNT(*) as c FROM grey_fabric_inwards WHERE qc_status = 'inspection_pending'")['c'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if ($isNewForm): ?>
      <!-- ========================================================================= -->
      <!-- FULL-PAGE FORM: NEW GREY FABRIC INWARD (No Modals)               -->
      <!-- ========================================================================= -->

      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">Register Grey Fabric Inward</h1>
              <span class="badge badge-bw">NEW INWARD</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Record incoming grey rolls with gross, tare, and net weighment, storage allocation, and lot tracking.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newInwardForm" class="btn btn-dark btn-sm">
            <i class="bi bi-check-lg me-1"></i> Receive Fabric & Send to QC
          </button>
        </div>
      </div>

      <form id="newInwardForm" method="POST" action="<?= Url::to('/dyeing/grey-inward') ?>">
        <input type="hidden" name="action" value="save_inward">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <!-- Section A: Source & Transport Details -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-truck"></i> Section A — Source & Supplier Details</h2>
              <p class="cz-doc-section-desc">Knitting source mill, delivery challan details, vehicle registration, and storage bay.</p>
            </div>
            <span class="badge badge-bw font-monospace">SOURCE SPECS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Source Type <span class="text-danger">*</span></label>
              <select name="source_type" class="form-select form-select-sm" required>
                <option value="knitting">Knitting Unit</option>
                <option value="weaving">Weaving Unit</option>
                <option value="external_supplier">External Yarn / Fabric Supplier</option>
                <option value="transfer">Inter-Unit Transfer</option>
                <option value="customer_jobwork">Customer-Owned Jobwork</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Supplier / Knitting Source Unit <span class="text-danger">*</span></label>
              <input type="text" name="supplier_name" class="form-control form-control-sm" placeholder="e.g. Sri Gomathi Knitting Mills, Tirupur" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Delivery Challan No <span class="text-danger">*</span></label>
              <input type="text" name="delivery_challan_no" class="form-control form-control-sm font-monospace" placeholder="DC-2026-XXXX" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">PO / Order Ref</label>
              <input type="text" name="po_or_order_ref" class="form-control form-control-sm" placeholder="PO-2026-XXXX">
            </div>
            <div class="col-md-4">
              <label class="form-label">Vehicle No</label>
              <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace" placeholder="TN-39-AA-1234">
            </div>
            <div class="col-md-6">
              <label class="form-label">Warehouse Storage Location <span class="text-danger">*</span></label>
              <select name="warehouse_location" class="form-select form-select-sm" required>
                <option value="Grey Fabric Store - Bay A">Grey Fabric Store - Bay A</option>
                <option value="Grey Fabric Store - Bay B">Grey Fabric Store - Bay B</option>
                <option value="Customer Material Hold - Bay C">Customer Material Hold - Bay C (Jobwork)</option>
                <option value="Quarantine Holding Area">Quarantine Holding Area</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Received By <span class="text-danger">*</span></label>
              <input type="text" name="received_by" class="form-control form-control-sm" value="P. Murugan (Stores Officer)" required>
            </div>
          </div>
        </div>

        <!-- Section B: Fabric Details & Weight -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-box-seam"></i> Section B — Fabric & Tare Weight Details</h2>
              <p class="cz-doc-section-desc">Weighment bridge scale metrics: Gross weight, packaging tare weight, and net weight calculation.</p>
            </div>
            <span class="badge badge-bw font-monospace">WEIGHMENT METRICS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Fabric Code <span class="text-danger">*</span></label>
              <input type="text" name="fabric_code" class="form-control form-control-sm font-monospace" value="FAB-SJ-160" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
              <input type="text" name="fabric_description" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s Ne" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Construction</label>
              <select name="construction" class="form-select form-select-sm">
                <option value="knitted">Knitted</option>
                <option value="woven">Woven</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Composition <span class="text-danger">*</span></label>
              <input type="text" name="composition" class="form-control form-control-sm" value="100% Combed Cotton" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Grey Lot No <span class="text-danger">*</span></label>
              <input type="text" name="grey_lot_no" class="form-control form-control-sm font-monospace" value="LOT-GRY-26-<?= rand(5000,9999) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Received Rolls <span class="text-danger">*</span></label>
              <input type="number" name="received_rolls" id="inwardRolls" class="form-control form-control-sm" value="20" min="1" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Gross Wt (Kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="gross_weight_kg" id="inwardGross" class="form-control form-control-sm" value="510.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tare Wt (Kg)</label>
              <input type="number" step="0.01" name="tare_weight_kg" id="inwardTare" class="form-control form-control-sm" value="10.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Net Wt (Kg)</label>
              <input type="number" step="0.01" id="inwardNet" class="form-control form-control-sm fw-bold bg-light" readonly value="500.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Width (Inches) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" name="width_inches" class="form-control form-control-sm" value="48.0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Target GSM <span class="text-danger">*</span></label>
              <input type="number" name="gsm" class="form-control form-control-sm" value="160" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Remarks / Inspection Notes</label>
              <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Clean rolls, paper cones weighed, good loop structure">
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-check2-circle me-1"></i> Receive Fabric & Send to QC
          </button>
        </div>
      </form>

      <script>
      function calcNetWeight() {
        const gross = parseFloat(document.getElementById('inwardGross')?.value) || 0;
        const tare = parseFloat(document.getElementById('inwardTare')?.value) || 0;
        const net = Math.max(0, gross - tare);
        const netEl = document.getElementById('inwardNet');
        if (netEl) netEl.value = net.toFixed(2);
      }
      document.getElementById('inwardGross')?.addEventListener('input', calcNetWeight);
      document.getElementById('inwardTare')?.addEventListener('input', calcNetWeight);
      </script>

    <?php else: ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED REGISTER VIEW (Grey Fabric Inward Register)                     -->
      <!-- ========================================================================= -->

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
              <li class="breadcrumb-item active">Grey Fabric Inward</li>
            </ol>
          </nav>
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-box-arrow-in-down text-dark me-2"></i>Grey Fabric Inward & Verification</h1>
          <p class="text-secondary small mb-0">Record incoming rolls from Knitting / Weaving / Subcontractors with tare weighment and lot verification.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= Url::to('/dyeing/grey-inward?action=new') ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Grey Inward
          </a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> Grey Fabric Inward recorded successfully. Assigned to QC for inspection.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Inward Lots</div>
            <div class="cz-stat-val"><?= count($inwards) ?></div>
            <div class="text-secondary small mt-1">Recorded shipments</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Rolls Received</div>
            <div class="cz-stat-val"><?= number_format($totalRolls) ?></div>
            <div class="text-secondary small mt-1">Incoming grey rolls</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Net Weight</div>
            <div class="cz-stat-val"><?= number_format($totalNetKg, 1) ?> <span class="fs-6 fw-normal text-secondary">Kg</span></div>
            <div class="text-secondary small mt-1">Net fabric inventory</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Pending QC Check</div>
            <div class="cz-stat-val text-dark"><?= $pendingQc ?></div>
            <div class="text-secondary small mt-1">Ready for 4-point check</div>
          </div>
        </div>
      </div>

      <!-- Inwards Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-dark me-2"></i>Grey Fabric Inward Register</h2>
          <span class="small text-secondary"><?= count($inwards) ?> Inward Receipts</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Inward No</th>
                <th>Supplier / Source</th>
                <th>Challan / Vehicle</th>
                <th>Fabric Details</th>
                <th>Lot No</th>
                <th class="text-center">Rolls</th>
                <th class="text-end">Gross (Kg)</th>
                <th class="text-end">Tare (Kg)</th>
                <th class="text-end">Net (Kg)</th>
                <th>QC Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($inwards as $in): ?>
                <tr>
                  <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($in['inward_no']) ?></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($in['supplier_name']) ?></div>
                    <span class="badge bg-light text-secondary border text-uppercase" style="font-size:0.65rem;"><?= htmlspecialchars($in['source_type']) ?></span>
                  </td>
                  <td>
                    <div class="font-monospace"><?= htmlspecialchars($in['delivery_challan_no']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($in['vehicle_no'] ?: 'Direct Handover') ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($in['fabric_description']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= $in['gsm'] ?> GSM &bull; <?= $in['width_inches'] ?>"</div>
                  </td>
                  <td>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($in['grey_lot_no']) ?></span>
                  </td>
                  <td class="text-center fw-bold"><?= $in['received_rolls'] ?></td>
                  <td class="text-end font-monospace"><?= number_format($in['gross_weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace text-secondary"><?= number_format($in['tare_weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($in['net_weight_kg'], 2) ?></td>
                  <td>
                    <span class="badge <?= $in['qc_status'] === 'passed' ? 'badge-bw-dark' : 'badge-bw' ?>">
                      <?= strtoupper(str_replace('_', ' ', $in['qc_status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/dyeing/grey-inspection?inward_id=' . $in['id']) ?>" class="btn btn-outline-dark" title="Inspect Fabric">
                        <i class="bi bi-search"></i>
                      </a>
                      <a href="<?= Url::to('/print/roll-label?lot=' . urlencode($in['grey_lot_no'])) ?>" target="_blank" class="btn btn-outline-secondary" title="Print Roll Labels">
                        <i class="bi bi-upc"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>

  </div>
</main>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
