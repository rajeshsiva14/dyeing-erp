<?php
/**
 * Dedicated Full-Page Form: Update Dyeing Batch QC Disposition
 * File: modules/dyeing/views/batch-qc/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Batch QC Disposition';
$activeModule = 'batch-qc';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$qcId = (int)($_GET['id'] ?? 0);

$qc = Database::fetch("SELECT * FROM dyeing_batch_qcs WHERE id = ?", [$qcId]);
if (!$qc) {
    header('Location: ' . Url::to('/dyeing/batch-qc?error=not_found'));
    exit;
}

$batch = Database::fetch("SELECT b.*, r.shade_name, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id LEFT JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$qc['dye_batch_id']]);
$errors = [];

// Handle Update Batch QC Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_batch_qc') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $inspector = trim($_POST['inspector_name'] ?? $qc['inspector_name']);
    $shadeMatch = $_POST['shade_match'] ?? 'approved';
    $sideToSide = $_POST['side_to_side_variation'] ?? 'none';
    $endToEnd = $_POST['end_to_end_variation'] ?? 'none';
    $gsm = (int)($_POST['measured_gsm'] ?? $qc['measured_gsm']);
    $width = (float)($_POST['measured_width_inches'] ?? $qc['measured_width_inches']);
    $washFastness = trim($_POST['wash_fastness'] ?? '4-5');
    $rubbingFastness = trim($_POST['rubbing_fastness'] ?? '4');
    $perspirationFastness = trim($_POST['perspiration_fastness'] ?? '4');
    $phAfter = (float)($_POST['ph_after_wash'] ?? 6.5);
    
    $acceptedKg = (float)($_POST['accepted_kg'] ?? $qc['accepted_kg']);
    $holdKg = (float)($_POST['hold_kg'] ?? 0.0);
    $rejectedKg = (float)($_POST['rejected_kg'] ?? 0.0);
    $disposition = $_POST['qc_disposition'] ?? 'accepted';
    $remarks = trim($_POST['remarks'] ?? '');

    Database::beginTransaction();
    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_batch_qcs SET
                inspector_name = ?,
                shade_match = ?,
                side_to_side_variation = ?,
                end_to_end_variation = ?,
                measured_gsm = ?,
                measured_width_inches = ?,
                wash_fastness = ?,
                rubbing_fastness = ?,
                perspiration_fastness = ?,
                ph_after_wash = ?,
                accepted_kg = ?,
                hold_kg = ?,
                rejected_kg = ?,
                qc_disposition = ?,
                remarks = ?
            WHERE id = ?
        ")->execute([
            $inspector,
            $shadeMatch,
            $sideToSide,
            $endToEnd,
            $gsm,
            $width,
            $washFastness,
            $rubbingFastness,
            $perspirationFastness,
            $phAfter,
            $acceptedKg,
            $holdKg,
            $rejectedKg,
            $disposition,
            $remarks,
            $qcId
        ]);

        if ($batch) {
            $newStatus = ($disposition === 'accepted') ? 'completed' : (($disposition === 'reprocess') ? 'reprocess' : 'unloaded');
            Database::getConnection()->prepare("UPDATE dye_batches SET status = ?, output_weight_kg = ? WHERE id = ?")->execute([$newStatus, $acceptedKg, $batch['id']]);
        }

        Database::commit();
        header('Location: ' . Url::to('/dyeing/batch-qc?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update QC: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/batch-qc') ?>">Batch QC</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($qc['qc_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Dyeing Batch QC Disposition</h1>
        <p class="text-secondary small mb-0">Update physical parameters, fastness ratings, and batch acceptance disposition.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/batch-qc') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="update_batch_qc">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Batch Details Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">QC Report Number</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($qc['qc_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Dye Batch No</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($qc['batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Shade / Vessel</label>
                <div class="p-2 border rounded bg-light small fw-bold text-dark">
                  <?= htmlspecialchars($batch['shade_name'] ?? 'Royal Navy Blue') ?>
                  <div class="text-secondary fw-normal font-monospace"><?= htmlspecialchars($batch['machine_name'] ?? 'Vessel 01') ?></div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">QC Inspector *</label>
                <input type="text" name="inspector_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($qc['inspector_name']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Original Date</label>
                <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($qc['inspection_date']) ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Physical & Fastness Test Results -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-palette me-2"></i>Lab Physical & Fastness Test Results</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Shade Match Standard *</label>
                  <select name="shade_match" class="form-select form-select-sm" required>
                    <option value="approved" <?= $qc['shade_match'] === 'approved' ? 'selected' : '' ?>>Approved (Within ΔE 0.8 Tolerance)</option>
                    <option value="within_tolerance" <?= $qc['shade_match'] === 'within_tolerance' ? 'selected' : '' ?>>Conditional Pass (Tolerance Met)</option>
                    <option value="rejected" <?= $qc['shade_match'] === 'rejected' ? 'selected' : '' ?>>Rejected (Off-Shade)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Side-to-Side Listing *</label>
                  <select name="side_to_side_variation" class="form-select form-select-sm">
                    <option value="none" <?= $qc['side_to_side_variation'] === 'none' ? 'selected' : '' ?>>None (Perfect Uniformity)</option>
                    <option value="acceptable" <?= $qc['side_to_side_variation'] === 'acceptable' ? 'selected' : '' ?>>Minor (Commercial Acceptable)</option>
                    <option value="rejectable" <?= $qc['side_to_side_variation'] === 'rejectable' ? 'selected' : '' ?>>Rejectable Variation</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">End-to-End Variation *</label>
                  <select name="end_to_end_variation" class="form-select form-select-sm">
                    <option value="none" <?= $qc['end_to_end_variation'] === 'none' ? 'selected' : '' ?>>None (Uniform Head to Tail)</option>
                    <option value="acceptable" <?= $qc['end_to_end_variation'] === 'acceptable' ? 'selected' : '' ?>>Acceptable</option>
                    <option value="rejectable" <?= $qc['end_to_end_variation'] === 'rejectable' ? 'selected' : '' ?>>Tail-end Dark/Light Variation</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Finished GSM *</label>
                  <input type="number" name="measured_gsm" class="form-control form-control-sm" value="<?= (int)$qc['measured_gsm'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Finished Width (Inches) *</label>
                  <input type="number" step="0.1" name="measured_width_inches" class="form-control form-control-sm" value="<?= (float)$qc['measured_width_inches'] ?>" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Wash Fastness</label>
                  <input type="text" name="wash_fastness" class="form-control form-control-sm" value="<?= htmlspecialchars($qc['wash_fastness']) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Rub Fastness</label>
                  <input type="text" name="rubbing_fastness" class="form-control form-control-sm" value="<?= htmlspecialchars($qc['rubbing_fastness']) ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Fabric pH</label>
                  <input type="number" step="0.1" name="ph_after_wash" class="form-control form-control-sm" value="<?= (float)$qc['ph_after_wash'] ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- Final Disposition & Approved Kilograms -->
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-check2-circle me-2"></i>Final Disposition & Weight Sign-off</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">QC Disposition Decision *</label>
                  <select name="qc_disposition" class="form-select form-select-sm fw-bold" required>
                    <option value="accepted" <?= $qc['qc_disposition'] === 'accepted' ? 'selected' : '' ?>>Accepted (Pass to Finishing & Stenter)</option>
                    <option value="partially_accepted" <?= $qc['qc_disposition'] === 'partially_accepted' ? 'selected' : '' ?>>Partially Accepted</option>
                    <option value="hold" <?= $qc['qc_disposition'] === 'hold' ? 'selected' : '' ?>>Hold (Pending Lab Clearance)</option>
                    <option value="reprocess" <?= $qc['qc_disposition'] === 'reprocess' ? 'selected' : '' ?>>Reprocess (To Re-dyeing Cycle)</option>
                    <option value="rejected" <?= $qc['qc_disposition'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-success">Accepted Weight (Kg) *</label>
                  <input type="number" step="0.01" name="accepted_kg" class="form-control form-control-sm fw-bold" value="<?= (float)$qc['accepted_kg'] ?>" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-warning">Hold (Kg)</label>
                  <input type="number" step="0.01" name="hold_kg" class="form-control form-control-sm" value="<?= (float)$qc['hold_kg'] ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-danger">Rejected (Kg)</label>
                  <input type="number" step="0.01" name="rejected_kg" class="form-control form-control-sm" value="<?= (float)$qc['rejected_kg'] ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">QC Findings & Remarks</label>
                  <textarea name="remarks" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($qc['remarks'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/batch-qc') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-shield-check me-1"></i> Update QC Disposition
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
