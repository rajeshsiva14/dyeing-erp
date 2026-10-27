<?php
/**
 * Dedicated Full-Page Form: Log Real-Time Temperature & pH Reading
 * File: modules/dyeing/views/production-entry/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Log Floor Temperature & pH Reading';
$activeModule = 'production-entry';

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

// Handle New Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_parameter') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $targetBatchId = (int)($_POST['dye_batch_id'] ?? $batchId);
    $step = trim($_POST['process_step'] ?? 'Reactive Dyeing Hold');
    $tempSet = (float)($_POST['set_temperature_c'] ?? 60.0);
    $tempAct = (float)($_POST['actual_temperature_c'] ?? 60.2);
    $phSet = (float)($_POST['set_ph'] ?? 11.2);
    $phAct = (float)($_POST['actual_ph'] ?? 11.15);
    $cycleMins = (int)($_POST['cycle_time_minutes'] ?? 30);
    $notes = trim($_POST['operator_notes'] ?? '');

    try {
        Database::insert('dye_batch_parameters', [
            'dye_batch_id'         => $targetBatchId,
            'process_step'         => $step,
            'set_temperature_c'    => $tempSet,
            'actual_temperature_c' => $tempAct,
            'set_ph'               => $phSet,
            'actual_ph'            => $phAct,
            'cycle_time_minutes'   => $cycleMins,
            'recorded_at'          => date('Y-m-d H:i:s'),
            'operator_notes'       => $notes
        ]);

        header('Location: ' . Url::to('/dyeing/production-entry?batch_id=' . $targetBatchId . '&logged=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to log readings: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $batchId) ?>">Production Entry</a></li>
            <li class="breadcrumb-item active">New Log</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-thermometer-half text-dark me-2"></i>Log Floor Temperature & pH Reading</h1>
        <p class="text-secondary small mb-0">Record calibrated physical sensor readings, current stage, and operator comments.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $batchId) ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Floor Log
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
      <input type="hidden" name="action" value="log_parameter">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Target Batch Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cpu me-2"></i>Target Vessel & Batch</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Active Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required>
                  <?php foreach ($allBatches as $ab): ?>
                    <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == $batchId) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= htmlspecialchars($ab['machine_code']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Current Timestamp</label>
                <input type="text" class="form-control form-control-sm bg-light font-monospace" value="<?= date('d M Y · H:i:s') ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Sensor Parameters Entry -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2"></i>Process Stage & Telemetry Parameters</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-bold">Current Process Step / Phase *</label>
                  <select name="process_step" class="form-select form-select-sm" required>
                    <option value="Pre-treatment & Bio-Polishing">1. Pre-treatment & Bio-Polishing (Enzyme/Acid)</option>
                    <option value="Bleaching & Scouring (95°C)">2. Bleaching & Scouring (95°C Peroxide/Caustic)</option>
                    <option value="Hot Rinse & Peroxide Neutralization">3. Hot Rinse & Peroxide Neutralization</option>
                    <option value="Reactive Dye Dosing & Salt Migration">4. Reactive Dye Dosing & Salt Migration (60°C)</option>
                    <option value="Soda Ash Dosing & Alkali Fixation" selected>5. Soda Ash Dosing & Alkali Fixation (pH 11.2)</option>
                    <option value="Neutralization & Polymeric Hot Soaping">6. Neutralization & Polymeric Hot Soaping (95°C)</option>
                    <option value="Softening & Vessel Unloading Handover">7. Softening & Vessel Unloading Handover</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Target Temp (°C) *</label>
                  <input type="number" step="0.1" name="set_temperature_c" class="form-control form-control-sm" value="60.0" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-danger">Actual Temp (°C) *</label>
                  <input type="number" step="0.1" name="actual_temperature_c" class="form-control form-control-sm fw-bold" value="60.2" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Target pH *</label>
                  <input type="number" step="0.01" name="set_ph" class="form-control form-control-sm" value="11.20" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-primary">Actual pH *</label>
                  <input type="number" step="0.01" name="actual_ph" class="form-control form-control-sm fw-bold" value="11.15" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Cycle Hold Time (Mins) *</label>
                  <input type="number" name="cycle_time_minutes" class="form-control form-control-sm" value="45" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label small fw-bold">Operator Floor Notes & Observations</label>
                  <input type="text" name="operator_notes" class="form-control form-control-sm" placeholder="e.g., Soda Ash dosing complete. Liquor clear, circulation uniform.">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $batchId) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-broadcast me-1"></i> Commit Parameter Log
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
