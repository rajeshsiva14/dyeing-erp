<?php
/**
 * Lab Dip Request, Trial Entry & Shade Approvals
 * Dedicated Register View
 * File: modules/dyeing/views/lab-dips/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Lab Dip & Shade Approvals';
$activeModule = 'lab-dips';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Lab Dip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_lab_dip') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $ldId = (int)($_POST['lab_dip_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM lab_dip_requests WHERE id = ? AND company_id = ?", [$ldId, $companyId]);
    if ($target) {
        Database::execute("DELETE FROM lab_dip_trials WHERE lab_dip_id = ?", [$ldId]);
        Database::execute("DELETE FROM lab_dip_requests WHERE id = ? AND company_id = ?", [$ldId, $companyId]);
        header('Location: ' . Url::to('/dyeing/lab-dips?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM lab_dip_requests WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$requests = Database::fetchAll("
    SELECT r.*, c.name as customer_name
    FROM lab_dip_requests r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.company_id = ?
    ORDER BY r.id DESC
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

$trials = Database::fetchAll("
    SELECT t.*, r.request_no, r.shade_name
    FROM lab_dip_trials t
    JOIN lab_dip_requests r ON t.lab_dip_id = r.id
    WHERE r.company_id = ?
    ORDER BY t.id DESC
    LIMIT 10
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
            <li class="breadcrumb-item active">Lab Dip Approvals</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-eyedropper text-dark me-2"></i>Lab Dip Request & Spectrophotometer Approvals</h1>
        <p class="text-secondary small mb-0">Record shade matching requests, D65/TL84 light box evaluations, trials (A, B, C) and customer sign-offs.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/lab-dips/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Lab Dip Request
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> New Lab Dip shade request created and routed to Color Kitchen Lab.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Lab Dip trial results and customer approval updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Lab Dip request removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Lab Dip Requests Table (Row click navigates to edit page) -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-palette text-dark me-2"></i>Active Lab Dip Shade Development Requests</h2>
        <span class="small text-secondary"><?= count($requests) ?> Requests (Click any row to record trial / update)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="labDipTable">
          <thead class="table-light">
            <tr>
              <th>Request No</th>
              <th>Customer</th>
              <th>Shade & Pantone Code</th>
              <th>Fabric Construction</th>
              <th>Light Source</th>
              <th>Target Due Date</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($requests)): ?>
              <tr>
                <td colspan="8" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No lab dip requests found. Click "New Lab Dip Request" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $r): ?>
                <?php $editUrl = Url::to('/dyeing/lab-dips/edit?id=' . $r['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update or add trials to this shade">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($r['request_no']) ?></a>
                  </td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($r['customer_name']) ?></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['shade_name']) ?></div>
                    <code class="text-secondary"><?= htmlspecialchars($r['shade_code']) ?></code>
                  </td>
                  <td><?= htmlspecialchars($r['fabric_type']) ?></td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($r['light_source']) ?></span>
                  </td>
                  <td class="font-monospace"><?= date('d M Y', strtotime($r['due_date'])) ?></td>
                  <td>
                    <span class="badge <?= $r['status'] === 'approved' ? 'bg-success' : ($r['status'] === 'trial_in_progress' ? 'bg-primary' : 'bg-warning text-dark') ?> text-uppercase">
                      <?= str_replace('_', ' ', $r['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                      <a href="<?= $editUrl ?>" class="btn btn-sm btn-dark" onclick="event.stopPropagation();">
                        <i class="bi bi-flask me-1"></i> Trials &rarr;
                      </a>
                      <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteLabDip(<?= $r['id'] ?>, '<?= htmlspecialchars($r['request_no']) ?>')">
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

    <!-- Recent Trials Section -->
    <?php if (!empty($trials)): ?>
      <div class="cz-panel">
        <div class="cz-panel-head">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-check2-circle text-dark me-2"></i>Recent Spectrophotometer Trial Logs</h2>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Lab Dip Ref</th>
                <th>Trial #</th>
                <th>Dyestuff Formulation Recipe</th>
                <th class="text-center">Spectro ΔE</th>
                <th class="text-center">Visual Match</th>
                <th>Customer Feedback</th>
                <th class="text-center">Approval</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trials as $t): ?>
                <?php 
                  $dyes = json_decode($t['dyes_formula'] ?? '[]', true); 
                  $editUrl = Url::to('/dyeing/lab-dips/edit?id=' . $t['lab_dip_id']);
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click to view lab dip request">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($t['request_no']) ?></a>
                    <div class="text-secondary small"><?= htmlspecialchars($t['shade_name']) ?></div>
                  </td>
                  <td class="text-center fw-bold font-monospace fs-6">Trial <?= $t['trial_number'] ?></td>
                  <td>
                    <?php if (is_array($dyes)): ?>
                      <?php foreach ($dyes as $d): ?>
                        <span class="badge bg-light text-dark border me-1">
                          <?= htmlspecialchars($d['dye'] ?? '') ?>: <strong><?= number_format((float)($d['dose_pct'] ?? 0), 3) ?>%</strong>
                        </span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-secondary"><?= htmlspecialchars($t['dyes_formula']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center font-monospace fw-bold <?= ($t['delta_e'] <= 0.8) ? 'text-success' : 'text-danger' ?> fs-6">
                    ΔE <?= number_format($t['delta_e'], 2) ?>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= ($t['result'] === 'match') ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase">
                      <?= htmlspecialchars($t['result']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($t['customer_feedback'] ?? 'Pending review') ?></td>
                  <td class="text-center">
                    <?php if ($t['is_approved']): ?>
                      <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Approved</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Pending</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLabDipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_lab_dip">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="lab_dip_id" id="deleteLabDipId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this lab dip request and all its trials?</p>
          <strong id="deleteLabDipNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteLabDip(id, no) {
  document.getElementById('deleteLabDipId').value = id;
  document.getElementById('deleteLabDipNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteLabDipModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
