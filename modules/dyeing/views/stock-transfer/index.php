<?php
/**
 * Dyeing Stock Transfer & Dispatch Handover
 * Dedicated Register View
 * File: modules/dyeing/views/stock-transfer/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Stock Transfer';
$activeModule = 'stock-transfer';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Transfer Memo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_transfer') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $trId = (int)($_POST['transfer_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_stock_transfers WHERE id = ?", [$trId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_stock_transfers WHERE id = ?", [$trId]);
        header('Location: ' . Url::to('/dyeing/stock-transfer?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_stock_transfers")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$transfers = Database::fetchAll("SELECT * FROM dyeing_stock_transfers ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
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
            <li class="breadcrumb-item active">Stock Transfers</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-truck text-dark me-2"></i>Dyeing Stock Transfer & Dispatch Handover</h1>
        <p class="text-secondary small mb-0">Hand over QC-approved finished rolls to Central Warehouse, Garment Cutting, or Customer Dispatch with gate pass audit.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/stock-transfer/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Transfer Memo
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Stock transfer memo created and dispatched.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Stock transfer memo updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Transfer memo removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Stock Transfers Table (Row click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-arrow-right-circle text-dark me-2"></i>Finished Fabric Transfer Memos</h2>
        <span class="small text-secondary"><?= count($transfers) ?> Transfers (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="transferTable">
          <thead class="table-light">
            <tr>
              <th>Transfer No</th>
              <th>Batch No</th>
              <th>Source &rarr; Destination Warehouse</th>
              <th>Fabric & Shade</th>
              <th class="text-center">Rolls</th>
              <th class="text-end">Total Weight (Kg)</th>
              <th>Sign-off (Sender / Receiver)</th>
              <th>Vehicle</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transfers)): ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No stock transfer records found. Click "New Transfer Memo" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($transfers as $tr): ?>
                <?php $editUrl = Url::to('/dyeing/stock-transfer/edit?id=' . $tr['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this transfer memo">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($tr['transfer_no']) ?></a>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= date('d M Y', strtotime($tr['transfer_date'])) ?></div>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($tr['batch_no']) ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($tr['source_warehouse']) ?></div>
                    <div class="text-secondary small">&rarr; <?= htmlspecialchars($tr['dest_warehouse']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($tr['fabric_name']) ?></div>
                    <div class="text-secondary small"><?= htmlspecialchars($tr['shade_name']) ?></div>
                  </td>
                  <td class="text-center font-monospace fw-bold fs-6"><?= $tr['rolls_count'] ?></td>
                  <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format($tr['total_weight_kg'], 2) ?></td>
                  <td>
                    <div>From: <strong><?= htmlspecialchars($tr['sender_name']) ?></strong></div>
                    <div class="text-secondary" style="font-size:0.75rem;">To: <?= htmlspecialchars($tr['receiver_name']) ?></div>
                  </td>
                  <td class="font-monospace small"><?= htmlspecialchars($tr['vehicle_no']) ?></td>
                  <td>
                    <span class="badge <?= $tr['status'] === 'received' ? 'bg-success' : 'bg-primary' ?> text-uppercase">
                      <?= htmlspecialchars($tr['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteTransfer(<?= $tr['id'] ?>, '<?= htmlspecialchars($tr['transfer_no']) ?>')">
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
<div class="modal fade" id="deleteTransferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_transfer">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="transfer_id" id="deleteTransferId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Transfer</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this transfer memo?</p>
          <strong id="deleteTransferNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteTransfer(id, no) {
  document.getElementById('deleteTransferId').value = id;
  document.getElementById('deleteTransferNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteTransferModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
