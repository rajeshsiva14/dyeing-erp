<?php
/**
 * Dedicated Standalone Form: Update Existing Dyeing Job Card
 * File: modules/dyeing/views/job-cards-edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Dyeing Job Card';
$activeModule = 'job-cards';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$jobCardId = (int)($_GET['id'] ?? ($_POST['job_card_id'] ?? 0));

if ($jobCardId <= 0) {
    header('Location: ' . Url::to('/dyeing/job-cards'));
    exit;
}

$errors = [];

// Handle POST to update Job Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_job_card') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $customerId = (int)($_POST['customer_id'] ?? 1);
    $salesOrderRef = trim($_POST['sales_order_ref'] ?? '');
    $customerPo = trim($_POST['customer_po'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $requiredDate = $_POST['required_date'] ?? date('Y-m-d');
    $jobType = $_POST['job_type'] ?? 'regular';
    $dyeingRoute = $_POST['dyeing_route'] ?? 'reactive';
    $status = $_POST['status'] ?? 'planned';
    
    $fabricName = trim($_POST['fabric_name'] ?? '');
    $fabricComposition = trim($_POST['fabric_composition'] ?? '');
    $greyLotNo = trim($_POST['grey_lot_no'] ?? '');
    $requiredQtyKg = (float)($_POST['required_qty_kg'] ?? 0);
    $rollCount = (int)($_POST['roll_count'] ?? 1);
    $targetGsm = (int)($_POST['target_gsm'] ?? 160);
    $targetWidth = (float)($_POST['target_width_inches'] ?? 48);
    
    $shadeCode = trim($_POST['shade_code'] ?? '');
    $shadeName = trim($_POST['shade_name'] ?? '');
    $customerShadeRef = trim($_POST['customer_shade_ref'] ?? '');
    $fastnessSpec = trim($_POST['fastness_spec'] ?? '');
    $specialFinish = trim($_POST['special_finish'] ?? '');

    if ($fabricName === '') $errors[] = 'Fabric description is required.';
    if ($requiredQtyKg <= 0) $errors[] = 'Required quantity (Kg) must be greater than 0.';

    if (!$errors) {
        Database::execute("
            UPDATE dyeing_job_cards 
            SET customer_id = ?, sales_order_ref = ?, customer_po = ?, priority = ?,
                required_date = ?, job_type = ?, dyeing_route = ?, status = ?,
                fabric_name = ?, fabric_composition = ?, grey_lot_no = ?,
                required_qty_kg = ?, roll_count = ?, target_gsm = ?, target_width_inches = ?,
                shade_code = ?, shade_name = ?, customer_shade_ref = ?,
                fastness_spec = ?, special_finish = ?
            WHERE id = ? AND company_id = ?
        ", [
            $customerId, $salesOrderRef, $customerPo, $priority,
            $requiredDate, $jobType, $dyeingRoute, $status,
            $fabricName, $fabricComposition, $greyLotNo,
            $requiredQtyKg, $rollCount, $targetGsm, $targetWidth,
            $shadeCode, $shadeName, $customerShadeRef,
            $fastnessSpec, $specialFinish,
            $jobCardId, $companyId
        ]);

        header('Location: ' . Url::to('/dyeing/job-cards?updated=' . $jobCardId));
        exit;
    }
}

$jc = Database::fetch("SELECT * FROM dyeing_job_cards WHERE id = ? AND company_id = ?", [$jobCardId, $companyId]);
if (!$jc) {
    header('Location: ' . Url::to('/dyeing/job-cards'));
    exit;
}

$customers = Database::fetchAll("SELECT * FROM customers WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$companyId]);
$shades = Database::fetchAll("SELECT * FROM dyeing_shades WHERE company_id = ? AND is_active = 1 ORDER BY shade_name ASC", [$companyId]);
$greyInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE company_id = ? AND qc_status = 'passed' ORDER BY id DESC", [$companyId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Document Action Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Dyeing Job Card</h1>
            <span class="badge bg-primary font-monospace"><?= htmlspecialchars($jc['job_card_no']) ?></span>
            <span class="badge bg-light text-secondary border"><?= strtoupper(str_replace('_', ' ', $jc['status'])) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Full technical update for customer specifications, fabric parameters, target shade standard, and finishing recipes.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/print/sales-order?id=' . $jc['id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Job Card
        </a>
        <a href="<?= Url::to('/dyeing/batch-planning?job_card_id=' . $jc['id']) ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-calendar-range me-1"></i> Plan into Batch
        </a>
        <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editJobCardForm" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="editJobCardForm" method="POST" action="<?= Url::to('/dyeing/job-cards-edit') ?>">
      <input type="hidden" name="action" value="update_job_card">
      <input type="hidden" name="job_card_id" value="<?= $jc['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Commercial & Customer Order Details -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-building"></i> Section A — Commercial & Buyer Specifications</h2>
            <p class="cz-doc-section-desc">Map customer purchase order, delivery schedule, priority, and route parameters.</p>
          </div>
          <span class="badge badge-bw font-monospace">COMMERCIAL SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Customer / Buyer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select form-select-sm" required>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($jc['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['city']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Customer PO Ref</label>
            <input type="text" name="customer_po" value="<?= htmlspecialchars($jc['customer_po'] ?? '') ?>" class="form-control form-control-sm" placeholder="e.g. PO-CP-9812">
          </div>
          <div class="col-md-4">
            <label class="form-label">Internal Sales Order Reference</label>
            <input type="text" name="sales_order_ref" value="<?= htmlspecialchars($jc['sales_order_ref'] ?? '') ?>" class="form-control form-control-sm font-monospace">
          </div>
          <div class="col-md-3">
            <label class="form-label">Priority Order <span class="text-danger">*</span></label>
            <select name="priority" class="form-select form-select-sm">
              <option value="normal" <?= ($jc['priority'] === 'normal') ? 'selected' : '' ?>>Normal (Standard Lead Time)</option>
              <option value="medium" <?= ($jc['priority'] === 'medium') ? 'selected' : '' ?>>Medium Priority</option>
              <option value="high" <?= ($jc['priority'] === 'high') ? 'selected' : '' ?>>High Priority</option>
              <option value="urgent" <?= ($jc['priority'] === 'urgent') ? 'selected' : '' ?>>Urgent (Express 24-48h)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Job Type <span class="text-danger">*</span></label>
            <select name="job_type" class="form-select form-select-sm">
              <option value="regular" <?= ($jc['job_type'] === 'regular') ? 'selected' : '' ?>>Regular Production</option>
              <option value="sample" <?= ($jc['job_type'] === 'sample') ? 'selected' : '' ?>>Sample Development</option>
              <option value="urgent" <?= ($jc['job_type'] === 'urgent') ? 'selected' : '' ?>>Urgent Shipment</option>
              <option value="reprocess" <?= ($jc['job_type'] === 'reprocess') ? 'selected' : '' ?>>Reprocess / Re-dyeing</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Dyeing Route <span class="text-danger">*</span></label>
            <select name="dyeing_route" class="form-select form-select-sm" required>
              <option value="reactive" <?= ($jc['dyeing_route'] === 'reactive') ? 'selected' : '' ?>>Reactive Dyeing (Cotton / Viscose / Modal)</option>
              <option value="disperse" <?= ($jc['dyeing_route'] === 'disperse') ? 'selected' : '' ?>>Disperse Dyeing (Polyester / Microfibre)</option>
              <option value="vat" <?= ($jc['dyeing_route'] === 'vat') ? 'selected' : '' ?>>Vat Dyeing (High Fastness Workwear)</option>
              <option value="pigment" <?= ($jc['dyeing_route'] === 'pigment') ? 'selected' : '' ?>>Pigment Dyeing</option>
              <option value="cationic" <?= ($jc['dyeing_route'] === 'cationic') ? 'selected' : '' ?>>Cationic Dyeing (Acrylic)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Production Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="planned" <?= ($jc['status'] === 'planned') ? 'selected' : '' ?>>Planned</option>
              <option value="approved" <?= ($jc['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
              <option value="in_production" <?= ($jc['status'] === 'in_production') ? 'selected' : '' ?>>In Production</option>
              <option value="completed" <?= ($jc['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
              <option value="on_hold" <?= ($jc['status'] === 'on_hold') ? 'selected' : '' ?>>On Hold</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Target Delivery Date <span class="text-danger">*</span></label>
            <input type="date" name="required_date" class="form-control form-control-sm" value="<?= htmlspecialchars($jc['required_date']) ?>" required>
          </div>
        </div>
      </div>

      <!-- Section B: Fabric Details & Grey Lot Allocation -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-layers"></i> Section B — Fabric Specifications & Grey Lot Allocation</h2>
            <p class="cz-doc-section-desc">Allocated grey fabric, lot number, target GSM, width and required weight.</p>
          </div>
          <span class="badge badge-bw font-monospace"><?= htmlspecialchars($jc['grey_lot_no'] ?: 'NO LOT') ?></span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
            <input type="text" name="fabric_name" class="form-control form-control-sm" value="<?= htmlspecialchars($jc['fabric_name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fiber Composition <span class="text-danger">*</span></label>
            <input type="text" name="fabric_composition" class="form-control form-control-sm" value="<?= htmlspecialchars($jc['fabric_composition']) ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Grey Lot Allocation</label>
            <select name="grey_lot_no" class="form-select form-select-sm">
              <option value="">-- Direct Allocation from Available QC Passed Grey Stock --</option>
              <?php foreach ($greyInwards as $gi): ?>
                <option value="<?= htmlspecialchars($gi['grey_lot_no']) ?>" <?= ($jc['grey_lot_no'] === $gi['grey_lot_no']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($gi['grey_lot_no']) ?> (<?= $gi['net_weight_kg'] ?> Kg, <?= htmlspecialchars($gi['fabric_code']) ?> · <?= htmlspecialchars($gi['supplier_name']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Required Net Quantity (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="required_qty_kg" class="form-control form-control-sm fw-bold font-monospace" value="<?= $jc['required_qty_kg'] ?>" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Roll Count <span class="text-danger">*</span></label>
            <input type="number" name="roll_count" class="form-control form-control-sm font-monospace" value="<?= $jc['roll_count'] ?>" required>
          </div>
          <div class="col-md-1">
            <label class="form-label">GSM <span class="text-danger">*</span></label>
            <input type="number" name="target_gsm" class="form-control form-control-sm font-monospace" value="<?= $jc['target_gsm'] ?>" required>
          </div>
          <div class="col-md-1">
            <label class="form-label">Width (") <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="target_width_inches" class="form-control form-control-sm font-monospace" value="<?= $jc['target_width_inches'] ?>" required>
          </div>
        </div>
      </div>

      <!-- Section C: Shade Target & Recipe Binding -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-palette"></i> Section C — Shade Specification & Lab Recipe Binding</h2>
            <p class="cz-doc-section-desc">Bind computerized master shade standard, Pantone reference, and light source parameters.</p>
          </div>
          <span class="badge badge-bw font-monospace"><?= htmlspecialchars($jc['shade_code']) ?></span>
        </div>

        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Target Shade Standard <span class="text-danger">*</span></label>
            <select name="shade_code" id="jcShadeSelect" class="form-select form-select-sm" required>
              <?php foreach ($shades as $sh): ?>
                <option value="<?= htmlspecialchars($sh['shade_code']) ?>" data-name="<?= htmlspecialchars($sh['shade_name']) ?>" data-pantone="<?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>" <?= ($jc['shade_code'] === $sh['shade_code']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($sh['shade_name']) ?> (<?= htmlspecialchars($sh['shade_code']) ?> · <?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="shade_name" id="jcShadeName" value="<?= htmlspecialchars($jc['shade_name']) ?>">
            <input type="hidden" name="customer_shade_ref" id="jcShadePantone" value="<?= htmlspecialchars($jc['customer_shade_ref']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Light Source for Assessment</label>
            <input type="text" class="form-control form-control-sm" value="D65 (Daylight) / TL84 Dual Light" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Colour Fastness Requirements</label>
            <input type="text" name="fastness_spec" class="form-control form-control-sm" value="<?= htmlspecialchars($jc['fastness_spec']) ?>">
          </div>
        </div>
      </div>

      <!-- Section D: Special Finishing & Technical Requirements -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-gear-wide-connected"></i> Section D — Special Finishes & Technical Process Notes</h2>
            <p class="cz-doc-section-desc">Specify enzyme bio-polishing, stenter heat setting, compaction, and softening finishes.</p>
          </div>
          <span class="badge badge-bw font-monospace">FINISHING SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Special Finishing Recipe / Treatments</label>
            <input type="text" name="special_finish" class="form-control form-control-sm" value="<?= htmlspecialchars($jc['special_finish']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Technical Instructions / Shrinkage Standards</label>
            <input type="text" class="form-control form-control-sm" value="Length shrinkage &le; 4.5%, Width shrinkage &le; 3.0%, Spirality &le; 3.5%">
          </div>
        </div>
      </div>

      <!-- Document Bottom Actions -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-x-circle me-1"></i> Discard Changes
        </a>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Job Card
        </button>
      </div>
    </form>

    <script>
    document.getElementById('jcShadeSelect')?.addEventListener('change', function() {
      const opt = this.options[this.selectedIndex];
      document.getElementById('jcShadeName').value = opt.getAttribute('data-name') || '';
      document.getElementById('jcShadePantone').value = opt.getAttribute('data-pantone') || '';
    });
    </script>
  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
