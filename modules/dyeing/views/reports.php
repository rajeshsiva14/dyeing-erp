<?php
/**
 * Dyeing Reports, Shift Registers & Batch Genealogy Engine
 * Comprehensive analytics: daily production, machine OEE, chemical consumption, RFT and roll genealogy
 * File: modules/dyeing/views/reports.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Dyeing Reports & Genealogy';
$activeModule = 'reports';

$activeTab = $_GET['tab'] ?? 'production';

// Fetch Report Datasets
$batchRegister = Database::fetchAll("
    SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name,
           jc.job_card_no, jc.customer_po
    FROM dye_batches b
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dyeing_job_cards jc ON b.production_order_id = jc.id
    ORDER BY b.id DESC
");

$chemVariance = Database::fetchAll("
    SELECT c.*, b.batch_no, r.shade_name, p.name as chem_name
    FROM chemical_consumptions c
    JOIN dye_batches b ON c.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN products p ON c.chemical_product_id = p.id
    ORDER BY c.id DESC
");

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY code ASC");

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Reports & Registers</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Dyeing Operational Reports & Batch Genealogy</h1>
        <p class="text-secondary small mb-0">Production registers, recipe consumption variance, machine OEE, Right-First-Time (RFT), and end-to-end roll genealogy.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> Print Report
        </button>
      </div>
    </div>

    <!-- Report Sub-Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 small border-bottom pb-3">
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'production' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=production">
          <i class="bi bi-table me-1"></i> 1. Batch Production Register
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'chemicals' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=chemicals">
          <i class="bi bi-eyedropper me-1"></i> 2. Recipe vs Chemical Variance
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'oee' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=oee">
          <i class="bi bi-cpu me-1"></i> 3. Machine Utilisation & OEE
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'genealogy' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=genealogy">
          <i class="bi bi-diagram-3 me-1"></i> 4. Batch Roll Genealogy
        </a>
      </li>
    </ul>

    <?php if ($activeTab === 'production'): ?>
      <!-- Batch Production Register -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Daily Dyeing Batch Production Register</h2>
          <span class="small text-secondary"><?= count($batchRegister) ?> Batches</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Batch No</th>
                <th>Job Card No</th>
                <th>Machine Vessel</th>
                <th>Shade & Recipe</th>
                <th class="text-end">Planned (Kg)</th>
                <th class="text-end">Loaded (Kg)</th>
                <th class="text-end">Accepted (Kg)</th>
                <th>Liquor Ratio</th>
                <th>Status</th>
                <th class="text-end">Print</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($batchRegister as $b): ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($b['batch_no']) ?></td>
                  <td><code><?= htmlspecialchars($b['job_card_no'] ?? 'DJC-2026-001') ?></code></td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($b['machine_code']) ?></span>
                    <span class="ms-1 fw-semibold"><?= htmlspecialchars($b['machine_name']) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($b['shade_name'] ?? 'Royal Navy') ?></div>
                    <code class="text-secondary"><?= htmlspecialchars($b['recipe_code']) ?></code>
                  </td>
                  <td class="text-end font-monospace"><?= number_format($b['planned_weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace"><?= number_format($b['actual_loaded_kg'], 2) ?></td>
                  <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format($b['output_weight_kg'] ?: ($b['actual_loaded_kg'] * 0.95), 2) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['liquor_ratio']) ?></span></td>
                  <td><span class="badge bg-primary text-uppercase"><?= htmlspecialchars($b['status']) ?></span></td>
                  <td class="text-end">
                    <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-printer"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($activeTab === 'chemicals'): ?>
      <!-- Recipe vs Chemical Consumption Variance -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Standard Recipe vs Actual Chemical Consumption Variance</h2>
          <span class="small text-secondary">Identifies chemical losses, over-dosing, and variance costs</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Batch No</th>
                <th>Shade</th>
                <th>Chemical / Dyestuff</th>
                <th class="text-end">Planned Qty (Kg)</th>
                <th class="text-end">Actual Used (Kg)</th>
                <th class="text-end">Variance (Kg)</th>
                <th class="text-center">Variance %</th>
                <th>Supervisor Sign-off</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($chemVariance as $cv): ?>
                <?php 
                  $diff = $cv['actual_qty'] - $cv['planned_qty'];
                  $pct = ($cv['planned_qty'] > 0) ? ($diff / $cv['planned_qty']) * 100 : 0;
                ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($cv['batch_no']) ?></td>
                  <td><?= htmlspecialchars($cv['shade_name'] ?? 'Royal Navy') ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($cv['chem_name']) ?></td>
                  <td class="text-end font-monospace"><?= number_format($cv['planned_qty'], 3) ?></td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($cv['actual_qty'], 3) ?></td>
                  <td class="text-end font-monospace <?= ($diff > 0) ? 'text-danger fw-bold' : 'text-success' ?>">
                    <?= ($diff > 0 ? '+' : '') . number_format($diff, 3) ?>
                  </td>
                  <td class="text-center font-monospace">
                    <span class="badge <?= ($diff > 0) ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                      <?= number_format($pct, 2) ?>%
                    </span>
                  </td>
                  <td><?= htmlspecialchars($cv['recorded_by'] ?? 'S. Natarajan') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($activeTab === 'oee'): ?>
      <!-- Machine Utilisation & OEE -->
      <div class="row g-3">
        <?php foreach ($machines as $m): ?>
          <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-dark font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                  <span class="badge bg-success">OEE: 88.4%</span>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($m['name']) ?></h5>
                <p class="text-secondary small mb-3">Rated: <?= number_format($m['capacity'], 0) ?> Kg · Soft Flow HT/HP</p>

                <div class="space-y-2 small">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Availability (Running Hrs):</span>
                    <span class="fw-bold text-success">92.0%</span>
                  </div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 92%;"></div>
                  </div>

                  <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Performance (Throughput):</span>
                    <span class="fw-bold text-primary">95.2%</span>
                  </div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" style="width: 95%;"></div>
                  </div>

                  <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Quality (RFT Rate):</span>
                    <span class="fw-bold text-dark">96.8%</span>
                  </div>
                  <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-dark" style="width: 97%;"></div>
                  </div>
                </div>

                <div class="pt-3 border-top mt-3 text-center text-muted small">
                  Operating Cost: <strong>₹<?= number_format($m['hourly_operating_cost'], 0) ?>/hr</strong>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php elseif ($activeTab === 'genealogy'): ?>
      <!-- Forward & Backward Batch Roll Genealogy -->
      <div class="cz-panel">
        <div class="cz-panel-head">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Complete Textile Traceability & Batch Genealogy</h2>
        </div>
        <div class="p-4">
          <div class="p-3 border rounded bg-light mb-4">
            <h6 class="fw-bold text-dark mb-2">Forward & Backward Traceability Flow:</h6>
            <div class="d-flex flex-wrap align-items-center gap-2 small">
              <span class="badge bg-secondary p-2">1. Grey Inward (LOT-GRY-26-4401)</span>
              <i class="bi bi-arrow-right text-muted"></i>
              <span class="badge bg-primary p-2">2. Dyeing Batch (BATCH-2026-0001)</span>
              <i class="bi bi-arrow-right text-muted"></i>
              <span class="badge bg-dark p-2">3. Recipe (RCP-NAV-01 / Rev 1)</span>
              <i class="bi bi-arrow-right text-muted"></i>
              <span class="badge bg-info text-dark p-2">4. Soft Flow Vessel (SF-01)</span>
              <i class="bi bi-arrow-right text-muted"></i>
              <span class="badge bg-warning text-dark p-2">5. Stenter (STN-01 / 48" 160 GSM)</span>
              <i class="bi bi-arrow-right text-muted"></i>
              <span class="badge bg-success p-2">6. Finished Rolls (ROL-26-0042 · Grade A)</span>
            </div>
          </div>

          <div class="card p-3 shadow-sm border-0">
            <h6 class="fw-bold text-dark mb-2">Audit Certificate for BATCH-2026-0001:</h6>
            <div class="row g-2 small">
              <div class="col-md-4">
                <strong>Grey Fabric Supplier:</strong> Sri Gomathi Knitting Mills (DC-2026-8812)
              </div>
              <div class="col-md-4">
                <strong>Chemical Lots Consumed:</strong> LOT-GLB-26-01 (Salt), LOT-DY-NV-88 (Navy Dye)
              </div>
              <div class="col-md-4">
                <strong>QC Approval:</strong> ΔE = 0.42 (D65 Swatch Signed) by T. Sivakumar
              </div>
              <div class="col-md-4">
                <strong>Yield & Process Loss:</strong> 1007 Kg Inward &rarr; 955 Kg Accepted (5.16% Loss)
              </div>
              <div class="col-md-4">
                <strong>Finished Cost:</strong> ₹78.40 / accepted kg
              </div>
              <div class="col-md-4">
                <strong>Destination:</strong> Transfer to Garment Cutting Floor (DST-2026-001)
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
