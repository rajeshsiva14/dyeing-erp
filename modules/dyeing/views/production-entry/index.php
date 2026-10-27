<?php
/**
 * Dyeing Production Entry & Real-time Floor Log
 * Dedicated Register View
 * File: modules/dyeing/views/production-entry/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Live Production Floor Entry';
$activeModule = 'production-entry';

// Handle Delete Log Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_parameter') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $paramId = (int)($_POST['param_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dye_batch_parameters WHERE id = ?", [$paramId]);
    if ($target) {
        Database::execute("DELETE FROM dye_batch_parameters WHERE id = ?", [$paramId]);
        header('Location: ' . Url::to('/dyeing/production-entry?batch_id=' . $target['dye_batch_id'] . '&deleted=1'));
        exit;
    }
}

$batchId = (int)($_GET['batch_id'] ?? 0);
if ($batchId > 0) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$batchId]);
}
if (empty($batch)) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC LIMIT 1");
}

$parameters = $batch ? Database::fetchAll("SELECT * FROM dye_batch_parameters WHERE dye_batch_id = ? ORDER BY recorded_at DESC", [$batch['id']]) : [];
$allBatches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
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
            <li class="breadcrumb-item active">Production Entry</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-broadcast text-dark me-2"></i>Dyeing Production Entry & Floor Log</h1>
        <p class="text-secondary small mb-0">Record real-time temperature, pH sensors, chemical additions, cycle duration, and downtime reasons.</p>
      </div>
      <div class="d-flex gap-2">
        <?php if ($batch): ?>
          <a href="<?= Url::to('/dyeing/production-entry/create?batch_id=' . $batch['id']) ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-thermometer-half me-1"></i> Log Temperature / pH Reading
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['logged'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Sensor telemetry and operator readings recorded successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Reading updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Parameter reading removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Active Batch Switcher Bar -->
    <?php if ($batch): ?>
      <div class="cz-panel mb-4 p-3">
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
            <a href="<?= Url::to('/dyeing/process-sheets?batch_id=' . $batch['id']) ?>" class="btn btn-sm btn-outline-dark">
              <i class="bi bi-file-earmark-text me-1"></i> Process Sheet
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Real-time Parameters Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark">
          <i class="bi bi-activity text-dark me-2"></i>Time-Series Temperature & pH Sensor Log
        </h2>
        <span class="small text-secondary"><?= count($parameters) ?> Timestamped Logs for <?= htmlspecialchars($batch['batch_no'] ?? '') ?> (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="parametersTable">
          <thead class="table-light">
            <tr>
              <th>Timestamp</th>
              <th>Process Step / Stage</th>
              <th class="text-center">Target Temp</th>
              <th class="text-center">Actual Temp</th>
              <th class="text-center">Target pH</th>
              <th class="text-center">Actual pH</th>
              <th class="text-center">Hold Time</th>
              <th>Operator Notes & Observations</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($parameters)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No parameter readings recorded yet for this batch. Click "Log Temperature / pH Reading" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($parameters as $p): ?>
                <?php $editUrl = Url::to('/dyeing/production-entry/edit?id=' . $p['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this parameter log">
                  <td class="font-monospace fw-bold text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= date('H:i:s · d M', strtotime($p['recorded_at'])) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark-subtle text-dark border"><?= htmlspecialchars($p['process_step']) ?></span>
                  </td>
                  <td class="text-center font-monospace text-secondary"><?= number_format($p['set_temperature_c'], 1) ?>°C</td>
                  <td class="text-center font-monospace fw-bold text-danger fs-6"><?= number_format($p['actual_temperature_c'], 1) ?>°C</td>
                  <td class="text-center font-monospace text-secondary"><?= number_format($p['set_ph'], 2) ?></td>
                  <td class="text-center font-monospace fw-bold text-primary fs-6"><?= number_format($p['actual_ph'], 2) ?></td>
                  <td class="text-center font-monospace"><?= $p['cycle_time_minutes'] ?> mins</td>
                  <td><?= htmlspecialchars($p['operator_notes'] ?? 'Normal cycle running') ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteParam(<?= $p['id'] ?>, '<?= date('H:i', strtotime($p['recorded_at'])) ?> - <?= htmlspecialchars($p['process_step']) ?>')">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteParamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_parameter">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="param_id" id="deleteParamId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Reading</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this sensor reading?</p>
          <strong id="deleteParamName" class="text-danger small"></strong>
        </div>
        <div class="modal-footer py-2 justify-content-between">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDeleteParam(id, name) {
  document.getElementById('deleteParamId').value = id;
  document.getElementById('deleteParamName').innerText = name;
  new bootstrap.Modal(document.getElementById('deleteParamModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
