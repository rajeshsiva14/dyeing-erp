<?php
/**
 * Dyeing Order / Job Cards Register
 * Dedicated list & register view for dyeing production job cards.
 * File: modules/dyeing/views/job-cards.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dyeing Job Cards Register';
$activeModule = 'job-cards';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to delete Job Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job_card') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $jobCardId = (int)($_POST['job_card_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM dyeing_job_cards WHERE id = ? AND company_id = ?", [$jobCardId, $companyId]);
    if ($target) {
        // Check if allocated to active batch
        $inBatch = Database::fetch("SELECT id FROM dye_batches WHERE company_id = ? AND (batch_no = ? OR notes LIKE ?) LIMIT 1", [
            $companyId, $target['job_card_no'], '%' . $target['job_card_no'] . '%'
        ]);
        if ($inBatch) {
            header('Location: ' . Url::to('/dyeing/job-cards?error=in_use'));
            exit;
        }

        Database::execute("DELETE FROM dyeing_job_cards WHERE id = ? AND company_id = ?", [$jobCardId, $companyId]);
        header('Location: ' . Url::to('/dyeing/job-cards?deleted=1'));
        exit;
    }
}

// Pagination & Filtering
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_job_cards WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$jobCards = Database::fetchAll("
    SELECT jc.*, c.name as customer_name
    FROM dyeing_job_cards jc
    JOIN customers c ON jc.customer_id = c.id
    WHERE jc.company_id = ?
    ORDER BY jc.id DESC
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

$stats = Database::fetch("
    SELECT 
        COALESCE(SUM(required_qty_kg), 0) as total_kg,
        COALESCE(SUM(CASE WHEN priority IN ('urgent', 'high') THEN 1 ELSE 0 END), 0) as urgent_count,
        COALESCE(SUM(CASE WHEN status = 'in_production' THEN 1 ELSE 0 END), 0) as in_prod_count
    FROM dyeing_job_cards
    WHERE company_id = ?
", [$companyId]);

$totalKg = (float)($stats['total_kg'] ?? 0);
$urgentCount = (int)($stats['urgent_count'] ?? 0);
$inProdCount = (int)($stats['in_prod_count'] ?? 0);

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
            <li class="breadcrumb-item active">Dyeing Job Cards</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-card-checklist text-dark me-2"></i>Dyeing Order / Job Cards</h1>
        <p class="text-secondary small mb-0">Customer PO instructions, target specifications (GSM, width, fastness), route, and recipe binding.</p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <button type="button" onclick="exportTableToCSV('jobCardsTable', 'dyeing_job_cards_register.csv')" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
        </button>
        <a href="<?= Url::to('/dyeing/job-cards-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Create Job Card
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing Job Card created and approved. Ready for Batch Planning.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dyeing Job Card updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Dyeing Job Card deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'in_use'): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> Cannot delete this Job Card because it is already allocated to an active production batch.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Summary KPI Row -->
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="cz-stat-card">
          <div class="cz-stat-lbl">Total Job Cards</div>
          <div class="cz-stat-val"><?= number_format($totalRecords) ?></div>
          <div class="text-secondary small mt-1">Active technical orders</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="cz-stat-card">
          <div class="cz-stat-lbl">Total Weight Volume</div>
          <div class="cz-stat-val"><?= number_format($totalKg, 0) ?> <span class="fs-6 fw-normal text-secondary">Kg</span></div>
          <div class="text-secondary small mt-1">Dyeing order weight</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="cz-stat-card">
          <div class="cz-stat-lbl">In Production</div>
          <div class="cz-stat-val"><?= $inProdCount ?></div>
          <div class="text-secondary small mt-1">Running in vessels</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="cz-stat-card">
          <div class="cz-stat-lbl">Urgent / Priority</div>
          <div class="cz-stat-val text-dark"><?= $urgentCount ?></div>
          <div class="text-secondary small mt-1">Express delivery orders</div>
        </div>
      </div>
    </div>

    <!-- Job Cards List Table (Click anywhere on row to update) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-folder-fill text-dark me-2"></i>Active Dyeing Job Card Register</h2>
        <span class="small text-secondary"><?= count($jobCards) ?> Job Cards on page (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table id="jobCardsTable" class="table table-hover align-middle mb-0 small">
          <thead>
            <tr>
              <th>Job Card No</th>
              <th>Customer / PO</th>
              <th>Fabric & Grey Lot</th>
              <th>Shade & Route</th>
              <th class="text-end">Required (Kg)</th>
              <th class="text-center">Rolls</th>
              <th>Spec (GSM/Width)</th>
              <th>Due Date</th>
              <th>Priority</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jobCards)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No job cards found. Click "Create Job Card" to issue a new order.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($jobCards as $jc): ?>
                <?php
                  $prio = strtolower($jc['priority'] ?? 'normal');
                  $prioBadge = match($prio) {
                    'urgent' => 'badge bg-danger',
                    'high' => 'badge bg-warning',
                    'medium' => 'badge bg-info',
                    default => 'badge bg-light text-secondary border',
                  };
                  $st = strtolower($jc['status'] ?? 'planned');
                  $stBadge = match($st) {
                    'in_production', 'running' => 'badge bg-primary',
                    'completed', 'dispatched' => 'badge bg-success',
                    'on_hold' => 'badge bg-danger',
                    default => 'badge bg-warning',
                  };
                  $editUrl = Url::to('/dyeing/job-cards-edit?id=' . $jc['id']);
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this Job Card">
                  <td>
                    <a href="<?= $editUrl ?>" class="fw-bold font-monospace text-primary text-decoration-none" title="Open Job Card Update Form">
                      <?= htmlspecialchars($jc['job_card_no']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($jc['customer_name']) ?></div>
                    <div class="text-secondary small" style="font-size:0.75rem;">PO: <?= htmlspecialchars($jc['customer_po'] ?: 'Direct Work Order') ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($jc['fabric_name']) ?></div>
                    <div class="text-secondary small" style="font-size:0.75rem;">Lot: <span class="font-monospace"><?= htmlspecialchars($jc['grey_lot_no'] ?: 'Stock Allocation Pending') ?></span></div>
                  </td>
                  <td>
                    <code class="code-pill mb-1"><?= htmlspecialchars($jc['shade_code']) ?></code>
                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($jc['shade_name']) ?></div>
                    <span class="badge bg-light text-secondary border mt-1" style="font-size:0.65rem;"><?= strtoupper($jc['dyeing_route']) ?></span>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($jc['required_qty_kg'], 2) ?></td>
                  <td class="text-center font-monospace fw-semibold"><?= $jc['roll_count'] ?></td>
                  <td class="small text-secondary"><?= $jc['target_gsm'] ?> GSM / <?= $jc['target_width_inches'] ?>"</td>
                  <td class="small font-monospace"><?= date('d M Y', strtotime($jc['required_date'])) ?></td>
                  <td>
                    <span class="<?= $prioBadge ?>">
                      <?= strtoupper($jc['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="<?= $stBadge ?>">
                      <?= strtoupper(str_replace('_', ' ', $jc['status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/dyeing/batch-planning?job_card_id=' . $jc['id']) ?>" class="btn btn-outline-dark" title="Plan into Batch" onclick="event.stopPropagation();">
                        <i class="bi bi-calendar-range"></i>
                      </a>
                      <a href="<?= Url::to('/print/sales-order?id=' . $jc['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print Job Card" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger js-del-jc" data-id="<?= $jc['id'] ?>" data-no="<?= htmlspecialchars($jc['job_card_no']) ?>" title="Delete Job Card" onclick="event.stopPropagation();">
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
          <span class="fw-bold text-dark"><?= $totalRecords ?></span> technical orders
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
    <div class="modal fade" id="deleteJobCardModal" tabindex="-1" aria-labelledby="deleteJobCardModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteJobCardModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Dyeing Job Card
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/job-cards') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_job_card">
            <input type="hidden" name="job_card_id" id="del_jc_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2">Are you sure you want to permanently delete job card:</p>
              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_jc_no"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> This action cannot be undone. Order will be removed from schedule.
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
      const delModalEl = document.getElementById('deleteJobCardModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      // Delete button click
      document.querySelectorAll('.js-del-jc').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_jc_id').value = this.dataset.id;
          document.getElementById('del_jc_no').textContent = this.dataset.no;
          if (delModal) delModal.show();
        });
      });
    });
    </script>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
