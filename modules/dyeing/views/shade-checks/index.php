<?php
/**
 * In-process Sample & Shade Check
 * Dedicated Register View
 * File: modules/dyeing/views/shade-checks/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'In-Process Shade Check';
$activeModule = 'shade-checks';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Shade Check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_shade_check') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $scId = (int)($_POST['check_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM dyeing_shade_checks WHERE id = ?", [$scId]);
    if ($target) {
        Database::execute("DELETE FROM dyeing_shade_checks WHERE id = ?", [$scId]);
        header('Location: ' . Url::to('/dyeing/shade-checks?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dyeing_shade_checks")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$checks = Database::fetchAll("
    SELECT sc.*, b.actual_loaded_kg, r.shade_name as batch_shade, m.name as machine_name
    FROM dyeing_shade_checks sc
    JOIN dye_batches b ON sc.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    LEFT JOIN machines m ON b.machine_id = m.id
    ORDER BY sc.id DESC
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
            <li class="breadcrumb-item active">In-process Shade Check</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-palette text-dark me-2"></i>In-Process Sample & Spectrophotometer Shade Check</h1>
        <p class="text-secondary small mb-0">Monitor live batch color matching, L* a* b* color coordinates, ΔE tolerance (&le; 0.8), and add shading dyes if needed.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/shade-checks/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Record Mid-Process Sample
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['logged'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Mid-process shade swatch recorded and ΔE evaluation saved.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Shade check reading updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Shade check record removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Shade Checks Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-patch-check text-dark me-2"></i>In-Process Sampling & ΔE Match Register</h2>
        <span class="small text-secondary"><?= count($checks) ?> Shade Tests (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="shadeCheckTable">
          <thead class="table-light">
            <tr>
              <th>Sample ID</th>
              <th>Batch No</th>
              <th>Sampling Stage</th>
              <th>Reference Standard</th>
              <th class="text-center">Light Box</th>
              <th class="text-center">ΔE CMC</th>
              <th class="text-center">L* a* b* Readings</th>
              <th>Visual Comparison</th>
              <th>QC Decision</th>
              <th>Reviewer</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($checks)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No in-process shade tests found. Click "Record Mid-Process Sample" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($checks as $sc): ?>
                <?php $editUrl = Url::to('/dyeing/shade-checks/edit?id=' . $sc['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this shade check">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($sc['sample_id']) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($sc['batch_no']) ?></span>
                    <div class="text-secondary small"><?= htmlspecialchars($sc['machine_name']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($sc['sampling_stage']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= date('H:i · d M', strtotime($sc['sampled_at'])) ?></div>
                  </td>
                  <td><?= htmlspecialchars($sc['reference_shade']) ?></td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($sc['light_source']) ?></span>
                  </td>
                  <td class="text-center font-monospace fw-bold fs-6 <?= ($sc['delta_e'] <= 0.8) ? 'text-success' : 'text-danger' ?>">
                    ΔE <?= number_format($sc['delta_e'], 2) ?>
                  </td>
                  <td class="text-center font-monospace small">
                    L: <?= $sc['l_val'] ?><br>a: <?= $sc['a_val'] ?>, b: <?= $sc['b_val'] ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?= strtoupper(str_replace('_', ' ', $sc['visual_assessment'])) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= ($sc['qc_decision'] === 'pass') ? 'bg-success' : (($sc['qc_decision'] === 'add_dyes') ? 'bg-warning text-dark' : 'bg-danger') ?> text-uppercase">
                      <?= strtoupper(str_replace('_', ' ', $sc['qc_decision'])) ?>
                    </span>
                  </td>
                  <td class="small"><?= htmlspecialchars($sc['qc_reviewer']) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteShade(<?= $sc['id'] ?>, '<?= htmlspecialchars($sc['sample_id']) ?>')">
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
<div class="modal fade" id="deleteShadeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_shade_check">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="check_id" id="deleteShadeId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Sample</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this in-process shade test?</p>
          <strong id="deleteShadeNo" class="text-danger font-monospace"></strong>
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
function confirmDeleteShade(id, no) {
  document.getElementById('deleteShadeId').value = id;
  document.getElementById('deleteShadeNo').innerText = no;
  new bootstrap.Modal(document.getElementById('deleteShadeModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
