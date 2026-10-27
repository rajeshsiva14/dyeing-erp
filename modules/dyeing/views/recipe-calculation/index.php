<?php
/**
 * Dedicated Register View: Batch Recipe Calculation & Chemical Formulations
 * File: modules/dyeing/views/recipe-calculation/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Batch Recipe Calculation';
$activeModule = 'recipe-calculation';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

$batchId = (int)($_GET['batch_id'] ?? 1);
$batch = Database::fetch("
    SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.name as machine_name, m.code as machine_code
    FROM dye_batches b
    JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN machines m ON b.machine_id = m.id
    WHERE b.id = ? AND b.company_id = ?
", [$batchId, $companyId]);

if (!$batch) {
    $batch = Database::fetch("
        SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.name as machine_name, m.code as machine_code
        FROM dye_batches b
        JOIN dye_recipes r ON b.dye_recipe_id = r.id
        JOIN machines m ON b.machine_id = m.id
        WHERE b.company_id = ?
        LIMIT 1
    ", [$companyId]);
}

$recipeItems = [];
if ($batch) {
    $recipeItems = Database::fetchAll("
        SELECT ri.*, p.name as chem_name, p.sku as chem_sku,
               COALESCE((SELECT SUM(quantity_base_uom) FROM inventory_movements WHERE product_id = p.id), 250.0) as available_stock
        FROM dye_recipe_items ri
        JOIN products p ON ri.chemical_product_id = p.id
        WHERE ri.dye_recipe_id = ?
        ORDER BY ri.process_stage ASC
    ", [$batch['dye_recipe_id']]);
}

$allBatches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id WHERE b.company_id = ? ORDER BY b.id DESC", [$companyId]);

$fabricKg = (float)($batch ? ($batch['actual_loaded_kg'] ?: $batch['planned_weight_kg']) : 500);
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
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Recipe Calculation</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-calculator text-dark me-2"></i>Batch Recipe Calculation (% OWF & g/L)</h1>
        <p class="text-secondary small mb-0">Compute accurate chemical and dye additions from fabric weight, liquor ratio, and bath volume.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/recipe-calculation/create') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-calculator me-1"></i> New Formulation
        </a>
        <a href="<?= Url::to('/dyeing/chemical-requests/create?dye_batch_id=' . ($batch['id'] ?? 1)) ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-cart-plus me-1"></i> Generate Chemical Request
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['computed']) || isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Batch formulation and dosing quantities computed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Active Batch Selection & Live Calculation Parameters -->
    <?php if ($batch): ?>
      <?php $editUrl = Url::to('/dyeing/recipe-calculation/edit?id=' . $batch['id']); ?>
      <div class="cz-panel mb-4 cz-clickable-row" data-href="<?= $editUrl ?>" style="cursor: pointer;" title="Click anywhere to edit parameters">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders text-dark me-2"></i>Batch Parameters & Formula Basis</h2>
          <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation();">
            <label class="small text-secondary fw-bold">Select Batch:</label>
            <select class="form-select form-select-sm" onchange="location.href='?batch_id='+this.value">
              <?php foreach ($allBatches as $ab): ?>
                <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == $batch['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= number_format($ab['actual_loaded_kg'] ?: $ab['planned_weight_kg'], 0) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="p-3">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label small text-secondary fw-bold">Batch Number</label>
              <div class="form-control form-control-sm bg-light font-monospace fw-bold text-primary">
                <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($batch['batch_no']) ?></a>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label small text-secondary fw-bold">Recipe & Shade</label>
              <div class="form-control form-control-sm bg-light fw-bold"><?= htmlspecialchars($batch['recipe_code']) ?> — <?= htmlspecialchars($batch['shade_name']) ?></div>
            </div>
            <div class="col-md-2">
              <label class="form-label small text-secondary fw-bold">Fabric Wt (Kg)</label>
              <div class="form-control form-control-sm bg-light font-monospace fw-bold"><?= number_format($fabricKg, 1) ?> kg</div>
            </div>
            <div class="col-md-2">
              <label class="form-label small text-secondary fw-bold">Liquor Ratio</label>
              <div class="form-control form-control-sm bg-light font-monospace text-center"><?= htmlspecialchars($batch['liquor_ratio'] ?? '1:6') ?></div>
            </div>
            <div class="col-md-2">
              <label class="form-label small text-secondary fw-bold">Bath Volume (L)</label>
              <div class="form-control form-control-sm bg-light font-monospace fw-bold text-end text-primary"><?= number_format($bathVolume, 0) ?> L</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calculated Dosing Schedule Grid (Click row to edit) -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-list text-dark me-2"></i>Calculated Chemical & Dye Formulation Quantities</h2>
          <span class="small text-secondary"><?= count($recipeItems) ?> Components Calculated</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Stage</th>
                <th>Chemical / Dye Name</th>
                <th>Type</th>
                <th class="text-center">Dosage Unit</th>
                <th class="text-end">Dosage Value</th>
                <th class="text-end">Required Qty (Kg)</th>
                <th class="text-end">Available Stock</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recipeItems)): ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-secondary">
                    <i class="bi bi-inbox fs-3 d-block mb-1"></i> No recipe lines found for this shade.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recipeItems as $ri): ?>
                  <?php 
                    $dosage = (float)$ri['dosage_value'];
                    if ($ri['dosage_unit'] === '% OWF') {
                        $neededKg = ($fabricKg * $dosage) / 100.0;
                    } else {
                        $neededKg = ($bathVolume * $dosage) / 1000.0;
                    }
                  ?>
                  <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this formulation">
                    <td><span class="badge bg-secondary"><?= strtoupper($ri['process_stage'] ?? 'DYEING') ?></span></td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($ri['chem_name']) ?></div>
                      <code class="text-secondary"><?= htmlspecialchars($ri['chem_sku']) ?></code>
                    </td>
                    <td><?= ucwords(str_replace('_', ' ', $ri['chemical_type'] ?? 'auxiliary')) ?></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($ri['dosage_unit']) ?></span></td>
                    <td class="text-end font-monospace"><?= number_format($dosage, 2) ?></td>
                    <td class="text-end font-monospace fw-bold text-primary"><?= number_format($neededKg, 3) ?> kg</td>
                    <td class="text-end font-monospace text-muted"><?= number_format($ri['available_stock'], 1) ?> kg</td>
                    <td class="text-end">
                      <a href="<?= Url::to('/print/recipe-sheet?id=' . $batch['dye_recipe_id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print Sheet" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
