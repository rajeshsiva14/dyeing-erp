<?php
/**
 * Dedicated Standalone Form: Issue New Dyeing Job Card
 * File: modules/dyeing/views/job-cards-create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Issue New Dyeing Job Card';
$activeModule = 'job-cards';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to save new Job Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_job_card') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $jobCardNo = 'DJC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $customerId = (int)($_POST['customer_id'] ?? 1);
    $salesOrderRef = trim($_POST['sales_order_ref'] ?? '');
    $customerPo = trim($_POST['customer_po'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $requiredDate = $_POST['required_date'] ?? date('Y-m-d', strtotime('+5 days'));
    $jobType = $_POST['job_type'] ?? 'regular';
    $dyeingRoute = $_POST['dyeing_route'] ?? 'reactive';
    
    $fabricName = trim($_POST['fabric_name'] ?? '100% Combed Cotton Single Jersey 30s');
    $fabricComposition = trim($_POST['fabric_composition'] ?? '100% Combed Cotton');
    $greyLotNo = trim($_POST['grey_lot_no'] ?? '');
    $requiredQtyKg = (float)($_POST['required_qty_kg'] ?? 500);
    $rollCount = (int)($_POST['roll_count'] ?? 20);
    $targetGsm = (int)($_POST['target_gsm'] ?? 160);
    $targetWidth = (float)($_POST['target_width_inches'] ?? 48);
    
    $shadeCode = trim($_POST['shade_code'] ?? 'SH-NAV-01');
    $shadeName = trim($_POST['shade_name'] ?? 'Royal Navy Blue');
    $customerShadeRef = trim($_POST['customer_shade_ref'] ?? 'Pantone 19-4024 TCX');
    $fastnessSpec = trim($_POST['fastness_spec'] ?? 'Wash: 4-5, Rubbing: 4, Perspiration: 4');
    $specialFinish = trim($_POST['special_finish'] ?? 'Bio-Polishing Enzyme + Cationic Softener');

    Database::insert('dyeing_job_cards', [
        'company_id'         => $companyId,
        'job_card_no'        => $jobCardNo,
        'job_date'           => date('Y-m-d'),
        'customer_id'        => $customerId,
        'sales_order_ref'    => $salesOrderRef,
        'customer_po'        => $customerPo,
        'priority'           => $priority,
        'required_date'      => $requiredDate,
        'job_type'           => $jobType,
        'dyeing_route'       => $dyeingRoute,
        'fabric_name'        => $fabricName,
        'fabric_composition' => $fabricComposition,
        'grey_lot_no'        => $greyLotNo,
        'required_qty_kg'    => $requiredQtyKg,
        'roll_count'         => $rollCount,
        'target_gsm'         => $targetGsm,
        'target_width_inches'=> $targetWidth,
        'shade_code'         => $shadeCode,
        'shade_name'         => $shadeName,
        'customer_shade_ref' => $customerShadeRef,
        'fastness_spec'      => $fastnessSpec,
        'special_finish'     => $specialFinish,
        'status'             => 'approved'
    ]);

    header('Location: ' . Url::to('/dyeing/job-cards?success=1'));
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
            <h1 class="h5 fw-bold text-dark mb-0">Issue New Dyeing Job Card</h1>
            <span class="badge badge-bw">NEW DRAFT</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Transform customer PO and shade requirements into a technical batch-ready production order.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newJobCardForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save & Issue Job Card
        </button>
      </div>
    </div>

    <form id="newJobCardForm" method="POST" action="<?= Url::to('/dyeing/job-cards-create') ?>">
      <input type="hidden" name="action" value="save_job_card">
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
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['city']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Customer PO Ref</label>
            <input type="text" name="customer_po" class="form-control form-control-sm" placeholder="e.g. PO-CP-9812">
          </div>
          <div class="col-md-4">
            <label class="form-label">Internal Sales Order Reference</label>
            <input type="text" name="sales_order_ref" class="form-control form-control-sm font-monospace" value="SO-2026-<?= rand(100000, 999999) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Priority Order <span class="text-danger">*</span></label>
            <select name="priority" class="form-select form-select-sm">
              <option value="normal">Normal (Standard Lead Time)</option>
              <option value="medium">Medium Priority</option>
              <option value="high">High Priority</option>
              <option value="urgent">Urgent (Express 24-48h)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Job Type <span class="text-danger">*</span></label>
            <select name="job_type" class="form-select form-select-sm">
              <option value="regular">Regular Production</option>
              <option value="sample">Sample Development</option>
              <option value="urgent">Urgent Shipment</option>
              <option value="reprocess">Reprocess / Re-dyeing</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Dyeing Route <span class="text-danger">*</span></label>
            <select name="dyeing_route" class="form-select form-select-sm" required>
              <option value="reactive">Reactive Dyeing (Cotton / Viscose / Modal)</option>
              <option value="disperse">Disperse Dyeing (Polyester / Microfibre)</option>
              <option value="vat">Vat Dyeing (High Fastness Workwear)</option>
              <option value="pigment">Pigment Dyeing</option>
              <option value="cationic">Cationic Dyeing (Acrylic)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Target Delivery Date <span class="text-danger">*</span></label>
            <input type="date" name="required_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+4 days')) ?>" required>
          </div>
        </div>
      </div>

      <!-- Section B: Fabric Details & Grey Lot Allocation -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-layers"></i> Section B — Fabric Specifications & Grey Lot Allocation</h2>
            <p class="cz-doc-section-desc">Select received grey rolls from warehouse and define finished GSM and width specs.</p>
          </div>
          <span class="badge badge-bw font-monospace">FABRIC & LOT</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
            <input type="text" name="fabric_name" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s Ne" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fiber Composition <span class="text-danger">*</span></label>
            <input type="text" name="fabric_composition" class="form-control form-control-sm" value="100% Combed Cotton" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Grey Lot Allocation (QC Passed Inward Stock)</label>
            <select name="grey_lot_no" class="form-select form-select-sm">
              <option value="">-- Direct Allocation from Available QC Passed Grey Stock --</option>
              <?php foreach ($greyInwards as $gi): ?>
                <option value="<?= htmlspecialchars($gi['grey_lot_no']) ?>">
                  <?= htmlspecialchars($gi['grey_lot_no']) ?> (<?= $gi['net_weight_kg'] ?> Kg, <?= htmlspecialchars($gi['fabric_code']) ?> · <?= htmlspecialchars($gi['supplier_name']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Required Net Quantity (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="required_qty_kg" class="form-control form-control-sm fw-bold font-monospace" value="500.00" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Roll Count <span class="text-danger">*</span></label>
            <input type="number" name="roll_count" class="form-control form-control-sm font-monospace" value="20" required>
          </div>
          <div class="col-md-1">
            <label class="form-label">GSM <span class="text-danger">*</span></label>
            <input type="number" name="target_gsm" class="form-control form-control-sm font-monospace" value="160" required>
          </div>
          <div class="col-md-1">
            <label class="form-label">Width (") <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="target_width_inches" class="form-control form-control-sm font-monospace" value="48.0" required>
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
          <span class="badge badge-bw font-monospace">SHADE RECIPE</span>
        </div>

        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Target Shade Standard <span class="text-danger">*</span></label>
            <select name="shade_code" id="jcShadeSelect" class="form-select form-select-sm" required>
              <?php foreach ($shades as $sh): ?>
                <option value="<?= htmlspecialchars($sh['shade_code']) ?>" data-name="<?= htmlspecialchars($sh['shade_name']) ?>" data-pantone="<?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>">
                  <?= htmlspecialchars($sh['shade_name']) ?> (<?= htmlspecialchars($sh['shade_code']) ?> · <?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="shade_name" id="jcShadeName" value="Royal Navy Blue">
            <input type="hidden" name="customer_shade_ref" id="jcShadePantone" value="19-4024 TCX">
          </div>
          <div class="col-md-3">
            <label class="form-label">Light Source for Assessment</label>
            <input type="text" class="form-control form-control-sm" value="D65 (Daylight) / TL84 Dual Light" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Colour Fastness Requirements</label>
            <input type="text" name="fastness_spec" class="form-control form-control-sm" value="Wash: 4-5, Rubbing: 4, Perspiration: 4">
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
            <input type="text" name="special_finish" class="form-control form-control-sm" value="Bio-Polishing Acid Cellulase Enzyme + Cationic Micro-Silicone Softener">
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
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save & Issue Dyeing Job Card
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
