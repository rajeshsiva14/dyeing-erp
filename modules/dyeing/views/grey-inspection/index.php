<?php
/**
 * Grey Fabric Inspection Register & Quality Disposition
 * Dedicated list & register view for grey inspections.
 * File: modules/dyeing/views/grey-inspection/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Grey Fabric Inspection';
$activeModule = 'grey-inspection';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to delete inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $inspId = (int)($_POST['inspection_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM grey_fabric_inspections WHERE id = ? AND company_id = ?", [$inspId, $companyId]);
    if ($target) {
        Database::execute("DELETE FROM grey_fabric_inspections WHERE id = ? AND company_id = ?", [$inspId, $companyId]);
        header('Location: ' . Url::to('/dyeing/grey-inspection?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM grey_fabric_inspections WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$inspections = Database::fetchAll("
    SELECT * FROM grey_fabric_inspections 
    WHERE company_id = ? 
    ORDER BY id DESC 
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

$pendingInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE company_id = ? AND qc_status = 'inspection_pending' ORDER BY id ASC LIMIT 5", [$companyId]);

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
            <li class="breadcrumb-item active">Grey Fabric Inspection</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-search text-dark me-2"></i>Grey Fabric Inspection & Quality Disposition</h1>
        <p class="text-secondary small mb-0">Check physical loop structure, GSM, holes, oil contamination, and decide usable vs hold vs rejected.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/grey-inspection-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> New Grey Inspection
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Grey Inspection recorded and inward status updated. Usable stock released for Batch Planning.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Grey Inspection record deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Pending Inwards Alert Banner -->
    <?php if (!empty($pendingInwards)): ?>
      <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 shadow-sm gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="p-2 rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
          </div>
          <div>
            <div class="fw-bold text-dark"><?= count($pendingInwards) ?> Grey Inward(s) awaiting QC Inspection</div>
            <div class="small text-secondary mt-0">
              <?php foreach($pendingInwards as $pi): ?>
                <span class="me-2"><code><?= htmlspecialchars($pi['inward_no']) ?></code> <span class="text-muted">(<?= htmlspecialchars($pi['fabric_code']) ?> &bull; <?= $pi['net_weight_kg'] ?> Kg)</span></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <a href="<?= Url::to('/dyeing/grey-inspection-create?inward_id=' . $pendingInwards[0]['id']) ?>" class="btn btn-sm btn-primary">
          <i class="bi bi-search me-1"></i> Inspect Now
        </a>
      </div>
    <?php endif; ?>

    <!-- Inspections Table (Click any row to open inspection report) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-dark me-2"></i>Grey Fabric QC Disposition Register</h2>
          <span class="small text-secondary"><?= count($inspections) ?> Completed Inspections on page (Click any row to view)</span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Register
          </button>
          <button type="button" onclick="exportTableToCSV('inspectionTable', 'grey_qc_register.csv')" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="inspectionTable">
          <thead class="table-light">
            <tr>
              <th>Inspection No</th>
              <th>Inward Ref</th>
              <th>Fabric & Lot</th>
              <th>Measured GSM/Width</th>
              <th>Defect Counts</th>
              <th class="text-center">Grade</th>
              <th class="text-end">Accepted (Kg)</th>
              <th class="text-end">Rejected (Kg)</th>
              <th class="text-center">Disposition</th>
              <th>Inspector</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inspections)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No inspection records found. Click "New Grey Inspection" to record an inspection.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inspections as $insp): ?>
                <?php 
                  $disp = strtolower($insp['qc_disposition'] ?? 'accepted');
                  $dispBadge = match($disp) {
                    'accepted' => 'badge bg-success',
                    'concession' => 'badge bg-info',
                    'rejected' => 'badge bg-danger',
                    default => 'badge bg-warning',
                  };
                  $editUrl = Url::to('/dyeing/grey-inspection/edit?id=' . $insp['id']);
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update inspection disposition">
                  <td class="fw-bold text-primary font-monospace">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($insp['inspection_no']) ?></a>
                  </td>
                  <td><code><?= htmlspecialchars($insp['inward_no']) ?></code></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($insp['fabric_code']) ?></div>
                    <div class="text-secondary small font-monospace">Lot: <?= htmlspecialchars($insp['grey_lot_no']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark font-monospace"><?= $insp['measured_gsm'] ?> GSM</div>
                    <div class="text-secondary small font-monospace"><?= $insp['measured_width_inches'] ?>" Width</div>
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-1">
                      <span class="badge bg-light text-dark border">Holes: <?= $insp['hole_tear_count'] ?></span>
                      <span class="badge bg-light text-dark border">Stains: <?= $insp['oil_stain_count'] ?></span>
                      <span class="badge bg-light text-dark border">Knits: <?= $insp['knitting_defect_count'] ?></span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $insp['defect_grade'] === 'Grade A' ? 'bg-success' : ($insp['defect_grade'] === 'Grade B' ? 'bg-info' : 'bg-danger') ?>">
                      <?= htmlspecialchars($insp['defect_grade']) ?>
                    </span>
                  </td>
                  <td class="text-end font-monospace fw-bold text-success"><?= number_format($insp['accepted_weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace text-danger"><?= number_format($insp['rejected_weight_kg'], 2) ?></td>
                  <td class="text-center">
                    <span class="<?= $dispBadge ?>">
                      <?= strtoupper(htmlspecialchars($disp)) ?>
                    </span>
                  </td>
                  <td class="small text-secondary"><?= htmlspecialchars($insp['inspector_name']) ?></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/grey-inward?id=' . $insp['inward_id'] . '&format=pdf') ?>" target="_blank" class="btn btn-outline-primary" title="Download TCPDF Slip" onclick="event.stopPropagation();">
                        <i class="bi bi-file-earmark-pdf"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger js-del-insp" data-id="<?= $insp['id'] ?>" data-no="<?= htmlspecialchars($insp['inspection_no']) ?>" title="Delete Inspection" onclick="event.stopPropagation();">
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
          <span class="fw-bold text-dark"><?= $totalRecords ?></span> inspections
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
    <div class="modal fade" id="deleteInspModal" tabindex="-1" aria-labelledby="deleteInspModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteInspModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Grey Fabric Inspection
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/grey-inspection') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_inspection">
            <input type="hidden" name="inspection_id" id="del_insp_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2">Are you sure you want to delete inspection record:</p>
              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_insp_no"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> Inward lot status will remain unchanged.
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
      const delModalEl = document.getElementById('deleteInspModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      // Delete button click
      document.querySelectorAll('.js-del-insp').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_insp_id').value = this.dataset.id;
          document.getElementById('del_insp_no').textContent = this.dataset.no;
          if (delModal) delModal.show();
        });
      });
    });
    </script>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
