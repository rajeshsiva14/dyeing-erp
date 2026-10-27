<?php
/**
 * Dyeing Production Entry & Real-time Floor Log
 * Shift-wise time-series temperature & pH logging, chemical dosing, downtime and alarms
 * File: modules/dyeing/views/production-entry.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Live Production Floor Entry';
$activeModule = 'production-entry';

// Handle adding new real-time parameter reading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_parameter') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $step = trim($_POST['process_step'] ?? 'Reactive Dyeing Hold');
    $tempSet = (float)($_POST['set_temperature_c'] ?? 60.0);
    $tempAct = (float)($_POST['actual_temperature_c'] ?? 60.2);
    $phSet = (float)($_POST['set_ph'] ?? 11.2);
    $phAct = (float)($_POST['actual_ph'] ?? 11.15);
    $cycleMins = (int)($_POST['cycle_time_minutes'] ?? 30);
    $notes = trim($_POST['operator_notes'] ?? '');

    Database::insert('dye_batch_parameters', [
        'dye_batch_id'         => $batchId,
        'process_step'         => $step,
        'set_temperature_c'    => $tempSet,
        'actual_temperature_c' => $tempAct,
        'set_ph'               => $phSet,
        'actual_ph'            => $phAct,
        'cycle_time_minutes'   => $cycleMins,
        'recorded_at'          => date('Y-m-d H:i:s'),
        'operator_notes'       => $notes
    ]);

    header('Location: ' . Url::to('/dyeing/production-entry?batch_id=' . $batchId . '&logged=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$batchId = (int)($_GET['batch_id'] ?? 1);
$batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$batchId]);
if (!$batch) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id LIMIT 1");
}

$parameters = Database::fetchAll("SELECT * FROM dye_batch_parameters WHERE dye_batch_id = ? ORDER BY recorded_at DESC", [$batch['id']]);
$allBatches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Production Entry</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-broadcast text-primary me-2"></i>Dyeing Production Entry & Floor Log</h1>
        <p class="text-secondary small mb-0">Record real-time temperature, pH sensors, chemical additions, cycle duration, and downtime reasons.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newLogModal">
          <i class="bi bi-thermometer-half me-1"></i> Log Temperature / pH Reading
        </button>
      </div>
    </div>

    <!-- Active Batch Switcher Bar -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center g-3">
          <div class="col-md-4">
            <label class="small text-secondary fw-bold">Active Batch Floor Monitor:</label>
            <select class="form-select form-select-sm" onchange="location.href='?batch_id='+this.value">
              <?php foreach ($allBatches as $ab): ?>
                <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == ($batch['id'] ?? 0)) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= number_format($ab['actual_loaded_kg'], 0) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small">Vessel & Liquor Ratio</div>
            <div class="fw-bold text-dark"><?= htmlspecialchars($batch['machine_code']) ?> · LR: <?= htmlspecialchars($batch['liquor_ratio']) ?> (<?= number_format($batch['water_volume_litres'], 0) ?> L)</div>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small">Current Process Status</div>
            <span class="badge bg-primary text-uppercase px-3 py-1"><?= htmlspecialchars($batch['status']) ?></span>
          </div>
          <div class="col-md-2 text-md-end">
            <a href="<?= Url::to('/dyeing/shade-checks?batch_id=' . $batch['id']) ?>" class="btn btn-sm btn-outline-dark">
              <i class="bi bi-palette me-1"></i> Shade Check &rarr;
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Real-time Temperature & pH Readings Timeline -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Time-Series Process Parameter Logs</h2>
        <span class="small text-secondary"><?= count($parameters) ?> Timestamped Readings</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Timestamp</th>
              <th>Process Step</th>
              <th class="text-center">Set Temp</th>
              <th class="text-center">Actual Temp</th>
              <th class="text-center">Set pH</th>
              <th class="text-center">Actual pH</th>
              <th class="text-center">Duration</th>
              <th>Operator Notes & Alarms</th>
              <th class="text-end">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($parameters as $p): ?>
              <?php 
                $tempDev = abs($p['actual_temperature_c'] - $p['set_temperature_c']);
                $phDev = abs($p['actual_ph'] - $p['set_ph']);
              ?>
              <tr>
                <td class="font-monospace fw-semibold text-secondary">
                  <?= date('d M H:i', strtotime($p['recorded_at'])) ?>
                </td>
                <td class="fw-bold text-dark"><?= htmlspecialchars($p['process_step']) ?></td>
                <td class="text-center font-monospace"><?= $p['set_temperature_c'] ?>°C</td>
                <td class="text-center font-monospace fw-bold <?= ($tempDev > 2.0) ? 'text-danger' : 'text-success' ?>">
                  <?= $p['actual_temperature_c'] ?>°C
                </td>
                <td class="text-center font-monospace">pH <?= $p['set_ph'] ?></td>
                <td class="text-center font-monospace fw-bold <?= ($phDev > 0.5) ? 'text-danger' : 'text-primary' ?>">
                  pH <?= $p['actual_ph'] ?>
                </td>
                <td class="text-center font-monospace"><?= $p['cycle_time_minutes'] ?> Mins</td>
                <td><?= htmlspecialchars($p['operator_notes'] ?: 'Normal circulation, pump pressure 2.8 bar') ?></td>
                <td class="text-end">
                  <?php if ($tempDev <= 2.0 && $phDev <= 0.5): ?>
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>In Limit</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Deviation</span>
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

<!-- New Floor Parameter Modal -->
<div class="modal fade" id="newLogModal" tabindex="-1" aria-labelledby="newLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="log_parameter">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <input type="hidden" name="dye_batch_id" value="<?= $batch['id'] ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newLogModalLabel"><i class="bi bi-thermometer-half text-primary me-2"></i>Record Process Parameter Reading</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Current Process Step *</label>
              <select name="process_step" class="form-select form-select-sm" required>
                <option value="Pre-treatment & Bio-Polishing">Pre-treatment & Bio-Polishing (60°C)</option>
                <option value="Bleaching & Caustic Scouring">Bleaching & Caustic Scouring (95°C)</option>
                <option value="Peroxide Neutralization & Rinse">Peroxide Neutralization & Rinse (60°C)</option>
                <option value="Reactive Dye Addition & Salt Migration">Reactive Dye Addition & Salt Migration (60°C)</option>
                <option value="Soda Ash Alkali Fixation Hold" selected>Soda Ash Alkali Fixation Hold (60°C)</option>
                <option value="Hot Wash & Neutralization">Hot Wash & Neutralization (95°C)</option>
                <option value="Cationic Softener Application">Cationic Softener Application (40°C)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Set Temp (°C) *</label>
              <input type="number" step="0.1" name="set_temperature_c" class="form-control form-control-sm" value="60.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-success">Actual Measured Temp (°C) *</label>
              <input type="number" step="0.1" name="actual_temperature_c" class="form-control form-control-sm fw-bold" value="60.2" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Set pH *</label>
              <input type="number" step="0.1" name="set_ph" class="form-control form-control-sm" value="11.2" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-primary">Actual Measured pH *</label>
              <input type="number" step="0.05" name="actual_ph" class="form-control form-control-sm fw-bold" value="11.15" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Cycle Duration (Minutes) *</label>
              <input type="number" name="cycle_time_minutes" class="form-control form-control-sm" value="30" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nozzle Pressure (Bar)</label>
              <input type="text" class="form-control form-control-sm" value="2.8 Bar">
            </div>
            <div class="col-12">
              <label class="form-label">Operator Observations / Alarms</label>
              <input type="text" name="operator_notes" class="form-control form-control-sm" placeholder="e.g. Pump running smooth, dyestuff dissolving uniform, no foam build-up">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Log Reading</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
