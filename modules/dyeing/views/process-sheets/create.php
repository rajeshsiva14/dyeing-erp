<?php
/**
 * Dedicated Full-Page Form: Add Custom Stage to Dyeing Process Sheet
 * File: modules/dyeing/views/process-sheets/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Add Process Stage';
$activeModule = 'process-sheets';

$batchId = (int)($_GET['batch_id'] ?? 0);
$allBatches = Database::fetchAll("SELECT b.*, r.shade_name, m.code as machine_code FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC");

$selectedBatch = null;
if ($batchId > 0) {
    foreach ($allBatches as $ab) {
        if ($ab['id'] == $batchId) { $selectedBatch = $ab; break; }
    }
}
if (!$selectedBatch && !empty($allBatches)) {
    $selectedBatch = $allBatches[0];
    $batchId = $selectedBatch['id'];
}

$errors = [];

// Handle Adding Custom Stage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_stage') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $targetBatchId = (int)($_POST['process_sheet_id'] ?? $batchId);
    $stageNo = (int)($_POST['stage_no'] ?? 8);
    $stageName = trim($_POST['stage_name'] ?? 'Additional Washing Stage');
    $targetTemp = (float)($_POST['target_temperature_c'] ?? 60.0);
    $targetPh = (float)($_POST['target_ph'] ?? 7.0);
    $targetMins = (int)($_POST['target_duration_mins'] ?? 30);
    $chem = trim($_POST['chemical_addition'] ?? '');
    $inst = trim($_POST['operator_instruction'] ?? '');

    try {
        Database::insert('dyeing_process_stages', [
            'process_sheet_id'       => $targetBatchId,
            'stage_no'               => $stageNo,
            'stage_name'             => $stageName,
            'target_temperature_c'   => $targetTemp,
            'actual_temperature_c'   => $targetTemp,
            'target_ph'              => $targetPh,
            'actual_ph'              => $targetPh,
            'target_duration_mins'   => $targetMins,
            'actual_duration_mins'   => $targetMins,
            'chemical_addition'      => $chem,
            'operator_instruction'   => $inst,
            'status'                 => 'pending',
            'completed_at'           => null
        ]);

        header('Location: ' . Url::to('/dyeing/process-sheets?batch_id=' . $targetBatchId . '&stage_added=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to add stage: ' . $e->getMessage();
    }
}

$maxStage = (int)(Database::fetch("SELECT MAX(stage_no) as m FROM dyeing_process_stages WHERE process_sheet_id = ?", [$batchId])['m'] ?? 7);
$nextStage = $maxStage + 1;
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $batchId) ?>">Process Sheets</a></li>
            <li class="breadcrumb-item active">Add Stage</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-plus-circle text-dark me-2"></i>Add Custom Process Stage</h1>
        <p class="text-secondary small mb-0">Append customized scouring, neutralization, dye injection, or post-rinse stage to the batch process route.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $batchId) ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Process Route
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
      <input type="hidden" name="action" value="save_stage">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Target Batch Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Target Batch Route</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Active Batch *</label>
                <select name="process_sheet_id" class="form-select form-select-sm" required>
                  <?php foreach ($allBatches as $ab): ?>
                    <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == $batchId) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= htmlspecialchars($ab['machine_code']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Stage Number Sequence *</label>
                <input type="number" name="stage_no" class="form-control form-control-sm font-monospace fw-bold" value="<?= $nextStage ?>" required min="1">
              </div>
            </div>
          </div>
        </div>

        <!-- Stage Parameters -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2"></i>Stage Name & Recipe Parameters</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-bold">Stage Title & Nomenclature *</label>
                  <input type="text" name="stage_name" class="form-control form-control-sm fw-bold" placeholder="e.g., 8. Hot Acid Wash & Acetic Neutralization" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Target Temperature (°C) *</label>
                  <input type="number" step="0.1" name="target_temperature_c" class="form-control form-control-sm fw-bold" value="60.0" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Target pH *</label>
                  <input type="number" step="0.1" name="target_ph" class="form-control form-control-sm fw-bold" value="6.5" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Target Duration (Minutes) *</label>
                  <input type="number" name="target_duration_mins" class="form-control form-control-sm fw-bold" value="30" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Chemical Additions / Dosing Indent</label>
                  <input type="text" name="chemical_addition" class="form-control form-control-sm" placeholder="e.g., Acetic Acid 0.5 g/L + Wetting Agent 0.3 g/L">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Operator Floor Instructions</label>
                  <textarea name="operator_instruction" class="form-control form-control-sm" rows="3" placeholder="Specify vessel cycle instructions, temperature ramp rate (°C/min), holding time, or bath sample checks..."></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $batchId) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-plus-lg me-1"></i> Append Stage to Sheet
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
