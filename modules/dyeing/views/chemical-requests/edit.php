<?php
/**
 * Dedicated Standalone Form: Update Chemical Issue & Consumption
 * File: modules/dyeing/views/chemical-requests/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Chemical Issue & Consumption';
$activeModule = 'chemical-requests';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$request = Database::fetch("SELECT * FROM chemical_issue_requests WHERE id = ? AND company_id = ?", [$id, $companyId]);

if (!$request) {
    header('Location: ' . Url::to('/dyeing/chemical-requests'));
    exit;
}

$items = Database::fetchAll("SELECT * FROM chemical_issue_items WHERE request_id = ? ORDER BY id ASC", [$id]);

// Handle POST Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_request') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $status = trim($_POST['status'] ?? $request['status']);
    $approvedBy = trim($_POST['approved_by'] ?? $request['approved_by']);
    $returnedQtys = $_POST['returned_qty'] ?? [];
    $actualConsumed = $_POST['actual_consumed_qty'] ?? [];

    Database::beginTransaction();
    try {
        Database::query("UPDATE chemical_issue_requests SET status = ?, approved_by = ?, updated_at = NOW() WHERE id = ? AND company_id = ?", [
            $status, $approvedBy, $id, $companyId
        ]);

        foreach ($items as $item) {
            $itemId = $item['id'];
            $ret = isset($returnedQtys[$itemId]) ? (float)$returnedQtys[$itemId] : (float)$item['returned_qty'];
            $cons = isset($actualConsumed[$itemId]) ? (float)$actualConsumed[$itemId] : (float)$item['actual_consumed_qty'];

            Database::query("UPDATE chemical_issue_items SET returned_qty = ?, actual_consumed_qty = ? WHERE id = ? AND request_id = ?", [
                $ret, $cons, $itemId, $id
            ]);
        }

        Database::commit();
        header('Location: ' . Url::to('/dyeing/chemical-requests?updated=1&req_id=' . $id));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update chemical request: ' . $e->getMessage();
    }
}

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Document Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Chemical Issue & Reconciliation</h1>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($request['status']) ?></span>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($request['request_no']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Batch <?= htmlspecialchars($request['batch_no']) ?> · Requested <?= date('d M Y', strtotime($request['request_date'])) ?> <?= $request['required_time'] ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editChemForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Reconciliation
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

    <form id="editChemForm" method="POST" action="<?= Url::to('/dyeing/chemical-requests/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_request">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4 mb-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Reconcile Issued vs Actual Consumed Dyes & Chemicals</h2>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Material</th>
                    <th>Type</th>
                    <th class="text-end">Required (Kg)</th>
                    <th class="text-end">Issued (Kg)</th>
                    <th class="text-end" style="width: 140px;">Returned Unused (Kg)</th>
                    <th class="text-end" style="width: 140px;">Actual Consumed (Kg)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $it): ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($it['chemical_name']) ?></div>
                        <code class="text-muted"><?= htmlspecialchars($it['lot_number'] ?: 'LOT-DISP-01') ?></code>
                      </td>
                      <td><span class="badge bg-secondary"><?= strtoupper($it['item_type']) ?></span></td>
                      <td class="text-end font-monospace"><?= number_format($it['required_qty'], 3) ?></td>
                      <td class="text-end font-monospace fw-bold text-primary"><?= number_format($it['issued_qty'], 3) ?></td>
                      <td>
                        <input type="number" step="0.001" min="0" name="returned_qty[<?= $it['id'] ?>]" class="form-control form-control-sm text-end font-monospace" value="<?= htmlspecialchars($it['returned_qty']) ?>">
                      </td>
                      <td>
                        <input type="number" step="0.001" min="0" name="actual_consumed_qty[<?= $it['id'] ?>]" class="form-control form-control-sm text-end font-monospace fw-bold" value="<?= htmlspecialchars($it['actual_consumed_qty']) ?>">
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Indent Metadata</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Requester</label>
                <input type="text" class="form-control form-control-sm" readonly value="<?= htmlspecialchars($request['requester_name']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="pending" <?= ($request['status'] === 'pending') ? 'selected' : '' ?>>Pending Dispensing</option>
                  <option value="approved" <?= ($request['status'] === 'approved') ? 'selected' : '' ?>>Approved & Issued</option>
                  <option value="completed" <?= ($request['status'] === 'completed') ? 'selected' : '' ?>>Fully Reconciled & Closed</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Approved / Checked By</label>
                <input type="text" name="approved_by" class="form-control form-control-sm" value="<?= htmlspecialchars($request['approved_by'] ?? $currentUser['name'] ?? 'Store Supervisor') ?>">
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
