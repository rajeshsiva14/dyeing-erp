<?php
/**
 * Dedicated Full-Page Form: Update Dyeing Batch Costing Worksheet
 * File: modules/dyeing/views/batch-costing/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Batch Costing';
$activeModule = 'batch-costing';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$cstId = (int)($_GET['id'] ?? 0);

$costing = Database::fetch("SELECT * FROM dyeing_batch_costings WHERE id = ?", [$cstId]);
if (!$costing) {
    header('Location: ' . Url::to('/dyeing/batch-costing?error=not_found'));
    exit;
}

$errors = [];

// Handle update batch costing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_costing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $greyFabric = (float)($_POST['grey_fabric_cost'] ?? $costing['grey_fabric_cost']);
    $dyesChems = (float)($_POST['dyes_chemicals_cost'] ?? $costing['dyes_chemicals_cost']);
    $waterCost = (float)($_POST['water_cost'] ?? $costing['water_cost']);
    $steamFuel = (float)($_POST['steam_fuel_cost'] ?? $costing['steam_fuel_cost']);
    $powerCost = (float)($_POST['power_electricity_cost'] ?? $costing['power_electricity_cost']);
    $directLabour = (float)($_POST['direct_labour_cost'] ?? $costing['direct_labour_cost']);
    $machineOverhead = (float)($_POST['machine_overhead_cost'] ?? $costing['machine_overhead_cost']);
    $reprocessCost = (float)($_POST['reprocess_cost'] ?? $costing['reprocess_cost']);
    
    $totalCost = $greyFabric + $dyesChems + $waterCost + $steamFuel + $powerCost + $directLabour + $machineOverhead + $reprocessCost;
    $acceptedOutputKg = (float)($_POST['accepted_output_kg'] ?? $costing['accepted_output_kg']);
    
    $costPerKgAccepted = ($acceptedOutputKg > 0) ? ($totalCost / $acceptedOutputKg) : 0;
    $sellingRate = (float)($_POST['selling_rate_per_kg'] ?? $costing['selling_rate_per_kg']);
    $totalRevenue = $sellingRate * $acceptedOutputKg;
    $grossMargin = ($totalRevenue > 0) ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0;

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_batch_costings SET
                grey_fabric_cost = ?,
                dyes_chemicals_cost = ?,
                water_cost = ?,
                steam_fuel_cost = ?,
                power_electricity_cost = ?,
                direct_labour_cost = ?,
                machine_overhead_cost = ?,
                reprocess_cost = ?,
                total_batch_cost = ?,
                accepted_output_kg = ?,
                cost_per_kg_accepted = ?,
                selling_rate_per_kg = ?,
                gross_margin_pct = ?
            WHERE id = ?
        ")->execute([
            $greyFabric,
            $dyesChems,
            $waterCost,
            $steamFuel,
            $powerCost,
            $directLabour,
            $machineOverhead,
            $reprocessCost,
            $totalCost,
            $acceptedOutputKg,
            $costPerKgAccepted,
            $sellingRate,
            $grossMargin,
            $cstId
        ]);

        header('Location: ' . Url::to('/dyeing/batch-costing?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update costing: ' . $e->getMessage();
    }
}

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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/batch-costing') ?>">Batch Costing</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($costing['costing_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Batch Costing #<?= htmlspecialchars($costing['costing_no']) ?></h1>
        <p class="text-secondary small mb-0">Update input expenses, steam, chemical adjustments, selling rate per kg, and margin reconciliation.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/batch-costing') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="action" value="update_costing">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Batch Reference Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Costing Worksheet No</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($costing['costing_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Batch Reference</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($costing['batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-success">Finished Accepted Weight (Kg) *</label>
                <input type="number" step="0.1" name="accepted_output_kg" id="cstOutputKg" class="form-control form-control-sm fw-bold text-success" value="<?= (float)$costing['accepted_output_kg'] ?>" required oninput="calcTotals()">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-primary">Target Selling Price / Rate (₹/Kg) *</label>
                <input type="number" step="0.01" name="selling_rate_per_kg" id="cstSellingRate" class="form-control form-control-sm fw-bold text-primary" value="<?= (float)$costing['selling_rate_per_kg'] ?>" required oninput="calcTotals()">
              </div>
              <div class="p-3 bg-light rounded border mt-3">
                <div class="small text-secondary fw-bold">Live Profitability Summary:</div>
                <div class="d-flex justify-content-between mt-2">
                  <span class="small">Total Batch Cost:</span>
                  <strong class="font-monospace" id="lblTotalCost">₹<?= number_format($costing['total_batch_cost'], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <span class="small">Cost / Accepted Kg:</span>
                  <strong class="font-monospace text-danger" id="lblCostPerKg">₹<?= number_format($costing['cost_per_kg_accepted'], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <span class="small">Gross Profit Margin:</span>
                  <strong class="font-monospace text-success fs-6" id="lblMargin"><?= number_format($costing['gross_margin_pct'], 1) ?>%</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Absorption Cost Breakdown -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cash-stack me-2"></i>Cost Components Breakdown (INR)</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Grey Fabric Cost (₹) *</label>
                  <input type="number" step="0.01" name="grey_fabric_cost" class="form-control form-control-sm fw-bold cost-input" value="<?= (float)$costing['grey_fabric_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Dyestuffs & Auxiliary Chemicals (₹) *</label>
                  <input type="number" step="0.01" name="dyes_chemicals_cost" class="form-control form-control-sm fw-bold cost-input" value="<?= (float)$costing['dyes_chemicals_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Process Water & RO Softening (₹) *</label>
                  <input type="number" step="0.01" name="water_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['water_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Steam Boiler Fuel / Wood Pellets (₹) *</label>
                  <input type="number" step="0.01" name="steam_fuel_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['steam_fuel_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Electricity / Power (₹) *</label>
                  <input type="number" step="0.01" name="power_electricity_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['power_electricity_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Direct Labor & Shift Wages (₹) *</label>
                  <input type="number" step="0.01" name="direct_labour_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['direct_labour_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Machine Vessel Amortization & Overhead (₹) *</label>
                  <input type="number" step="0.01" name="machine_overhead_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['machine_overhead_cost'] ?>" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Reprocess / Stripping Cost (₹)</label>
                  <input type="number" step="0.01" name="reprocess_cost" class="form-control form-control-sm cost-input" value="<?= (float)$costing['reprocess_cost'] ?>" oninput="calcTotals()">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/batch-costing') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Batch Costing Worksheet
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function calcTotals() {
  var inputs = document.querySelectorAll('.cost-input');
  var total = 0;
  inputs.forEach(function(i) {
    total += parseFloat(i.value) || 0;
  });
  var outKg = parseFloat(document.getElementById('cstOutputKg').value) || 0;
  var selling = parseFloat(document.getElementById('cstSellingRate').value) || 0;
  
  var costPerKg = (outKg > 0) ? (total / outKg) : 0;
  var revenue = selling * outKg;
  var margin = (revenue > 0) ? (((revenue - total) / revenue) * 100) : 0;

  document.getElementById('lblTotalCost').innerText = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
  document.getElementById('lblCostPerKg').innerText = '₹' + costPerKg.toFixed(2);
  document.getElementById('lblMargin').innerText = margin.toFixed(2) + '%';
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
