<?php
/**
 * Dyeing Batch Quality Control (QC) & Comprehensive Fastness Disposition
 * Dedicated Register View
 * File: modules/dyeing/views/batch-qc/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Batch QC';
$activeModule = 'batch-qc';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete QC Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_qc') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $qcId = (int)($_POST['qc_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_batch_qcs WHERE id = ?", [$qcId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_batch_qcs WHERE id = ?", [$qcId]);
        header('Location: ' . Url::to('/dyeing/batch-qc?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_batch_qcs")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$qcs = Database::fetchAll("
    SELECT q.*, b.actual_loaded_kg, r.shade_name, m.name as machine_name
    FROM dyeing_batch_qcs q
    JOIN dye_batches b ON q.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    ORDER BY q.id DESC
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
            <li class="breadcrumb-item active">Dyeing Batch QC</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-shield-check text-dark me-2"></i>Dyeing Batch QC & Fastness Disposition</h1>
        <p class="text-secondary small mb-0">Record shade match, listing/ending check, GSM, fastness tests (Wash, Rub, Perspiration) and final acceptance disposition.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/batch-qc/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-shield-plus me-1"></i> Record Batch QC Disposition
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing Batch QC recorded successfully and batch status updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing Batch QC record updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Batch QC record removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Batch QC Table (Row click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-dark me-2"></i>Dyeing Batch Quality Dispositions Register</h2>
        <span class="small text-secondary"><?= count($qcs) ?> Batch Inspections (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="qcTable">
          <thead class="table-light">
            <tr>
              <th>QC Report No</th>
              <th>Batch No</th>
              <th>Shade & Vessel</th>
              <th>Shade Match</th>
              <th>Side / End Variation</th>
              <th>GSM / Width</th>
              <th>Fastness (Wash / Rub)</th>
              <th class="text-end">Accepted (Kg)</th>
              <th>Disposition</th>
              <th>Inspector</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($qcs)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No batch QC inspection records found. Click "Record Batch QC Disposition" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($qcs as $q): ?>
                <?php $editUrl = Url::to('/dyeing/batch-qc/edit?id=' . $q['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this QC record">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($q['qc_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($q['batch_no']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($q['shade_name'] ?? 'Royal Navy') ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($q['machine_name']) ?></div>
                  </td>
                  <td>
                    <span class="badge bg-success-subtle text-success text-uppercase"><?= htmlspecialchars($q['shade_match']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">Side: <?= htmlspecialchars($q['side_to_side_variation']) ?></span>
                  </td>
                  <td><?= $q['measured_gsm'] ?> GSM / <?= $q['measured_width_inches'] ?>"</td>
                  <td>
                    <div>Wash: <strong><?= htmlspecialchars($q['wash_fastness']) ?></strong></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Rub: <?= htmlspecialchars($q['rubbing_fastness']) ?> · pH <?= $q['ph_after_wash'] ?></div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-success fs-6">
                    <?= number_format($q['accepted_kg'], 2) ?>
                  </td>
                  <td>
                    <span class="badge <?= $q['qc_disposition'] === 'accepted' ? 'bg-success' : ($q['qc_disposition'] === 'reprocess' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                      <?= strtoupper(str_replace('_', ' ', $q['qc_disposition'])) ?>
                    </span>
                  </td>
                  <td class="small"><?= htmlspecialchars($q['inspector_name']) ?></td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                      <?php if ($q['qc_disposition'] === 'reprocess'): ?>
                        <a href="<?= Url::to('/dyeing/reprocess-requests?batch_id=' . $q['dye_batch_id']) ?>" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();">
                          <i class="bi bi-arrow-repeat me-1"></i> Reprocess
                        </a>
                      <?php else: ?>
                        <a href="<?= Url::to('/dyeing/finishing?batch_id=' . $q['dye_batch_id']) ?>" class="btn btn-sm btn-outline-dark" onclick="event.stopPropagation();">
                          <i class="bi bi-water me-1"></i> Finishing &rarr;
                        </a>
                      <?php endif; ?>
                      <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteQc(<?= $q['id'] ?>, '<?= htmlspecialchars($q['qc_no']) ?>')">
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
<div class="modal fade" id="deleteQcModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_qc">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="qc_id" id="deleteQcId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Record</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this batch QC disposition?</p>
          <strong id="deleteQcNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteQc(id, no) {
  document.getElementById('deleteQcId').value = id;
  document.getElementById('deleteQcNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteQcModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
