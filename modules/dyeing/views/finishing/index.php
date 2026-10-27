<?php
/**
 * Finishing Department (Hydro Extractor, Dryer & Stenter / Compactor)
 * Dedicated Register View
 * File: modules/dyeing/views/finishing/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Finishing (Hydro, Dryer, Stenter)';
$activeModule = 'finishing';

// Handle Delete Finishing Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_finishing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $entryId = (int)($_POST['entry_id'] ?? 0);
    $target = Database::fetch("SELECT * FROM finishing_entries WHERE id = ?", [$entryId]);
    if ($target) {
        Database::execute("DELETE FROM finishing_entries WHERE id = ?", [$entryId]);
        header('Location: ' . Url::to('/dyeing/finishing?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM finishing_entries")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$entries = Database::fetchAll("
    SELECT fe.*, b.batch_no, r.shade_name, m.code as machine_code, m.name as machine_name
    FROM finishing_entries fe
    JOIN dye_batches b ON fe.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN machines m ON fe.machine_id = m.id
    ORDER BY fe.id DESC
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
            <li class="breadcrumb-item active">Finishing Operations</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-water text-dark me-2"></i>Finishing (Hydro Extractor, Relax Dryer, Stenter)</h1>
        <p class="text-secondary small mb-0">Track water extraction % removal, chamber drying temps, stenter overfeed, width, GSM, and shrinkage control.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/finishing/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Record Finishing Run
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Finishing operation run logged successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Finishing run parameters updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Finishing record removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Active Finishing Register Table (Row Click navigates to edit page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders2-vertical text-dark me-2"></i>Finishing Department Production Log</h2>
        <span class="small text-secondary"><?= count($entries) ?> Finishing Runs (Click any row to edit)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="finishingTable">
          <thead class="table-light">
            <tr>
              <th>Timestamp</th>
              <th>Batch No</th>
              <th>Finishing Stage</th>
              <th>Machine</th>
              <th class="text-end">In (Kg)</th>
              <th class="text-end">Out (Kg)</th>
              <th class="text-center">Moisture Loss</th>
              <th class="text-center">Overfeed %</th>
              <th class="text-center">Finished GSM / Width</th>
              <th class="text-center">Shrinkage (L / W)</th>
              <th>Operator</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($entries)): ?>
              <tr>
                <td colspan="12" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No finishing entries recorded yet. Click "Record Finishing Run" to start.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($entries as $e): ?>
                <?php $editUrl = Url::to('/dyeing/finishing/edit?id=' . $e['id']); ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this finishing record">
                  <td class="font-monospace text-primary fw-bold">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= date('H:i · d M', strtotime($e['created_at'])) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($e['batch_no']) ?></span>
                    <div class="text-secondary small"><?= htmlspecialchars($e['shade_name'] ?? '') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border text-uppercase">
                      <?= strtoupper(str_replace('_', ' ', $e['process_type'])) ?>
                    </span>
                  </td>
                  <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($e['machine_code']) ?></td>
                  <td class="text-end font-monospace"><?= number_format($e['input_weight_kg'], 1) ?></td>
                  <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format($e['output_weight_kg'], 1) ?></td>
                  <td class="text-center font-monospace text-danger fw-bold">-<?= number_format($e['process_loss_pct'], 1) ?>%</td>
                  <td class="text-center font-monospace">+<?= number_format($e['overfeed_pct'], 1) ?>%</td>
                  <td class="text-center">
                    <div class="fw-bold font-monospace"><?= $e['finished_gsm'] ?> GSM</div>
                    <div class="text-secondary small font-monospace"><?= $e['finished_width_inches'] ?>"</div>
                  </td>
                  <td class="text-center font-monospace small">
                    <?= $e['shrinkage_length_pct'] ?>% L / <?= $e['shrinkage_width_pct'] ?>% W
                  </td>
                  <td><?= htmlspecialchars($e['operator_name']) ?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); confirmDeleteFinish(<?= $e['id'] ?>, '<?= htmlspecialchars($e['batch_no']) ?> - <?= htmlspecialchars($e['process_type']) ?>')">
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
<div class="modal fade" id="deleteFinishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete_finishing">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="entry_id" id="deleteFinishId" value="">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Delete Record</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-3">
          <p class="mb-1">Are you sure you want to delete this finishing run?</p>
          <strong id="deleteFinishName" class="text-danger small"></strong>
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
function confirmDeleteFinish(id, name) {
  document.getElementById('deleteFinishId').value = id;
  document.getElementById('deleteFinishName').innerText = name;
  new bootstrap.Modal(document.getElementById('deleteFinishModal')).show();
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
