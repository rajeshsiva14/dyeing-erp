<?php
/**
 * Textile Production Costing & Margin Report - KARCEM Textile ERP
 * File: modules/reports/views/costing-margins.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Services\CostingService;

$pageTitle = 'Production Costing & Profitability';
$activeModule = 'reports';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

// Illustrative real-world dyeing batch costing
$greyFabricCost = 159520.00; // 498.5 Kg * ₹320/Kg
$dyesChemCost = 18450.00;    // Reactive Blue + Soda ash + Acetic
$directLabourCost = 4200.00; // 2 operators * 1 shift
$utilitiesCost = 6800.00;    // Steam from Briquettes boiler + Power
$machineOverhead = 9250.00;  // 5 hrs * ₹1850/hr SF-01 rate
$totalAllocatedCost = $greyFabricCost + $dyesChemCost + $directLabourCost + $utilitiesCost + $machineOverhead;

$acceptedKg = 486.20;
$costPerKg = CostingService::calculateCostPerAcceptedUnit($totalAllocatedCost, $acceptedKg);

$sellingRatePerKg = 480.00;
$netRevenue = $acceptedKg * $sellingRatePerKg;
$grossProfit = $netRevenue - $totalAllocatedCost;
$grossMarginPct = CostingService::calculateGrossMarginPct($netRevenue, $totalAllocatedCost);
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/reports') ?>">Reports</a></li>
            <li class="breadcrumb-item active">Production Costing</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Textile Production Costing & Gross Margin Analysis</h1>
        <p class="text-secondary small mb-0">Section 9 SRS Formulae: Actual material, machine hours, direct labour, boiler steam, ETP utilities, and gross margin %.</p>
      </div>
      <div class="d-flex gap-2">
        <span class="badge bg-success-subtle text-success border border-success p-2 fs-6">
          <i class="bi bi-graph-up-arrow me-1"></i> Gross Margin: <?= $grossMarginPct ?>%
        </span>
      </div>
    </div>

    <!-- Batch Costing Breakdown Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-primary shadow-sm">
          <div class="text-secondary small fw-bold">TOTAL ALLOCATED BATCH COST</div>
          <div class="fs-4 fw-bold text-primary">₹<?= NumberToWords::formatIndian($totalAllocatedCost, 2) ?></div>
          <div class="text-muted" style="font-size: 0.75rem;">Batch: BAT-26-006104 (500 Kg)</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-info shadow-sm">
          <div class="text-secondary small fw-bold">ACCEPTED OUTPUT</div>
          <div class="fs-4 fw-bold text-info"><?= number_format($acceptedKg, 1) ?> Kg</div>
          <div class="text-muted" style="font-size: 0.75rem;">Yield: 97.54% · Loss: 2.46%</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-dark shadow-sm">
          <div class="text-secondary small fw-bold">COST PER ACCEPTED KG</div>
          <div class="fs-4 fw-bold text-dark">₹<?= number_format($costPerKg, 2) ?> <small class="fs-6">/ Kg</small></div>
          <div class="text-muted" style="font-size: 0.75rem;">Formula: C_unit = C_alloc / Q_acc</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-success shadow-sm">
          <div class="text-secondary small fw-bold">NET GROSS PROFIT</div>
          <div class="fs-4 fw-bold text-success">₹<?= NumberToWords::formatIndian($grossProfit, 2) ?></div>
          <div class="text-muted" style="font-size: 0.75rem;">At Selling Rate ₹<?= $sellingRatePerKg ?>/Kg</div>
        </div>
      </div>
    </div>

    <!-- Detailed Cost Sheet Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-calculator text-primary me-2"></i>Dyeing Batch Cost Sheet (BAT-26-006104)</h2>
        <span class="badge bg-light text-dark border">Soft Flow SF-01 · 100% Cotton Single Jersey</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Cost Component</th>
              <th>Description & Basis</th>
              <th>Quantity / Rate</th>
              <th class="text-end">Amount (₹)</th>
              <th class="text-end">Cost Share (%)</th>
            </tr>
          </thead>
          <tbody class="small">
            <tr>
              <td class="fw-bold text-dark">1. Grey Fabric Consumed</td>
              <td>100% Combed Cotton Single Jersey (Lot: LOT-GF-202610-01)</td>
              <td>498.50 Kg &times; ₹320.00/Kg</td>
              <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($greyFabricCost, 2) ?></td>
              <td class="text-end fw-semibold"><?= round(($greyFabricCost / $totalAllocatedCost) * 100, 1) ?>%</td>
            </tr>
            <tr>
              <td class="fw-bold text-dark">2. Dyes & Auxiliary Chemicals</td>
              <td>Reactive Blue RGB (2.45% OWF) + Soda Ash Dense + Acetic Acid</td>
              <td>Recipe: REC-ROYAL-01</td>
              <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($dyesChemCost, 2) ?></td>
              <td class="text-end fw-semibold"><?= round(($dyesChemCost / $totalAllocatedCost) * 100, 1) ?>%</td>
            </tr>
            <tr>
              <td class="fw-bold text-dark">3. Direct Production Labour</td>
              <td>Soft flow machine operators & chemical weighing technicians</td>
              <td>2 Operators &times; ₹2,100/shift</td>
              <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($directLabourCost, 2) ?></td>
              <td class="text-end fw-semibold"><?= round(($directLabourCost / $totalAllocatedCost) * 100, 1) ?>%</td>
            </tr>
            <tr>
              <td class="fw-bold text-dark">4. Utilities (Steam, Power, ETP)</td>
              <td>Briquette boiler steam (14.4 T steam) + power + ZLD water recycling</td>
              <td>Allocated to batch</td>
              <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($utilitiesCost, 2) ?></td>
              <td class="text-end fw-semibold"><?= round(($utilitiesCost / $totalAllocatedCost) * 100, 1) ?>%</td>
            </tr>
            <tr>
              <td class="fw-bold text-dark">5. Machine Overhead</td>
              <td>Soft Flow Vessel SF-01 operating cost & maintenance</td>
              <td>5.0 Hours &times; ₹1,850.00/hr</td>
              <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($machineOverhead, 2) ?></td>
              <td class="text-end fw-semibold"><?= round(($machineOverhead / $totalAllocatedCost) * 100, 1) ?>%</td>
            </tr>
          </tbody>
          <tfoot class="table-dark small font-monospace">
            <tr>
              <th colspan="3" class="text-uppercase text-end">Total Manufacturing Cost (COGS):</th>
              <th class="text-end">₹<?= NumberToWords::formatIndian($totalAllocatedCost, 2) ?></th>
              <th class="text-end">100.0%</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
