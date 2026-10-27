<?php
/**
 * Dedicated Standalone Form: Update Dyeing Batch & Vessel Operation
 * File: modules/dyeing/views/batches-edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Dyeing Batch';
$activeModule = 'batches';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$batchId = (int)($_GET['id'] ?? ($_POST['batch_id'] ?? 0));

if ($batchId <= 0) {
    header('Location: ' . Url::to('/dyeing/batches'));
    exit;
}

$errors = [];

// Handle POST to update batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $machineId = (int)($_POST['machine_id'] ?? 0);
    $actualLoaded = (float)($_POST['actual_loaded_kg'] ?? 0);
    $outputWeight = (float)($_POST['output_weight_kg'] ?? 0);
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');
    $operatorName = trim($_POST['operator_name'] ?? '');
    $status = $_POST['status'] ?? 'dyeing';
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if ($machineId <= 0) $errors[] = 'Please select a valid dyeing vessel.';
    if ($actualLoaded <= 0) $errors[] = 'Actual loaded weight must be greater than 0.';

    if (!$errors) {
        $ratioNum = 6;
        if (preg_match('/1:(\d+(\.\d+)?)/', $liquorRatio, $m)) {
            $ratioNum = (float)$m[1];
        }
        $waterVolume = round($actualLoaded * $ratioNum, 2);

        $endTimeSql = in_array($status, ['completed', 'unloaded'], true) ? ", process_end_time = IFNULL(process_end_time, NOW())" : "";

        Database::execute("
            UPDATE dye_batches 
            SET machine_id = ?, actual_loaded_kg = ?, output_weight_kg = ?,
                water_volume_litres = ?, liquor_ratio = ?, operator_name = ?,
                status = ?, rejection_reason = ? {$endTimeSql}
            WHERE id = ? AND company_id = ?
        ", [
            $machineId, $actualLoaded, $outputWeight,
            $waterVolume, $liquorRatio, $operatorName,
            $status, $rejectionReason,
            $batchId, $companyId
        ]);

        // If completed, free up the vessel
        if (in_array($status, ['completed', 'unloaded'], true)) {
            Database::execute("UPDATE machines SET current_status = 'available' WHERE id = ? AND company_id = ?", [$machineId, $companyId]);
        } else {
            Database::execute("UPDATE machines SET current_status = 'running' WHERE id = ? AND company_id = ?", [$machineId, $companyId]);
        }

        header('Location: ' . Url::to('/dyeing/batches?updated=' . $batchId));
        exit;
    }
}

// Handle POST to log parameter reading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_parameter') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $step = trim($_POST['process_step'] ?? '');
    $temp = (float)($_POST['actual_temperature_c'] ?? 0);
    $ph = (float)($_POST['actual_ph'] ?? 7.0);
    $cycle = (int)($_POST['cycle_time_minutes'] ?? 0);
    $notes = trim($_POST['operator_notes'] ?? '');

    if ($step !== '' && $temp > 0) {
        Database::insert('dye_batch_parameters', [
            'company_id'           => $companyId,
            'dye_batch_id'         => $batchId,
            'process_step'         => $step,
            'set_temperature_c'    => $temp,
            'actual_temperature_c' => $temp,
            'set_ph'               => $ph,
            'actual_ph'            => $ph,
            'cycle_time_minutes'   => $cycle,
            'operator_notes'       => $notes,
            'recorded_at'          => date('Y-m-d H:i:s'),
        ]);
        header('Location: ' . Url::to('/dyeing/batches-edit?id=' . $batchId . '&param_logged=1'));
        exit;
    }
}

$b = Database::fetch("
    SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.code as machine_code, m.name as machine_name, m.capacity as machine_cap 
    FROM dye_batches b 
    JOIN dye_recipes r ON b.dye_recipe_id = r.id 
    JOIN machines m ON b.machine_id = m.id 
    WHERE b.id = ? AND b.company_id = ?
", [$batchId, $companyId]);

if (!$b) {
    header('Location: ' . Url::to('/dyeing/batches'));
    exit;
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE company_id = ? AND process_type = 'soft_flow_dyeing' ORDER BY code ASC", [$companyId]);
$batchParams = Database::fetchAll("SELECT * FROM dye_batch_parameters WHERE dye_batch_id = ? ORDER BY id DESC", [$batchId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Action Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Dyeing Batch</h1>
            <span class="badge bg-primary font-monospace"><?= htmlspecialchars($b['batch_no']) ?></span>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['machine_code']) ?></span>
            <span class="badge bg-light text-secondary border"><?= strtoupper(str_replace('_', ' ', $b['status'])) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Full operation update for soft flow vessel parameters, process stages, and actual unloaded weight.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print P40 Batch Card
        </a>
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editBatchForm" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Batch
        </button>
      </div>
    </div>

    <?php if (isset($_GET['param_logged'])): ?>
      <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Temperature & pH parameter log recorded successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="editBatchForm" method="POST" action="<?= Url::to('/dyeing/batches-edit') ?>">
      <input type="hidden" name="action" value="update_batch">
      <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Recipe & Vessel Assignment -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-droplet-half"></i> Section A — Recipe & Machine Allocation</h2>
            <p class="cz-doc-section-desc">Allocated recipe details and soft flow vessel assignment.</p>
          </div>
          <span class="badge badge-bw font-monospace"><?= htmlspecialchars($b['recipe_code']) ?></span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Dye Recipe Standard</label>
            <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($b['shade_name']) ?> (<?= htmlspecialchars($b['recipe_code']) ?> · <?= htmlspecialchars($b['dye_class'] ?? 'Reactive') ?>)" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Vessel / Machine <span class="text-danger">*</span></label>
            <select name="machine_id" id="vesselSelect" class="form-select form-select-sm" required>
              <?php foreach ($machines as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($b['machine_id'] == $m['id']) ? 'selected' : '' ?> data-cap="<?= $m['capacity'] ?>" data-lr="<?= htmlspecialchars($m['liquor_ratio'] ?? '1:6') ?>">
                  <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (Cap: <?= number_format($m['capacity'], 0) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Section B: Weighment & Production Yield -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-speedometer2"></i> Section B — Weighment & Production Yield Telemetry</h2>
            <p class="cz-doc-section-desc">Track planned vs actual loaded weight, unloaded output, and bath liquor ratio.</p>
          </div>
          <span class="badge badge-bw font-monospace">YIELD TELEMETRY</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Planned Weight (Kg)</label>
            <input type="text" class="form-control form-control-sm font-monospace bg-light" readonly value="<?= number_format($b['planned_weight_kg'], 2) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Actual Loaded (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="actual_loaded_kg" id="actualWt" value="<?= $b['actual_loaded_kg'] ?>" class="form-control form-control-sm font-monospace fw-bold" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Unloaded Output (Kg)</label>
            <input type="number" step="0.1" name="output_weight_kg" id="outputWt" value="<?= $b['output_weight_kg'] ?>" class="form-control form-control-sm font-monospace text-success fw-bold">
          </div>
          <div class="col-md-3">
            <label class="form-label">Liquor Ratio</label>
            <input type="text" name="liquor_ratio" id="liquorRatio" value="<?= htmlspecialchars($b['liquor_ratio']) ?>" class="form-control form-control-sm font-monospace">
          </div>
          <div class="col-md-4">
            <label class="form-label">Operator / Vessel In-charge</label>
            <input type="text" name="operator_name" value="<?= htmlspecialchars($b['operator_name'] ?? '') ?>" class="form-control form-control-sm">
          </div>
          <div class="col-md-4">
            <label class="form-label">Batch Process Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select form-select-sm" required>
              <?php 
                $statuses = ['planned', 'loaded', 'dosing', 'dyeing', 'washing', 'unloaded', 'hydro', 'drying', 'compacted', 'completed', 'reprocess'];
                foreach ($statuses as $st):
              ?>
                <option value="<?= $st ?>" <?= ($b['status'] === $st) ? 'selected' : '' ?>><?= strtoupper(str_replace('_', ' ', $st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Rejection / Reprocess Reason</label>
            <input type="text" name="rejection_reason" value="<?= htmlspecialchars($b['rejection_reason'] ?? '') ?>" class="form-control form-control-sm" placeholder="e.g. Patchy dyeing, shade variation">
          </div>
        </div>
      </div>

      <!-- Document Bottom Actions -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top mb-4">
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Batch
        </button>
      </div>
    </form>

    <!-- Section C: Direct Process Parameter Logging -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-thermometer-half text-danger me-2"></i>Log Process Parameter Reading (Temperature °C & pH)</h2>
        <span class="small text-secondary">Batch <?= htmlspecialchars($b['batch_no']) ?></span>
      </div>
      <div class="p-3 bg-light rounded-bottom">
        <form method="POST" action="<?= Url::to('/dyeing/batches-edit') ?>" class="row g-2 align-items-end">
          <input type="hidden" name="action" value="log_parameter">
          <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

          <div class="col-md-3">
            <label class="form-label small fw-semibold">Process Step <span class="text-danger">*</span></label>
            <select name="process_step" class="form-select form-select-sm" required>
              <option value="Pre-treatment / Scouring">01. Pre-treatment / Scouring (95°C, pH 10.5)</option>
              <option value="Reactive Dyeing Exhaustion" selected>02. Reactive Dyeing Exhaustion (60°C, pH 7.0)</option>
              <option value="Fixation with Soda Ash">03. Fixation with Soda Ash (60°C, pH 10.8)</option>
              <option value="Hot Wash & Soaping">04. Hot Wash & Soaping (95°C, pH 7.0)</option>
              <option value="Neutralization & Softener">05. Neutralization & Softener (45°C, pH 5.5)</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Temp (°C) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="actual_temperature_c" value="60.0" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Actual pH <span class="text-danger">*</span></label>
            <input type="number" step="0.05" name="actual_ph" value="7.00" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Duration (Mins)</label>
            <input type="number" name="cycle_time_minutes" value="30" class="form-control form-control-sm font-monospace">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Operator Notes</label>
            <div class="d-flex gap-2">
              <input type="text" name="operator_notes" placeholder="e.g. Shade uniform" class="form-control form-control-sm">
              <button type="submit" class="btn btn-dark btn-sm text-nowrap"><i class="bi bi-plus-lg me-1"></i> Add Log</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Time-Series Log Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Logged At</th>
              <th>Process Step</th>
              <th>Temperature (°C)</th>
              <th>pH Reading</th>
              <th>Cycle Time</th>
              <th>Operator Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($batchParams)): ?>
              <tr>
                <td colspan="6" class="text-center py-3 text-secondary">No parameter readings recorded yet for this batch.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($batchParams as $bp): ?>
                <tr>
                  <td class="font-monospace"><?= date('d M Y, H:i', strtotime($bp['recorded_at'])) ?></td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($bp['process_step']) ?></td>
                  <td class="font-monospace fw-bold text-danger"><?= $bp['actual_temperature_c'] ?> °C</td>
                  <td class="font-monospace fw-bold text-primary"><?= $bp['actual_ph'] ?></td>
                  <td class="font-monospace"><?= $bp['cycle_time_minutes'] ?> mins</td>
                  <td class="text-secondary"><?= htmlspecialchars($bp['operator_notes'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
