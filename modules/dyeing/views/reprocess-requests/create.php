<?php
/**
 * Dedicated Full-Page Form: Create Dyeing Reprocess / Re-dye Request
 * File: modules/dyeing/views/reprocess-requests/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'New Reprocess Request';
$activeModule = 'reprocess-requests';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle New Reprocess Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_reprocess') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $reqNo = 'RPR-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $origBatchId = (int)($_POST['original_batch_id'] ?? 0);
    $origBatch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$origBatchId]);
    
    if (!$origBatch) {
        $errors[] = 'Please select a valid original dye batch.';
    }

    if (!$errors) {
        $origBatchNo = $origBatch['batch_no'] ?? 'BATCH-2026-0001';
        $defectCat = $_POST['defect_category'] ?? 'off_shade';
        $failedCrit = trim($_POST['failed_criteria'] ?? 'Delta E > 1.2 under D65');
        $affectedRolls = (int)($_POST['affected_rolls'] ?? 20);
        $affectedKg = (float)($_POST['affected_weight_kg'] ?? ($origBatch['actual_loaded_kg'] ?? 500));
        $rootCause = trim($_POST['root_cause'] ?? '');
        $correctiveAction = trim($_POST['corrective_action'] ?? '');
        $revisedRecipe = trim($_POST['revised_recipe_code'] ?? 'RCP-NAV-01-R1');
        $extraCost = (float)($_POST['additional_chem_cost'] ?? 3500.00);
        $targetMachine = (int)($_POST['target_machine_id'] ?? 1);
        $approvedBy = trim($_POST['approved_by'] ?? ($currentUser['name'] ?? 'S. Natarajan (Dye House Mgr)'));

        try {
            Database::insert('dyeing_reprocess_requests', [
                'company_id'           => $companyId,
                'request_no'           => $reqNo,
                'original_batch_id'    => $origBatchId,
                'original_batch_no'    => $origBatchNo,
                'defect_category'      => $defectCat,
                'failed_criteria'      => $failedCrit,
                'affected_rolls'       => $affectedRolls,
                'affected_weight_kg'   => $affectedKg,
                'root_cause'           => $rootCause,
                'corrective_action'    => $correctiveAction,
                'revised_recipe_code'  => $revisedRecipe,
                'additional_chem_cost' => $extraCost,
                'reprocess_attempt'    => 1,
                'target_machine_id'    => $targetMachine,
                'status'               => 'approved',
                'approved_by'          => $approvedBy
            ]);

            // Update original batch status to reprocess
            Database::getConnection()->prepare("UPDATE dye_batches SET status = 'reprocess', reprocess_count = reprocess_count + 1 WHERE id = ?")->execute([$origBatchId]);

            header('Location: ' . Url::to('/dyeing/reprocess-requests?success=1'));
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'Failed to create reprocess request: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' OR machine_type = 'dyeing' ORDER BY code ASC");
if (empty($machines)) {
    $machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
}
$csrfToken = CSRF::getToken();

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/reprocess-requests') ?>">Reprocess Requests</a></li>
            <li class="breadcrumb-item active">New Request</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-arrow-repeat text-dark me-2"></i>New Reprocess / Re-dye Authorization</h1>
        <p class="text-secondary small mb-0">Record failure root cause analysis (RCA), corrective recipe formulation, and estimated extra cost.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/reprocess-requests') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="action" value="save_reprocess">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Original Batch & Defect Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Original Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Affected Batch *</label>
                <select name="original_batch_id" class="form-select form-select-sm" required onchange="populateBatchWeight(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-weight="<?= $b['actual_loaded_kg'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Defect Category *</label>
                <select name="defect_category" class="form-select form-select-sm fw-bold text-danger" required>
                  <option value="off_shade" selected>Off-Shade (ΔE Exceeded Standard)</option>
                  <option value="uneven_dyeing">Uneven Dyeing (Listing / Ending)</option>
                  <option value="patchy">Patchy / Spotty Dyestuff Fixation</option>
                  <option value="shade_variation">Side-to-Side Shade Variation</option>
                  <option value="stains">Sinker / Lubricant Stains Post-Dyeing</option>
                  <option value="poor_fastness">Poor Rubbing / Wash Fastness</option>
                  <option value="other">Other Commercial Non-Conformance</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Failed Quality Criteria *</label>
                <input type="text" name="failed_criteria" class="form-control form-control-sm" value="Delta E > 1.2 under D65 / Light on Selvedge" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Authorizing Manager *</label>
                <input type="text" name="approved_by" class="form-control form-control-sm fw-bold" value="S. Natarajan (Dye House Mgr)" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Corrective Plan & Costs -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-tools me-2"></i>RCA & Corrective Reprocess Plan</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Affected Fabric Net Weight (Kg) *</label>
                  <input type="number" step="0.1" name="affected_weight_kg" id="rprWeightKg" class="form-control form-control-sm fw-bold text-danger" value="500.0" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Affected Rolls Count *</label>
                  <input type="number" name="affected_rolls" class="form-control form-control-sm" value="20" required min="1">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Root Cause Analysis (RCA) *</label>
                  <textarea name="root_cause" class="form-control form-control-sm" rows="2" placeholder="e.g., Alkali fixation dosing too rapid causing sudden dyestuff strike before level migration." required>Alkali fixation dosing too fast causing uneven dyestuff migration</textarea>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold text-success">Corrective Action Plan *</label>
                  <textarea name="corrective_action" class="form-control form-control-sm fw-bold text-success" rows="2" placeholder="e.g., Run level stripping bath with Sodium Hydrosulphite (3 g/L) + Caustic Soda (2 g/L) at 85°C. Re-level shade with 12% shading top-up recipe." required>Level stripping with hydrosulphite and re-shade top-up 10%</textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Revised Recipe Code *</label>
                  <input type="text" name="revised_recipe_code" class="form-control form-control-sm font-monospace fw-bold" value="RCP-NAV-01-R1" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Estimated Additional Chemical Cost (₹) *</label>
                  <input type="number" step="0.01" name="additional_chem_cost" class="form-control form-control-sm fw-bold text-danger" value="3500.00" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Target Vessel / Machine *</label>
                  <select name="target_machine_id" class="form-select form-select-sm" required>
                    <?php foreach ($machines as $m): ?>
                      <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/reprocess-requests') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-arrow-repeat me-1"></i> Authorize Reprocess Run
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function populateBatchWeight(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.weight) {
    document.getElementById('rprWeightKg').value = parseFloat(opt.dataset.weight).toFixed(1);
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
