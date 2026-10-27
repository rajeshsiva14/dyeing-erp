<?php
/**
 * Dyeing Batch Costing & Profitability Engine
 * Dedicated Register View
 * File: modules/dyeing/views/batch-costing/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Batch Costing';
$activeModule = 'batch-costing';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Costing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_costing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $cstId = (int)($_POST['costing_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_batch_costings WHERE id = ?", [$cstId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_batch_costings WHERE id = ?", [$cstId]);
        header('Location: ' . Url::to('/dyeing/batch-costing?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_batch_costings")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$costings = Database::fetchAll("
    SELECT c.*, r.shade_name
    FROM dyeing_batch_costings c
    JOIN dye_batches b ON c.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY c.id DESC
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
            <li class="breadcrumb-item active">Batch Costing</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-currency-rupee text-dark me-2"></i>Dyeing Batch Costing & Margin Engine</h1>
        <p class="text-secondary small mb-0">Compute full cost per accepted kg: Grey fabric + Dyes/Chemicals + Steam (Boiler) + Water + Power + Labour + Vessel Overhead.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/batch-costing/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-calculator me-1"></i> Compute Batch Cost
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Batch absorption costing and gross margin calculated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Batch cost worksheet updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Costing record removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Batch Costing Register Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cash-stack text-dark me-2"></i>Batch Production Cost & Profitability Register</h2>
        <span class="small text-secondary"><?= count($costings) ?> Worksheets (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="costingTable">
          <thead class="table-light">
            <tr>
              <th>Costing No</th>
              <th>Batch No</th>
              <th>Shade</th>
              <th class="text-end">Total Batch Cost (₹)</th>
              <th class="text-end">Accepted (Kg)</th>
              <th class="text-end">Cost / Kg (₹)</th>
              <th class="text-end">Selling Rate / Kg</th>
              <th class="text-center">Gross Margin %</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($costings)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No batch costing sheets calculated yet. Click "Compute Batch Cost" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($costings as $c): ?>
                <?php $editUrl = Url::to('/dyeing/batch-costing/edit?id=' . $c['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this costing worksheet">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($c['costing_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($c['batch_no']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($c['shade_name'] ?? 'Royal Navy') ?></div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark fs-6">₹<?= number_format($c['total_batch_cost'], 2) ?></td>
                  <td class="text-end font-monospace fw-bold text-success"><?= number_format($c['accepted_output_kg'], 1) ?></td>
                  <td class="text-end font-monospace fw-bold text-danger fs-6">₹<?= number_format($c['cost_per_kg_accepted'], 2) ?></td>
                  <td class="text-end font-monospace text-primary fw-bold">₹<?= number_format($c['selling_rate_per_kg'], 2) ?></td>
                  <td class="text-center">
                    <span class="badge <?= ($c['gross_margin_pct'] >= 15) ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                      <?= number_format($c['gross_margin_pct'], 1) ?>%
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteCosting(<?= $c['id'] ?>, '<?= htmlspecialchars($c['costing_no']) ?>')">
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
<div class="modal fade" id="deleteCostingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_costing">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="costing_id" id="deleteCostingId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Record</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this batch costing worksheet?</p>
          <strong id="deleteCostingNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteCosting(id, no) {
  document.getElementById('deleteCostingId').value = id;
  document.getElementById('deleteCostingNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteCostingModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
