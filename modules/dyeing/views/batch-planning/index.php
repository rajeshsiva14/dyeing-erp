<?php
/**
 * Dedicated Register View: Dyeing Batch Planning & Machine Grouping
 * File: modules/dyeing/views/batch-planning/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Batch Planning';
$activeModule = 'batch-planning';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete/Cancel Batch Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $delId = (int)($_POST['id'] ?? 0);
    Database::query("DELETE FROM dye_batches WHERE id = ? AND company_id = ? AND status = 'planned'", [$delId, $companyId]);
    header('Location: ' . Url::to('/dyeing/batch-planning?deleted=1'));
    exit;
}

$jobCards = Database::fetchAll("SELECT jc.*, c.name as customer_name FROM dyeing_job_cards jc JOIN customers c ON jc.customer_id = c.id WHERE jc.status IN ('approved', 'planned') ORDER BY jc.id DESC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY capacity DESC");
$plannedBatches = Database::fetchAll("
    SELECT b.*, m.code as machine_code, m.name as machine_name, r.shade_name, r.recipe_code
    FROM dye_batches b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    WHERE b.company_id = ?
    ORDER BY b.id DESC
", [$companyId]);

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
            <li class="breadcrumb-item active">Batch Planning</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-calendar-range text-dark me-2"></i>Dyeing Batch Planning & Machine Grouping</h1>
        <p class="text-secondary small mb-0">Group compatible customer orders, verify machine capacity (Min/Max load), liquor ratio, and schedule vessels.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/batch-planning/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Create Production Batch
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing batch plan and vessel parameters updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Planned batch removed from vessel schedule.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Machine Capacity & Availability Cards -->
    <div class="row g-3 mb-4">
      <?php foreach ($machines as $m): ?>
        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge badge-bw font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                <span class="badge <?= $m['current_status'] === 'running' ? 'badge-bw-dark' : 'badge-bw' ?>">
                  <?= strtoupper($m['current_status']) ?>
                </span>
              </div>
              <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($m['name']) ?></h6>
              <div class="row text-center g-2 mt-2 pt-2 border-top small">
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size:0.7rem;">Rated Cap</div>
                  <div class="fw-bold"><?= $m['capacity'] ?> Kg</div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size:0.7rem;">Liquor Ratio</div>
                  <div class="fw-bold font-monospace"><?= $m['liquor_ratio'] ?></div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size:0.7rem;">Chambers</div>
                  <div class="fw-bold"><?= $m['spring_or_tubes'] ?? 2 ?> Ports</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Scheduled Batches Register (Click anywhere on row to update) -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers text-dark me-2"></i>Scheduled Batches Register</h2>
        <span class="small text-secondary"><?= count($plannedBatches) ?> Active / Scheduled Batches</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Batch No</th>
              <th>Machine / Vessel</th>
              <th>Shade & Recipe</th>
              <th class="text-end">Planned Wt (Kg)</th>
              <th class="text-center">MLR</th>
              <th class="text-end">Bath Vol (L)</th>
              <th>Operator</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($plannedBatches)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No batches planned yet. Select an order below to create a batch.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($plannedBatches as $pb): ?>
                <?php $editUrl = Url::to('/dyeing/batch-planning/edit?id=' . $pb['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this batch plan">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($pb['batch_no']) ?></a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($pb['machine_code']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($pb['machine_name']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($pb['shade_name'] ?? 'N/A') ?></div>
                    <code class="text-secondary"><?= htmlspecialchars($pb['recipe_code'] ?? 'N/A') ?></code>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($pb['planned_weight_kg'], 1) ?></td>
                  <td class="text-center font-monospace"><?= htmlspecialchars($pb['liquor_ratio'] ?? '1:6') ?></td>
                  <td class="text-end font-monospace"><?= number_format($pb['water_volume_litres'], 0) ?> L</td>
                  <td><?= htmlspecialchars($pb['operator_name'] ?? 'Assigned') ?></td>
                  <td>
                    <span class="badge <?= $pb['status'] === 'running' ? 'bg-primary' : 'bg-dark' ?> text-uppercase">
                      <?= htmlspecialchars($pb['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/loading-sheet?id=' . $pb['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print Sheet" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger" title="Cancel/Delete Plan" onclick="event.stopPropagation(); openDeleteModal(<?= $pb['id'] ?>, '<?= htmlspecialchars($pb['batch_no']) ?>');">
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
    </div>

    <!-- Eligible Job Cards Table Ready for Batching -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-dark me-2"></i>Approved Job Cards Ready for Batch Allocation</h2>
        <span class="small text-secondary"><?= count($jobCards) ?> Eligible Orders</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Job Card No</th>
              <th>Customer / PO</th>
              <th>Fabric Details</th>
              <th>Shade & Route</th>
              <th class="text-end">Weight (Kg)</th>
              <th class="text-center">Rolls</th>
              <th>Due Date</th>
              <th>Status</th>
              <th class="text-end">Batch Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jobCards as $jc): ?>
              <?php $planUrl = Url::to('/dyeing/batch-planning/create?job_card_id=' . $jc['id']); ?>
              <tr class="cz-clickable-row" data-href="<?= $planUrl ?>" title="Click anywhere to create batch for this order">
                <td class="fw-bold font-monospace text-primary">
                  <a href="<?= $planUrl ?>" class="text-decoration-none"><?= htmlspecialchars($jc['job_card_no']) ?></a>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($jc['customer_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">PO: <?= htmlspecialchars($jc['customer_po'] ?: 'Direct PO') ?></div>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($jc['fabric_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Lot: <?= htmlspecialchars($jc['grey_lot_no'] ?: 'Allocated') ?></div>
                </td>
                <td>
                  <span class="badge badge-bw font-monospace"><?= htmlspecialchars($jc['shade_code']) ?></span>
                  <div class="small fw-semibold"><?= htmlspecialchars($jc['shade_name']) ?></div>
                </td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($jc['required_qty_kg'], 2) ?></td>
                <td class="text-center fw-semibold"><?= $jc['roll_count'] ?></td>
                <td><?= date('d M Y', strtotime($jc['required_date'])) ?></td>
                <td>
                  <span class="badge <?= $jc['status'] === 'planned' ? 'badge-bw-dark' : 'badge-bw' ?>">
                    <?= strtoupper($jc['status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?= $planUrl ?>" class="btn btn-sm btn-dark" onclick="event.stopPropagation();">
                    <i class="bi bi-plus-circle me-1"></i> Plan Batch
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBatchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold">Cancel Batch Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        Are you sure you want to cancel planned batch <strong id="deleteBatchNo"></strong>? This will release the allocated vessel capacity.
      </div>
      <div class="modal-footer p-2">
        <form method="POST" action="<?= Url::to('/dyeing/batch-planning') ?>">
          <?= CSRF::getField() ?>
          <input type="hidden" name="action" value="delete_batch">
          <input type="hidden" name="id" id="deleteBatchId">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Keep</button>
          <button type="submit" class="btn btn-danger btn-sm">Yes, Cancel Plan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openDeleteModal(id, batchNo) {
  document.getElementById('deleteBatchId').value = id;
  document.getElementById('deleteBatchNo').textContent = batchNo;
  new bootstrap.Modal(document.getElementById('deleteBatchModal')).show();
}
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
