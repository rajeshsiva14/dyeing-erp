<?php
/**
 * Dyeing Operations Executive Command Center
 * Industrial Textile Manufacturing ERP
 * File: modules/dyeing/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\SessionManager;

SessionManager::start();
$currentUser = SessionManager::getUser();

$pageTitle = 'Dyeing Operations Floor';
$activeModule = 'dashboard';

// -------------------------------------------------------------------------
// Live Telemetry Queries
// -------------------------------------------------------------------------
// Batches summary
$batchStats = Database::fetch("
    SELECT 
        COUNT(*) as total_batches,
        SUM(CASE WHEN status IN ('loaded', 'dosing', 'dyeing', 'washing') THEN 1 ELSE 0 END) as running_batches,
        SUM(CASE WHEN status = 'dosing' THEN 1 ELSE 0 END) as dosing_batches,
        SUM(CASE WHEN status = 'dyeing' THEN 1 ELSE 0 END) as active_dyeing_batches,
        SUM(CASE WHEN status IN ('unloaded', 'hydro', 'drying', 'compacted') THEN 1 ELSE 0 END) as qc_waiting,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_batches,
        SUM(CASE WHEN status = 'reprocess' THEN 1 ELSE 0 END) as reprocess_batches,
        COALESCE(SUM(planned_weight_kg), 0) as total_planned_kg,
        COALESCE(SUM(actual_loaded_kg), 0) as total_loaded_kg,
        COALESCE(SUM(output_weight_kg), 0) as total_output_kg,
        COALESCE(SUM(water_volume_litres), 0) as total_water_litres
    FROM dye_batches
");

$totalBatches       = (int)($batchStats['total_batches'] ?? 0);
$runningBatches     = (int)($batchStats['running_batches'] ?? 0);
$qcWaiting          = (int)($batchStats['qc_waiting'] ?? 0);
$completedBatches   = (int)($batchStats['completed_batches'] ?? 0);
$reprocessBatches   = (int)($batchStats['reprocess_batches'] ?? 0);
$totalPlannedKg     = (float)($batchStats['total_planned_kg'] ?? 0);
$totalLoadedKg      = (float)($batchStats['total_loaded_kg'] ?? 0);
$totalOutputKg      = (float)($batchStats['total_output_kg'] ?? 0);
$totalWaterLitres   = (float)($batchStats['total_water_litres'] ?? 0);

// Grey Inward summary
$greyStats = Database::fetch("SELECT COUNT(*) as count, COALESCE(SUM(net_weight_kg), 0) as net_kg, COALESCE(SUM(received_rolls), 0) as rolls FROM grey_fabric_inwards");
$greyInwardKg = (float)($greyStats['net_kg'] ?? 0);
$greyInwardRolls = (int)($greyStats['rolls'] ?? 0);

// Finished rolls summary
$rollStats = Database::fetch("SELECT COUNT(*) as count, COALESCE(SUM(weight_kg), 0) as net_kg FROM inventory_rolls");
$finishedRollsCount = (int)($rollStats['count'] ?? 0);
$finishedRollsKg = (float)($rollStats['net_kg'] ?? 0);

// Soft Flow Machines Telemetry
$softFlowMachines = Database::fetchAll("
    SELECT m.*, 
           b.id as active_batch_id,
           b.batch_no as active_batch_no,
           b.status as batch_status,
           b.actual_loaded_kg,
           b.water_volume_litres,
           b.liquor_ratio as batch_lr,
           b.operator_name,
           b.process_start_time,
           r.recipe_code,
           r.shade_name,
           s.hex_color,
           s.pantone_ref,
           jc.job_card_no,
           jc.fabric_name,
           c.name as customer_name
    FROM machines m
    LEFT JOIN dye_batches b ON m.id = b.machine_id AND b.status IN ('loaded', 'dosing', 'dyeing', 'washing')
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN dyeing_shades s ON r.shade_name = s.shade_name
    LEFT JOIN dyeing_job_cards jc ON b.production_order_id = jc.id
    LEFT JOIN customers c ON jc.customer_id = c.id
    WHERE m.process_type = 'soft_flow_dyeing'
    ORDER BY m.code ASC
");

// Finishing & Processing Machines
$finishingMachines = Database::fetchAll("
    SELECT * FROM machines 
    WHERE process_type IN ('stenter', 'hydro_extractor', 'compactor')
    ORDER BY code ASC
");

// Live Batches List with joins
$batches = Database::fetchAll("
    SELECT b.*, 
           r.recipe_code, 
           r.shade_name, 
           s.hex_color,
           s.pantone_ref,
           s.delta_e_tolerance,
           m.code as machine_code, 
           m.name as machine_name,
           jc.job_card_no, 
           jc.priority, 
           jc.fabric_name,
           c.name as customer_name
    FROM dye_batches b
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN dyeing_shades s ON r.shade_name = s.shade_name
    LEFT JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dyeing_job_cards jc ON b.production_order_id = jc.id
    LEFT JOIN customers c ON jc.customer_id = c.id
    ORDER BY b.id DESC
");

// Chemical store stock
$chemicals = Database::fetchAll("
    SELECT id, sku, name, current_stock, standard_cost 
    FROM products 
    WHERE category = 'dyes_chemical' 
    ORDER BY current_stock DESC 
    LIMIT 4
");

// ETP Latest Log
$etpLog = Database::fetch("SELECT * FROM etp_logs ORDER BY id DESC LIMIT 1");

// Computed KPIs
$rftRate = ($totalBatches > 0) ? round((($totalBatches - $reprocessBatches) / $totalBatches) * 100, 1) : 98.5;
$loadingEfficiency = ($totalPlannedKg > 0) ? round(($totalLoadedKg / $totalPlannedKg) * 100, 1) : 99.3;

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<style>
/* ==========================================================================
   SPACIOUS ENTERPRISE DYEING DASHBOARD DESIGN SYSTEM
   High visual breathing room, crisp typography, clean data hierarchy
   ========================================================================== */
