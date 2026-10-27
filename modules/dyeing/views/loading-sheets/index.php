<?php
/**
 * Machine Loading Sheet & Vessel Safety Verification
 * Dedicated Register View
 * File: modules/dyeing/views/loading-sheets/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Machine Loading Sheet';
$activeModule = 'loading-sheets';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Loading Sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_loading') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $lsId = (int)($_POST['loading_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM machine_loading_sheets WHERE id = ?", [$lsId]);
    if ($target) {
        Database::execute("DELETE FROM machine_loading_sheets WHERE id = ?", [$lsId]);
        header('Location: ' . Url::to('/dyeing/loading-sheets?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM machine_loading_sheets")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$loadingSheets = Database::fetchAll("
    SELECT ls.*, m.code as machine_code, m.name as machine_name, r.shade_name
    FROM machine_loading_sheets ls
    JOIN machines m ON ls.machine_id = m.id
    JOIN dye_batches b ON ls.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY ls.id DESC
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
            <li class="breadcrumb-item active">Machine Loading Sheet</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-box-arrow-in-down-right text-dark me-2"></i>Machine Loading Sheet & Vessel Clearance</h1>
        <p class="text-secondary small mb-0">Record physical fabric roll loading into soft flow vessel, cleaning verification, nozzle check, and operator sign-off.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/loading-sheets/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Loading Sheet
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Machine loading sheet logged and batch status set to loaded.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Machine loading sheet updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Loading sheet removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Loading Sheets Register Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clipboard-check text-dark me-2"></i>Vessel Loading Logs & Sign-Offs</h2>
        <span class="small text-secondary"><?= count($loadingSheets) ?> Loading Records (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="loadingTable">
          <thead class="table-light">
            <tr>
              <th>Loading No</th>
              <th>Batch No</th>
              <th>Machine Vessel</th>
              <th>Shade</th>
              <th class="text-end">Planned (Kg)</th>
              <th class="text-end">Actual Loaded (Kg)</th>
              <th class="text-center">Rolls</th>
              <th class="text-center">Cleanliness & Safety</th>
              <th>Operator / Supervisor</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($loadingSheets)): ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No loading records found. Click "New Loading Sheet" to record vessel loading.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($loadingSheets as $ls): ?>
                <?php $editUrl = Url::to('/dyeing/loading-sheets/edit?id=' . $ls['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this loading sheet">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($ls['loading_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($ls['batch_no']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($ls['machine_code']) ?></div>
                    <div class="text-secondary small"><?= htmlspecialchars($ls['machine_name']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($ls['shade_name'] ?? 'Royal Navy') ?></td>
                  <td class="text-end font-monospace"><?= number_format($ls['planned_weight_kg'], 1) ?></td>
                  <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format($ls['actual_loaded_kg'], 1) ?></td>
                  <td class="text-center font-monospace fw-bold"><?= $ls['rolls_count'] ?></td>
                  <td class="text-center">
                    <span class="badge <?= $ls['cleaning_verified'] ? 'bg-success' : 'bg-secondary' ?> me-1">Clean: <?= $ls['cleaning_verified'] ? 'Yes' : 'No' ?></span>
                    <span class="badge <?= $ls['safety_checked'] ? 'bg-primary' : 'bg-secondary' ?>">Safety: <?= $ls['safety_checked'] ? 'Yes' : 'No' ?></span>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($ls['operator_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Lead: <?= htmlspecialchars($ls['supervisor_name']) ?></div>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteLoading(<?= $ls['id'] ?>, '<?= htmlspecialchars($ls['loading_no']) ?>')">
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
<div class="modal fade" id="deleteLoadingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_loading">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="loading_id" id="deleteLoadingId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Sheet</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this machine loading record?</p>
          <strong id="deleteLoadingNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteLoading(id, no) {
  document.getElementById('deleteLoadingId').value = id;
  document.getElementById('deleteLoadingNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteLoadingModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
