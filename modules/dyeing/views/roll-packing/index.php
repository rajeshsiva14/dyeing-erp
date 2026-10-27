<?php
/**
 * Roll Formation, Barcode Generation & Thermal Packing Labels Register
 * Dedicated list & register view for finished rolls.
 * File: modules/dyeing/views/roll-packing/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Roll Formation & Packing';
$activeModule = 'roll-packing';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);

// Handle POST to delete a roll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_roll') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $rollId = (int)($_POST['roll_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM inventory_rolls WHERE id = ? AND company_id = ?", [$rollId, $companyId]);
    if ($target) {
        Database::execute("DELETE FROM inventory_movements WHERE roll_id = ? AND company_id = ?", [$rollId, $companyId]);
        Database::execute("DELETE FROM inventory_rolls WHERE id = ? AND company_id = ?", [$rollId, $companyId]);
        header('Location: ' . Url::to('/dyeing/roll-packing?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM inventory_rolls WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$rolls = Database::fetchAll("
    SELECT r.*, p.name as product_name, p.sku
    FROM inventory_rolls r
    JOIN products p ON r.product_id = p.id
    WHERE r.company_id = ?
    ORDER BY r.id DESC
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

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
            <li class="breadcrumb-item active">Roll Formation & Packing</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-upc-scan text-dark me-2"></i>Roll Formation, Barcode Generation & Packing</h1>
        <p class="text-secondary small mb-0">Pack finished dyed rolls, generate thermal barcodes (Code128 / QR), track roll weight, length, grade and post to finished stock.</p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <button type="button" onclick="exportTableToCSV('rollPackingTable', 'finished_roll_packing_register.csv')" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
        </button>
        <a href="<?= Url::to('/dyeing/roll-packing-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Form & Pack New Roll
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['packed'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Finished fabric roll packaged, thermal barcode generated, and inventory posted to Warehouse Rack A-01.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Fabric roll record and ledger movements removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Rolls Table (Click anywhere on row to view label) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-dark me-2"></i>Packaged Finished Fabric Rolls Register</h2>
        <span class="small text-secondary"><?= count($rolls) ?> Packaged Rolls on page (Click any row to print label)</span>
      </div>
      <div class="table-responsive">
        <table id="rollPackingTable" class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Roll Barcode / ID</th>
              <th>Product / Fabric</th>
              <th class="text-end">Net Weight (Kg)</th>
              <th class="text-end">Length (Meters)</th>
              <th class="text-center">Width / GSM</th>
              <th class="text-center">Quality Grade</th>
              <th>Bin Location</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rolls)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No finished fabric rolls found. Click "Form & Pack New Roll" to package rolls.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rolls as $r): ?>
                <?php $editUrl = Url::to('/dyeing/roll-packing/edit?id=' . $r['id']); ?>
                <?php $printUrl = Url::to('/print/roll-label?id=' . $r['id'] . '&roll=' . urlencode($r['roll_number'])); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this fabric roll">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none">
                      <i class="bi bi-upc me-1 text-dark"></i><?= htmlspecialchars($r['roll_number']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['product_name']) ?></div>
                    <code class="text-secondary"><?= htmlspecialchars($r['sku']) ?></code>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($r['weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace"><?= number_format($r['length_meters'], 1) ?> m</td>
                  <td class="text-center font-monospace"><?= $r['width_inches'] ?>" / <?= $r['gsm'] ?> GSM</td>
                  <td class="text-center">
                    <span class="badge bg-success">
                      Grade <?= htmlspecialchars($r['quality_grade'] ?? 'A') ?>
                    </span>
                  </td>
                  <td><span class="badge bg-light text-dark border">Finished Rack A-01</span></td>
                  <td>
                    <span class="badge bg-primary text-uppercase">
                      <?= htmlspecialchars($r['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= $printUrl ?>" target="_blank" class="btn btn-outline-primary" title="Print L01 Thermal Barcode Label" onclick="event.stopPropagation();">
                        <i class="bi bi-printer me-1"></i> Print L01 Label
                      </a>
                      <button type="button" class="btn btn-outline-danger js-del-roll" data-id="<?= $r['id'] ?>" data-no="<?= htmlspecialchars($r['roll_number']) ?>" title="Delete Roll" onclick="event.stopPropagation();">
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
          <span class="fw-bold text-dark"><?= $totalRecords ?></span> finished rolls
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRollModal" tabindex="-1" aria-labelledby="deleteRollModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteRollModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Fabric Roll Record
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/roll-packing') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_roll">
            <input type="hidden" name="roll_id" id="del_roll_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2">Are you sure you want to permanently delete fabric roll:</p>
              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_roll_no"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> Inventory movement will be reversed and barcode cancelled.
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
      const delModalEl = document.getElementById('deleteRollModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      // Delete button click
      document.querySelectorAll('.js-del-roll').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_roll_id').value = this.dataset.id;
          document.getElementById('del_roll_no').textContent = this.dataset.no;
          if (delModal) delModal.show();
        });
      });
    });
    </script>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
