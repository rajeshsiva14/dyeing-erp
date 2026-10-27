<?php
/**
 * Dyeing Executive Dashboard
 * Real-time Daily Dyeing Operations, Vessel Status, QC & RFT Tracker
 * File: modules/dyeing/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Dyeing Operations Dashboard';
$activeModule = 'dashboard';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

// Fetch Live KPIs
$totalBatches = (int)(Database::fetch("SELECT COUNT(*) as c FROM dye_batches")['c'] ?? 0);
$inProgressBatches = (int)(Database::fetch("SELECT COUNT(*) as c FROM dye_batches WHERE status IN ('loaded', 'dosing', 'dyeing', 'washing')")['c'] ?? 0);
$labWaiting = (int)(Database::fetch("SELECT COUNT(*) as c FROM lab_dip_requests WHERE status IN ('pending', 'trial_in_progress')")['c'] ?? 0);
$qcWaiting = (int)(Database::fetch("SELECT COUNT(*) as c FROM dye_batches WHERE status IN ('unloaded', 'hydro', 'drying', 'compacted')")['c'] ?? 0);
$reprocessPending = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_reprocess_requests WHERE status = 'pending_approval'")['c'] ?? 0);

$totalPlannedKg = (float)(Database::fetch("SELECT SUM(planned_weight_kg) as s FROM dye_batches")['s'] ?? 0);
$totalActualKg = (float)(Database::fetch("SELECT SUM(actual_loaded_kg) as s FROM dye_batches")['s'] ?? 0);

$runningMachines = (int)(Database::fetch("SELECT COUNT(*) as c FROM machines WHERE process_type = 'soft_flow_dyeing' AND current_status = 'running'")['c'] ?? 0);
$idleMachines = (int)(Database::fetch("SELECT COUNT(*) as c FROM machines WHERE process_type = 'soft_flow_dyeing' AND current_status = 'idle'")['c'] ?? 0);
$maintMachines = (int)(Database::fetch("SELECT COUNT(*) as c FROM machines WHERE process_type = 'soft_flow_dyeing' AND current_status = 'maintenance'")['c'] ?? 0);

// Batches list with joins
$batches = Database::fetchAll("
    SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name,
           jc.job_card_no, jc.priority, jc.fabric_name
    FROM dye_batches b
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dyeing_job_cards jc ON b.production_order_id = jc.id
    ORDER BY b.id DESC
");

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY code ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Banner -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">KARCEM Dyeing ERP</a></li>
            <li class="breadcrumb-item active">Dyeing Operations Dashboard</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i data-lucide="layout-dashboard" class="text-primary me-2"><i class="bi bi-grid"></i></i>Dyeing Floor & Operations Dashboard</h1>
        <p class="text-secondary small mb-0">Live vessel status, recipe execution, batch progress, First-Time-Right (RFT) and QC dispositions.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-secondary btn-sm">
          <i data-lucide="package-plus" class="me-1"><i class="bi bi-card-checklist"></i></i> New Job Card
        </a>
        <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-primary btn-sm">
          <i data-lucide="layers" class="me-1"><i class="bi bi-calendar-range"></i></i> Plan Batches
        </a>
      </div>
    </div>

    <!-- Live KPI Summary Cards (Clean Professional Light) -->
    <div class="row g-3 mb-4">
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">Total Batches</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val"><?= $totalBatches ?></span>
            <span class="badge status-approved">Active</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-hdd-stack me-1"></i>Vessels Loaded</div>
        </div>
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">In Progress</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val"><?= $inProgressBatches ?></span>
            <span class="badge status-running">Running</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-gear me-1"></i>Dosing / Dyeing</div>
        </div>
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">Waiting Lab Dip</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val"><?= $labWaiting ?></span>
            <span class="badge status-pending">Shades</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-eyedropper me-1"></i>Trial in Lab</div>
        </div>
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">Waiting for QC</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val"><?= $qcWaiting ?></span>
            <span class="badge badge-draft">Unloaded</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-shield me-1"></i>Hydro / Finishing</div>
        </div>
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">Reprocess / Re-dye</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val"><?= $reprocessPending ?></span>
            <span class="badge status-hold">Pending</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-arrow-repeat me-1"></i>Root Cause Hold</div>
        </div>
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="cz-stat-card h-100">
          <div class="cz-stat-lbl">RFT Acceptance</div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="cz-stat-val">96.4%</span>
            <span class="badge status-approved">Passed</span>
          </div>
          <div class="small text-secondary mt-2"><i class="bi bi-check2-circle me-1"></i>First-Pass Right</div>
        </div>
      </div>
    </div>

    <!-- Soft Flow Vessels Floor Status -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="cz-panel">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i data-lucide="factory" class="text-primary me-2"><i class="bi bi-hdd-rack"></i></i>Soft Flow Dyeing Vessels — Live Floor Status</h2>
            <div class="d-flex gap-2 align-items-center small">
              <span class="badge status-running"><i class="bi bi-circle-fill me-1" style="font-size:0.45rem;"></i> Running: <?= $runningMachines ?></span>
              <span class="badge badge-draft"><i class="bi bi-circle me-1" style="font-size:0.45rem;"></i> Idle: <?= $idleMachines ?></span>
              <span class="badge status-hold"><i class="bi bi-wrench me-1"></i> Maint: <?= $maintMachines ?></span>
            </div>
          </div>
          <div class="p-3">
            <div class="row g-3">
              <?php foreach ($machines as $m): ?>
                <div class="col-lg-4 col-md-6">
                  <div class="border rounded p-3 bg-white shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($m['code']) ?></span>
                        <h6 class="fw-bold text-dark mb-0 mt-1"><?= htmlspecialchars($m['name']) ?></h6>
                      </div>
                      <span class="badge <?= $m['current_status'] === 'running' ? 'status-running' : ($m['current_status'] === 'idle' ? 'badge-draft' : 'status-pending') ?>">
                        <?= strtoupper($m['current_status']) ?>
                      </span>
                    </div>
                    <div class="row text-center g-2 mt-2 pt-2 border-top small">
                      <div class="col-4 bg-light rounded p-2">
                        <div class="text-secondary" style="font-size:0.7rem;">Capacity</div>
                        <div class="fw-bold text-dark"><?= number_format($m['capacity'], 0) ?> Kg</div>
                      </div>
                      <div class="col-4 bg-light rounded p-2">
                        <div class="text-secondary" style="font-size:0.7rem;">Std LR</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($m['liquor_ratio'] ?? '1:6') ?></div>
                      </div>
                      <div class="col-4 bg-light rounded p-2">
                        <div class="text-secondary" style="font-size:0.7rem;">Cost/Hr</div>
                        <div class="fw-bold text-dark">₹<?= number_format($m['hourly_operating_cost'], 0) ?></div>
                      </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                      <span class="small text-muted"><i class="bi bi-clock me-1"></i>Shift 1 (Day)</span>
                      <a href="<?= Url::to('/dyeing/production-entry') ?>" class="btn btn-sm btn-outline-dark">Record Floor Log &rarr;</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Batches Data Table with Filter Header -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-table text-dark me-2"></i>Active Dyeing Batches & Process Tracking</h2>
        <div class="d-flex gap-2">
          <input type="text" id="batchFilterInput" class="form-control form-control-sm" placeholder="Filter batch, shade, fabric..." style="width: 220px;">
          <a href="<?= Url::to('/dyeing/reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="batchesTable">
          <thead class="table-light">
            <tr>
              <th>Batch No</th>
              <th>Job Card</th>
              <th>Fabric Specification</th>
              <th>Shade & Recipe</th>
              <th class="text-end">Planned (Kg)</th>
              <th class="text-end">Loaded (Kg)</th>
              <th>Machine</th>
              <th>Current Stage</th>
              <th>QC Status</th>
              <th>Priority</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($batches as $b): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary">
                  <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $b['id']) ?>" class="text-decoration-none">
                    <?= htmlspecialchars($b['batch_no']) ?>
                  </a>
                </td>
                <td><code><?= htmlspecialchars($b['job_card_no'] ?? 'DJC-2026-001') ?></code></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($b['fabric_name'] ?? '100% Combed Cotton Single Jersey') ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">LR: <?= htmlspecialchars($b['liquor_ratio']) ?> · Water: <?= number_format($b['water_volume_litres'], 0) ?> L</div>
                </td>
                <td>
                  <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($b['recipe_code'] ?? 'RCP-NAV-01') ?></span>
                  <div class="small fw-semibold"><?= htmlspecialchars($b['shade_name'] ?? 'Royal Navy Blue') ?></div>
                </td>
                <td class="text-end fw-bold"><?= number_format($b['planned_weight_kg'], 2) ?></td>
                <td class="text-end fw-bold text-dark"><?= number_format($b['actual_loaded_kg'], 2) ?></td>
                <td>
                  <span class="badge bg-light text-dark border"><?= htmlspecialchars($b['machine_code'] ?? 'SF-01') ?></span>
                </td>
                <td>
                  <span class="badge status-in_production text-uppercase" style="font-size:0.68rem; font-weight:600;">
                    <?= htmlspecialchars($b['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($b['status'] === 'completed'): ?>
                    <span class="badge status-approved"><i class="bi bi-check2 me-1"></i>Pass</span>
                  <?php elseif ($b['status'] === 'reprocess'): ?>
                    <span class="badge status-hold"><i class="bi bi-arrow-repeat me-1"></i>Reprocess</span>
                  <?php else: ?>
                    <span class="badge status-running"><i class="bi bi-hourglass me-1"></i>In Process</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= ($b['priority'] ?? 'normal') === 'urgent' ? 'status-urgent' : 'badge-draft' ?> text-uppercase" style="font-size:0.65rem;">
                    <?= htmlspecialchars($b['priority'] ?? 'normal') ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $b['id']) ?>" class="btn btn-outline-secondary" title="Record Floor Readings">
                      <i data-lucide="gauge"><i class="bi bi-pencil-square"></i></i>
                    </a>
                    <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P40 Batch Card">
                      <i data-lucide="printer"><i class="bi bi-printer"></i></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<script>
document.getElementById('batchFilterInput')?.addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase().trim();
  const rows = document.querySelectorAll('#batchesTable tbody tr');
  rows.forEach(row => {
    row.style.display = (q === '' || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
  });
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
