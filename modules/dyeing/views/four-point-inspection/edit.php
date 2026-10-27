<?php
/**
 * Dedicated Standalone Form: Update 4-Point Inspection Record
 * File: modules/dyeing/views/four-point-inspection/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Fabric Inspection';
$activeModule = 'four-point-inspection';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$insp = Database::fetch("
    SELECT fi.*, r.roll_number, p.name as product_name, u.full_name as inspector_name
    FROM fabric_inspections fi
    JOIN inventory_rolls r ON fi.roll_id = r.id
    JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON fi.inspector_id = u.id
    WHERE fi.id = ? AND fi.company_id = ?
", [$id, $companyId]);

if (!$insp) {
    header('Location: ' . Url::to('/dyeing/four-point-inspection'));
    exit;
}

// Handle Update Inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $points = (int)($_POST['total_penalty_points'] ?? $insp['total_penalty_points']);
    $disposition = trim($_POST['disposition'] ?? $insp['disposition']);
    $grade = trim($_POST['quality_grade'] ?? $insp['quality_grade']);
    $remarks = trim($_POST['remarks'] ?? $insp['remarks']);

    $lengthYds = (float)$insp['inspected_meters'] * 1.0936;
    $sqYds = ($lengthYds * (float)$insp['width_inches']) / 36;
    $pointsPer100 = ($sqYds > 0) ? round(($points * 100) / $sqYds, 2) : 0;

    Database::beginTransaction();
    try {
        Database::query("
            UPDATE fabric_inspections
            SET total_penalty_points = ?, points_per_100_sq_yd = ?, quality_grade = ?, disposition = ?, remarks = ?
            WHERE id = ? AND company_id = ?
        ", [$points, $pointsPer100, $grade, $disposition, $remarks, $id, $companyId]);

        Database::getConnection()->prepare("UPDATE inventory_rolls SET quality_grade = ? WHERE id = ?")->execute([
            $grade === 'reject' ? 'C' : $grade, $insp['roll_id']
        ]);

        Database::commit();
        header('Location: ' . Url::to('/dyeing/four-point-inspection?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update inspection: ' . $e->getMessage();
    }
}

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/four-point-inspection') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Fabric Quality Inspection</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($insp['inspection_no']) ?></span>
            <span class="badge bg-primary">GRADE <?= htmlspecialchars($insp['quality_grade']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Roll <?= htmlspecialchars($insp['roll_number']) ?> · <?= htmlspecialchars($insp['product_name']) ?> · Inspected on <?= date('d M Y', strtotime($insp['inspection_date'])) ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/four-point-inspection') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editInspForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Inspection Changes
        </button>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form id="editInspForm" method="POST" action="<?= Url::to('/dyeing/four-point-inspection/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_inspection">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Inspection Penalty Scoring & Disposition</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Inspected Meters</label>
                  <input type="text" class="form-control form-control-sm bg-light font-monospace" readonly value="<?= number_format($insp['inspected_meters'], 1) ?> m">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Width / GSM</label>
                  <input type="text" class="form-control form-control-sm bg-light font-monospace" readonly value="<?= $insp['width_inches'] ?>&quot; / <?= $insp['gsm'] ?> GSM">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Total Penalty Points *</label>
                  <input type="number" min="0" name="total_penalty_points" class="form-control form-control-sm font-monospace fw-bold" required value="<?= htmlspecialchars($insp['total_penalty_points']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Quality Grade</label>
                  <select name="quality_grade" class="form-select form-select-sm">
                    <option value="A" <?= ($insp['quality_grade'] === 'A') ? 'selected' : '' ?>>Grade A (&le; 20 pts / 100 sq yd)</option>
                    <option value="B" <?= ($insp['quality_grade'] === 'B') ? 'selected' : '' ?>>Grade B (21 - 28 pts)</option>
                    <option value="C" <?= ($insp['quality_grade'] === 'C') ? 'selected' : '' ?>>Grade C (29 - 40 pts)</option>
                    <option value="reject" <?= ($insp['quality_grade'] === 'reject') ? 'selected' : '' ?>>Rejected (&gt; 40 pts)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Disposition Decision</label>
                  <select name="disposition" class="form-select form-select-sm">
                    <option value="accepted" <?= ($insp['disposition'] === 'accepted') ? 'selected' : '' ?>>Accepted for Dispatch</option>
                    <option value="rework" <?= ($insp['disposition'] === 'rework') ? 'selected' : '' ?>>Rework / Re-compact</option>
                    <option value="downgrade" <?= ($insp['disposition'] === 'downgrade') ? 'selected' : '' ?>>Concession / Downgrade</option>
                    <option value="rejected" <?= ($insp['disposition'] === 'rejected') ? 'selected' : '' ?>>Scrap / Rejection</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small text-secondary fw-semibold">Inspector Remarks</label>
                  <textarea name="remarks" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($insp['remarks'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Audit Log</h2>
            </div>
            <div class="p-3 small text-secondary">
              <div class="mb-2"><strong>Inspector:</strong> <?= htmlspecialchars($insp['inspector_name'] ?? 'Quality Officer') ?></div>
              <div class="mb-2"><strong>Score:</strong> <span class="font-monospace fw-bold text-dark"><?= number_format($insp['points_per_100_sq_yd'], 2) ?> pts / 100 sq yd</span></div>
              <div class="mb-2"><strong>Recorded Date:</strong> <?= date('d M Y', strtotime($insp['inspection_date'])) ?></div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
