<?php
/**
 * Dedicated Standalone Form: Update Subcontract Job-Work Challan & Returns
 * File: modules/dyeing/views/job-work/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Job-Work Challan';
$activeModule = 'job-work';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$challan = Database::fetch("SELECT * FROM dyeing_job_work_challans WHERE id = ? AND company_id = ?", [$id, $companyId]);

if (!$challan) {
    header('Location: ' . Url::to('/dyeing/job-work'));
    exit;
}

// Handle Update Challan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_challan') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $receivedRolls = (int)($_POST['received_rolls'] ?? $challan['received_rolls']);
    $receivedWeight = (float)($_POST['received_weight_kg'] ?? $challan['received_weight_kg']);
    $status = trim($_POST['status'] ?? $challan['status']);
    $vehicleNo = trim($_POST['vehicle_no'] ?? $challan['vehicle_no']);
    $rate = (float)($_POST['process_rate_per_kg'] ?? $challan['process_rate_per_kg']);

    $loss = round($challan['dispatched_weight_kg'] - $receivedWeight, 2);

    Database::beginTransaction();
    try {
        Database::query("
            UPDATE dyeing_job_work_challans
            SET received_rolls = ?, received_weight_kg = ?, weight_loss_kg = ?, status = ?, vehicle_no = ?, process_rate_per_kg = ?
            WHERE id = ? AND company_id = ?
        ", [$receivedRolls, $receivedWeight, $loss, $status, $vehicleNo, $rate, $id, $companyId]);

        Database::commit();
        header('Location: ' . Url::to('/dyeing/job-work?updated=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to update challan: ' . $e->getMessage();
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
        <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Job-Work Return Reconciliation</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($challan['challan_no']) ?></span>
            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($challan['status']) ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1"><?= htmlspecialchars($challan['partner_name']) ?> · Dispatched <?= date('d M Y', strtotime($challan['challan_date'])) ?> · <?= htmlspecialchars($challan['process_type']) ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <a href="<?= Url::to('/print/job-work-challan?id=' . $challan['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-printer me-1"></i> Print Delivery Challan
        </a>
        <button type="submit" form="editChallanForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Inward Return
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

    <form id="editChallanForm" method="POST" action="<?= Url::to('/dyeing/job-work/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_challan">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Inward Material Receipt & Weight Loss</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Dispatched Weight (Kg)</label>
                  <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold" readonly value="<?= number_format($challan['dispatched_weight_kg'], 2) ?> kg (<?= $challan['dispatched_rolls'] ?> Rolls)">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Target Shade</label>
                  <input type="text" class="form-control form-control-sm bg-light" readonly value="<?= htmlspecialchars($challan['required_shade']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Received Rolls</label>
                  <input type="number" min="0" name="received_rolls" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($challan['received_rolls']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Received Weight (Kg)</label>
                  <input type="number" step="0.1" min="0" name="received_weight_kg" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($challan['received_weight_kg']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Vehicle Number</label>
                  <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($challan['vehicle_no'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Process Rate (₹ / Kg)</label>
                  <input type="number" step="0.5" name="process_rate_per_kg" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($challan['process_rate_per_kg']) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Status & Reconciliation</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Challan Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="dispatched" <?= ($challan['status'] === 'dispatched') ? 'selected' : '' ?>>Dispatched (At Mill)</option>
                  <option value="in_process" <?= ($challan['status'] === 'in_process') ? 'selected' : '' ?>>In-Process</option>
                  <option value="received" <?= ($challan['status'] === 'received') ? 'selected' : '' ?>>Received Inward</option>
                  <option value="qc_passed" <?= ($challan['status'] === 'qc_passed') ? 'selected' : '' ?>>QC Passed</option>
                  <option value="settled" <?= ($challan['status'] === 'settled') ? 'selected' : '' ?>>Settled & Billed</option>
                </select>
              </div>
              <div class="p-2 rounded bg-light border small text-secondary">
                <div><strong>Current Loss:</strong> <?= number_format($challan['weight_loss_kg'], 2) ?> Kg</div>
                <div><strong>Formula:</strong> Loss = Dispatched − Received</div>
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