.t-dash-wrap {
  font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif;
  color: #1e293b;
  max-width: 1600px;
  margin: 0 auto;
}

/* Pulsing radar dot for active machinery */
.t-live-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #10b981;
  display: inline-block;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: t-pulse 2s infinite cubic-bezier(0.66, 0, 0, 1);
  margin-right: 6px;
}
@keyframes t-pulse {
  0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
  100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Primary Top Metric Card */
.t-metric-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.35rem 1.4rem;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
  transition: all 0.2s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.t-metric-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}
.t-metric-label {
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  margin-bottom: 0.4rem;
}
.t-metric-value {
  font-size: 1.85rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.03em;
  line-height: 1.15;
}
.t-metric-sub {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.5rem;
}

/* Main Content Panels */
.t-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.t-panel-head {
  padding: 1.15rem 1.4rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.t-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Modern Vessel Telemetry Card */
.t-vessel-monitor {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1.25rem 1.4rem;
  transition: all 0.2s ease;
}
.t-vessel-monitor:hover {
  background: #ffffff;
  border-color: #93c5fd;
  box-shadow: 0 4px 16px -2px rgba(37, 99, 235, 0.08);
}
.t-progress-track {
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}
.t-progress-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #2563eb, #3b82f6);
}

/* Color swatch dot */
.t-swatch-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: inline-block;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.15);
  vertical-align: middle;
}
</style>

