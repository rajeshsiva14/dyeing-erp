<?php
/**
 * Dedicated Standalone Form: Update Recipe Dosing Parameters
 * File: modules/dyeing/views/recipe-calculation/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Recipe Calculation';
$activeModule = 'recipe-calculation';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

$id = (int)($_GET['id'] ?? ($_GET['batch_id'] ?? ($_POST['id'] ?? 0)));
$batch = Database::fetch("
    SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.name as machine_name, m.code as machine_code
    FROM dye_batches b
    JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN machines m ON b.machine_id = m.id
    WHERE b.id = ? AND b.company_id = ?
", [$id, $companyId]);

if (!$batch) {
    header('Location: ' . Url::to('/dyeing/recipe-calculation'));
    exit;
}

// Handle Update Calculation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_calculation') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $fabricKg = (float)($_POST['fabric_weight_kg'] ?? 0);
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');

    if ($fabricKg <= 0) $errors[] = 'Fabric weight (Kg) must be greater than 0.';

    if (!$errors) {
        $ratioNum = 6.0;
        if (preg_match('/1:([0-9\.]+)/', $liquorRatio, $m)) {
            $ratioNum = (float)$m[1];
        }
        $waterVol = $fabricKg * $ratioNum;

        Database::query("
            UPDATE dye_batches
            SET planned_weight_kg = ?, actual_loaded_kg = ?, liquor_ratio = ?, water_volume_litres = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ", [$fabricKg, $fabricKg, $liquorRatio, $waterVol, $id, $companyId]);

        header('Location: ' . Url::to('/dyeing/recipe-calculation?batch_id=' . $id . '&updated=1'));
        exit;
    }
}

$recipeItems = Database::fetchAll("
    SELECT ri.*, p.name as chem_name, p.sku as chem_sku,
           COALESCE((SELECT SUM(quantity_base_uom) FROM inventory_movements WHERE product_id = p.id), 250.0) as available_stock
    FROM dye_recipe_items ri
    JOIN products p ON ri.chemical_product_id = p.id
    WHERE ri.dye_recipe_id = ?
    ORDER BY ri.process_stage ASC
", [$batch['dye_recipe_id']]);

$fabricKg = (float)($batch['actual_loaded_kg'] ?: $batch['planned_weight_kg']);
$lrRatio = 6.0;
if (preg_match('/1:([0-9\.]+)/', $batch['liquor_ratio'] ?? '1:6', $m)) {
    $lrRatio = (float)$m[1];
}
$bathVolume = $fabricKg * $lrRatio;

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/recipe-calculation') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Dosing Formulation Parameters</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($batch['batch_no']) ?></span>
            <span class="badge bg-primary"><?= htmlspecialchars($batch['shade_name']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1"><?= htmlspecialchars($batch['recipe_code']) ?> · Vessel: <?= htmlspecialchars($batch['machine_name']) ?> (<?= htmlspecialchars($batch['machine_code']) ?>)</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/recipe-calculation') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <a href="<?= Url::to('/dyeing/chemical-requests/create?dye_batch_id=' . $batch['id']) ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-cart-plus me-1"></i> Issue Chemical Request
        </a>
        <button type="submit" form="editCalcForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Formulation
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

    <form id="editCalcForm" method="POST" action="<?= Url::to('/dyeing/recipe-calculation/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_calculation">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4 mb-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-list me-2 text-primary"></i>Computed Chemical & Dye Quantities</h2>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Stage</th>
                    <th>Material / Chemical</th>
                    <th class="text-center">Dosage Unit</th>
                    <th class="text-end">Dosage Value</th>
                    <th class="text-end">Computed (Kg)</th>
                    <th class="text-end">Available Stock</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recipeItems as $ri): ?>
                    <?php 
                      $dosage = (float)$ri['dosage_value'];
                      if ($ri['dosage_unit'] === '% OWF') {
                          $neededKg = ($fabricKg * $dosage) / 100.0;
                      } else {
                          $neededKg = ($bathVolume * $dosage) / 1000.0;
                      }
                    ?>
                    <tr>
                      <td><span class="badge bg-secondary"><?= strtoupper($ri['process_stage'] ?? 'DYEING') ?></span></td>
                      <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($ri['chem_name']) ?></div>
                        <code class="text-secondary"><?= htmlspecialchars($ri['chem_sku']) ?></code>
                      </td>
                      <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($ri['dosage_unit']) ?></span></td>
                      <td class="text-end font-monospace"><?= number_format($dosage, 2) ?></td>
                      <td class="text-end font-monospace fw-bold text-primary"><?= number_format($neededKg, 3) ?> kg</td>
                      <td class="text-end font-monospace text-muted"><?= number_format($ri['available_stock'], 1) ?> kg</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Batch Inputs</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Fabric Loaded (Kg) *</label>
                <input type="number" step="0.1" min="1" name="fabric_weight_kg" id="calcFabricKg" class="form-control form-control-sm font-monospace fw-bold" required value="<?= htmlspecialchars($fabricKg) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Liquor Ratio (MLR)</label>
                <select name="liquor_ratio" id="calcLr" class="form-select form-select-sm font-monospace">
                  <option value="1:5" <?= ($batch['liquor_ratio'] === '1:5') ? 'selected' : '' ?>>1:5 (Eco Jet)</option>
                  <option value="1:6" <?= ($batch['liquor_ratio'] === '1:6' || empty($batch['liquor_ratio'])) ? 'selected' : '' ?>>1:6 (Standard Soft-flow)</option>
                  <option value="1:8" <?= ($batch['liquor_ratio'] === '1:8') ? 'selected' : '' ?>>1:8 (High Bath Ratio)</option>
                  <option value="1:10" <?= ($batch['liquor_ratio'] === '1:10') ? 'selected' : '' ?>>1:10 (Winch)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Calculated Bath Volume</label>
                <input type="text" id="calcBathVol" class="form-control form-control-sm bg-light font-monospace fw-bold text-end" readonly value="<?= number_format($bathVolume, 0) ?> L">
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function updateCalc() {
  const kg = parseFloat(document.getElementById('calcFabricKg')?.value) || 0;
  const lrStr = document.getElementById('calcLr')?.value || '1:6';
  let ratio = 6.0;
  const m = lrStr.match(/1:([0-9\.]+)/);
  if (m) ratio = parseFloat(m[1]);
  const vol = Math.round(kg * ratio);
  const el = document.getElementById('calcBathVol');
  if (el) el.value = vol + ' L';
}
document.getElementById('calcFabricKg')?.addEventListener('input', updateCalc);
document.getElementById('calcLr')?.addEventListener('change', updateCalc);
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
