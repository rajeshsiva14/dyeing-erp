<?php
/**
 * Dedicated Register View: External Dyeing Job-Work Challan & Returns
 * File: modules/dyeing/views/job-work/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Job-Work Challans';
$activeModule = 'job-work';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Challan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_challan') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $delId = (int)($_POST['id'] ?? 0);
    Database::query("DELETE FROM dyeing_job_work_challans WHERE id = ? AND company_id = ?", [$delId, $companyId]);
    header('Location: ' . Url::to('/dyeing/job-work?deleted=1'));
    exit;
}

$challans = Database::fetchAll("SELECT * FROM dyeing_job_work_challans WHERE company_id = ? ORDER BY id DESC", [$companyId]);

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
            <li class="breadcrumb-item active">Job-Work Challans</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-truck text-dark me-2"></i>External Dyeing Job-Work Challans & Subcontract</h1>
        <p class="text-secondary small mb-0">Track outward fabric to job-workers, GST Annexure-II delivery challans, inward return receipts, and process weight loss.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/job-work/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Issue Outward Challan
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Subcontract outward challan generated and fabric rolls dispatched to external processor.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Inward receipt and weight loss reconciliation updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Job-work challan record removed.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0">
          <div class="text-secondary small fw-bold">TOTAL OUTWARD CHALLANS</div>
          <div class="fs-4 fw-bold text-dark font-monospace"><?= count($challans) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0">
          <div class="text-secondary small fw-bold">FABRIC AT PROCESSORS</div>
          <div class="fs-4 fw-bold text-primary font-monospace">
            <?= number_format(array_sum(array_column($challans, 'dispatched_weight_kg')), 1) ?> Kg
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0">
          <div class="text-secondary small fw-bold">RECEIVED INWARD</div>
          <div class="fs-4 fw-bold text-success font-monospace">
            <?= number_format(array_sum(array_column($challans, 'received_weight_kg')), 1) ?> Kg
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0">
          <div class="text-secondary small fw-bold">ACTIVE PROCESSORS</div>
          <div class="fs-4 fw-bold text-dark font-monospace"><?= count(array_unique(array_column($challans, 'partner_name'))) ?> Mills</div>
        </div>
      </div>
    </div>

    <!-- Challans Register Table (Click anywhere on row to update) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-receipt text-dark me-2"></i>Job-Work Challans Register</h2>
        <span class="small text-secondary"><?= count($challans) ?> Consignments</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Challan No</th>
              <th>Processor / Mill</th>
              <th>Process</th>
              <th>Fabric & Shade</th>
              <th class="text-end">Dispatched (Kg)</th>
              <th class="text-end">Received (Kg)</th>
              <th class="text-end">Loss (Kg)</th>
              <th>Expected Return</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($challans)): ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No job-work delivery challans found. Click "Issue Outward Challan" to dispatch fabric.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($challans as $c): ?>
                <?php 
                  $editUrl = Url::to('/dyeing/job-work/edit?id=' . $c['id']);
                  $statusClass = ($c['status'] === 'received' || $c['status'] === 'qc_passed') ? 'bg-success' : (($c['status'] === 'dispatched') ? 'bg-primary' : 'bg-dark');
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to reconcile this challan">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($c['challan_no']) ?></a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['partner_name']) ?></div>
                    <code class="text-secondary"><?= htmlspecialchars($c['vehicle_no'] ?: 'Direct Transit') ?></code>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['process_type']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($c['fabric_description']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($c['required_shade']) ?></div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($c['dispatched_weight_kg'], 1) ?></td>
                  <td class="text-end font-monospace"><?= number_format($c['received_weight_kg'], 1) ?></td>
                  <td class="text-end font-monospace text-muted"><?= number_format($c['weight_loss_kg'], 1) ?></td>
                  <td><?= date('d M Y', strtotime($c['expected_return_date'])) ?></td>
                  <td>
                    <span class="badge <?= $statusClass ?> text-uppercase"><?= htmlspecialchars($c['status']) ?></span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/job-work-challan?id=' . $c['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print Challan" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger" title="Delete Challan" onclick="event.stopPropagation(); openDeleteModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['challan_no']) ?>');">
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

  </div>
</main>

<!-- Delete Modal -->
<div class="modal fade" id="deleteChallanModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold">Delete Job-Work Challan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        Are you sure you want to delete delivery challan <strong id="deleteChallanNo"></strong>?
      </div>
      <div class="modal-footer p-2">
        <form method="POST" action="<?= Url::to('/dyeing/job-work') ?>">
          <?= CSRF::getField() ?>
          <input type="hidden" name="action" value="delete_challan">
          <input type="hidden" name="id" id="deleteChallanId">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openDeleteModal(id, challanNo) {
  document.getElementById('deleteChallanId').value = id;
  document.getElementById('deleteChallanNo').textContent = challanNo;
  new bootstrap.Modal(document.getElementById('deleteChallanModal')).show();
}
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
