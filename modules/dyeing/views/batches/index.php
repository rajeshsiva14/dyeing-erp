<?php
/**
 * Dyeing Batches & Soft Flow Vessels Register
 * Dedicated list & register view for dyeing batches.
 * File: modules/dyeing/views/batches.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Batches & Vessel Floor';
$activeModule = 'batches';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to delete batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $batchId = (int)($_POST['batch_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM dye_batches WHERE id = ? AND company_id = ?", [$batchId, $companyId]);
    if ($target) {
        // Free machine if running
        Database::execute("UPDATE machines SET current_status = 'available' WHERE id = ? AND company_id = ?", [$target['machine_id'], $companyId]);
        Database::execute("DELETE FROM dye_batches WHERE id = ? AND company_id = ?", [$batchId, $companyId]);
        header('Location: ' . Url::to('/dyeing/batches?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dye_batches WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$batches = Database::fetchAll("
    SELECT b.*, r.recipe_code, r.shade_name, r.liquor_ratio as std_lr, m.code as machine_code, m.name as machine_name, m.capacity as machine_cap 
    FROM dye_batches b 
    JOIN dye_recipes r ON b.dye_recipe_id = r.id 
    JOIN machines m ON b.machine_id = m.id 
    WHERE b.company_id = ?
    ORDER BY b.id DESC 
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

$machines = Database::fetchAll("SELECT * FROM machines WHERE company_id = ? AND process_type = 'soft_flow_dyeing' ORDER BY code ASC", [$companyId]);
$parameters = Database::fetchAll("SELECT * FROM dye_batch_parameters WHERE company_id = ? ORDER BY recorded_at DESC LIMIT 10", [$companyId]);

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
            <li class="breadcrumb-item active">Dye Batches</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-droplet text-dark me-2"></i>Dyeing Batch Cards & Machine Loading</h1>
        <p class="text-secondary small mb-0">High-temperature Soft Flow dyeing vessels, recipe execution, and real-time process parameter logging.</p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <button type="button" onclick="exportTableToCSV('batchesTable', 'dye_batches_register.csv')" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
        </button>
        <a href="<?= Url::to('/dyeing/batches-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Load New Batch
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> New Dyeing Batch loaded into vessel successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing Batch updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Dyeing Batch deleted and vessel status restored.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Active Vessels Status Cards -->
    <div class="row g-3 mb-4">
      <?php foreach ($machines as $m): ?>
        <div class="col-md-6">
          <div class="card shadow-sm border-0">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-dark fs-6 font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                  <span class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></span>
                </div>
                <span class="badge <?= ($m['current_status'] === 'running') ? 'bg-primary' : 'bg-success' ?>">
                  <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?= strtoupper(htmlspecialchars($m['current_status'])) ?>
                </span>
              </div>
              <div class="row text-center g-2 mt-1">
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Capacity</div>
                  <div class="fw-bold text-dark"><?= number_format($m['capacity'], 0) ?> Kg</div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Liquor Ratio</div>
                  <div class="fw-bold text-primary"><?= htmlspecialchars($m['liquor_ratio'] ?? '1:5') ?></div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Operating Cost</div>
                  <div class="fw-bold text-dark">₹<?= number_format($m['hourly_operating_cost'], 0) ?>/hr</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Batches Table Panel (Click any row to edit) -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers-fill text-dark me-2"></i>Dyeing Batch Records</h2>
        <span class="small text-secondary"><?= count($batches) ?> Batches on page (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table id="batchesTable" class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Batch Number</th>
              <th>Vessel</th>
              <th>Shade & Recipe</th>
              <th class="text-end">Planned (Kg)</th>
              <th class="text-end">Loaded (Kg)</th>
              <th class="text-end">Output (Kg)</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($batches)): ?>
              <tr>
                <td colspan="8" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No dyeing batch records found. Click "Load New Batch" to load a vessel.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($batches as $b): ?>
                <?php
                  $bst = strtolower($b['status'] ?? 'planned');
                  $bstBadge = match($bst) {
                    'dyeing', 'running', 'loaded' => 'badge bg-primary',
                    'completed' => 'badge bg-success',
                    'failed' => 'badge bg-danger',
                    default => 'badge bg-warning',
                  };
                  $editUrl = Url::to('/dyeing/batches-edit?id=' . $b['id']);
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this batch">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($b['batch_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['machine_code']) ?></span></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($b['shade_name']) ?></div>
                    <span class="text-secondary small" style="font-size: 0.75rem;"><?= htmlspecialchars($b['recipe_code']) ?> &bull; Ratio: <?= htmlspecialchars($b['liquor_ratio']) ?></span>
                  </td>
                  <td class="text-end font-monospace"><?= number_format($b['planned_weight_kg'], 1) ?></td>
                  <td class="text-end font-monospace fw-semibold text-dark"><?= number_format($b['actual_loaded_kg'], 1) ?></td>
                  <td class="text-end font-monospace text-success fw-bold"><?= $b['output_weight_kg'] > 0 ? number_format($b['output_weight_kg'], 1) : '-' ?></td>
                  <td><span class="<?= $bstBadge ?>"><?= strtoupper(str_replace('_', ' ', $b['status'])) ?></span></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P40 Batch Card" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger js-del-batch" data-id="<?= $b['id'] ?>" data-no="<?= htmlspecialchars($b['batch_no']) ?>" title="Delete Batch" onclick="event.stopPropagation();">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Server-Side Pagination Bar -->
      <div class="cz-panel-foot d-flex flex-wrap justify-content-between align-items-center p-3 border-top gap-2 bg-light">
        <div class="small text-secondary">
          Showing <span class="fw-bold text-dark"><?= min($totalRecords, $offset + 1) ?></span> to 
          <span class="fw-bold text-dark"><?= min($totalRecords, $offset + $limit) ?></span> of 
          <span class="fw-bold text-dark"><?= $totalRecords ?></span> batches
        </div>
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>">Previous</a>
              </li>
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $p ?>&limit=<?= $limit ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </section>

    <!-- Recent Process Readings Log -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>Time-Series Process Parameter Logs (Temperature °C & pH)</h2>
        <span class="badge bg-success text-white">Live Floor Telemetry</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Batch ID</th>
              <th>Process Step</th>
              <th>Set / Actual Temp (°C)</th>
              <th>Set / Actual pH</th>
              <th>Cycle Time</th>
              <th>Logged At</th>
              <th>Operator Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($parameters)): ?>
              <tr>
                <td colspan="7" class="text-center py-3 text-secondary">No parameter telemetry logged yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($parameters as $p): ?>
                <tr>
                  <td class="fw-bold">#BAT-<?= $p['dye_batch_id'] ?></td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($p['process_step']) ?></td>
                  <td>
                    <span class="text-muted"><?= $p['set_temperature_c'] ?>°C</span> &rarr;
                    <span class="fw-bold text-danger"><?= $p['actual_temperature_c'] ?>°C</span>
                  </td>
                  <td>
                    <span class="text-muted"><?= $p['set_ph'] ?></span> &rarr;
                    <span class="fw-bold text-primary"><?= $p['actual_ph'] ?></span>
                  </td>
                  <td><?= $p['cycle_time_minutes'] ?> mins</td>
                  <td class="font-monospace"><?= date('d M H:i', strtotime($p['recorded_at'])) ?></td>
                  <td class="text-secondary"><?= htmlspecialchars($p['operator_notes'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteBatchModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Dyeing Batch
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/batches') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_batch">
            <input type="hidden" name="batch_id" id="del_batch_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2">Are you sure you want to delete dyeing batch:</p>
              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_batch_no"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> Vessel status will be restored to available.
              </p>
            </div>

            <div class="modal-footer bg-light p-3">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm px-4">
                <i class="bi bi-trash me-1"></i> Confirm Delete
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const delModalEl = document.getElementById('deleteBatchModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      // Delete button click
      document.querySelectorAll('.js-del-batch').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_batch_id').value = this.dataset.id;
          document.getElementById('del_batch_no').textContent = this.dataset.no;
          if (delModal) delModal.show();
        });
      });
    });
    </script>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
