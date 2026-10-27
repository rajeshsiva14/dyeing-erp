<?php
/**
 * Reprocess / Re-dye Request & Root Cause Corrective Action
 * Dedicated Register View
 * File: modules/dyeing/views/reprocess-requests/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Reprocess / Re-dye Requests';
$activeModule = 'reprocess-requests';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Reprocess Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_reprocess') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $rprId = (int)($_POST['reprocess_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_reprocess_requests WHERE id = ?", [$rprId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_reprocess_requests WHERE id = ?", [$rprId]);
        header('Location: ' . Url::to('/dyeing/reprocess-requests?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_reprocess_requests")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$reprocessRequests = Database::fetchAll("
    SELECT r.*, m.name as machine_name, m.code as machine_code
    FROM dyeing_reprocess_requests r
    LEFT JOIN machines m ON r.target_machine_id = m.id
    ORDER BY r.id DESC
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
            <li class="breadcrumb-item active">Reprocess Requests</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-arrow-repeat text-dark me-2"></i>Reprocess / Re-dye Request & Corrective Action</h1>
        <p class="text-secondary small mb-0">Root cause analysis (RCA), corrective chemical recipes, additional cost tracking, and re-dyeing authorization.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/reprocess-requests/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Reprocess Request
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Reprocess authorization request created and scheduled.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Reprocess corrective action updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Reprocess request removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Reprocess Register Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-exclamation-octagon text-dark me-2"></i>Controlled Re-dyeing & Reprocess Authorizations</h2>
        <span class="small text-secondary"><?= count($reprocessRequests) ?> Reprocess Requests (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="reprocessTable">
          <thead class="table-light">
            <tr>
              <th>Request No</th>
              <th>Original Batch No</th>
              <th>Defect Category</th>
              <th>Failed Criteria</th>
              <th class="text-end">Affected (Kg)</th>
              <th>Root Cause & Corrective Action</th>
              <th>Revised Recipe</th>
              <th class="text-end">Extra Cost (₹)</th>
              <th>Target Machine</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reprocessRequests)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No reprocess requests found. Click "New Reprocess Request" to create one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($reprocessRequests as $r): ?>
                <?php $editUrl = Url::to('/dyeing/reprocess-requests/edit?id=' . $r['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this reprocess request">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($r['request_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['original_batch_no']) ?></span></td>
                  <td>
                    <span class="badge bg-danger-subtle text-danger text-uppercase">
                      <?= strtoupper(str_replace('_', ' ', $r['defect_category'])) ?>
                    </span>
                  </td>
                  <td class="small"><?= htmlspecialchars($r['failed_criteria']) ?></td>
                  <td class="text-end font-monospace fw-bold text-danger fs-6"><?= number_format($r['affected_weight_kg'], 1) ?></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['root_cause']) ?></div>
                    <div class="text-success small"><i class="bi bi-arrow-return-right me-1"></i><?= htmlspecialchars($r['corrective_action']) ?></div>
                  </td>
                  <td><code><?= htmlspecialchars($r['revised_recipe_code'] ?? 'Same Recipe') ?></code></td>
                  <td class="text-end font-monospace fw-bold text-dark fs-6">₹<?= number_format($r['additional_chem_cost'], 2) ?></td>
                  <td><?= htmlspecialchars($r['machine_code'] ?? 'M-01') ?></td>
                  <td>
                    <span class="badge <?= $r['status'] === 'approved' ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase">
                      <?= htmlspecialchars($r['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteReprocess(<?= $r['id'] ?>, '<?= htmlspecialchars($r['request_no']) ?>')">
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
<div class="modal fade" id="deleteReprocessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_reprocess">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="reprocess_id" id="deleteReprocessId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this reprocess authorization request?</p>
          <strong id="deleteReprocessNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteReprocess(id, no) {
  document.getElementById('deleteReprocessId').value = id;
  document.getElementById('deleteReprocessNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteReprocessModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
