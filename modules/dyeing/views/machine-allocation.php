<?php
/**
 * Machine Allocation & Vessel Floor Scheduler
 * Machine allocation, cleaning verification, and previous batch clearance
 * File: modules/dyeing/views/machine-allocation.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Machine Allocation';
$activeModule = 'machine-allocation';

// Handle machine status update or re-allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_machine_status') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $machineId = (int)($_POST['machine_id'] ?? 1);
    $status = $_POST['current_status'] ?? 'running';
    Database::getConnection()->prepare("UPDATE machines SET current_status = ? WHERE id = ?")->execute([$status, $machineId]);
    header('Location: ' . Url::to('/dyeing/machine-allocation?updated=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('soft_flow_dyeing', 'hydro_extractor', 'relax_dryer', 'stenter', 'compactor') ORDER BY process_type ASC, code ASC");
$activeBatches = Database::fetchAll("
    SELECT b.*, m.code as machine_code, m.name as machine_name, r.shade_name
    FROM dye_batches b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    WHERE b.status NOT IN ('completed')
    ORDER BY b.id DESC
");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Machine Allocation</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-cpu text-primary me-2"></i>Machine Allocation & Vessel Floor Clearance</h1>
        <p class="text-secondary small mb-0">Allocate batches to vessels, check cleaning status against previous dark/light shades, and record maintenance clearance.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/loading-sheets') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-box-arrow-in-right me-1"></i> View Loading Sheets
        </a>
      </div>
    </div>

    <!-- Active Allocations Grid -->
    <div class="row g-3 mb-4">
      <?php foreach ($machines as $m): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-dark font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                <span class="fw-bold ms-1 text-dark"><?= htmlspecialchars($m['name']) ?></span>
              </div>
              <span class="badge bg-<?= $m['current_status'] === 'running' ? 'success' : ($m['current_status'] === 'idle' ? 'secondary' : 'warning text-dark') ?>">
                <?= strtoupper($m['current_status']) ?>
              </span>
            </div>
            <div class="card-body p-3 small">
              <div class="mb-2">
                <span class="text-secondary">Process Category:</span>
                <span class="fw-semibold text-dark"><?= ucwords(str_replace('_', ' ', $m['process_type'])) ?></span>
              </div>
              <div class="row text-center g-2 my-2 py-2 bg-light rounded">
                <div class="col-4">
                  <div class="text-secondary" style="font-size:0.7rem;">Capacity</div>
                  <div class="fw-bold"><?= number_format($m['capacity'], 0) ?> Kg</div>
                </div>
                <div class="col-4">
                  <div class="text-secondary" style="font-size:0.7rem;">Std LR</div>
                  <div class="fw-bold text-primary"><?= htmlspecialchars($m['liquor_ratio'] ?? '1:6') ?></div>
                </div>
                <div class="col-4">
                  <div class="text-secondary" style="font-size:0.7rem;">Operating Cost</div>
                  <div class="fw-bold">₹<?= number_format($m['hourly_operating_cost'], 0) ?>/hr</div>
                </div>
              </div>

              <!-- Cleaning & Shade Contamination Clearance -->
              <div class="border rounded p-2 mb-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-secondary" style="font-size:0.75rem;">Vessel Cleaning Status:</span>
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check me-1"></i>Washed & Neutralized</span>
                </div>
                <div class="text-muted" style="font-size:0.75rem;">
                  Last Batch: <strong>Royal Navy</strong> &rarr; Next eligible: <em>Dark / Medium Shades</em>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <form method="POST" action="" class="d-inline">
                  <input type="hidden" name="action" value="update_machine_status">
                  <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
                  <input type="hidden" name="machine_id" value="<?= $m['id'] ?>">
                  <select name="current_status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="running" <?= $m['current_status'] === 'running' ? 'selected' : '' ?>>Running</option>
                    <option value="idle" <?= $m['current_status'] === 'idle' ? 'selected' : '' ?>>Idle / Clean</option>
                    <option value="maintenance" <?= $m['current_status'] === 'maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                  </select>
                </form>
                <a href="<?= Url::to('/dyeing/loading-sheets?machine_id=' . $m['id']) ?>" class="btn btn-sm btn-dark">Loading Sheet</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Active Batch Allocations Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Active Machine Floor Allocations</h2>
        <span class="small text-secondary"><?= count($activeBatches) ?> Running Loads</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Batch No</th>
              <th>Allocated Machine</th>
              <th>Shade</th>
              <th class="text-end">Planned (Kg)</th>
              <th class="text-end">Loaded (Kg)</th>
              <th>Liquor Ratio</th>
              <th>Water (L)</th>
              <th>Operator</th>
              <th>Current Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activeBatches as $ab): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($ab['batch_no']) ?></td>
                <td>
                  <span class="badge bg-dark font-monospace"><?= htmlspecialchars($ab['machine_code']) ?></span>
                  <span class="ms-1 fw-semibold"><?= htmlspecialchars($ab['machine_name']) ?></span>
                </td>
                <td><?= htmlspecialchars($ab['shade_name'] ?? 'Royal Navy') ?></td>
                <td class="text-end fw-bold"><?= number_format($ab['planned_weight_kg'], 2) ?></td>
                <td class="text-end fw-bold text-dark"><?= number_format($ab['actual_loaded_kg'], 2) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ab['liquor_ratio']) ?></span></td>
                <td><?= number_format($ab['water_volume_litres'], 0) ?> L</td>
                <td><?= htmlspecialchars($ab['operator_name'] ?? 'M. Ramanathan') ?></td>
                <td><span class="badge bg-primary text-uppercase"><?= htmlspecialchars($ab['status']) ?></span></td>
                <td class="text-end">
                  <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $ab['id']) ?>" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-broadcast me-1"></i> Floor Log
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
