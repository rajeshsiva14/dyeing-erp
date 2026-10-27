<?php
/**
 * Dedicated Standalone Form: Grey Fabric 4-Point Inspection & Quality Disposition
 * File: modules/dyeing/views/grey-inspection/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Grey Fabric Inspection';
$activeModule = 'grey-inspection';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle POST to save inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $inspectionNo = 'GQC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $inwardId = (int)($_POST['inward_id'] ?? 0);
    $inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inwardId, $companyId]);

    if (!$inward) {
        $errors[] = 'Please select a valid Grey Fabric Inward lot.';
    }

    if (!$errors) {
        $inwardNo = $inward['inward_no'] ?? 'GIN-2026-0001';
        $measuredGsm = (int)($_POST['measured_gsm'] ?? 160);
        $measuredWidth = (float)($_POST['measured_width_inches'] ?? 48.0);
        $holeTear = (int)($_POST['hole_tear_count'] ?? 0);
        $oilStain = (int)($_POST['oil_stain_count'] ?? 0);
        $knittingDefects = (int)($_POST['knitting_defect_count'] ?? 0);
        $contamination = $_POST['contamination_found'] ?? 'no';
        $shadeVariation = trim($_POST['shade_variation_obs'] ?? 'Uniform');
        $defectGrade = $_POST['defect_grade'] ?? 'Grade A';
        
        $inspectedRolls = (int)($_POST['inspected_rolls'] ?? 1);
        $acceptedRolls = (int)($_POST['accepted_rolls'] ?? 1);
        $rejectedRolls = (int)($_POST['rejected_rolls'] ?? 0);
        $holdRolls = (int)($_POST['hold_rolls'] ?? 0);
        
        $acceptedWeight = (float)($_POST['accepted_weight_kg'] ?? 0);
        $rejectedWeight = (float)($_POST['rejected_weight_kg'] ?? 0);
        $disposition = $_POST['qc_disposition'] ?? 'accepted';
        $comments = trim($_POST['inspector_comments'] ?? '');

        Database::beginTransaction();
        try {
            Database::insert('grey_fabric_inspections', [
                'company_id'            => $companyId,
                'inspection_no'         => $inspectionNo,
                'inward_id'             => $inwardId,
                'inward_no'             => $inwardNo,
                'inspection_date'       => date('Y-m-d'),
                'inspector_name'        => trim($_POST['inspector_name'] ?? ($currentUser['name'] ?? 'K. Arumugam')),
                'fabric_code'           => $inward['fabric_code'] ?? 'FAB-SJ-160',
                'grey_lot_no'           => $inward['grey_lot_no'] ?? 'LOT-GRY-26-001',
                'measured_gsm'          => $measuredGsm,
                'measured_width_inches' => $measuredWidth,
                'hole_tear_count'       => $holeTear,
                'oil_stain_count'       => $oilStain,
                'knitting_defect_count' => $knittingDefects,
                'contamination_found'   => $contamination,
                'shade_variation_obs'   => $shadeVariation,
                'defect_grade'          => $defectGrade,
                'inspected_rolls'       => $inspectedRolls,
                'accepted_rolls'        => $acceptedRolls,
                'rejected_rolls'        => $rejectedRolls,
                'hold_rolls'            => $holdRolls,
                'accepted_weight_kg'    => $acceptedWeight,
                'rejected_weight_kg'    => $rejectedWeight,
                'qc_disposition'        => $disposition,
                'inspector_comments'    => $comments,
                'status'                => 'finalized'
            ]);

            // Synchronize status back to grey_fabric_inwards
            $newInwardQc = ($disposition === 'accepted' || $disposition === 'concession') ? 'passed' : (($disposition === 'rejected') ? 'rejected' : 'on_hold');
            Database::execute("UPDATE grey_fabric_inwards SET qc_status = ? WHERE id = ? AND company_id = ?", [$newInwardQc, $inwardId, $companyId]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/grey-inspection?success=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Inspection could not be saved: ' . $e->getMessage();
        }
    }
}

$preSelectedInwardId = (int)($_GET['inward_id'] ?? 0);
$allInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE company_id = ? ORDER BY id DESC", [$companyId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Action Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/grey-inspection') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Record Grey Fabric Inspection</h1>
            <span class="badge badge-bw">ASTM D5430</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Physical 4-point inspection of received grey fabric rolls, dimensional verification, defect counting, and release disposition.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/grey-inspection') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newInspectionForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-shield-check me-1"></i> Submit QC Disposition
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

    <form id="newInspectionForm" method="POST" action="<?= Url::to('/dyeing/grey-inspection-create') ?>">
      <input type="hidden" name="action" value="save_inspection">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Inward Document Link -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-box-arrow-in-down"></i> Section A — Inward Lot Link & Inspector</h2>
            <p class="cz-doc-section-desc">Select received grey fabric consignment to inherit tare weighment and lot specifications.</p>
          </div>
          <span class="badge badge-bw font-monospace">CONSIGNMENT</span>
        </div>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Select Inward Consignment <span class="text-danger">*</span></label>
            <select name="inward_id" id="inspInwardSelect" class="form-select form-select-sm" required>
              <option value="">-- Select Inward Consignment --</option>
              <?php foreach ($allInwards as $inw): ?>
                <option value="<?= $inw['id'] ?>" <?= ($inw['id'] == $preSelectedInwardId) ? 'selected' : '' ?> data-net="<?= $inw['net_weight_kg'] ?>" data-rolls="<?= $inw['received_rolls'] ?>" data-gsm="<?= $inw['gsm'] ?>" data-width="<?= $inw['width_inches'] ?>">
                  <?= htmlspecialchars($inw['inward_no']) ?> &bull; <?= htmlspecialchars($inw['fabric_description']) ?> (<?= $inw['net_weight_kg'] ?> Kg, <?= $inw['received_rolls'] ?> Rolls, Lot: <?= htmlspecialchars($inw['grey_lot_no']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Inspector Name <span class="text-danger">*</span></label>
            <input type="text" name="inspector_name" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['name'] ?? 'K. Arumugam (Senior QC Lead)') ?>" required>
          </div>
        </div>
      </div>

      <!-- Section B: Physical Measurements & Defects -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-rulers"></i> Section B — Physical Measurements & Defect Counts</h2>
            <p class="cz-doc-section-desc">Verify GSM with GSM cutter balance, measured width, and tally physical defects.</p>
          </div>
          <span class="badge badge-bw font-monospace">DEFECT AUDIT</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Measured GSM <span class="text-danger">*</span></label>
            <input type="number" name="measured_gsm" id="inspGsm" class="form-control form-control-sm font-monospace fw-bold" value="162" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Measured Width (Inches) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="measured_width_inches" id="inspWidth" class="form-control form-control-sm font-monospace fw-bold" value="48.2" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Hole / Tear Count</label>
            <input type="number" name="hole_tear_count" class="form-control form-control-sm font-monospace" value="0" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Oil / Grease Stain Count</label>
            <input type="number" name="oil_stain_count" class="form-control form-control-sm font-monospace" value="0" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Knitting Defects (Drop stitches, needle lines)</label>
            <input type="number" name="knitting_defect_count" class="form-control form-control-sm font-monospace" value="1" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Contamination / Foreign Fibers</label>
            <select name="contamination_found" class="form-select form-select-sm">
              <option value="no" selected>None (Pass - Clean Grey)</option>
              <option value="minor">Minor Foreign Fibre (Washable)</option>
              <option value="severe">Severe Contamination (Defect)</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Defect Classification Grade <span class="text-danger">*</span></label>
            <select name="defect_grade" class="form-select form-select-sm" required>
              <option value="Grade A" selected>Grade A (Premium Export Quality)</option>
              <option value="Grade B">Grade B (Commercial Quality)</option>
              <option value="Grade C">Grade C (Second Quality)</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section C: Disposition Quantities -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-shield-check"></i> Section C — Disposition Quantities & Verdict</h2>
            <p class="cz-doc-section-desc">Accepted vs rejected weight allocations and release to production schedule.</p>
          </div>
          <span class="badge badge-bw font-monospace">DISPOSITION</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Inspected Rolls <span class="text-danger">*</span></label>
            <input type="number" name="inspected_rolls" id="inspRolls" class="form-control form-control-sm font-monospace" value="20" required>
          </div>
          <div class="col-md-3">
            <label class="form-label text-success fw-bold">Accepted Rolls <span class="text-danger">*</span></label>
            <input type="number" name="accepted_rolls" id="inspAcceptedRolls" class="form-control form-control-sm font-monospace" value="20" required>
          </div>
          <div class="col-md-3">
            <label class="form-label text-success fw-bold">Accepted Weight (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="accepted_weight_kg" id="inspAcceptedKg" class="form-control form-control-sm font-monospace fw-bold" value="500.00" required>
          </div>
          <div class="col-md-3">
            <label class="form-label text-danger fw-bold">Rejected Weight (Kg)</label>
            <input type="number" step="0.01" name="rejected_weight_kg" id="inspRejectedKg" class="form-control form-control-sm font-monospace" value="0.00">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Final QC Disposition <span class="text-danger">*</span></label>
            <select name="qc_disposition" class="form-select form-select-sm" required>
              <option value="accepted" selected>Accepted (Release for Dyeing)</option>
              <option value="concession">Accepted with Concession</option>
              <option value="hold">Hold for Manager Review</option>
              <option value="rejected">Rejected (Return to Supplier)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Inspector Comments / Observations</label>
            <input type="text" name="inspector_comments" class="form-control form-control-sm" value="Good loop density, skewing under 2.5%, no fly contamination.">
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/grey-inspection') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-shield-check me-1"></i> Submit QC Disposition
        </button>
      </div>
    </form>

    <script>
    document.getElementById('inspInwardSelect')?.addEventListener('change', function() {
      const opt = this.options[this.selectedIndex];
      if (opt.dataset.net) {
        document.getElementById('inspAcceptedKg').value = opt.dataset.net;
        document.getElementById('inspRolls').value = opt.dataset.rolls || 1;
        document.getElementById('inspAcceptedRolls').value = opt.dataset.rolls || 1;
        if (opt.dataset.gsm) document.getElementById('inspGsm').value = opt.dataset.gsm;
        if (opt.dataset.width) document.getElementById('inspWidth').value = opt.dataset.width;
      }
    });
    </script>
  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
