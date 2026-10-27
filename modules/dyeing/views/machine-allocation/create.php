<?php
/**
 * Dedicated Standalone Form: Allocate Machine & Vessel Schedule
 * File: modules/dyeing/views/machine-allocation/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Allocate Machine & Vessel Schedule';
$activeModule = 'machine-allocation';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle POST to allocate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'allocate_machine') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $machineId = (int)($_POST['machine_id'] ?? 0);
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $operatorName = trim($_POST['operator_name'] ?? 'M. Ramanathan');
    $status = trim($_POST['machine_status'] ?? 'running');

    if ($machineId <= 0) $errors[] = 'Please select a Machine / Vessel.';
    if ($batchId <= 0) $errors[] = 'Please select an Active or Planned Batch.';

    if (!$errors) {
        Database::beginTransaction();
        try {
            Database::getConnection()->prepare("UPDATE machines SET current_status = ? WHERE id = ?")->execute([$status, $machineId]);
            Database::getConnection()->prepare("UPDATE dye_batches SET machine_id = ?, operator_name = ?, status = 'in_progress', updated_at = NOW() WHERE id = ? AND company_id = ?")->execute([$machineId, $operatorName, $batchId, $companyId]);
            Database::commit();

            header('Location: ' . Url::to('/dyeing/machine-allocation?allocated=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Failed to allocate machine: ' . $e->getMessage();
        }
    }
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('soft_flow_dyeing', 'hydro_extractor', 'relax_dryer', 'stenter', 'compactor') ORDER BY process_type ASC, code ASC");
$batches = Database::fetchAll("SELECT b.*, r.shade_name, r.recipe_code FROM dye_batches b LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id WHERE b.company_id = ? AND b.status IN ('planned', 'in_progress') ORDER BY b.id DESC", [$companyId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/machine-allocation') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Allocate Vessel / Machine Schedule</h1>
            <span class="badge badge-bw">NEW ALLOCATION</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Assign active batch to vessel, verify wash cycle clearance, and set operating status.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/machine-allocation') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="allocMachineForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Confirm Vessel Allocation
        </button>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form id="allocMachineForm" method="POST" action="<?= Url::to('/dyeing/machine-allocation/create') ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="allocate_machine">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cpu me-2 text-primary"></i>Select Machine & Production Batch</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Machine / Vessel *</label>
                  <select name="machine_id" class="form-select form-select-sm" required>
                    <option value="">-- Choose Machine --</option>
                    <?php foreach ($machines as $m): ?>
                      <option value="<?= $m['id'] ?>">
                        <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (<?= $m['capacity'] ?> Kg, <?= strtoupper($m['current_status'] ?? 'idle') ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Dyeing Batch *</label>
                  <select name="batch_id" class="form-select form-select-sm" required>
                    <option value="">-- Select Production Batch --</option>
                    <?php foreach ($batches as $b): ?>
                      <option value="<?= $b['id'] ?>">
                        <?= htmlspecialchars($b['batch_no']) ?> (<?= htmlspecialchars($b['shade_name'] ?? 'N/A') ?> · <?= number_format($b['planned_weight_kg'], 1) ?> Kg)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Floor Operator Assigned</label>
                  <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Ramanathan (Vessel Lead)">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">New Machine Status</label>
                  <select name="machine_status" class="form-select form-select-sm">
                    <option value="running" selected>Running / Active Batch</option>
                    <option value="idle">Idle / Prepared</option>
                    <option value="maintenance">Maintenance / Wash Down</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-shield-check me-2 text-primary"></i>Cleaning & Clearance Checklist</h2>
            </div>
            <div class="p-3 small text-secondary">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="chkFilter" checked>
                <label class="form-check-label text-dark fw-semibold" for="chkFilter">Filter basket & lint trap cleaned</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="chkRinse" checked>
                <label class="form-check-label text-dark fw-semibold" for="chkRinse">Caustic rinse done after previous dark shade</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="chkPH" checked>
                <label class="form-check-label text-dark fw-semibold" for="chkPH">Incoming water TDS & pH verified (pH 6.5–7.0)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="chkSeals" checked>
                <label class="form-check-label text-dark fw-semibold" for="chkSeals">Vessel door seal & pressure gauge locked</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
