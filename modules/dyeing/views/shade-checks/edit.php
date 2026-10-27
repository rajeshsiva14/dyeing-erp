<?php
/**
 * Dedicated Full-Page Form: Update Mid-Process Shade Sample & ΔE Readings
 * File: modules/dyeing/views/shade-checks/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update In-Process Shade Check';
$activeModule = 'shade-checks';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$scId = (int)($_GET['id'] ?? 0);

$check = Database::fetch("SELECT * FROM dyeing_shade_checks WHERE id = ?", [$scId]);
if (!$check) {
    header('Location: ' . Url::to('/dyeing/shade-checks?error=not_found'));
    exit;
}

$batch = Database::fetch("SELECT b.*, r.shade_name, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id LEFT JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$check['dye_batch_id']]);
$errors = [];

// Handle update shade check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_shade_check') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $stage = trim($_POST['sampling_stage'] ?? $check['sampling_stage']);
    $refShade = trim($_POST['reference_shade'] ?? $check['reference_shade']);
    $lightSource = $_POST['light_source'] ?? $check['light_source'];
    $deltaE = (float)($_POST['delta_e'] ?? $check['delta_e']);
    $lVal = (float)($_POST['l_val'] ?? $check['l_val']);
    $aVal = (float)($_POST['a_val'] ?? $check['a_val']);
    $bVal = (float)($_POST['b_val'] ?? $check['b_val']);
    $visual = $_POST['visual_assessment'] ?? $check['visual_assessment'];
    $decision = $_POST['qc_decision'] ?? $check['qc_decision'];
    $correction = trim($_POST['correction_notes'] ?? '');
    $reviewer = trim($_POST['qc_reviewer'] ?? $check['qc_reviewer']);

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_shade_checks SET
                sampling_stage = ?,
                reference_shade = ?,
                light_source = ?,
                delta_e = ?,
                l_val = ?,
                a_val = ?,
                b_val = ?,
                visual_assessment = ?,
                qc_decision = ?,
                correction_notes = ?,
                qc_reviewer = ?
            WHERE id = ?
        ")->execute([
            $stage,
            $refShade,
            $lightSource,
            $deltaE,
            $lVal,
            $aVal,
            $bVal,
            $visual,
            $decision,
            $correction,
            $reviewer,
            $scId
        ]);

        header('Location: ' . Url::to('/dyeing/shade-checks?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update shade check: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/shade-checks') ?>">Shade Checks</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($check['sample_id']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Shade Sample #<?= htmlspecialchars($check['sample_id']) ?></h1>
        <p class="text-secondary small mb-0">Update spectrophotometer ΔE, L*a*b* readings, visual comparison, and shading dye additions.</p>
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
      <input type="hidden" name="action" value="update_shade_check">
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
                <label class="form-label small fw-bold">Sample Swatch ID</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($check['sample_id']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Batch Reference</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($check['batch_no']) ?> (<?= htmlspecialchars($batch['shade_name'] ?? '') ?>)" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sampling Stage *</label>
                <select name="sampling_stage" class="form-select form-select-sm" required>
                  <option value="Mid-Dye Migration (60°C)" <?= $check['sampling_stage'] === 'Mid-Dye Migration (60°C)' ? 'selected' : '' ?>>Mid-Dye Migration (60°C)</option>
                  <option value="Post-Fixation Sampling" <?= $check['sampling_stage'] === 'Post-Fixation Sampling' ? 'selected' : '' ?>>Post-Fixation Sampling</option>
                  <option value="Post Hot-Wash (Clear Liquor)" <?= $check['sampling_stage'] === 'Post Hot-Wash (Clear Liquor)' ? 'selected' : '' ?>>Post Hot-Wash (Clear Liquor)</option>
                  <option value="Pre-Unload Final Swatch" <?= $check['sampling_stage'] === 'Pre-Unload Final Swatch' ? 'selected' : '' ?>>Pre-Unload Final Swatch</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sampled By</label>
                <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($check['sampled_by']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">QC Reviewer / Lead *</label>
                <input type="text" name="qc_reviewer" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($check['qc_reviewer']) ?>" required>
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
                  <input type="text" name="reference_shade" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($check['reference_shade']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Light Box Illuminant *</label>
                  <select name="light_source" class="form-select form-select-sm" required>
                    <option value="D65" <?= $check['light_source'] === 'D65' ? 'selected' : '' ?>>D65 (Daylight 6500K Primary)</option>
                    <option value="TL84" <?= $check['light_source'] === 'TL84' ? 'selected' : '' ?>>TL84 (Marks & Spencer Store Light)</option>
                    <option value="CWF" <?= $check['light_source'] === 'CWF' ? 'selected' : '' ?>>CWF (Cool White Fluorescent)</option>
                    <option value="Incandescent A" <?= $check['light_source'] === 'Incandescent A' ? 'selected' : '' ?>>Incandescent A (Tungsten)</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-success">Spectro ΔE CMC (2:1) *</label>
                  <input type="number" step="0.01" name="delta_e" class="form-control form-control-sm fw-bold font-monospace text-success fs-6" value="<?= (float)$check['delta_e'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">L* (Lightness)</label>
                  <input type="number" step="0.01" name="l_val" class="form-control form-control-sm font-monospace" value="<?= (float)$check['l_val'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">a* (Red/Green)</label>
                  <input type="number" step="0.01" name="a_val" class="form-control form-control-sm font-monospace" value="<?= (float)$check['a_val'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">b* (Yellow/Blue)</label>
                  <input type="number" step="0.01" name="b_val" class="form-control form-control-sm font-monospace" value="<?= (float)$check['b_val'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Visual Comparison in Light Box *</label>
                  <select name="visual_assessment" class="form-select form-select-sm fw-bold" required>
                    <option value="exact_match" <?= $check['visual_assessment'] === 'exact_match' ? 'selected' : '' ?>>Exact Match (Commercially Acceptable)</option>
                    <option value="slightly_lighter" <?= $check['visual_assessment'] === 'slightly_lighter' ? 'selected' : '' ?>>Slightly Lighter</option>
                    <option value="slightly_darker" <?= $check['visual_assessment'] === 'slightly_darker' ? 'selected' : '' ?>>Slightly Darker</option>
                    <option value="slightly_redder" <?= $check['visual_assessment'] === 'slightly_redder' ? 'selected' : '' ?>>Slightly Redder Cast</option>
                    <option value="slightly_greener" <?= $check['visual_assessment'] === 'slightly_greener' ? 'selected' : '' ?>>Slightly Greener Cast</option>
                    <option value="off_shade" <?= $check['visual_assessment'] === 'off_shade' ? 'selected' : '' ?>>Off Shade (Requires Stripping/Shading)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">QC Decision / Disposition *</label>
                  <select name="qc_decision" class="form-select form-select-sm fw-bold" required>
                    <option value="pass" <?= $check['qc_decision'] === 'pass' ? 'selected' : '' ?>>Pass (Clear Batch for Hot Wash / Rinsing)</option>
                    <option value="add_dyes" <?= $check['qc_decision'] === 'add_dyes' ? 'selected' : '' ?>>Add Shading Dyestuff (Top-up Addition)</option>
                    <option value="hold" <?= $check['qc_decision'] === 'hold' ? 'selected' : '' ?>>Hold (Re-check Sample in 15 mins)</option>
                    <option value="fail" <?= $check['qc_decision'] === 'fail' ? 'selected' : '' ?>>Fail (Route to Reprocess / Strip)</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Shading Additions / Lab Correction Notes</label>
                  <textarea name="correction_notes" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($check['correction_notes'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/shade-checks') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Shade Swatch Test
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
