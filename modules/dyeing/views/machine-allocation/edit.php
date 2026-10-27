<?php
/**
 * Dedicated Standalone Form: Update Machine Allocation & Floor Status
 * File: modules/dyeing/views/machine-allocation/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Machine Allocation';
$activeModule = 'machine-allocation';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$machine = Database::fetch("SELECT * FROM machines WHERE id = ?", [$id]);

if (!$machine) {
    header('Location: ' . Url::to('/dyeing/machine-allocation'));
    exit;
}

// Handle Update Machine Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_machine') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $status = trim($_POST['current_status'] ?? $machine['current_status']);
    $cost = (float)($_POST['hourly_operating_cost'] ?? $machine['hourly_operating_cost']);
    $notes = trim($_POST['maintenance_notes'] ?? '');

    Database::beginTransaction();
    try {
        Database::getConnection()->prepare("UPDATE machines SET current_status = ?, hourly_operating_cost = ? WHERE id = ?")->execute([$status, $cost, $id]);
        Database::commit();

        header('Location: ' . Url::to('/dyeing/machine-allocation?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update machine: ' . $e->getMessage();
    }
}

$allocatedBatches = Database::fetchAll("
    SELECT b.*, r.shade_name, r.recipe_code
    FROM dye_batches b
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    WHERE b.machine_id = ? AND b.status NOT IN ('completed')
    ORDER BY b.id DESC
", [$id]);

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
            <h1 class="h5 fw-bold text-dark mb-0">Update Machine Vessel Parameters</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($machine['code']) ?></span>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($machine['current_status']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1"><?= htmlspecialchars($machine['name']) ?> · Rated Capacity: <?= number_format($machine['capacity'], 0) ?> Kg · MLR: <?= htmlspecialchars($machine['liquor_ratio'] ?? '1:6') ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/machine-allocation') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editMachineForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Vessel Status
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

    <form id="editMachineForm" method="POST" action="<?= Url::to('/dyeing/machine-allocation/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_machine">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4 mb-4">
        <div class="col-lg-7">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Operating State & Floor Clearance</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Current Operating Status</label>
                  <select name="current_status" class="form-select form-select-sm">
                    <option value="running" <?= ($machine['current_status'] === 'running') ? 'selected' : '' ?>>Running (Processing Batch)</option>
                    <option value="idle" <?= ($machine['current_status'] === 'idle') ? 'selected' : '' ?>>Idle / Available for Loading</option>
                    <option value="maintenance" <?= ($machine['current_status'] === 'maintenance') ? 'selected' : '' ?>>Under Maintenance / Washdown</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Hourly Operating Cost (₹/hr)</label>
                  <input type="number" step="1" name="hourly_operating_cost" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($machine['hourly_operating_cost']) ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small text-secondary fw-semibold">Floor Maintenance / Wash Log Notes</label>
                  <textarea name="maintenance_notes" class="form-control form-control-sm" rows="3" placeholder="Enter pump inspection, nozzle cleaning, or vessel wash remarks..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-list-task me-2 text-primary"></i>Active Batches in Vessel</h2>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Batch</th>
                    <th>Shade</th>
                    <th class="text-end">Weight</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($allocatedBatches)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-3 text-secondary">No active batches running in this vessel.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($allocatedBatches as $ab): ?>
                      <tr>
                        <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($ab['batch_no']) ?></td>
                        <td><?= htmlspecialchars($ab['shade_name'] ?? 'N/A') ?></td>
                        <td class="text-end font-monospace"><?= number_format($ab['actual_loaded_kg'] ?: $ab['planned_weight_kg'], 1) ?> kg</td>
                        <td><span class="badge bg-dark text-uppercase"><?= htmlspecialchars($ab['status']) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
