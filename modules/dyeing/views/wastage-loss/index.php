<?php
/**
 * Dyeing Wastage & Process Loss Reconciliation
 * Dedicated Register View
 * File: modules/dyeing/views/wastage-loss/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Wastage & Process Loss';
$activeModule = 'wastage-loss';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Loss Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_loss') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $entryId = (int)($_POST['entry_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_wastage_entries WHERE id = ?", [$entryId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_wastage_entries WHERE id = ?", [$entryId]);
        header('Location: ' . Url::to('/dyeing/wastage-loss?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_wastage_entries")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$losses = Database::fetchAll("
    SELECT w.*, r.shade_name
    FROM dyeing_wastage_entries w
    JOIN dye_batches b ON w.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY w.id DESC
    LIMIT {$limit} OFFSET {$offset}
");

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
            <li class="breadcrumb-item active">Wastage & Loss</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-dark me-2"></i>Dyeing Wastage & Process Loss Reconciliation</h1>
        <p class="text-secondary small mb-0">Reconcile input weight against output weight: distinguish end-bits, stenter edge trimming, sample cuts, and moisture loss.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/wastage-loss/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Record Process Loss
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['logged'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Process loss reconciliation logged successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Process loss entry updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Process loss record removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Wastage Register Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clipboard-data text-dark me-2"></i>Process Loss & Yield Reconciliation Register</h2>
        <span class="small text-secondary"><?= count($losses) ?> Reconciled Records (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="wastageTable">
          <thead class="table-light">
            <tr>
              <th>Entry No</th>
              <th>Batch No</th>
              <th>Process Stage</th>
              <th class="text-end">In (Kg)</th>
              <th class="text-end">Out (Kg)</th>
              <th class="text-end">Waste (Kg)</th>
              <th class="text-end">Net Loss (Kg)</th>
              <th class="text-center">Loss %</th>
              <th>Reason & Breakdown</th>
              <th>Disposal Method</th>
              <th>Reported By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($losses)): ?>
              <tr>
                <td colspan="12" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No process loss records found. Click "Record Process Loss" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($losses as $w): ?>
                <?php $editUrl = Url::to('/dyeing/wastage-loss/edit?id=' . $w['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this process loss entry">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($w['entry_no']) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($w['batch_no']) ?></span>
                    <div class="text-secondary small"><?= htmlspecialchars($w['shade_name'] ?? '') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($w['process_stage']) ?></span>
                  </td>
                  <td class="text-end font-monospace"><?= number_format($w['input_kg'], 1) ?></td>
                  <td class="text-end font-monospace text-success fw-bold"><?= number_format($w['output_kg'], 1) ?></td>
                  <td class="text-end font-monospace"><?= number_format($w['process_waste_kg'], 1) ?></td>
                  <td class="text-end font-monospace fw-bold text-danger fs-6"><?= number_format($w['net_loss_kg'], 1) ?></td>
                  <td class="text-center font-monospace fw-bold text-danger"><?= number_format($w['loss_percentage'], 2) ?>%</td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($w['loss_reason']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Sample: <?= $w['sample_cut_kg'] ?> Kg · Unacc: <?= $w['unaccounted_loss_kg'] ?> Kg</div>
                  </td>
                  <td><?= htmlspecialchars($w['disposal_method'] ?? 'Recycled Cotton Waste') ?></td>
                  <td><?= htmlspecialchars($w['reported_by']) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteLoss(<?= $w['id'] ?>, '<?= htmlspecialchars($w['entry_no']) ?>')">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <span class="small text-secondary">Page <?= $page ?> of <?= $totalPages ?> (Total <?= $totalRecords ?> items)</span>
          <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLossModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_loss">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="entry_id" id="deleteLossId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Record</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this process loss reconciliation entry?</p>
          <strong id="deleteLossNo" class="text-danger font-monospace"></strong>
        </div>
        <div class="modal-footer py-2 justify-content-between">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDeleteLoss(id, no) {
  document.getElementById('deleteLossId').value = id;
  document.getElementById('deleteLossNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteLossModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
