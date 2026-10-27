<?php
/**
 * Dedicated Full-Page Form: Verify & Sign-Off Process Stage Completion
 * File: modules/dyeing/views/process-sheets/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Sign-off Process Stage';
$activeModule = 'process-sheets';

$stageId = (int)($_GET['id'] ?? 0);
$stage = Database::fetch("SELECT * FROM dyeing_process_stages WHERE id = ?", [$stageId]);

if (!$stage) {
    header('Location: ' . Url::to('/dyeing/process-sheets?error=not_found'));
    exit;
}

$batch = Database::fetch("SELECT b.*, r.shade_name, m.code as machine_code FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$stage['process_sheet_id']]);
$errors = [];

// Handle Stage Completion Sign-Off
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_stage') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $actualTemp = (float)($_POST['actual_temperature_c'] ?? $stage['target_temperature_c']);
    $actualPh = (float)($_POST['actual_ph'] ?? $stage['target_ph']);
    $actualMins = (int)($_POST['actual_duration_mins'] ?? $stage['target_duration_mins']);
    $status = $_POST['status'] ?? 'completed';
    $chem = trim($_POST['chemical_addition'] ?? $stage['chemical_addition']);
    $inst = trim($_POST['operator_instruction'] ?? $stage['operator_instruction']);

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_process_stages SET
                actual_temperature_c = ?,
                actual_ph = ?,
                actual_duration_mins = ?,
                chemical_addition = ?,
                operator_instruction = ?,
                status = ?,
                completed_at = IF(? = 'completed', NOW(), completed_at)
            WHERE id = ?
        ")->execute([
            $actualTemp,
            $actualPh,
            $actualMins,
            $chem,
            $inst,
            $status,
            $status,
            $stageId
        ]);

        header('Location: ' . Url::to('/dyeing/process-sheets?batch_id=' . $stage['process_sheet_id'] . '&completed=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update stage: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $stage['process_sheet_id']) ?>">Process Sheets</a></li>
            <li class="breadcrumb-item active">Stage #<?= $stage['stage_no'] ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-check2-circle text-dark me-2"></i>Sign-off Stage: <?= htmlspecialchars($stage['stage_name']) ?></h1>
        <p class="text-secondary small mb-0">Record actual bath temperature, pH sensor reading, cycle duration, and clear stage for next process.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $stage['process_sheet_id']) ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="complete_stage">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Target Batch Info Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Stage & Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Active Batch</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($batch['batch_no'] ?? '') ?> (<?= htmlspecialchars($batch['shade_name'] ?? '') ?>)" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Machine Vessel</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($batch['machine_code'] ?? 'M-01') ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Stage Number Sequence</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="Stage <?= $stage['stage_no'] ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Current Status</label>
                <span class="badge <?= $stage['status'] === 'completed' ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase p-2 d-block text-center">
                  <?= htmlspecialchars($stage['status']) ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Telemetry Parameters & Sign-off -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2"></i>Actual Parameter Telemetry & Sign-off</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-12">
                  <div class="p-3 rounded bg-light border mb-2">
                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($stage['stage_name']) ?></div>
                    <div class="text-secondary small mt-1"><?= htmlspecialchars($stage['operator_instruction'] ?? '') ?></div>
                    <div class="mt-2 text-primary small fw-semibold">Target Spec: <?= $stage['target_temperature_c'] ?>°C · pH <?= $stage['target_ph'] ?> · <?= $stage['target_duration_mins'] ?> Mins</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Actual Temp (°C) *</label>
                  <input type="number" step="0.1" name="actual_temperature_c" class="form-control form-control-sm fw-bold" value="<?= (float)($stage['actual_temperature_c'] ?? $stage['target_temperature_c']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-primary">Actual pH *</label>
                  <input type="number" step="0.1" name="actual_ph" class="form-control form-control-sm fw-bold" value="<?= (float)($stage['actual_ph'] ?? $stage['target_ph']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Actual Duration (Mins) *</label>
                  <input type="number" name="actual_duration_mins" class="form-control form-control-sm fw-bold" value="<?= (int)($stage['actual_duration_mins'] ?? $stage['target_duration_mins']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Stage Disposition Decision *</label>
                  <select name="status" class="form-select form-select-sm fw-bold" required>
                    <option value="completed" <?= $stage['status'] === 'completed' ? 'selected' : '' ?>>Completed (Parameters Met Acceptance Criteria)</option>
                    <option value="deviation" <?= $stage['status'] === 'deviation' ? 'selected' : '' ?>>Completed with Approved Deviation</option>
                    <option value="in_progress" <?= $stage['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress (Vessel Running)</option>
                    <option value="hold" <?= $stage['status'] === 'hold' ? 'selected' : '' ?>>Hold for Supervisor Inspection</option>
                    <option value="pending" <?= $stage['status'] === 'pending' ? 'selected' : '' ?>>Pending (Not Started)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Chemical Additions Dosed</label>
                  <input type="text" name="chemical_addition" class="form-control form-control-sm" value="<?= htmlspecialchars($stage['chemical_addition'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Operator Verification Notes</label>
                  <textarea name="operator_instruction" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($stage['operator_instruction'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $stage['process_sheet_id']) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Sign-off Stage Clearance
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
