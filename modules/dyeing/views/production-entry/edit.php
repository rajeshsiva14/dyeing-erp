<?php
/**
 * Dedicated Full-Page Form: Update Telemetry Parameter Reading
 * File: modules/dyeing/views/production-entry/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Telemetry Parameter Log';
$activeModule = 'production-entry';

$paramId = (int)($_GET['id'] ?? 0);
$param = Database::fetch("SELECT * FROM dye_batch_parameters WHERE id = ?", [$paramId]);

if (!$param) {
    header('Location: ' . Url::to('/dyeing/production-entry?error=not_found'));
    exit;
}

$batch = Database::fetch("SELECT b.*, r.shade_name, m.code as machine_code FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$param['dye_batch_id']]);
$errors = [];

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_parameter') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $step = trim($_POST['process_step'] ?? $param['process_step']);
    $tempSet = (float)($_POST['set_temperature_c'] ?? $param['set_temperature_c']);
    $tempAct = (float)($_POST['actual_temperature_c'] ?? $param['actual_temperature_c']);
    $phSet = (float)($_POST['set_ph'] ?? $param['set_ph']);
    $phAct = (float)($_POST['actual_ph'] ?? $param['actual_ph']);
    $cycleMins = (int)($_POST['cycle_time_minutes'] ?? $param['cycle_time_minutes']);
    $notes = trim($_POST['operator_notes'] ?? '');

    try {
        Database::getConnection()->prepare("
            UPDATE dye_batch_parameters SET
                process_step = ?,
                set_temperature_c = ?,
                actual_temperature_c = ?,
                set_ph = ?,
                actual_ph = ?,
                cycle_time_minutes = ?,
                operator_notes = ?
            WHERE id = ?
        ")->execute([
            $step,
            $tempSet,
            $tempAct,
            $phSet,
            $phAct,
            $cycleMins,
            $notes,
            $paramId
        ]);

        header('Location: ' . Url::to('/dyeing/production-entry?batch_id=' . $param['dye_batch_id'] . '&updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update reading: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $param['dye_batch_id']) ?>">Production Entry</a></li>
            <li class="breadcrumb-item active">Edit Reading #<?= $paramId ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Floor Parameter Reading</h1>
        <p class="text-secondary small mb-0">Calibrate recorded sensor readings, hold duration, or update floor remarks.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $param['dye_batch_id']) ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="update_parameter">
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
                <label class="form-label small fw-bold">Active Batch</label>
                <input type="text" class="form-control form-control-sm bg-light fw-bold font-monospace" value="<?= htmlspecialchars($batch['batch_no'] ?? '') ?> (<?= htmlspecialchars($batch['shade_name'] ?? '') ?>)" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Machine Vessel</label>
                <input type="text" class="form-control form-control-sm bg-light font-monospace" value="<?= htmlspecialchars($batch['machine_code'] ?? 'M-01') ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Recorded Timestamp</label>
                <input type="text" class="form-control form-control-sm bg-light font-monospace" value="<?= date('d M Y · H:i:s', strtotime($param['recorded_at'])) ?>" readonly>
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
                    <option value="Pre-treatment & Bio-Polishing" <?= $param['process_step'] === 'Pre-treatment & Bio-Polishing' ? 'selected' : '' ?>>1. Pre-treatment & Bio-Polishing</option>
                    <option value="Bleaching & Scouring (95°C)" <?= $param['process_step'] === 'Bleaching & Scouring (95°C)' ? 'selected' : '' ?>>2. Bleaching & Scouring (95°C)</option>
                    <option value="Hot Rinse & Peroxide Neutralization" <?= $param['process_step'] === 'Hot Rinse & Peroxide Neutralization' ? 'selected' : '' ?>>3. Hot Rinse & Peroxide Neutralization</option>
                    <option value="Reactive Dye Dosing & Salt Migration" <?= $param['process_step'] === 'Reactive Dye Dosing & Salt Migration' ? 'selected' : '' ?>>4. Reactive Dye Dosing & Salt Migration (60°C)</option>
                    <option value="Soda Ash Dosing & Alkali Fixation" <?= $param['process_step'] === 'Soda Ash Dosing & Alkali Fixation' ? 'selected' : '' ?>>5. Soda Ash Dosing & Alkali Fixation (pH 11.2)</option>
                    <option value="Neutralization & Polymeric Hot Soaping" <?= $param['process_step'] === 'Neutralization & Polymeric Hot Soaping' ? 'selected' : '' ?>>6. Neutralization & Polymeric Hot Soaping (95°C)</option>
                    <option value="Softening & Vessel Unloading Handover" <?= $param['process_step'] === 'Softening & Vessel Unloading Handover' ? 'selected' : '' ?>>7. Softening & Vessel Unloading Handover</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Target Temp (°C) *</label>
                  <input type="number" step="0.1" name="set_temperature_c" class="form-control form-control-sm" value="<?= (float)$param['set_temperature_c'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-danger">Actual Temp (°C) *</label>
                  <input type="number" step="0.1" name="actual_temperature_c" class="form-control form-control-sm fw-bold" value="<?= (float)$param['actual_temperature_c'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Target pH *</label>
                  <input type="number" step="0.01" name="set_ph" class="form-control form-control-sm" value="<?= (float)$param['set_ph'] ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-primary">Actual pH *</label>
                  <input type="number" step="0.01" name="actual_ph" class="form-control form-control-sm fw-bold" value="<?= (float)$param['actual_ph'] ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Cycle Hold Time (Mins) *</label>
                  <input type="number" name="cycle_time_minutes" class="form-control form-control-sm" value="<?= (int)$param['cycle_time_minutes'] ?>" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label small fw-bold">Operator Floor Notes & Observations</label>
                  <input type="text" name="operator_notes" class="form-control form-control-sm" value="<?= htmlspecialchars($param['operator_notes'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $param['dye_batch_id']) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Parameter Reading
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
