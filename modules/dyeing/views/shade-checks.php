<?php
/**
 * In-process Sample & Shade Check
 * Mid-process shade sampling from pot, spectrophotometer ΔE, L*a*b* readings and shading dye additions
 * File: modules/dyeing/views/shade-checks.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'In-Process Shade Check';
$activeModule = 'shade-checks';

// Handle adding new in-process shade check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_shade_check') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $sampleId = 'SMP-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 3, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    
    $stage = trim($_POST['sampling_stage'] ?? 'Post-Fixation Sampling');
    $refShade = trim($_POST['reference_shade'] ?? 'SH-NAV-01 (Royal Navy)');
    $lightSource = $_POST['light_source'] ?? 'D65';
    $deltaE = (float)($_POST['delta_e'] ?? 0.42);
    $lVal = (float)($_POST['l_val'] ?? 22.0);
    $aVal = (float)($_POST['a_val'] ?? 1.8);
    $bVal = (float)($_POST['b_val'] ?? -28.0);
    $visual = $_POST['visual_assessment'] ?? 'exact_match';
    $decision = $_POST['qc_decision'] ?? 'pass';
    $correction = trim($_POST['correction_notes'] ?? '');
    $reviewer = trim($_POST['qc_reviewer'] ?? 'T. Sivakumar (QC Head)');

    Database::insert('dyeing_shade_checks', [
        'company_id'        => 1,
        'sample_id'         => $sampleId,
        'dye_batch_id'      => $batchId,
        'batch_no'          => $batchNo,
        'sampling_stage'    => $stage,
        'sampled_at'        => date('Y-m-d H:i:s'),
        'sampled_by'        => trim($_POST['sampled_by'] ?? 'K. Arumugam (Lab QC)'),
        'reference_shade'   => $refShade,
        'light_source'      => $lightSource,
        'delta_e'           => $deltaE,
        'l_val'             => $lVal,
        'a_val'             => $aVal,
        'b_val'             => $bVal,
        'visual_assessment' => $visual,
        'qc_decision'       => $decision,
        'correction_notes'  => $correction,
        'qc_reviewer'       => $reviewer
    ]);

    header('Location: ' . Url::to('/dyeing/shade-checks?logged=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$checks = Database::fetchAll("
    SELECT sc.*, b.actual_loaded_kg, r.shade_name as batch_shade, m.name as machine_name
    FROM dyeing_shade_checks sc
    JOIN dye_batches b ON sc.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    ORDER BY sc.id DESC
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
            <li class="breadcrumb-item active">In-process Shade Check</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-palette text-primary me-2"></i>In-Process Sample & Spectrophotometer Shade Check</h1>
        <p class="text-secondary small mb-0">Monitor live batch color matching, L* a* b* color coordinates, ΔE tolerance (&le; 0.8), and add shading dyes if needed.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newCheckModal">
          <i class="bi bi-plus-circle me-1"></i> Record Mid-Process Sample
        </button>
      </div>
    </div>

    <!-- Shade Checks Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-patch-check text-primary me-2"></i>In-Process Sampling & ΔE Match Register</h2>
        <span class="small text-secondary"><?= count($checks) ?> Shade Tests</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Sample ID</th>
              <th>Batch No</th>
              <th>Sampling Stage</th>
              <th>Reference Standard</th>
              <th>Light Source</th>
              <th class="text-center">L* a* b* Readings</th>
              <th class="text-center">ΔE (Tolerance &le; 0.8)</th>
              <th>Visual Tone Assessment</th>
              <th>QC Decision</th>
              <th>Reviewer</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($checks as $sc): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($sc['sample_id']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($sc['batch_no']) ?></span></td>
                <td><?= htmlspecialchars($sc['sampling_stage']) ?></td>
                <td><strong><?= htmlspecialchars($sc['reference_shade']) ?></strong></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sc['light_source']) ?></span></td>
                <td class="text-center font-monospace small">
                  L: <strong><?= number_format($sc['l_val'] ?? 22.0, 1) ?></strong> · 
                  a: <strong><?= number_format($sc['a_val'] ?? 1.8, 1) ?></strong> · 
                  b: <strong><?= number_format($sc['b_val'] ?? -28.0, 1) ?></strong>
                </td>
                <td class="text-center font-monospace">
                  <span class="badge <?= ($sc['delta_e'] <= 0.8) ? 'bg-success' : 'bg-danger' ?> fs-6">
                    ΔE = <?= number_format($sc['delta_e'], 2) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border text-uppercase" style="font-size:0.7rem;">
                    <?= str_replace('_', ' ', $sc['visual_assessment']) ?>
                  </span>
                  <?php if (!empty($sc['correction_notes'])): ?>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($sc['correction_notes']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($sc['qc_decision'] === 'pass'): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Pass (Unload)</span>
                  <?php elseif ($sc['qc_decision'] === 'add_dyes'): ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-plus me-1"></i>Add Shading Dyes</span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?= strtoupper($sc['qc_decision']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($sc['qc_reviewer']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Shade Check Modal -->
<div class="modal fade" id="newCheckModal" tabindex="-1" aria-labelledby="newCheckModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_shade_check">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newCheckModalLabel"><i class="bi bi-palette text-primary me-2"></i>Record In-Process Shade Sample Reading</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Active Production Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> (<?= htmlspecialchars($b['shade_name']) ?> · <?= number_format($b['actual_loaded_kg'], 0) ?> Kg)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Sampling Stage *</label>
              <select name="sampling_stage" class="form-select form-select-sm" required>
                <option value="Middle of Fixation Hold (30 mins)">Middle of Fixation Hold (30 mins)</option>
                <option value="End of Alkali Fixation Hold (60 mins)" selected>End of Alkali Fixation Hold (60 mins)</option>
                <option value="Post-Hot Wash & Neutralization">Post-Hot Wash & Neutralization</option>
                <option value="After Softener Application">After Softener Application</option>
              </select>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Spectrophotometer Instrumental Readings</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold text-primary">ΔE Color Difference *</label>
              <input type="number" step="0.01" name="delta_e" class="form-control form-control-sm fw-bold" value="0.45" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">L* (Lightness)</label>
              <input type="number" step="0.01" name="l_val" class="form-control form-control-sm" value="22.15">
            </div>
            <div class="col-md-3">
              <label class="form-label">a* (Red/Green)</label>
              <input type="number" step="0.01" name="a_val" class="form-control form-control-sm" value="1.85">
            </div>
            <div class="col-md-3">
              <label class="form-label">b* (Yellow/Blue)</label>
              <input type="number" step="0.01" name="b_val" class="form-control form-control-sm" value="-28.40">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Viewing Light Source *</label>
              <select name="light_source" class="form-select form-select-sm">
                <option value="D65" selected>D65 (Artificial Daylight 6500K)</option>
                <option value="TL84">TL84 (Marks & Spencer / European Store)</option>
                <option value="CWF">CWF (Cool White Fluorescent - US)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Visual Assessment *</label>
              <select name="visual_assessment" class="form-select form-select-sm" required>
                <option value="exact_match" selected>Exact Match with Approved Standard</option>
                <option value="slightly_lighter">Slightly Lighter (Needs dye top-up)</option>
                <option value="slightly_darker">Slightly Darker (Within tolerance)</option>
                <option value="slightly_redder">Slightly Redder Tone</option>
                <option value="slightly_greener">Slightly Greener Tone</option>
                <option value="off_shade">Off-Shade (Hold for Re-trial)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">QC Floor Decision *</label>
              <select name="qc_decision" class="form-select form-select-sm" required>
                <option value="pass" selected>Pass (Authorize Vessel Drain & Unload)</option>
                <option value="add_dyes">Add Shading Dye Top-up (5-10%)</option>
                <option value="hold">Hold for Manager Approval</option>
                <option value="fail">Fail (Route to Reprocess)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Correction Recommendation / Notes</label>
              <input type="text" name="correction_notes" class="form-control form-control-sm" value="Tone is pure navy blue, ΔE is well within 0.8 tolerance. Approved to proceed to hot wash & softener.">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Save Shade Decision</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
