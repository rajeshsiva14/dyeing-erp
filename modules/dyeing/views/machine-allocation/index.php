<?php
/**
 * Dedicated Register View: Machine Allocation & Vessel Floor Clearance
 * File: modules/dyeing/views/machine-allocation/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Machine Allocation';
$activeModule = 'machine-allocation';

// Handle quick machine status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_machine_status') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $machineId = (int)($_POST['machine_id'] ?? 1);
    $status = $_POST['current_status'] ?? 'running';
    Database::getConnection()->prepare("UPDATE machines SET current_status = ? WHERE id = ?")->execute([$status, $machineId]);
    header('Location: ' . Url::to('/dyeing/machine-allocation?updated=1'));
    exit;
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('soft_flow_dyeing', 'hydro_extractor', 'relax_dryer', 'stenter', 'compactor') ORDER BY process_type ASC, code ASC");
$activeBatches = Database::fetchAll("
    SELECT b.*, m.code as machine_code, m.name as machine_name, r.shade_name
    FROM dye_batches b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    WHERE b.status NOT IN ('completed')
    ORDER BY b.id DESC
");

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
            <li class="breadcrumb-item active">Machine Allocation</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-cpu text-primary me-2"></i>Machine Allocation & Vessel Floor Clearance</h1>
        <p class="text-secondary small mb-0">Allocate batches to vessels, check cleaning status against previous dark/light shades, and record maintenance clearance.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/machine-allocation/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Allocate Vessel
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['updated']) || isset($_GET['allocated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Machine status and vessel allocation successfully updated on the production floor.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Active Allocations Grid (Click card or button to edit) -->
    <div class="row g-3 mb-4">
      <?php foreach ($machines as $m): ?>
        <?php 
          $status = $m['current_status'] ?? 'idle';
          $statusBadgeClass = ($status === 'running') ? 'bg-primary' : (($status === 'idle') ? 'bg-success' : 'bg-warning text-dark');
          $statusIcon = ($status === 'running') ? 'bi-play-circle-fill' : (($status === 'idle') ? 'bi-check-circle-fill' : 'bi-tools');
          $statusLabel = ($status === 'running') ? 'RUNNING' : (($status === 'idle') ? 'AVAILABLE' : 'MAINTENANCE');
          $editUrl = Url::to('/dyeing/machine-allocation/edit?id=' . $m['id']);
        ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-sm border h-100 cz-clickable-row" data-href="<?= $editUrl ?>" style="border-radius: 14px; border-color: #E2E8F0 !important; cursor: pointer;" title="Click anywhere to update this machine">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                <a href="<?= $editUrl ?>" class="fw-bold text-dark text-decoration-none" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem;"><?= htmlspecialchars($m['name']) ?></a>
              </div>
              <span class="badge <?= $statusBadgeClass ?>">
                <i class="bi <?= $statusIcon ?> me-1"></i> <?= $statusLabel ?>
              </span>
            </div>
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-secondary fw-medium">Process Category:</span>
                <span class="badge bg-light text-dark border"><?= ucwords(str_replace('_', ' ', $m['process_type'])) ?></span>
              </div>

              <!-- 3 Key Machine Metrics -->
              <div class="row text-center g-2 my-2 py-2 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                <div class="col-4 border-end">
                  <div class="text-secondary small" style="font-size:0.7rem; text-transform: uppercase; font-weight: 600;">Capacity</div>
                  <div class="fw-bold text-dark font-monospace" style="font-size: 0.92rem;"><?= number_format($m['capacity'], 0) ?> Kg</div>
                </div>
                <div class="col-4 border-end">
                  <div class="text-secondary small" style="font-size:0.7rem; text-transform: uppercase; font-weight: 600;">Std LR</div>
                  <div class="fw-bold text-primary font-monospace" style="font-size: 0.92rem;"><?= htmlspecialchars($m['liquor_ratio'] ?? '1:6') ?></div>
                </div>
                <div class="col-4">
                  <div class="text-secondary small" style="font-size:0.7rem; text-transform: uppercase; font-weight: 600;">Cost/Hr</div>
                  <div class="fw-bold text-dark font-monospace" style="font-size: 0.92rem;">₹<?= number_format($m['hourly_operating_cost'], 0) ?></div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top small">
                <span class="text-secondary">Chambers: <strong><?= $m['spring_or_tubes'] ?? 2 ?> Ports</strong></span>
                <button type="button" class="btn btn-sm btn-outline-dark" onclick="event.stopPropagation(); openStatusModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['code']) ?>', '<?= $status ?>');">
                  <i class="bi bi-toggles me-1"></i> Change State
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Active Floor Allocations Table (Click anywhere on row to update) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-dark me-2"></i>Active Floor Batch Allocations</h2>
        <span class="small text-secondary"><?= count($activeBatches) ?> Active Vessel Batches</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Batch No</th>
              <th>Vessel / Machine</th>
              <th>Shade & Recipe</th>
              <th class="text-end">Loaded Weight</th>
              <th>Operator</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($activeBatches)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No active batches allocated on the machine floor.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($activeBatches as $ab): ?>
                <?php $editUrl = Url::to('/dyeing/machine-allocation/edit?id=' . $ab['machine_id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this machine allocation">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($ab['batch_no']) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($ab['machine_code']) ?></span>
                    <span class="fw-semibold text-dark ms-1"><?= htmlspecialchars($ab['machine_name']) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($ab['shade_name'] ?? 'N/A') ?></div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark">
                    <?= number_format($ab['actual_loaded_kg'] ?: $ab['planned_weight_kg'], 1) ?> Kg
                  </td>
                  <td><?= htmlspecialchars($ab['operator_name'] ?? 'Assigned') ?></td>
                  <td>
                    <span class="badge <?= $ab['status'] === 'in_progress' ? 'bg-primary' : 'bg-dark' ?> text-uppercase">
                      <?= htmlspecialchars($ab['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/loading-sheet?id=' . $ab['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print Vessel Sheet" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Quick State Change Modal -->
<div class="modal fade" id="machineStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold">Update Vessel State</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        <form id="stateForm" method="POST" action="<?= Url::to('/dyeing/machine-allocation') ?>">
          <?= CSRF::getField() ?>
          <input type="hidden" name="action" value="update_machine_status">
          <input type="hidden" name="machine_id" id="modalMachineId">
          <div class="mb-3">
            <label class="form-label small text-secondary fw-semibold">Vessel Code</label>
            <input type="text" id="modalMachineCode" class="form-control form-control-sm font-monospace fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small text-secondary fw-semibold">New Status</label>
            <select name="current_status" id="modalMachineStatus" class="form-select form-select-sm">
              <option value="running">Running (Processing Batch)</option>
              <option value="idle">Idle (Available for Loading)</option>
              <option value="maintenance">Maintenance / Wash Down</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer p-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="stateForm" class="btn btn-dark btn-sm">Update State</button>
      </div>
    </div>
  </div>
</div>

<script>
function openStatusModal(id, code, status) {
  document.getElementById('modalMachineId').value = id;
  document.getElementById('modalMachineCode').value = code;
  document.getElementById('modalMachineStatus').value = status;
  new bootstrap.Modal(document.getElementById('machineStatusModal')).show();
}
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
