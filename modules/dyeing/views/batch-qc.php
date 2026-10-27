<?php
/**
 * Dyeing Batch Quality Control (QC) & Comprehensive Fastness Disposition
 * Post-dyeing lab quality verification: shade, GSM, width, shrinkage, wash/rubbing fastness
 * File: modules/dyeing/views/batch-qc.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Batch QC';
$activeModule = 'batch-qc';

// Handle New Batch QC Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_batch_qc') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $qcNo = 'BQC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    
    $inspector = trim($_POST['inspector_name'] ?? 'T. Sivakumar (QC Head)');
    $shadeMatch = $_POST['shade_match'] ?? 'approved';
    $sideToSide = $_POST['side_to_side_variation'] ?? 'none';
    $endToEnd = $_POST['end_to_end_variation'] ?? 'none';
    $gsm = (int)($_POST['measured_gsm'] ?? 160);
    $width = (float)($_POST['measured_width_inches'] ?? 48.0);
    $washFastness = trim($_POST['wash_fastness'] ?? '4-5');
    $rubbingFastness = trim($_POST['rubbing_fastness'] ?? '4');
    $perspirationFastness = trim($_POST['perspiration_fastness'] ?? '4');
    $phAfter = (float)($_POST['ph_after_wash'] ?? 6.5);
    
    $acceptedKg = (float)($_POST['accepted_kg'] ?? 950.0);
    $holdKg = (float)($_POST['hold_kg'] ?? 0.0);
    $rejectedKg = (float)($_POST['rejected_kg'] ?? 0.0);
    $disposition = $_POST['qc_disposition'] ?? 'accepted';
    $remarks = trim($_POST['remarks'] ?? '');

    Database::insert('dyeing_batch_qcs', [
        'company_id'             => 1,
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

    header('Location: ' . Url::to('/dyeing/batch-qc?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$qcs = Database::fetchAll("
    SELECT q.*, b.actual_loaded_kg, r.shade_name, m.name as machine_name
    FROM dyeing_batch_qcs q
    JOIN dye_batches b ON q.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    ORDER BY q.id DESC
");

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Dyeing Batch QC</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-shield-check text-primary me-2"></i>Dyeing Batch QC & Fastness Disposition</h1>
        <p class="text-secondary small mb-0">Record shade match, listing/ending check, GSM, fastness tests (Wash, Rub, Perspiration) and final acceptance disposition.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newQcModal">
          <i class="bi bi-shield-plus me-1"></i> Record Batch QC Disposition
        </button>
      </div>
    </div>

    <!-- Batch QC Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Dyeing Batch Quality Dispositions Register</h2>
        <span class="small text-secondary"><?= count($qcs) ?> Batch Inspections</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>QC Report No</th>
              <th>Batch No</th>
              <th>Shade & Vessel</th>
              <th>Shade Match</th>
              <th>Side / End Variation</th>
              <th>GSM / Width</th>
              <th>Fastness (Wash / Rub)</th>
              <th class="text-end">Accepted (Kg)</th>
              <th>Disposition</th>
              <th>Inspector</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($qcs as $q): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($q['qc_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($q['batch_no']) ?></span></td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($q['shade_name'] ?? 'Royal Navy') ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($q['machine_name']) ?></div>
                </td>
                <td>
                  <span class="badge bg-success-subtle text-success text-uppercase"><?= htmlspecialchars($q['shade_match']) ?></span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">Side: <?= htmlspecialchars($q['side_to_side_variation']) ?></span>
                </td>
                <td><?= $q['measured_gsm'] ?> GSM / <?= $q['measured_width_inches'] ?>"</td>
                <td>
                  <div>Wash: <strong><?= htmlspecialchars($q['wash_fastness']) ?></strong></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Rub: <?= htmlspecialchars($q['rubbing_fastness']) ?> · pH <?= $q['ph_after_wash'] ?></div>
                </td>
                <td class="text-end font-monospace fw-bold text-success fs-6">
                  <?= number_format($q['accepted_kg'], 2) ?>
                </td>
                <td>
                  <span class="badge <?= $q['qc_disposition'] === 'accepted' ? 'bg-success' : ($q['qc_disposition'] === 'reprocess' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                    <?= strtoupper(str_replace('_', ' ', $q['qc_disposition'])) ?>
                  </span>
                </td>
                <td class="small"><?= htmlspecialchars($q['inspector_name']) ?></td>
                <td class="text-end">
                  <?php if ($q['qc_disposition'] === 'reprocess'): ?>
                    <a href="<?= Url::to('/dyeing/reprocess-requests?batch_id=' . $q['dye_batch_id']) ?>" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-arrow-repeat me-1"></i> Reprocess
                    </a>
                  <?php else: ?>
                    <a href="<?= Url::to('/dyeing/finishing?batch_id=' . $q['dye_batch_id']) ?>" class="btn btn-sm btn-outline-dark">
                      <i class="bi bi-water me-1"></i> Finishing &rarr;
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Batch QC Modal -->
<div class="modal fade" id="newQcModal" tabindex="-1" aria-labelledby="newQcModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_batch_qc">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newQcModalLabel"><i class="bi bi-shield-check text-primary me-2"></i>Record Dyeing Batch QC Disposition</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" id="qcBatchSelect" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>" data-loaded="<?= $b['actual_loaded_kg'] ?>">
                    <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (Loaded: <?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">QC Inspector / Reviewer *</label>
              <input type="text" name="inspector_name" class="form-control form-control-sm" value="T. Sivakumar (QC Head)" required>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Lab Physical & Fastness Test Results</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">Shade Match Standard *</label>
              <select name="shade_match" class="form-select form-select-sm" required>
                <option value="approved" selected>Approved (Within ΔE 0.8 Tolerance)</option>
                <option value="within_tolerance">Conditional Pass (Tolerance Met)</option>
                <option value="rejected">Rejected (Off-Shade)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Side-to-Side Listing *</label>
              <select name="side_to_side_variation" class="form-select form-select-sm">
                <option value="none" selected>None (Perfect Uniformity)</option>
                <option value="acceptable">Minor (Commercial Acceptable)</option>
                <option value="rejectable">Rejectable Variation</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">End-to-End Variation *</label>
              <select name="end_to_end_variation" class="form-select form-select-sm">
                <option value="none" selected>None (Uniform Head to Tail)</option>
                <option value="acceptable">Acceptable</option>
                <option value="rejectable">Tail-end Dark/Light Variation</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Finished GSM *</label>
              <input type="number" name="measured_gsm" class="form-control form-control-sm" value="160" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Finished Width (Inches) *</label>
              <input type="number" step="0.1" name="measured_width_inches" class="form-control form-control-sm" value="48.0" required>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold">Wash Fastness</label>
              <input type="text" name="wash_fastness" class="form-control form-control-sm" value="4-5">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold">Rub Fastness</label>
              <input type="text" name="rubbing_fastness" class="form-control form-control-sm" value="4">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-bold">Fabric pH</label>
              <input type="number" step="0.1" name="ph_after_wash" class="form-control form-control-sm" value="6.5">
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Final Disposition & Approved Kilograms</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-bold text-success">Accepted Weight (Kg) *</label>
              <input type="number" step="0.01" name="accepted_kg" id="qcAcceptedKg" class="form-control form-control-sm fw-bold" value="480.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-warning">Hold Weight (Kg)</label>
              <input type="number" step="0.01" name="hold_kg" class="form-control form-control-sm" value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-danger">Rejected Weight (Kg)</label>
              <input type="number" step="0.01" name="rejected_kg" class="form-control form-control-sm" value="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Final QC Disposition *</label>
              <select name="qc_disposition" class="form-select form-select-sm" required>
                <option value="accepted" selected>Accepted (Release for Finishing & Stenter)</option>
                <option value="partially_accepted">Partially Accepted</option>
                <option value="hold">Hold for Management Review</option>
                <option value="reprocess">Reprocess / Re-dye Required</option>
                <option value="rejected">Rejected (Downgrade to Scrap)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">QC Remarks</label>
              <input type="text" name="remarks" class="form-control form-control-sm" value="Clean dye strike, no crease or dye blotches, meets buyer ISO standard.">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-shield-check me-1"></i> Post QC Disposition</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('qcBatchSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const loaded = parseFloat(opt.getAttribute('data-loaded')) || 500;
  // Estimate ~5% process moisture/loss
  const accepted = Math.round(loaded * 0.95);
  document.getElementById('qcAcceptedKg').value = accepted.toFixed(2);
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
