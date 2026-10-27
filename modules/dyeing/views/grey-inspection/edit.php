<?php
/**
 * Dedicated Standalone Form: Update Grey Fabric Inspection & Quality Disposition
 * File: modules/dyeing/views/grey-inspection/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Grey Fabric Inspection';
$activeModule = 'grey-inspection';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$inspId = (int)($_GET['id'] ?? 0);

$inspection = Database::fetch("SELECT * FROM grey_fabric_inspections WHERE id = ? AND company_id = ?", [$inspId, $companyId]);
if (!$inspection) {
    header('Location: ' . Url::to('/dyeing/grey-inspection?error=not_found'));
    exit;
}

$inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inspection['inward_id'], $companyId]);
$errors = [];

// Handle POST to update inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $measuredGsm = (int)($_POST['measured_gsm'] ?? $inspection['measured_gsm']);
    $measuredWidth = (float)($_POST['measured_width_inches'] ?? $inspection['measured_width_inches']);
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
    $inspectorName = trim($_POST['inspector_name'] ?? $inspection['inspector_name']);

    Database::beginTransaction();
    try {
        Database::getConnection()->prepare("
            UPDATE grey_fabric_inspections SET
                inspector_name = ?,
                measured_gsm = ?,
                measured_width_inches = ?,
                hole_tear_count = ?,
                oil_stain_count = ?,
                knitting_defect_count = ?,
                contamination_found = ?,
                shade_variation_obs = ?,
                defect_grade = ?,
                inspected_rolls = ?,
                accepted_rolls = ?,
                rejected_rolls = ?,
                hold_rolls = ?,
                accepted_weight_kg = ?,
                rejected_weight_kg = ?,
                qc_disposition = ?,
                inspector_comments = ?
            WHERE id = ? AND company_id = ?
        ")->execute([
            $inspectorName,
            $measuredGsm,
            $measuredWidth,
            $holeTear,
            $oilStain,
            $knittingDefects,
            $contamination,
            $shadeVariation,
            $defectGrade,
            $inspectedRolls,
            $acceptedRolls,
            $rejectedRolls,
            $holdRolls,
            $acceptedWeight,
            $rejectedWeight,
            $disposition,
            $comments,
            $inspId,
            $companyId
        ]);

        if ($inward) {
            $inwardStatus = ($disposition === 'accepted') ? 'approved' : (($disposition === 'rejected') ? 'rejected' : 'hold');
            Database::getConnection()->prepare("
                UPDATE grey_fabric_inwards 
                SET qc_status = ?, remarks = CONCAT(IFNULL(remarks,''), ' | Updated Insp: ', ?)
                WHERE id = ? AND company_id = ?
            ")->execute([$inwardStatus, $inspection['inspection_no'], $inward['id'], $companyId]);
        }

        Database::commit();
        header('Location: ' . Url::to('/dyeing/grey-inspection?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Update failed: ' . $e->getMessage();
    }
}

$csrfToken = CSRF::getToken();
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header with Breadcrumbs & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/grey-inspection') ?>">Grey Inspections</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($inspection['inspection_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">
          <i class="bi bi-pencil-square text-dark me-2"></i>Update Grey Fabric Inspection & QC Disposition
        </h1>
        <p class="text-secondary small mb-0">Record ASTM D5430 4-point parameters, defect grading, and lot disposition.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/grey-inspection') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="update_inspection">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Inward Reference & Details Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2"></i>Inward Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Inspection Number</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($inspection['inspection_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Inward DC / Lot No</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($inspection['inward_no']) ?> · Lot: <?= htmlspecialchars($inspection['grey_lot_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Fabric Specification</label>
                <div class="p-2 border rounded bg-light small fw-bold text-dark">
                  <?= htmlspecialchars($inspection['fabric_code']) ?>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Inspector Name *</label>
                <input type="text" name="inspector_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($inspection['inspector_name']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Recorded On</label>
                <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($inspection['inspection_date']) ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Measured Parameters & Defect Assessment -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-rulers me-2"></i>Physical Fabric Parameters</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Measured GSM *</label>
                  <input type="number" name="measured_gsm" class="form-control form-control-sm" value="<?= (int)$inspection['measured_gsm'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Measured Width (Inches) *</label>
                  <input type="number" step="0.1" name="measured_width_inches" class="form-control form-control-sm" value="<?= (float)$inspection['measured_width_inches'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Contamination Status</label>
                  <select name="contamination_found" class="form-select form-select-sm">
                    <option value="no" <?= $inspection['contamination_found'] === 'no' ? 'selected' : '' ?>>No Contamination (Clean)</option>
                    <option value="minor" <?= $inspection['contamination_found'] === 'minor' ? 'selected' : '' ?>>Minor Contamination (Scourable)</option>
                    <option value="severe" <?= $inspection['contamination_found'] === 'severe' ? 'selected' : '' ?>>Severe Foreign Fibres / Polypropylene</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Shade / Greige Tint</label>
                  <input type="text" name="shade_variation_obs" class="form-control form-control-sm" value="<?= htmlspecialchars($inspection['shade_variation_obs'] ?? 'Uniform Natural') ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-shield-slash me-2"></i>Knitting & Greige Defect Assessment</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Holes / Needle Tears</label>
                  <input type="number" name="hole_tear_count" class="form-control form-control-sm" value="<?= (int)$inspection['hole_tear_count'] ?>" min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Sinker Oil / Grease Stains</label>
                  <input type="number" name="oil_stain_count" class="form-control form-control-sm" value="<?= (int)$inspection['oil_stain_count'] ?>" min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Fly / Slub / Missing Yarn</label>
                  <input type="number" name="knitting_defect_count" class="form-control form-control-sm" value="<?= (int)$inspection['knitting_defect_count'] ?>" min="0">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Defect Grade Disposition *</label>
                  <select name="defect_grade" class="form-select form-select-sm" required>
                    <option value="Grade A" <?= $inspection['defect_grade'] === 'Grade A' ? 'selected' : '' ?>>Grade A (Export / Prime Quality)</option>
                    <option value="Grade B" <?= $inspection['defect_grade'] === 'Grade B' ? 'selected' : '' ?>>Grade B (Minor Blemishes - Acceptable)</option>
                    <option value="Grade C" <?= $inspection['defect_grade'] === 'Grade C' ? 'selected' : '' ?>>Grade C (Heavy Defect Count)</option>
                    <option value="Rejected" <?= $inspection['defect_grade'] === 'Rejected' ? 'selected' : '' ?>>Rejected (Unfit for Dyeing)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Final QC Disposition *</label>
                  <select name="qc_disposition" class="form-select form-select-sm fw-bold" required>
                    <option value="accepted" <?= $inspection['qc_disposition'] === 'accepted' ? 'selected' : '' ?>>Accepted (Pass directly to Dye Batch Planning)</option>
                    <option value="concession" <?= $inspection['qc_disposition'] === 'concession' ? 'selected' : '' ?>>Concession / Conditional Acceptance</option>
                    <option value="hold" <?= $inspection['qc_disposition'] === 'hold' ? 'selected' : '' ?>>Hold (Quarantine Pending Mill Feedback)</option>
                    <option value="rejected" <?= $inspection['qc_disposition'] === 'rejected' ? 'selected' : '' ?>>Rejected (Issue Rejection Debit & Return)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Roll Quantities & Disposition Weights -->
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box me-2"></i>Roll Quantities & Disposition Weights</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Inspected Rolls *</label>
                  <input type="number" name="inspected_rolls" class="form-control form-control-sm" value="<?= (int)$inspection['inspected_rolls'] ?>" required min="1">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-success">Accepted Rolls *</label>
                  <input type="number" name="accepted_rolls" class="form-control form-control-sm" value="<?= (int)$inspection['accepted_rolls'] ?>" required min="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-danger">Rejected Rolls</label>
                  <input type="number" name="rejected_rolls" class="form-control form-control-sm" value="<?= (int)$inspection['rejected_rolls'] ?>" min="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-warning">Hold Rolls</label>
                  <input type="number" name="hold_rolls" class="form-control form-control-sm" value="<?= (int)$inspection['hold_rolls'] ?>" min="0">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Accepted Net Weight (Kg) *</label>
                  <input type="number" step="0.01" name="accepted_weight_kg" class="form-control form-control-sm fw-bold" value="<?= (float)$inspection['accepted_weight_kg'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-danger">Rejected Weight (Kg)</label>
                  <input type="number" step="0.01" name="rejected_weight_kg" class="form-control form-control-sm" value="<?= (float)$inspection['rejected_weight_kg'] ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Quality Inspector Observations & Remarks</label>
                  <textarea name="inspector_comments" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($inspection['inspector_comments'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/grey-inspection') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Inspection Disposition
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
