<?php
/**
 * Dyeing Batch Costing & Profitability Engine
 * Full absorption costing: grey fabric, dyestuffs, steam, water, power, direct labour and overheads
 * File: modules/dyeing/views/batch-costing.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Batch Costing';
$activeModule = 'batch-costing';

// Handle Save Batch Costing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_costing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $costingNo = 'CST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    
    $greyFabric = (float)($_POST['grey_fabric_cost'] ?? 50000);
    $dyesChems = (float)($_POST['dyes_chemicals_cost'] ?? 8000);
    $waterCost = (float)($_POST['water_cost'] ?? 1200);
    $steamFuel = (float)($_POST['steam_fuel_cost'] ?? 4000);
    $powerCost = (float)($_POST['power_electricity_cost'] ?? 2800);
    $directLabour = (float)($_POST['direct_labour_cost'] ?? 3500);
    $machineOverhead = (float)($_POST['machine_overhead_cost'] ?? 4500);
    $reprocessCost = (float)($_POST['reprocess_cost'] ?? 0.0);
    
    $totalCost = $greyFabric + $dyesChems + $waterCost + $steamFuel + $powerCost + $directLabour + $machineOverhead + $reprocessCost;
    $acceptedOutputKg = (float)($_POST['accepted_output_kg'] ?? 950);
    $inputKg = (float)($batch['actual_loaded_kg'] ?: 1000);
    
    $costPerKgInput = ($inputKg > 0) ? ($totalCost / $inputKg) : 0;
    $costPerKgAccepted = ($acceptedOutputKg > 0) ? ($totalCost / $acceptedOutputKg) : 0;
    $sellingRate = (float)($_POST['selling_rate_per_kg'] ?? 92.0);
    $totalRevenue = $sellingRate * $acceptedOutputKg;
    $grossMargin = ($totalRevenue > 0) ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0;

    Database::insert('dyeing_batch_costings', [
        'company_id'             => 1,
        'costing_no'             => $costingNo,
        'dye_batch_id'           => $batchId,
        'batch_no'               => $batchNo,
        'grey_fabric_cost'       => $greyFabric,
        'dyes_chemicals_cost'    => $dyesChems,
        'water_cost'             => $waterCost,
        'steam_fuel_cost'        => $steamFuel,
        'power_electricity_cost' => $powerCost,
        'direct_labour_cost'     => $directLabour,
        'machine_overhead_cost'  => $machineOverhead,
        'reprocess_cost'         => $reprocessCost,
        'total_batch_cost'       => $totalCost,
        'accepted_output_kg'     => $acceptedOutputKg,
        'cost_per_kg_input'      => $costPerKgInput,
        'cost_per_kg_accepted'   => $costPerKgAccepted,
        'selling_rate_per_kg'    => $sellingRate,
        'gross_margin_pct'       => $grossMargin
    ]);

    header('Location: ' . Url::to('/dyeing/batch-costing?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$costings = Database::fetchAll("
    SELECT c.*, r.shade_name
    FROM dyeing_batch_costings c
    JOIN dye_batches b ON c.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY c.id DESC
");

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Batch Costing</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-currency-rupee text-primary me-2"></i>Dyeing Batch Costing & Margin Engine</h1>
        <p class="text-secondary small mb-0">Compute full cost per accepted kg: Grey fabric + Dyes/Chemicals + Steam (Boiler) + Water + Power + Labour + Vessel Overhead.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newCostingModal">
          <i class="bi bi-calculator me-1"></i> Compute Batch Cost
        </button>
      </div>
    </div>

    <!-- Costing Register Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-table text-primary me-2"></i>Batch Production Costing & Gross Margin Register</h2>
        <span class="small text-secondary"><?= count($costings) ?> Costed Batches</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Costing No</th>
              <th>Batch No</th>
              <th>Shade</th>
              <th class="text-end">Grey Fabric (₹)</th>
              <th class="text-end">Dyes & Chems (₹)</th>
              <th class="text-end">Steam & Power (₹)</th>
              <th class="text-end">Labour & O/H (₹)</th>
              <th class="text-end">Total Cost (₹)</th>
              <th class="text-end">Cost / Kg Accepted</th>
              <th class="text-end">Sell Rate / Kg</th>
              <th class="text-center">Gross Margin</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($costings as $cst): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($cst['costing_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($cst['batch_no']) ?></span></td>
                <td><?= htmlspecialchars($cst['shade_name'] ?? 'Royal Navy') ?></td>
                <td class="text-end font-monospace">₹<?= number_format($cst['grey_fabric_cost'], 2) ?></td>
                <td class="text-end font-monospace">₹<?= number_format($cst['dyes_chemicals_cost'], 2) ?></td>
                <td class="text-end font-monospace">₹<?= number_format($cst['steam_fuel_cost'] + $cst['power_electricity_cost'], 2) ?></td>
                <td class="text-end font-monospace">₹<?= number_format($cst['direct_labour_cost'] + $cst['machine_overhead_cost'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-dark fs-6">₹<?= number_format($cst['total_batch_cost'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-primary">₹<?= number_format($cst['cost_per_kg_accepted'], 2) ?>/kg</td>
                <td class="text-end font-monospace">₹<?= number_format($cst['selling_rate_per_kg'], 2) ?></td>
                <td class="text-center font-monospace">
                  <span class="badge bg-success-subtle text-success fs-6">
                    <?= number_format($cst['gross_margin_pct'], 2) ?>%
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Costing Modal -->
<div class="modal fade" id="newCostingModal" tabindex="-1" aria-labelledby="newCostingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_costing">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newCostingModalLabel"><i class="bi bi-currency-rupee text-primary me-2"></i>Compute Batch Full Absorption Costing</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> (<?= htmlspecialchars($b['shade_name']) ?> · <?= number_format($b['actual_loaded_kg'], 0) ?> Kg)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-success">Accepted Output (Kg) *</label>
              <input type="number" step="0.01" name="accepted_output_kg" id="costAcceptedKg" class="form-control form-control-sm fw-bold" value="955.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Selling Rate / Kg (₹) *</label>
              <input type="number" step="0.01" name="selling_rate_per_kg" id="costSellRate" class="form-control form-control-sm" value="92.00" required>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Itemized Cost Components</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">Grey Fabric Value (₹) *</label>
              <input type="number" step="0.01" name="grey_fabric_cost" id="cGrey" class="form-control form-control-sm cost-input" value="50350.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Dyes & Chemicals (₹) *</label>
              <input type="number" step="0.01" name="dyes_chemicals_cost" id="cChem" class="form-control form-control-sm cost-input" value="8420.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Steam / Boiler Fuel (₹) *</label>
              <input type="number" step="0.01" name="steam_fuel_cost" id="cSteam" class="form-control form-control-sm cost-input" value="4200.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Power / Electricity (₹) *</label>
              <input type="number" step="0.01" name="power_electricity_cost" id="cPower" class="form-control form-control-sm cost-input" value="2750.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Water / Effluent (₹) *</label>
              <input type="number" step="0.01" name="water_cost" id="cWater" class="form-control form-control-sm cost-input" value="1150.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Direct Labour (₹) *</label>
              <input type="number" step="0.01" name="direct_labour_cost" id="cLabour" class="form-control form-control-sm cost-input" value="3500.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Machine Overhead (₹) *</label>
              <input type="number" step="0.01" name="machine_overhead_cost" id="cOverhead" class="form-control form-control-sm cost-input" value="4500.00" required>
            </div>
          </div>

          <!-- Total & Cost per Kg Summary -->
          <div class="p-3 bg-light rounded border">
            <div class="row text-center g-2">
              <div class="col-4">
                <div class="text-secondary small">Total Production Cost</div>
                <div class="fs-5 fw-bold text-dark" id="dispTotalCost">₹74,870.00</div>
              </div>
              <div class="col-4">
                <div class="text-secondary small">Cost Per Accepted Kg</div>
                <div class="fs-5 fw-bold text-primary" id="dispCostPerKg">₹78.40 / kg</div>
              </div>
              <div class="col-4">
                <div class="text-secondary small">Gross Profit Margin</div>
                <div class="fs-5 fw-bold text-success" id="dispMargin">14.78%</div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Save Batch Costing</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calcBatchCost() {
  const grey = parseFloat(document.getElementById('cGrey')?.value) || 0;
  const chem = parseFloat(document.getElementById('cChem')?.value) || 0;
  const steam = parseFloat(document.getElementById('cSteam')?.value) || 0;
  const power = parseFloat(document.getElementById('cPower')?.value) || 0;
  const water = parseFloat(document.getElementById('cWater')?.value) || 0;
  const labour = parseFloat(document.getElementById('cLabour')?.value) || 0;
  const overhead = parseFloat(document.getElementById('cOverhead')?.value) || 0;

  const total = grey + chem + steam + power + water + labour + overhead;
  const acceptedKg = parseFloat(document.getElementById('costAcceptedKg')?.value) || 950;
  const sellRate = parseFloat(document.getElementById('costSellRate')?.value) || 92;

  const costPerKg = (acceptedKg > 0) ? total / acceptedKg : 0;
  const revenue = sellRate * acceptedKg;
  const margin = (revenue > 0) ? ((revenue - total) / revenue) * 100 : 0;

  document.getElementById('dispTotalCost').textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
  document.getElementById('dispCostPerKg').textContent = '₹' + costPerKg.toFixed(2) + ' / kg';
  document.getElementById('dispMargin').textContent = margin.toFixed(2) + '%';
}

document.querySelectorAll('.cost-input').forEach(inp => inp.addEventListener('input', calcBatchCost));
document.getElementById('costAcceptedKg')?.addEventListener('input', calcBatchCost);
document.getElementById('costSellRate')?.addEventListener('input', calcBatchCost);
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
