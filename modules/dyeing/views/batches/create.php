<?php
/**
 * Dedicated Standalone Form: Load New Dyeing Batch into Vessel
 * File: modules/dyeing/views/batches-create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Load New Dyeing Batch';
$activeModule = 'batches';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$errors = [];

// Handle POST to load new batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $recipeId = (int)($_POST['dye_recipe_id'] ?? 0);
    $machineId = (int)($_POST['machine_id'] ?? 0);
    $plannedWeight = (float)($_POST['planned_weight_kg'] ?? 0);
    $actualLoaded = (float)($_POST['actual_loaded_kg'] ?? $plannedWeight);
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');
    $operatorName = trim($_POST['operator_name'] ?? '');
    $status = $_POST['status'] ?? 'loaded';

    if ($recipeId <= 0) $errors[] = 'Please select a Dye Recipe / Shade standard.';
    if ($machineId <= 0) $errors[] = 'Please select an allocated Dyeing Vessel.';
    if ($plannedWeight <= 0) $errors[] = 'Planned weight (Kg) must be greater than 0.';

    if (!$errors) {
        $batchNo = 'BAT-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
        
        // Calculate water volume based on liquor ratio (e.g. 1:6 -> weight * 6)
        $ratioNum = 6;
        if (preg_match('/1:(\d+(\.\d+)?)/', $liquorRatio, $m)) {
            $ratioNum = (float)$m[1];
        }
        $waterVolume = round($actualLoaded * $ratioNum, 2);

        Database::insert('dye_batches', [
            'company_id'          => $companyId,
            'batch_no'            => $batchNo,
            'dye_recipe_id'       => $recipeId,
            'machine_id'          => $machineId,
            'planned_weight_kg'   => $plannedWeight,
            'actual_loaded_kg'    => $actualLoaded,
            'output_weight_kg'    => 0.00,
            'water_volume_litres' => $waterVolume,
            'liquor_ratio'        => $liquorRatio,
            'operator_name'       => $operatorName,
            'process_start_time'  => date('Y-m-d H:i:s'),
            'status'              => $status,
        ]);

        // Update machine status
        Database::execute("UPDATE machines SET current_status = 'running' WHERE id = ? AND company_id = ?", [$machineId, $companyId]);

        header('Location: ' . Url::to('/dyeing/batches?success=1'));
        exit;
    }
}

$recipes = Database::fetchAll("SELECT * FROM dye_recipes WHERE company_id = ? AND is_approved = 1 ORDER BY shade_name ASC", [$companyId]);
$machines = Database::fetchAll("SELECT * FROM machines WHERE company_id = ? AND process_type = 'soft_flow_dyeing' ORDER BY code ASC", [$companyId]);

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
            <h1 class="h5 fw-bold text-dark mb-0">Load New Dyeing Batch</h1>
            <span class="badge badge-bw">NEW VESSEL LOAD</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Dedicated form to bind approved recipe, assign high-temperature soft-flow vessel, and calculate bath water volume.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newBatchForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Release & Load Vessel
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="newBatchForm" method="POST" action="<?= Url::to('/dyeing/batches-create') ?>">
      <input type="hidden" name="action" value="save_batch">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Recipe & Vessel Assignment -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-droplet-half"></i> Section A — Recipe Standard & Vessel Assignment</h2>
            <p class="cz-doc-section-desc">Select certified dyeing recipe formulation and allocate soft-flow machine.</p>
          </div>
          <span class="badge badge-bw font-monospace">VESSEL ALLOCATION</span>
        </div>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Approved Dye Recipe / Shade Standard <span class="text-danger">*</span></label>
            <select name="dye_recipe_id" class="form-select form-select-sm" required>
              <option value="">-- Select Recipe / Shade --</option>
              <?php foreach ($recipes as $r): ?>
                <option value="<?= $r['id'] ?>">
                  <?= htmlspecialchars($r['shade_name']) ?> (<?= htmlspecialchars($r['recipe_code']) ?> &bull; <?= htmlspecialchars($r['dye_class'] ?? 'Reactive') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Dyeing Vessel / Machine <span class="text-danger">*</span></label>
            <select name="machine_id" id="vesselSelect" class="form-select form-select-sm" required>
              <option value="">-- Select Soft Flow Vessel --</option>
              <?php foreach ($machines as $m): ?>
                <option value="<?= $m['id'] ?>" data-cap="<?= $m['capacity'] ?>" data-lr="<?= htmlspecialchars($m['liquor_ratio'] ?? '1:6') ?>">
                  <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (Cap: <?= number_format($m['capacity'], 0) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Section B: Weighment & Liquor Ratio -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-speedometer2"></i> Section B — Batch Weighment & Bath Volume</h2>
            <p class="cz-doc-section-desc">Loaded fabric weight, liquor ratio, and automated water bath volume calculation.</p>
          </div>
          <span class="badge badge-bw font-monospace">BATH SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Planned Weight (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="planned_weight_kg" id="plannedWt" class="form-control form-control-sm font-monospace fw-bold" value="500.0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Actual Loaded Weight (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="actual_loaded_kg" id="actualWt" class="form-control form-control-sm font-monospace fw-bold" value="500.0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Liquor Ratio (L:R)</label>
            <input type="text" name="liquor_ratio" id="liquorRatio" class="form-control form-control-sm font-monospace" value="1:6" placeholder="1:6">
          </div>
          <div class="col-md-3">
            <label class="form-label">Estimated Bath Water (Litres)</label>
            <input type="text" id="waterLitres" class="form-control form-control-sm font-monospace fw-bold bg-light" readonly value="3,000 L">
          </div>
          <div class="col-md-6">
            <label class="form-label">Shift In-charge / Operator Name</label>
            <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Kandasamy (Senior Vessel Operator)">
          </div>
          <div class="col-md-6">
            <label class="form-label">Initial Floor Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="loaded">Loaded & Sealed</option>
              <option value="dosing">Chemical / Auxiliary Dosing</option>
              <option value="dyeing">Main Dyeing Cycle</option>
              <option value="planned">Planned (Standby)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Release & Load Vessel
        </button>
      </div>
    </form>

    <script>
    (function() {
      function calcWater() {
        const wt = parseFloat(document.getElementById('actualWt')?.value) || 0;
        const lrStr = document.getElementById('liquorRatio')?.value || '1:6';
        let ratio = 6;
        const m = lrStr.match(/1:(\d+(\.\d+)?)/);
        if (m) ratio = parseFloat(m[1]) || 6;
        const litres = wt * ratio;
        document.getElementById('waterLitres').value = litres.toLocaleString('en-IN', {maximumFractionDigits: 1}) + ' L';
      }
      document.getElementById('actualWt')?.addEventListener('input', calcWater);
      document.getElementById('liquorRatio')?.addEventListener('input', calcWater);
      document.getElementById('vesselSelect')?.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt.dataset.lr) {
          document.getElementById('liquorRatio').value = opt.dataset.lr;
          calcWater();
        }
      });
      calcWater();
    })();
    </script>
  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
