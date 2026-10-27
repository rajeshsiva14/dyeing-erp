<?php
/**
 * Dedicated Full-Page Form: Record Dyeing Batch QC Disposition
 * File: modules/dyeing/views/batch-qc/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Batch QC Disposition';
$activeModule = 'batch-qc';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle New Batch QC Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_batch_qc') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $qcNo = 'BQC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        $inspector = trim($_POST['inspector_name'] ?? ($currentUser['name'] ?? 'T. Sivakumar (QC Head)'));
        $shadeMatch = $_POST['shade_match'] ?? 'approved';
        $sideToSide = $_POST['side_to_side_variation'] ?? 'none';
        $endToEnd = $_POST['end_to_end_variation'] ?? 'none';
        $gsm = (int)($_POST['measured_gsm'] ?? 160);
        $width = (float)($_POST['measured_width_inches'] ?? 48.0);
        $washFastness = trim($_POST['wash_fastness'] ?? '4-5');
        $rubbingFastness = trim($_POST['rubbing_fastness'] ?? '4');
        $perspirationFastness = trim($_POST['perspiration_fastness'] ?? '4');
        $phAfter = (float)($_POST['ph_after_wash'] ?? 6.5);
        
        $acceptedKg = (float)($_POST['accepted_kg'] ?? ($batch['actual_loaded_kg'] ?? 0));
        $holdKg = (float)($_POST['hold_kg'] ?? 0.0);
        $rejectedKg = (float)($_POST['rejected_kg'] ?? 0.0);
        $disposition = $_POST['qc_disposition'] ?? 'accepted';
        $remarks = trim($_POST['remarks'] ?? '');

        Database::beginTransaction();
        try {
            Database::insert('dyeing_batch_qcs', [
                'company_id'             => $companyId,
                'qc_no'                  => $qcNo,
                'dye_batch_id'           => $batchId,
                'batch_no'               => $batchNo,
                'inspection_date'        => date('Y-m-d'),
                'inspector_name'         => $inspector,
                'shade_match'            => $shadeMatch,
                'side_to_side_variation' => $sideToSide,
                'end_to_end_variation'   => $endToEnd,
                'measured_gsm'           => $gsm,
                'measured_width_inches'  => $width,
                'wash_fastness'          => $washFastness,
                'rubbing_fastness'       => $rubbingFastness,
                'perspiration_fastness'  => $perspirationFastness,
                'ph_after_wash'          => $phAfter,
                'accepted_kg'            => $acceptedKg,
                'hold_kg'                => $holdKg,
                'rejected_kg'            => $rejectedKg,
                'qc_disposition'         => $disposition,
                'remarks'                => $remarks
            ]);

            // Update batch status
            $newStatus = ($disposition === 'accepted') ? 'completed' : (($disposition === 'reprocess') ? 'reprocess' : 'unloaded');
            Database::getConnection()->prepare("UPDATE dye_batches SET status = ?, output_weight_kg = ? WHERE id = ?")->execute([$newStatus, $acceptedKg, $batchId]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/batch-qc?success=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Failed to record QC: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, r.shade_name, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id LEFT JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC");
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
            <li class="breadcrumb-item active">New QC Record</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-shield-plus text-dark me-2"></i>Record Dyeing Batch QC Disposition</h1>
        <p class="text-secondary small mb-0">Record physical parameters, colorfastness, spectrophotometer shade match, and acceptance disposition.</p>
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
      <input type="hidden" name="action" value="save_batch_qc">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Batch Selection & Inspector -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Batch Details</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Production Batch *</label>
                <select name="dye_batch_id" id="qcBatchSelect" class="form-select form-select-sm" required onchange="updateBatchInfo(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-loaded="<?= $b['actual_loaded_kg'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">QC Inspector *</label>
                <input type="text" name="inspector_name" class="form-control form-control-sm fw-bold" value="T. Sivakumar (QC Head)" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Inspection Date</label>
                <input type="text" class="form-control form-control-sm bg-light" value="<?= date('Y-m-d') ?>" readonly>
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
                    <option value="approved" selected>Approved (Within ΔE 0.8 Tolerance)</option>
                    <option value="within_tolerance">Conditional Pass (Tolerance Met)</option>
                    <option value="rejected">Rejected (Off-Shade)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Side-to-Side Listing *</label>
                  <select name="side_to_side_variation" class="form-select form-select-sm">
                    <option value="none" selected>None (Perfect Uniformity)</option>
                    <option value="acceptable">Minor (Commercial Acceptable)</option>
                    <option value="rejectable">Rejectable Variation</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">End-to-End Variation *</label>
                  <select name="end_to_end_variation" class="form-select form-select-sm">
                    <option value="none" selected>None (Uniform Head to Tail)</option>
                    <option value="acceptable">Acceptable</option>
                    <option value="rejectable">Tail-end Dark/Light Variation</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Finished GSM *</label>
                  <input type="number" name="measured_gsm" class="form-control form-control-sm" value="160" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Finished Width (Inches) *</label>
                  <input type="number" step="0.1" name="measured_width_inches" class="form-control form-control-sm" value="48.0" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Wash Fastness</label>
                  <input type="text" name="wash_fastness" class="form-control form-control-sm" value="4-5">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Rub Fastness</label>
                  <input type="text" name="rubbing_fastness" class="form-control form-control-sm" value="4">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold">Fabric pH</label>
                  <input type="number" step="0.1" name="ph_after_wash" class="form-control form-control-sm" value="6.5">
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
                    <option value="accepted" selected>Accepted (Pass to Finishing & Stenter)</option>
                    <option value="partially_accepted">Partially Accepted</option>
                    <option value="hold">Hold (Pending Lab Clearance)</option>
                    <option value="reprocess">Reprocess (To Re-dyeing Cycle)</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-success">Accepted Weight (Kg) *</label>
                  <input type="number" step="0.01" name="accepted_kg" id="qcAcceptedKg" class="form-control form-control-sm fw-bold" value="950.0" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-warning">Hold (Kg)</label>
                  <input type="number" step="0.01" name="hold_kg" class="form-control form-control-sm" value="0.0">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-danger">Rejected (Kg)</label>
                  <input type="number" step="0.01" name="rejected_kg" class="form-control form-control-sm" value="0.0">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">QC Findings & Remarks</label>
                  <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Enter fastness ratings, delta E readings, or re-dyeing instructions..."></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/batch-qc') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-shield-check me-1"></i> Save QC Disposition
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function updateBatchInfo(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.loaded) {
    document.getElementById('qcAcceptedKg').value = parseFloat(opt.dataset.loaded).toFixed(2);
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
