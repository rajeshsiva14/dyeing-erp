<?php
/**
 * Dedicated Register View: ASTM 4-Point Fabric Quality Inspection
 * File: modules/dyeing/views/four-point-inspection/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = '4-Point Fabric Inspection';
$activeModule = 'four-point-inspection';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle Delete Inspection Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $delId = (int)($_POST['id'] ?? 0);
    Database::query("DELETE FROM fabric_inspections WHERE id = ? AND company_id = ?", [$delId, $companyId]);
    header('Location: ' . Url::to('/dyeing/four-point-inspection?deleted=1'));
    exit;
}

$inspections = Database::fetchAll("
    SELECT fi.*, r.roll_number, r.weight_kg, p.name as product_name, u.full_name as inspector_name 
    FROM fabric_inspections fi 
    JOIN inventory_rolls r ON fi.roll_id = r.id 
    JOIN products p ON r.product_id = p.id 
    LEFT JOIN users u ON fi.inspector_id = u.id 
    WHERE fi.company_id = ? 
    ORDER BY fi.id DESC
", [$companyId]);

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
            <li class="breadcrumb-item active">4-Point Quality Inspection</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-patch-check text-dark me-2"></i>ASTM D5430 4-Point System Fabric Inspection</h1>
        <p class="text-secondary small mb-0">International standard grading for knitted and woven fabric rolls based on penalty points per 100 square yards.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/four-point-inspection/create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Roll Inspection
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Fabric roll 4-point inspection recorded and quality grade updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Inspection disposition and grade score updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Inspection record removed from register.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Standards Reference Card -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-success shadow-sm">
          <div class="text-secondary small fw-bold">GRADE A (PREMIUM)</div>
          <div class="fs-4 fw-bold text-success">&le; 20 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Export Eligible</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-primary shadow-sm">
          <div class="text-secondary small fw-bold">GRADE B (COMMERCIAL)</div>
          <div class="fs-4 fw-bold text-primary">21 – 28 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Domestic Standard</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-warning shadow-sm">
          <div class="text-secondary small fw-bold">GRADE C (DOWNGRADE)</div>
          <div class="fs-4 fw-bold text-warning">29 – 40 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Concession / Second</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-danger shadow-sm">
          <div class="text-secondary small fw-bold">REJECTION</div>
          <div class="fs-4 fw-bold text-danger">&gt; 40 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Or Holes &gt; 2 Inches · Scrap Disposition</div>
        </div>
      </div>
    </div>

    <!-- Inspections Table (Click anywhere on row to update) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-dark me-2"></i>Fabric Roll Inspection Register</h2>
        <span class="small text-secondary"><?= count($inspections) ?> Rolls Inspected</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Inspection No</th>
              <th>Roll Barcode</th>
              <th>Fabric Product</th>
              <th class="text-end">Length</th>
              <th class="text-center">Width / GSM</th>
              <th class="text-center">Defect Points</th>
              <th class="text-center">Pts / 100 sq yd</th>
              <th>Grade</th>
              <th>Disposition</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inspections)): ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No roll inspection records found. Click "New Roll Inspection" to record an ASTM 4-point test.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inspections as $ins): ?>
                <?php 
                  $editUrl = Url::to('/dyeing/four-point-inspection/edit?id=' . $ins['id']);
                  $gradeClass = ($ins['quality_grade'] === 'A') ? 'bg-success' : (($ins['quality_grade'] === 'B') ? 'bg-primary' : (($ins['quality_grade'] === 'C') ? 'bg-warning text-dark' : 'bg-danger'));
                ?>
                <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update this inspection record">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($ins['inspection_no']) ?></a>
                  </td>
                  <td>
                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($ins['roll_number']) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($ins['product_name']) ?></div>
                  </td>
                  <td class="text-end font-monospace"><?= number_format($ins['inspected_meters'], 1) ?> m</td>
                  <td class="text-center font-monospace"><?= $ins['width_inches'] ?>" / <?= $ins['gsm'] ?></td>
                  <td class="text-center font-monospace fw-bold"><?= $ins['total_penalty_points'] ?> pts</td>
                  <td class="text-center font-monospace fw-bold text-dark"><?= number_format($ins['points_per_100_sq_yd'], 2) ?></td>
                  <td>
                    <span class="badge <?= $gradeClass ?> text-uppercase">Grade <?= htmlspecialchars($ins['quality_grade']) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($ins['disposition']) ?></span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/print/inspection-report?id=' . $ins['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print ASTM Certificate" onclick="event.stopPropagation();">
                        <i class="bi bi-printer"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger" title="Delete Record" onclick="event.stopPropagation(); openDeleteModal(<?= $ins['id'] ?>, '<?= htmlspecialchars($ins['inspection_no']) ?>');">
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
    </div>

  </div>
</main>

<!-- Delete Modal -->
<div class="modal fade" id="deleteInspModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-6 fw-bold">Delete Inspection Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        Are you sure you want to delete inspection <strong id="deleteInspNo"></strong>?
      </div>
      <div class="modal-footer p-2">
        <form method="POST" action="<?= Url::to('/dyeing/four-point-inspection') ?>">
          <?= CSRF::getField() ?>
          <input type="hidden" name="action" value="delete_inspection">
          <input type="hidden" name="id" id="deleteInspId">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openDeleteModal(id, inspNo) {
  document.getElementById('deleteInspId').value = id;
  document.getElementById('deleteInspNo').textContent = inspNo;
  new bootstrap.Modal(document.getElementById('deleteInspModal')).show();
}
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
