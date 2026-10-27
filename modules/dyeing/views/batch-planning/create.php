<?php
/**
 * Dedicated Standalone Form: Create Production Dyeing Batch
 * File: modules/dyeing/views/batch-planning/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Create Production Dyeing Batch';
$activeModule = 'batch-planning';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle POST to create batch from planned jobs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchNo = 'BATCH-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $jobCardId = (int)($_POST['job_card_id'] ?? 1);
    $recipeId = (int)($_POST['recipe_id'] ?? 1);
    $machineId = (int)($_POST['machine_id'] ?? 1);
    $plannedKg = (float)($_POST['planned_weight_kg'] ?? 500);
    $loadedKg = $plannedKg;
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');
    
    // Parse ratio to calculate bath volume
    $ratioNum = 6.0;
    if (preg_match('/1:([0-9\.]+)/', $liquorRatio, $m)) {
        $ratioNum = (float)$m[1];
    }
    $waterVolume = $plannedKg * $ratioNum;

    Database::beginTransaction();
    try {
        Database::insert('dye_batches', [
            'company_id'          => $companyId,
            'batch_no'            => $batchNo,
            'production_order_id' => $jobCardId,
            'dye_recipe_id'       => $recipeId,
            'machine_id'          => $machineId,
            'planned_weight_kg'   => $plannedKg,
            'actual_loaded_kg'    => $loadedKg,
            'water_volume_litres' => $waterVolume,
            'liquor_ratio'        => $liquorRatio,
            'operator_name'       => trim($_POST['operator_name'] ?? 'M. Ramanathan'),
            'status'              => 'planned'
        ]);

        Database::getConnection()->prepare("UPDATE dyeing_job_cards SET status = 'planned' WHERE id = ?")->execute([$jobCardId]);
        Database::commit();

        header('Location: ' . Url::to('/dyeing/batches?created=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to create batch: ' . $e->getMessage();
    }
}

$presetJobCardId = (int)($_GET['job_card_id'] ?? 0);
$jobCards = Database::fetchAll("SELECT jc.*, c.name as customer_name FROM dyeing_job_cards jc JOIN customers c ON jc.customer_id = c.id WHERE jc.status IN ('approved', 'planned') ORDER BY jc.id DESC");
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
            <h1 class="h5 fw-bold text-dark mb-0">Create Production Dyeing Batch</h1>
            <span class="badge badge-bw">NEW BATCH</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Combine customer orders, verify vessel load capacity, liquor ratio, and calculate total bath volume.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newBatchForm" class="btn btn-dark btn-sm">
          <i class="bi bi-calendar-check me-1"></i> Save Batch & Allocate Machine
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

    <form id="newBatchForm" method="POST" action="<?= Url::to('/dyeing/batch-planning/create') ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="create_batch">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2 text-primary"></i>Batch Order & Vessel Allocation</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Job Card / Customer Order *</label>
                  <select name="job_card_id" id="bpJobSelect" class="form-select form-select-sm" required>
                    <option value="">-- Select Approved Job Card --</option>
                    <?php foreach ($jobCards as $jc): ?>
                      <option value="<?= $jc['id'] ?>" data-qty="<?= $jc['required_qty_kg'] ?>" <?= ($jc['id'] == $presetJobCardId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($jc['job_card_no']) ?> (<?= htmlspecialchars($jc['customer_name']) ?> · <?= number_format($jc['required_qty_kg'], 1) ?> Kg)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Dyeing Machine / Vessel *</label>
                  <select name="machine_id" class="form-select form-select-sm" required>
                    <?php foreach ($machines as $m): ?>
                      <option value="<?= $m['id'] ?>">
                        <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (<?= $m['capacity'] ?> Kg, LR <?= $m['liquor_ratio'] ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Recipe Formulation *</label>
                  <select name="recipe_id" class="form-select form-select-sm" required>
                    <?php foreach ($recipes as $r): ?>
                      <option value="<?= $r['id'] ?>">
                        <?= htmlspecialchars($r['recipe_code']) ?> — <?= htmlspecialchars($r['shade_name']) ?> (<?= htmlspecialchars($r['shade_code']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Operator In-Charge</label>
                  <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Ramanathan (Lead Master)">
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
                <input type="number" step="0.1" name="planned_weight_kg" id="bpPlannedKg" class="form-control form-control-sm font-monospace fw-bold" required value="500.0">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Liquor Ratio (MLR)</label>
                <select name="liquor_ratio" id="bpLiquorRatio" class="form-select form-select-sm font-monospace">
                  <option value="1:5">1:5 (Ultra-low eco MLR)</option>
                  <option value="1:6" selected>1:6 (Standard Soft-flow)</option>
                  <option value="1:8">1:8 (High liquor blend)</option>
                  <option value="1:10">1:10 (Winch / Jet)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Total Bath Volume (Litres)</label>
                <input type="text" id="bpWaterVolume" class="form-control form-control-sm bg-light font-monospace fw-bold text-end" readonly value="3000">
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
document.getElementById('bpJobSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const qty = opt?.getAttribute('data-qty');
  if (qty) {
    const kgEl = document.getElementById('bpPlannedKg');
    if (kgEl) {
      kgEl.value = parseFloat(qty).toFixed(2);
      updateBathCalc();
    }
  }
});
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
