<?php
/**
 * Dedicated Full-Page Form: Update Reprocess / Re-dye Request
 * File: modules/dyeing/views/reprocess-requests/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Reprocess Request';
$activeModule = 'reprocess-requests';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$rprId = (int)($_GET['id'] ?? 0);

$request = Database::fetch("SELECT * FROM dyeing_reprocess_requests WHERE id = ?", [$rprId]);
if (!$request) {
    header('Location: ' . Url::to('/dyeing/reprocess-requests?error=not_found'));
    exit;
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' OR machine_type = 'dyeing' ORDER BY code ASC");
if (empty($machines)) {
    $machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
}

$errors = [];

// Handle update reprocess request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_reprocess') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $defectCat = $_POST['defect_category'] ?? $request['defect_category'];
    $failedCrit = trim($_POST['failed_criteria'] ?? $request['failed_criteria']);
    $affectedRolls = (int)($_POST['affected_rolls'] ?? $request['affected_rolls']);
    $affectedKg = (float)($_POST['affected_weight_kg'] ?? $request['affected_weight_kg']);
    $rootCause = trim($_POST['root_cause'] ?? $request['root_cause']);
    $correctiveAction = trim($_POST['corrective_action'] ?? $request['corrective_action']);
    $revisedRecipe = trim($_POST['revised_recipe_code'] ?? $request['revised_recipe_code']);
    $extraCost = (float)($_POST['additional_chem_cost'] ?? $request['additional_chem_cost']);
    $targetMachine = (int)($_POST['target_machine_id'] ?? $request['target_machine_id']);
    $status = $_POST['status'] ?? $request['status'];
    $approvedBy = trim($_POST['approved_by'] ?? $request['approved_by']);

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_reprocess_requests SET
                defect_category = ?,
                failed_criteria = ?,
                affected_rolls = ?,
                affected_weight_kg = ?,
                root_cause = ?,
                corrective_action = ?,
                revised_recipe_code = ?,
                additional_chem_cost = ?,
                target_machine_id = ?,
                status = ?,
                approved_by = ?
            WHERE id = ?
        ")->execute([
            $defectCat,
            $failedCrit,
            $affectedRolls,
            $affectedKg,
            $rootCause,
            $correctiveAction,
            $revisedRecipe,
            $extraCost,
            $targetMachine,
            $status,
            $approvedBy,
            $rprId
        ]);

        header('Location: ' . Url::to('/dyeing/reprocess-requests?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update reprocess request: ' . $e->getMessage();
    }
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
            <li class="breadcrumb-item active"><?= htmlspecialchars($request['request_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Reprocess Request #<?= htmlspecialchars($request['request_no']) ?></h1>
        <p class="text-secondary small mb-0">Update root cause findings, corrective actions, stripping recipe, and re-dyeing progress.</p>
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
      <input type="hidden" name="action" value="update_reprocess">
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
                <label class="form-label small fw-bold">Request Number</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($request['request_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Original Batch No</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($request['original_batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Defect Category *</label>
                <select name="defect_category" class="form-select form-select-sm fw-bold text-danger" required>
                  <option value="off_shade" <?= $request['defect_category'] === 'off_shade' ? 'selected' : '' ?>>Off-Shade (ΔE Exceeded Standard)</option>
                  <option value="uneven_dyeing" <?= $request['defect_category'] === 'uneven_dyeing' ? 'selected' : '' ?>>Uneven Dyeing (Listing / Ending)</option>
                  <option value="patchy" <?= $request['defect_category'] === 'patchy' ? 'selected' : '' ?>>Patchy / Spotty Dyestuff Fixation</option>
                  <option value="shade_variation" <?= $request['defect_category'] === 'shade_variation' ? 'selected' : '' ?>>Side-to-Side Shade Variation</option>
                  <option value="stains" <?= $request['defect_category'] === 'stains' ? 'selected' : '' ?>>Sinker / Lubricant Stains Post-Dyeing</option>
                  <option value="poor_fastness" <?= $request['defect_category'] === 'poor_fastness' ? 'selected' : '' ?>>Poor Rubbing / Wash Fastness</option>
                  <option value="other" <?= $request['defect_category'] === 'other' ? 'selected' : '' ?>>Other Commercial Non-Conformance</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Failed Quality Criteria *</label>
                <input type="text" name="failed_criteria" class="form-control form-control-sm" value="<?= htmlspecialchars($request['failed_criteria']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Reprocess Authorization Status *</label>
                <select name="status" class="form-select form-select-sm fw-bold text-uppercase" required>
                  <option value="pending_approval" <?= $request['status'] === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                  <option value="approved" <?= $request['status'] === 'approved' ? 'selected' : '' ?>>Approved (Ready to Load)</option>
                  <option value="in_reprocess" <?= $request['status'] === 'in_reprocess' ? 'selected' : '' ?>>In Reprocess (Vessel Running)</option>
                  <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                  <option value="scrapped" <?= $request['status'] === 'scrapped' ? 'selected' : '' ?>>Scrapped / Written Off</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Authorizing Manager *</label>
                <input type="text" name="approved_by" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($request['approved_by'] ?? '') ?>" required>
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
                  <input type="number" step="0.1" name="affected_weight_kg" class="form-control form-control-sm fw-bold text-danger" value="<?= (float)$request['affected_weight_kg'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Affected Rolls Count *</label>
                  <input type="number" name="affected_rolls" class="form-control form-control-sm" value="<?= (int)$request['affected_rolls'] ?>" required min="1">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Root Cause Analysis (RCA) *</label>
                  <textarea name="root_cause" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars($request['root_cause']) ?></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold text-success">Corrective Action Plan *</label>
                  <textarea name="corrective_action" class="form-control form-control-sm fw-bold text-success" rows="3" required><?= htmlspecialchars($request['corrective_action']) ?></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Revised Recipe Code *</label>
                  <input type="text" name="revised_recipe_code" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($request['revised_recipe_code'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Estimated Additional Chemical Cost (₹) *</label>
                  <input type="number" step="0.01" name="additional_chem_cost" class="form-control form-control-sm fw-bold text-danger" value="<?= (float)$request['additional_chem_cost'] ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Target Vessel / Machine *</label>
                  <select name="target_machine_id" class="form-select form-select-sm" required>
                    <?php foreach ($machines as $m): ?>
                      <option value="<?= $m['id'] ?>" <?= ($m['id'] == $request['target_machine_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/reprocess-requests') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Reprocess Request
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
