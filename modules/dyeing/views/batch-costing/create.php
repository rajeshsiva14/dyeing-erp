<?php
/**
 * Dedicated Full-Page Form: Compute Dyeing Batch Absorption Costing
 * File: modules/dyeing/views/batch-costing/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Compute Batch Costing';
$activeModule = 'batch-costing';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle Save Batch Costing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_costing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $costingNo = 'CST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
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

        try {
            Database::insert('dyeing_batch_costings', [
                'company_id'             => $companyId,
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
        } catch (\Throwable $e) {
            $errors[] = 'Failed to compute costing: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
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
            <li class="breadcrumb-item active">New Worksheet</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-calculator text-dark me-2"></i>Compute Batch Absorption Costing</h1>
        <p class="text-secondary small mb-0">Reconcile full manufacturing costs: Raw grey fabric, dyestuffs & chemicals, steam (boiler briquettes), water, power, labour, machine amortisation, and margin.</p>
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
      <input type="hidden" name="action" value="save_costing">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Batch Selection Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Production Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateBatchWeight(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-weight="<?= $b['actual_loaded_kg'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-success">Finished Accepted Weight (Kg) *</label>
                <input type="number" step="0.1" name="accepted_output_kg" id="cstOutputKg" class="form-control form-control-sm fw-bold text-success" value="950.0" required oninput="calcTotals()">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-primary">Target Selling Price / Rate (₹/Kg) *</label>
                <input type="number" step="0.01" name="selling_rate_per_kg" id="cstSellingRate" class="form-control form-control-sm fw-bold text-primary" value="92.00" required oninput="calcTotals()">
              </div>
              <div class="p-3 bg-light rounded border mt-3">
                <div class="small text-secondary fw-bold">Live Profitability Summary:</div>
                <div class="d-flex justify-content-between mt-2">
                  <span class="small">Total Batch Cost:</span>
                  <strong class="font-monospace" id="lblTotalCost">₹74,000.00</strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <span class="small">Cost / Accepted Kg:</span>
                  <strong class="font-monospace text-danger" id="lblCostPerKg">₹77.89</strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <span class="small">Gross Profit Margin:</span>
                  <strong class="font-monospace text-success fs-6" id="lblMargin">15.34%</strong>
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
                  <input type="number" step="0.01" name="grey_fabric_cost" class="form-control form-control-sm fw-bold cost-input" value="50000.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Dyestuffs & Auxiliary Chemicals (₹) *</label>
                  <input type="number" step="0.01" name="dyes_chemicals_cost" class="form-control form-control-sm fw-bold cost-input" value="8000.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Process Water & RO Softening (₹) *</label>
                  <input type="number" step="0.01" name="water_cost" class="form-control form-control-sm cost-input" value="1200.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Steam Boiler Fuel / Wood Pellets (₹) *</label>
                  <input type="number" step="0.01" name="steam_fuel_cost" class="form-control form-control-sm cost-input" value="4000.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Electricity / Power (₹) *</label>
                  <input type="number" step="0.01" name="power_electricity_cost" class="form-control form-control-sm cost-input" value="2800.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Direct Labor & Shift Wages (₹) *</label>
                  <input type="number" step="0.01" name="direct_labour_cost" class="form-control form-control-sm cost-input" value="3500.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Machine Vessel Amortization & Overhead (₹) *</label>
                  <input type="number" step="0.01" name="machine_overhead_cost" class="form-control form-control-sm cost-input" value="4500.00" required oninput="calcTotals()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Reprocess / Stripping Cost (₹)</label>
                  <input type="number" step="0.01" name="reprocess_cost" class="form-control form-control-sm cost-input" value="0.00" oninput="calcTotals()">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/batch-costing') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Save Batch Costing Worksheet
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function populateBatchWeight(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.weight) {
    document.getElementById('cstOutputKg').value = parseFloat(opt.dataset.weight).toFixed(1);
    calcTotals();
  }
}
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
