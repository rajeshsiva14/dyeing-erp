<?php
/**
 * Dedicated Standalone Form: Update / Reschedule Dyeing Batch Plan
 * File: modules/dyeing/views/batch-planning/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Dyeing Batch Plan';
$activeModule = 'batch-planning';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ? AND company_id = ?", [$id, $companyId]);

if (!$batch) {
    header('Location: ' . Url::to('/dyeing/batch-planning'));
    exit;
}

// Handle Update Batch Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $machineId = (int)($_POST['machine_id'] ?? $batch['machine_id']);
    $recipeId = (int)($_POST['recipe_id'] ?? $batch['dye_recipe_id']);
    $plannedKg = (float)($_POST['planned_weight_kg'] ?? $batch['planned_weight_kg']);
    $liquorRatio = trim($_POST['liquor_ratio'] ?? $batch['liquor_ratio']);
    $operatorName = trim($_POST['operator_name'] ?? $batch['operator_name']);
    $status = trim($_POST['status'] ?? $batch['status']);

    $ratioNum = 6.0;
    if (preg_match('/1:([0-9\.]+)/', $liquorRatio, $m)) {
        $ratioNum = (float)$m[1];
    }
    $waterVolume = $plannedKg * $ratioNum;

    Database::beginTransaction();
    try {
        Database::query("
            UPDATE dye_batches
            SET machine_id = ?, dye_recipe_id = ?, planned_weight_kg = ?, liquor_ratio = ?, water_volume_litres = ?, operator_name = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ", [$machineId, $recipeId, $plannedKg, $liquorRatio, $waterVolume, $operatorName, $status, $id, $companyId]);

        Database::commit();
        header('Location: ' . Url::to('/dyeing/batch-planning?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update batch plan: ' . $e->getMessage();
    }
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY capacity DESC");
$recipes = Database::fetchAll("SELECT * FROM dye_recipes WHERE is_approved = 1 ORDER BY shade_name ASC");

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Dyeing Batch Allocation</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($batch['batch_no']) ?></span>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($batch['status']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Adjust machine vessel capacity, liquor ratio formula, and operator assignment.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editBatchForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Changes
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

    <form id="editBatchForm" method="POST" action="<?= Url::to('/dyeing/batch-planning/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_batch">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2 text-primary"></i>Batch Machine & Recipe Parameters</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Dyeing Machine / Vessel *</label>
                  <select name="machine_id" class="form-select form-select-sm" required>
                    <?php foreach ($machines as $m): ?>
                      <option value="<?= $m['id'] ?>" <?= ($m['id'] == $batch['machine_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (<?= $m['capacity'] ?> Kg, LR <?= $m['liquor_ratio'] ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Recipe Formulation *</label>
                  <select name="recipe_id" class="form-select form-select-sm" required>
                    <?php foreach ($recipes as $r): ?>
                      <option value="<?= $r['id'] ?>" <?= ($r['id'] == $batch['dye_recipe_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['recipe_code']) ?> — <?= htmlspecialchars($r['shade_name']) ?> (<?= htmlspecialchars($r['shade_code']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Operator In-Charge</label>
                  <input type="text" name="operator_name" class="form-control form-control-sm" value="<?= htmlspecialchars($batch['operator_name']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Workflow Status</label>
                  <select name="status" class="form-select form-select-sm">
                    <option value="planned" <?= ($batch['status'] === 'planned') ? 'selected' : '' ?>>Planned / Scheduled</option>
                    <option value="in_progress" <?= ($batch['status'] === 'in_progress') ? 'selected' : '' ?>>In Progress / Dyeing</option>
                    <option value="completed" <?= ($batch['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="on_hold" <?= ($batch['status'] === 'on_hold') ? 'selected' : '' ?>>On Hold / Paused</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-droplet-half me-2 text-primary"></i>Bath Volume & MLR</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Planned Weight (Kg) *</label>
                <input type="number" step="0.1" name="planned_weight_kg" id="bpPlannedKg" class="form-control form-control-sm font-monospace fw-bold" required value="<?= htmlspecialchars($batch['planned_weight_kg']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Liquor Ratio (MLR)</label>
                <select name="liquor_ratio" id="bpLiquorRatio" class="form-select form-select-sm font-monospace">
                  <option value="1:5" <?= ($batch['liquor_ratio'] === '1:5') ? 'selected' : '' ?>>1:5 (Ultra-low eco MLR)</option>
                  <option value="1:6" <?= ($batch['liquor_ratio'] === '1:6' || empty($batch['liquor_ratio'])) ? 'selected' : '' ?>>1:6 (Standard Soft-flow)</option>
                  <option value="1:8" <?= ($batch['liquor_ratio'] === '1:8') ? 'selected' : '' ?>>1:8 (High liquor blend)</option>
                  <option value="1:10" <?= ($batch['liquor_ratio'] === '1:10') ? 'selected' : '' ?>>1:10 (Winch / Jet)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Total Bath Volume (Litres)</label>
                <input type="text" id="bpWaterVolume" class="form-control form-control-sm bg-light font-monospace fw-bold text-end" readonly value="<?= number_format($batch['water_volume_litres'], 0) ?>">
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function updateBathCalc() {
  const kg = parseFloat(document.getElementById('bpPlannedKg')?.value) || 0;
  const ratioStr = document.getElementById('bpLiquorRatio')?.value || '1:6';
  let ratioNum = 6.0;
  const m = ratioStr.match(/1:([0-9\.]+)/);
  if (m) ratioNum = parseFloat(m[1]);
  const vol = Math.round(kg * ratioNum);
  const el = document.getElementById('bpWaterVolume');
  if (el) el.value = vol;
}
document.getElementById('bpPlannedKg')?.addEventListener('input', updateBathCalc);
document.getElementById('bpLiquorRatio')?.addEventListener('change', updateBathCalc);
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
