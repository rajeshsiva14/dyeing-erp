<?php
/**
 * Batch Recipe Calculation & Chemical Dosing Formulations
 * Precise chemical quantity calculation based on % OWF and g/L bath volume
 * File: modules/dyeing/views/recipe-calculation.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Batch Recipe Calculation';
$activeModule = 'recipe-calculation';

$batchId = (int)($_GET['batch_id'] ?? 1);
$batch = Database::fetch("
    SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.name as machine_name, m.code as machine_code
    FROM dye_batches b
    JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN machines m ON b.machine_id = m.id
    WHERE b.id = ?
", [$batchId]);

if (!$batch) {
    $batch = Database::fetch("
        SELECT b.*, r.recipe_code, r.shade_name, r.dye_class, m.name as machine_name, m.code as machine_code
        FROM dye_batches b
        JOIN dye_recipes r ON b.dye_recipe_id = r.id
        JOIN machines m ON b.machine_id = m.id
        LIMIT 1
    ");
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

$allBatches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$fabricKg = (float)($batch['actual_loaded_kg'] ?: $batch['planned_weight_kg']);
$lrRatio = 6.0;
if (preg_match('/1:([0-9\.]+)/', $batch['liquor_ratio'] ?? '1:6', $m)) {
    $lrRatio = (float)$m[1];
}
$bathVolume = $fabricKg * $lrRatio;
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
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-calculator text-primary me-2"></i>Batch Recipe Calculation (% OWF & g/L)</h1>
        <p class="text-secondary small mb-0">Compute accurate chemical and dye additions from fabric weight, liquor ratio, and bath volume.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/chemical-requests?batch_id=' . ($batch['id'] ?? 1)) ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-cart-plus me-1"></i> Generate Chemical Request
        </a>
      </div>
    </div>

    <!-- Active Batch Selection & Live Calculation Parameters -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders text-primary me-2"></i>Batch Parameters & Formula Basis</h2>
        <div class="d-flex align-items-center gap-2">
          <label class="small text-secondary fw-bold">Select Batch:</label>
          <select class="form-select form-select-sm" onchange="location.href='?batch_id='+this.value">
            <?php foreach ($allBatches as $ab): ?>
              <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == ($batch['id'] ?? 0)) ? 'selected' : '' ?>>
                <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= number_format($ab['actual_loaded_kg'], 0) ?> Kg)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="p-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small text-secondary fw-bold">Batch Number</label>
            <div class="form-control form-control-sm bg-light font-monospace fw-bold text-primary"><?= htmlspecialchars($batch['batch_no']) ?></div>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-secondary fw-bold">Recipe & Shade</label>
            <div class="form-control form-control-sm bg-light fw-bold"><?= htmlspecialchars($batch['recipe_code']) ?> — <?= htmlspecialchars($batch['shade_name']) ?></div>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-secondary fw-bold">Fabric Wt (Kg)</label>
            <input type="number" id="calcFabricKg" class="form-control form-control-sm fw-bold" value="<?= $fabricKg ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small text-secondary fw-bold">Liquor Ratio</label>
            <input type="text" id="calcLr" class="form-control form-control-sm font-monospace text-center" value="<?= htmlspecialchars($batch['liquor_ratio']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small text-secondary fw-bold">Bath Volume (L)</label>
            <input type="text" id="calcBathVol" class="form-control form-control-sm bg-light font-monospace fw-bold text-end" readonly value="<?= number_format($bathVolume, 0) ?> L">
          </div>
        </div>

        <div class="alert alert-light border mt-3 mb-0 p-2 d-flex justify-content-between align-items-center small text-secondary">
          <div>
            <strong>Standard Textile Formulas:</strong>
            <code>% OWF: Material (Kg) = Fabric (Kg) × (Dosage % ÷ 100)</code> · 
            <code>g/L: Material (Kg) = (Dosage g/L × Bath Vol L) ÷ 1000</code>
          </div>
          <button class="btn btn-sm btn-outline-dark" onclick="recalculateFormulas()"><i class="bi bi-arrow-clockwise me-1"></i> Recalculate</button>
        </div>
      </div>
    </div>

    <!-- Calculated Dosing Schedule Grid -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-list text-primary me-2"></i>Calculated Chemical & Dye Formulation Quantities</h2>
        <span class="small text-secondary"><?= count($recipeItems) ?> Components Calculated</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="calcTable">
          <thead class="table-light">
            <tr>
              <th>Stage</th>
              <th>Material / Dyestuff</th>
              <th>Category</th>
              <th class="text-center">Recipe Dosage</th>
              <th class="text-end">Calculated Qty (Kg)</th>
              <th class="text-end">Available Stock</th>
              <th class="text-center">Shortage Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $totalChemKg = 0.0;
              foreach ($recipeItems as $it): 
                $calcKg = 0.0;
                if ($it['dosage_percentage'] !== null) {
                    $calcKg = ($fabricKg * (float)$it['dosage_percentage']) / 100;
                } elseif ($it['dosage_grams_per_litre'] !== null) {
                    $calcKg = ((float)$it['dosage_grams_per_litre'] * $bathVolume) / 1000;
                }
                $totalChemKg += $calcKg;
                $hasStock = ($it['available_stock'] >= $calcKg);
            ?>
              <tr data-pct="<?= $it['dosage_percentage'] ?>" data-gl="<?= $it['dosage_grams_per_litre'] ?>" data-stock="<?= $it['available_stock'] ?>">
                <td>
                  <span class="badge bg-light text-dark border text-uppercase" style="font-size:0.7rem;">
                    <?= str_replace('_', ' ', $it['process_stage']) ?>
                  </span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($it['chem_name']) ?></div>
                  <code class="text-secondary"><?= htmlspecialchars($it['chem_sku']) ?></code>
                </td>
                <td>
                  <span class="badge bg-<?= $it['item_type'] === 'dye' ? 'primary' : 'secondary' ?>">
                    <?= strtoupper($it['item_type']) ?>
                  </span>
                </td>
                <td class="text-center font-monospace fw-semibold">
                  <?= $it['dosage_percentage'] !== null ? $it['dosage_percentage'] . '% OWF' : $it['dosage_grams_per_litre'] . ' g/L' ?>
                </td>
                <td class="text-end font-monospace fw-bold text-primary calc-qty-cell">
                  <?= number_format($calcKg, 3) ?> Kg
                </td>
                <td class="text-end text-muted font-monospace">
                  <?= number_format($it['available_stock'], 1) ?> Kg
                </td>
                <td class="text-center">
                  <?php if ($hasStock): ?>
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>In Stock</span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Shortage</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a href="<?= Url::to('/dyeing/chemical-requests?batch_id=' . $batch['id']) ?>" class="btn btn-sm btn-outline-dark">
                    Issue Request
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <th colspan="4" class="text-end fw-bold">Total Chemical & Dyestuffs Addition:</th>
              <th class="text-end fw-bold font-monospace text-primary fs-6" id="totalCalcKg">
                <?= number_format($totalChemKg, 3) ?> Kg
              </th>
              <th colspan="3"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</main>

<script>
function recalculateFormulas() {
  const fabricKg = parseFloat(document.getElementById('calcFabricKg')?.value) || 0;
  const lrStr = document.getElementById('calcLr')?.value || '1:6';
  let ratio = 6.0;
  const m = lrStr.match(/1:([0-9\.]+)/);
  if (m) ratio = parseFloat(m[1]) || 6.0;

  const bathVol = fabricKg * ratio;
  const bathVolEl = document.getElementById('calcBathVol');
  if (bathVolEl) bathVolEl.value = bathVol.toLocaleString() + ' L';

  let totalKg = 0;
  document.querySelectorAll('#calcTable tbody tr').forEach(row => {
    const pct = parseFloat(row.getAttribute('data-pct'));
    const gl = parseFloat(row.getAttribute('data-gl'));
    let qty = 0;

    if (!isNaN(pct)) {
      qty = (fabricKg * pct) / 100;
    } else if (!isNaN(gl)) {
      qty = (gl * bathVol) / 1000;
    }

    totalKg += qty;
    const cell = row.querySelector('.calc-qty-cell');
    if (cell) cell.textContent = qty.toFixed(3) + ' Kg';
  });

  const totalEl = document.getElementById('totalCalcKg');
  if (totalEl) totalEl.textContent = totalKg.toFixed(3) + ' Kg';
}

document.getElementById('calcFabricKg')?.addEventListener('input', recalculateFormulas);
document.getElementById('calcLr')?.addEventListener('input', recalculateFormulas);
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
