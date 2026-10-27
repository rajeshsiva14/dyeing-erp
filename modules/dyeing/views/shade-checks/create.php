<?php
/**
 * Dedicated Full-Page Form: Record In-Process Mid-Dyeing Shade Sample & ΔE Check
 * File: modules/dyeing/views/shade-checks/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Mid-Process Shade Sample';
$activeModule = 'shade-checks';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle adding new in-process shade check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_shade_check') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $sampleId = 'SMP-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 3, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
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
        $reviewer = trim($_POST['qc_reviewer'] ?? ($currentUser['name'] ?? 'T. Sivakumar (QC Head)'));

        try {
            Database::insert('dyeing_shade_checks', [
                'company_id'        => $companyId,
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
        } catch (\Throwable $e) {
            $errors[] = 'Failed to record shade check: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/shade-checks') ?>">Shade Checks</a></li>
            <li class="breadcrumb-item active">New Shade Sample</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-eyedropper text-dark me-2"></i>Record Mid-Process Shade Sample (ΔE Check)</h1>
        <p class="text-secondary small mb-0">Record spectrophotometer L*a*b* coordinates, ΔE tolerance (&le; 0.8), visual comparison, and shading dye additions.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/shade-checks') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="save_shade_check">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Batch & Sampling Context -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Sampling Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Active Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateShade(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-shade="<?= htmlspecialchars($b['shade_name']) ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= htmlspecialchars($b['machine_name']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sampling Stage / Phase *</label>
                <select name="sampling_stage" class="form-select form-select-sm" required>
                  <option value="Mid-Dye Migration (60°C)">Mid-Dye Migration (60°C - Pre-Alkali)</option>
                  <option value="Post-Fixation Sampling" selected>Post-Fixation Sampling (Alkali Completed)</option>
                  <option value="Post Hot-Wash (Clear Liquor)">Post Hot-Wash (Clear Liquor Rinsed)</option>
                  <option value="Pre-Unload Final Swatch">Pre-Unload Final Swatch (Hydro Ready)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sampled By *</label>
                <input type="text" name="sampled_by" class="form-control form-control-sm fw-bold" value="K. Arumugam (Lab QC)" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">QC Reviewer / Lead *</label>
                <input type="text" name="qc_reviewer" class="form-control form-control-sm fw-bold" value="T. Sivakumar (QC Head)" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Spectrophotometer & Colorimetry -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-palette me-2"></i>Spectrophotometer L* a* b* & ΔE Readings</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Master Reference Standard *</label>
                  <input type="text" name="reference_shade" id="refShadeInput" class="form-control form-control-sm fw-bold" value="SH-NAV-01 (Royal Navy Standard)" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Light Box Illuminant *</label>
                  <select name="light_source" class="form-select form-select-sm" required>
                    <option value="D65" selected>D65 (Daylight 6500K Primary)</option>
                    <option value="TL84">TL84 (Marks & Spencer Store Light)</option>
                    <option value="CWF">CWF (Cool White Fluorescent)</option>
                    <option value="Incandescent A">Incandescent A (Tungsten)</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-success">Spectro ΔE CMC (2:1) *</label>
                  <input type="number" step="0.01" name="delta_e" class="form-control form-control-sm fw-bold font-monospace text-success fs-6" value="0.42" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">L* (Lightness)</label>
                  <input type="number" step="0.01" name="l_val" class="form-control form-control-sm font-monospace" value="22.10" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">a* (Red/Green)</label>
                  <input type="number" step="0.01" name="a_val" class="form-control form-control-sm font-monospace" value="1.85" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">b* (Yellow/Blue)</label>
                  <input type="number" step="0.01" name="b_val" class="form-control form-control-sm font-monospace" value="-28.40" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Visual Comparison in Light Box *</label>
                  <select name="visual_assessment" class="form-select form-select-sm fw-bold" required>
                    <option value="exact_match" selected>Exact Match (Commercially Acceptable)</option>
                    <option value="slightly_lighter">Slightly Lighter (Needs Depth Addition)</option>
                    <option value="slightly_darker">Slightly Darker</option>
                    <option value="slightly_redder">Slightly Redder Cast</option>
                    <option value="slightly_greener">Slightly Greener Cast</option>
                    <option value="off_shade">Off Shade (Requires Stripping/Shading)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">QC Decision / Disposition *</label>
                  <select name="qc_decision" class="form-select form-select-sm fw-bold" required>
                    <option value="pass" selected>Pass (Clear Batch for Hot Wash / Rinsing)</option>
                    <option value="add_dyes">Add Shading Dyestuff (Top-up Addition)</option>
                    <option value="hold">Hold (Re-check Sample in 15 mins)</option>
                    <option value="fail">Fail (Route to Reprocess / Strip)</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Shading Additions / Lab Correction Notes</label>
                  <textarea name="correction_notes" class="form-control form-control-sm" rows="3" placeholder="e.g., ΔE 0.42 within 0.8 tolerance. Tone match confirmed under D65 & TL84. Proceed to hot soaping."></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/shade-checks') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-patch-check me-1"></i> Log Shade Swatch Test
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function populateShade(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.shade) {
    document.getElementById('refShadeInput').value = opt.dataset.shade + ' Standard';
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