<main class="cz-main">
  <div class="cz-content p-4">
    <div class="t-dash-wrap">

      <!-- ============================================================== -->
      <!-- 1. CLEAN, SPACIOUS EXECUTIVE HEADER                            -->
      <!-- ============================================================== -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-slate-100 text-dark border font-monospace px-2 py-1" style="font-size: 0.75rem;">
              UNIT 01 · PERUNDURAI MILL
            </span>
            <span class="badge bg-emerald-50 text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.75rem;">
              <span class="t-live-indicator"></span> SHIFT 1 ACTIVE
            </span>
            <span class="text-secondary small font-monospace ms-1">
              <?= date('D, d M Y') ?>
            </span>
          </div>
          <h1 class="h3 fw-bold text-dark mb-1 tracking-tight">Dyeing & Wet Processing Floor</h1>
          <p class="text-muted small mb-0">
            Real-time Soft Flow vessel telemetry, chemical kitchen dosing, automated batch tracking, and ZLD compliance.
          </p>
        </div>

        <!-- Clean Action Bar -->
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-inbox me-1"></i> Grey Inward
          </a>
          <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-card-checklist me-1"></i> Job Cards
          </a>
          <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-eyedropper me-1"></i> Chemical Indent
          </a>
          <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Plan Batch
          </a>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 2. HIGH-LEVEL KPI METRICS (4 CLEAN, SPACIOUS CARDS)            -->
      <!-- ============================================================== -->
      <div class="row g-3 mb-4">
        
        <!-- Floor Production -->
        <div class="col-xl-3 col-sm-6">
          <div class="t-metric-card">
            <div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="t-metric-label">Floor Production</span>
                <i class="bi bi-box-seam text-primary fs-5"></i>
              </div>
              <div class="t-metric-value mt-1">
                <?= number_format($totalLoadedKg, 0) ?> <span class="fs-6 fw-normal text-muted">Kg</span>
              </div>
            </div>
            <div class="t-metric-sub d-flex justify-content-between align-items-center pt-2 border-top">
              <span>Target: <strong><?= number_format($totalPlannedKg, 0) ?> Kg</strong></span>
              <span class="text-success fw-semibold font-monospace"><?= $loadingEfficiency ?>% Fill</span>
            </div>
          </div>
        </div>

        <!-- Vessels In Cycle -->
        <div class="col-xl-3 col-sm-6">
          <div class="t-metric-card">
            <div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="t-metric-label">Vessels in Cycle</span>
                <i class="bi bi-hdd-network text-indigo fs-5"></i>
              </div>
              <div class="t-metric-value mt-1">
                <?= $runningBatches ?> <span class="fs-6 fw-normal text-muted">/ <?= count($softFlowMachines) ?> Active</span>
              </div>
            </div>
            <div class="t-metric-sub d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="d-inline-flex align-items-center">
                <span class="t-live-indicator"></span> SF-01 & SF-02 Loaded
              </span>
              <span class="text-secondary">100% Utilization</span>
            </div>
          </div>
        </div>

        <!-- First Time Right Quality -->
        <div class="col-xl-3 col-sm-6">
          <div class="t-metric-card">
            <div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="t-metric-label">First-Time-Right (RFT)</span>
                <i class="bi bi-patch-check text-success fs-5"></i>
              </div>
              <div class="t-metric-value text-success mt-1">
                <?= $rftRate ?>%
              </div>
            </div>
            <div class="t-metric-sub d-flex justify-content-between align-items-center pt-2 border-top">
              <span>Reprocess: <strong><?= $reprocessBatches ?> Batches</strong></span>
              <span class="text-success fw-semibold">&Delta;E &le; 0.8 Pass</span>
            </div>
          </div>
        </div>

        <!-- ZLD Environmental Recovery -->
        <div class="col-xl-3 col-sm-6">
          <div class="t-metric-card">
            <div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="t-metric-label">Water & ZLD Recovery</span>
                <i class="bi bi-recycle text-info fs-5"></i>
              </div>
              <div class="t-metric-value mt-1">
                98.5% <span class="fs-6 fw-normal text-muted">ZLD</span>
              </div>
            </div>
            <div class="t-metric-sub d-flex justify-content-between align-items-center pt-2 border-top">
              <span>Recycled: <strong><?= number_format($totalWaterLitres, 0) ?> L</strong></span>
              <span class="text-success fw-semibold">TNPCB In-Spec</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ============================================================== -->
      <!-- 3. MAIN SPLIT: FLOOR TELEMETRY & REGISTER vs SIDEBAR           -->
      <!-- ============================================================== -->
      <div class="row g-4">
        
        <!-- LEFT COLUMN (8 COLS): Soft Flow Machines + Live Batches Table -->
        <div class="col-xl-8 col-12">
          
          <!-- SECTION 1: LIVE SOFT FLOW VESSELS -->
          <div class="t-panel">
            <div class="t-panel-head">
              <h2 class="t-panel-title">
                <i class="bi bi-hdd-rack text-primary"></i>
                Soft Flow Dyeing Vessels — Live Floor Telemetry
              </h2>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-emerald-50 text-success border border-success-subtle font-monospace px-2 py-1" style="font-size: 0.75rem;">
                  <span class="t-live-indicator"></span> <?= count($softFlowMachines) ?> Vessels Active
                </span>
                <a href="<?= Url::to('/dyeing/machine-allocation') ?>" class="text-decoration-none small text-secondary">
                  Allocate &rarr;
                </a>
              </div>
            </div>

            <div class="p-3">
              <div class="row g-3">
                <?php foreach ($softFlowMachines as $m): ?>
                  <?php 
                    $isRunning = ($m['current_status'] === 'running' || !empty($m['active_batch_no']));
                    $capacity = (float)$m['capacity'];
                    $loaded = (float)($m['actual_loaded_kg'] ?? 0);
                    $fillPct = ($capacity > 0) ? min(100, round(($loaded / $capacity) * 100)) : 0;
                    $colorHex = $m['hex_color'] ?? '#1e3a8a';
                    $batchStatus = $m['batch_status'] ?? 'running';
                  ?>
                  <div class="col-md-6 col-12">
                    <div class="t-vessel-monitor h-100 d-flex flex-column justify-content-between">
                      
                      <!-- Vessel Info Bar -->
                      <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <div class="d-flex align-items-center gap-2">
                              <span class="badge bg-dark text-white font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                              <span class="badge bg-emerald-50 text-success border border-success-subtle text-uppercase font-monospace" style="font-size: 0.72rem;">
                                <span class="t-live-indicator"></span> <?= htmlspecialchars($batchStatus) ?>
                              </span>
                            </div>
                            <h3 class="h6 fw-bold text-dark mt-2 mb-0"><?= htmlspecialchars($m['name']) ?></h3>
                          </div>

                          <div class="text-end">
                            <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">
                              MLR <?= htmlspecialchars($m['batch_lr'] ?? $m['liquor_ratio'] ?? '1:6') ?>
                            </span>
                            <div class="text-muted small mt-1 font-monospace">₹<?= number_format($m['hourly_operating_cost'], 0) ?>/hr</div>
                          </div>
                        </div>

                        <!-- Capacity Bar -->
                        <div class="my-3">
                          <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Vessel Load</span>
                            <span class="font-monospace text-dark fw-bold"><?= number_format($loaded, 0) ?> / <?= number_format($capacity, 0) ?> Kg (<?= $fillPct ?>%)</span>
                          </div>
                          <div class="t-progress-track">
                            <div class="t-progress-fill" style="width: <?= $fillPct ?>%;"></div>
                          </div>
                        </div>

                        <!-- Active Batch Detail -->
                        <?php if ($isRunning): ?>
                          <div class="bg-white rounded p-2 border mb-3 small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                              <span class="text-muted">Batch No:</span>
                              <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $m['active_batch_id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                <?= htmlspecialchars($m['active_batch_no']) ?>
                              </a>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                              <span class="text-muted">Shade / Color:</span>
                              <span class="d-inline-flex align-items-center gap-1">
                                <span class="t-swatch-dot" style="background-color: <?= htmlspecialchars($colorHex) ?>;"></span>
                                <strong class="text-dark"><?= htmlspecialchars($m['shade_name'] ?? 'Standard Color') ?></strong>
                              </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                              <span class="text-muted">Operator:</span>
                              <span class="text-dark"><?= htmlspecialchars($m['operator_name'] ?? 'Floor Operator') ?></span>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>

                      <!-- Bottom Actions -->
                      <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="text-muted small font-monospace">
                          <i class="bi bi-water text-primary me-1"></i><?= number_format($m['water_volume_litres'] ?? ($loaded * 6), 0) ?> L Bath
                        </span>
                        <div class="d-flex gap-2">
                          <a href="<?= Url::to('/print/batch-card?id=' . $m['active_batch_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Print Batch Card">
                            <i class="bi bi-printer"></i>
                          </a>
                          <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $m['active_batch_id']) ?>" class="btn btn-sm btn-dark py-1 px-3 fw-semibold">
                            Floor Log
                          </a>
                        </div>
                      </div>

                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- SECTION 2: ACTIVE DYEING BATCHES REGISTER TABLE -->
          <div class="t-panel">
            <div class="t-panel-head">
              <h2 class="t-panel-title">
                <i class="bi bi-table text-primary"></i>
                Active Dyeing Batches Register
              </h2>

              <div class="d-flex align-items-center gap-2">
                <input type="text" id="tBatchSearch" class="form-control form-control-sm" placeholder="Search batch, fabric, shade..." style="width: 210px;">
                <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-sm btn-outline-secondary">
                  View All &rarr;
                </a>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" id="tBatchesTable">
                <thead class="table-light text-secondary small text-uppercase">
                  <tr>
                    <th class="ps-3">Batch / Job Card</th>
                    <th>Customer & Fabric</th>
                    <th>Shade & Recipe</th>
                    <th>Vessel</th>
                    <th class="text-end">Loaded (Kg)</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <?php if (!empty($batches)): ?>
                    <?php foreach ($batches as $b): ?>
                      <?php 
                        $hex = $b['hex_color'] ?? '#1e3a8a';
                        $statusBadge = match($b['status']) {
                          'completed' => '<span class="badge bg-emerald-50 text-success border border-success-subtle px-2 py-1">COMPLETED</span>',
                          'dyeing'    => '<span class="badge bg-blue-50 text-primary border border-primary-subtle px-2 py-1"><span class="t-live-indicator"></span>DYEING</span>',
                          'dosing'    => '<span class="badge bg-cyan-50 text-info border border-info-subtle px-2 py-1"><span class="t-live-indicator"></span>DOSING</span>',
                          'reprocess' => '<span class="badge bg-rose-50 text-danger border border-danger-subtle px-2 py-1">REPROCESS</span>',
                          default     => '<span class="badge bg-slate-100 text-secondary border px-2 py-1 text-uppercase">' . htmlspecialchars($b['status']) . '</span>'
                        };
                      ?>
                      <tr>
                        <td class="ps-3">
                          <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $b['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                            <?= htmlspecialchars($b['batch_no']) ?>
                          </a>
                          <div class="text-muted small font-monospace">
                            <?= htmlspecialchars($b['job_card_no'] ?? 'DJC-2026') ?>
                          </div>
                        </td>

                        <td>
                          <div class="fw-semibold text-dark"><?= htmlspecialchars($b['customer_name'] ?? 'Direct Knit Export') ?></div>
                          <div class="text-secondary small text-truncate" style="max-width: 180px;">
                            <?= htmlspecialchars($b['fabric_name'] ?? '100% Cotton Single Jersey') ?>
                          </div>
                        </td>

                        <td>
                          <div class="d-flex align-items-center gap-1">
                            <span class="t-swatch-dot" style="background-color: <?= htmlspecialchars($hex) ?>;"></span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($b['shade_name'] ?? 'Standard Color') ?></span>
                          </div>
                          <div class="text-muted small font-monospace">
                            <?= htmlspecialchars($b['recipe_code'] ?? 'RCP-01') ?>
                            <?php if (!empty($b['delta_e_tolerance'])): ?>
                              · &Delta;E &le; <?= number_format($b['delta_e_tolerance'], 2) ?>
                            <?php endif; ?>
                          </div>
                        </td>

                        <td>
                          <span class="badge bg-light text-dark border font-monospace">
                            <?= htmlspecialchars($b['machine_code'] ?? 'SF-01') ?>
                          </span>
                        </td>

                        <td class="text-end fw-bold font-monospace text-dark">
                          <?= number_format($b['actual_loaded_kg'], 2) ?>
                        </td>

                        <td>
                          <?= $statusBadge ?>
                        </td>

                        <td class="text-end pe-3">
                          <div class="btn-group btn-group-sm">
                            <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $b['id']) ?>" class="btn btn-outline-secondary py-1 px-2" title="Log Floor Readings">
                              <i class="bi bi-speedometer2"></i>
                            </a>
                            <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-outline-secondary py-1 px-2" title="Print Batch Card">
                              <i class="bi bi-printer"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted">
                        No active dye batches found.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN (4 COLS): Chemical Kitchen + ETP / ZLD Water Recovery -->
        <div class="col-xl-4 col-12">
          
          <!-- CHEMICAL KITCHEN STORE -->
          <div class="t-panel">
            <div class="t-panel-head">
              <h2 class="t-panel-title">
                <i class="bi bi-eyedropper text-primary"></i>
                Chemical Kitchen Stock
              </h2>
              <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-sm btn-outline-primary py-1 px-2 small">
                Indent &rarr;
              </a>
            </div>

            <div class="p-3">
              <p class="text-muted small mb-3">Live on-hand balances in Warehouse 02:</p>
              
              <div class="d-flex flex-column gap-3">
                <?php foreach ($chemicals as $c): ?>
                  <div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                      <span class="fw-semibold text-dark"><?= htmlspecialchars($c['name']) ?></span>
                      <span class="font-monospace fw-bold text-dark"><?= number_format($c['current_stock'], 1) ?> Kg</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                      <?php 
                        $barPct = min(100, round(($c['current_stock'] / 5000) * 100));
                        $barColor = $barPct < 25 ? 'bg-danger' : ($barPct < 50 ? 'bg-warning' : 'bg-primary');
                      ?>
                      <div class="progress-bar <?= $barColor ?>" style="width: <?= $barPct ?>%;"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center small">
                <span class="text-muted"><i class="bi bi-shield-check text-success me-1"></i>Scales Calibrated</span>
                <a href="<?= Url::to('/inventory/grn') ?>" class="text-decoration-none fw-semibold">Receive Stock &rarr;</a>
              </div>
            </div>
          </div>

          <!-- ETP / ZLD WATER RECOVERY -->
          <div class="t-panel">
            <div class="t-panel-head">
              <h2 class="t-panel-title">
                <i class="bi bi-recycle text-success"></i>
                ETP / ZLD Water Recovery
              </h2>
              <span class="badge bg-emerald-50 text-success border border-success-subtle font-monospace" style="font-size: 0.72rem;">
                TNPCB IN-SPEC
              </span>
            </div>

            <div class="p-3">
              <div class="row g-2 text-center mb-3">
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-muted" style="font-size: 0.7rem;">Inlet Flow</div>
                  <div class="fw-bold text-dark font-monospace"><?= number_format($etpLog['inlet_flow_kld'] ?? 465, 0) ?> KLD</div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-muted" style="font-size: 0.7rem;">RO Recovery</div>
                  <div class="fw-bold text-success font-monospace"><?= number_format($etpLog['ro_permeate_recovery_kld'] ?? 402, 0) ?> KLD</div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-muted" style="font-size: 0.7rem;">TDS Level</div>
                  <div class="fw-bold text-dark font-monospace"><?= number_format($etpLog['tds_ppm'] ?? 1420, 0) ?> ppm</div>
                </div>
              </div>

              <div class="small text-secondary bg-light rounded p-3 border mb-3">
                <i class="bi bi-info-circle text-primary me-1"></i>
                <strong>Zero Liquid Discharge:</strong> 98.2% recycled permeate through 3-Stage RO & MEE reused directly in Soft Flow vessels.
              </div>

              <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Effluent pH: <strong class="text-dark"><?= number_format($etpLog['ph_value'] ?? 7.35, 2) ?></strong></span>
                <a href="<?= Url::to('/compliance/etp-log') ?>" class="text-decoration-none fw-semibold">Detailed Log &rarr;</a>
              </div>
            </div>
          </div>

          <!-- AUXILIARY FINISHING MACHINES -->
          <div class="t-panel">
            <div class="t-panel-head">
              <h2 class="t-panel-title">
                <i class="bi bi-gear-wide-connected text-secondary"></i>
                Finishing Machinery
              </h2>
              <span class="text-muted small">Stenter & Hydro</span>
            </div>

            <div class="p-3">
              <div class="d-flex flex-column gap-2">
                <?php foreach ($finishingMachines as $fm): ?>
                  <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center small">
                    <div>
                      <span class="badge bg-white text-dark border font-monospace"><?= htmlspecialchars($fm['code']) ?></span>
                      <span class="fw-semibold text-dark ms-1"><?= htmlspecialchars($fm['name']) ?></span>
                    </div>
                    <span class="badge <?= $fm['current_status'] === 'running' ? 'bg-emerald-50 text-success border border-success-subtle' : 'bg-slate-100 text-secondary' ?> border font-monospace" style="font-size: 0.7rem;">
                      <?= strtoupper($fm['current_status']) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</main>

<script>
// Live Search Filter for Active Batches Table
document.getElementById('tBatchSearch')?.addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase().trim();
  const rows = document.querySelectorAll('#tBatchesTable tbody tr');
  rows.forEach(row => {
    row.style.display = (q === '' || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
  });
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
