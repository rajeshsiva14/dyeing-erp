<?php
/**
 * Dedicated Full-Page Form: Update Dyeing Stock Transfer Memo
 * File: modules/dyeing/views/stock-transfer/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Stock Transfer Memo';
$activeModule = 'stock-transfer';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$trId = (int)($_GET['id'] ?? 0);

$transfer = Database::fetch("SELECT * FROM dyeing_stock_transfers WHERE id = ?", [$trId]);
if (!$transfer) {
    header('Location: ' . Url::to('/dyeing/stock-transfer?error=not_found'));
    exit;
}

$errors = [];

// Handle Update Stock Transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_transfer') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $srcWh = trim($_POST['source_warehouse'] ?? $transfer['source_warehouse']);
    $destWh = trim($_POST['dest_warehouse'] ?? $transfer['dest_warehouse']);
    $fabric = trim($_POST['fabric_name'] ?? $transfer['fabric_name']);
    $shade = trim($_POST['shade_name'] ?? $transfer['shade_name']);
    $rolls = (int)($_POST['rolls_count'] ?? $transfer['rolls_count']);
    $weightKg = (float)($_POST['total_weight_kg'] ?? $transfer['total_weight_kg']);
    $sender = trim($_POST['sender_name'] ?? $transfer['sender_name']);
    $receiver = trim($_POST['receiver_name'] ?? $transfer['receiver_name']);
    $vehicle = trim($_POST['vehicle_no'] ?? $transfer['vehicle_no']);
    $status = $_POST['status'] ?? $transfer['status'];

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_stock_transfers SET
                source_warehouse = ?,
                dest_warehouse = ?,
                fabric_name = ?,
                shade_name = ?,
                rolls_count = ?,
                total_weight_kg = ?,
                sender_name = ?,
                receiver_name = ?,
                vehicle_no = ?,
                status = ?
            WHERE id = ?
        ")->execute([
            $srcWh,
            $destWh,
            $fabric,
            $shade,
            $rolls,
            $weightKg,
            $sender,
            $receiver,
            $vehicle,
            $status,
            $trId
        ]);

        header('Location: ' . Url::to('/dyeing/stock-transfer?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update transfer: ' . $e->getMessage();
    }
}

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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/stock-transfer') ?>">Stock Transfers</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($transfer['transfer_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Transfer Memo #<?= htmlspecialchars($transfer['transfer_no']) ?></h1>
        <p class="text-secondary small mb-0">Update dispatch status, receiving storekeeper sign-off, or gate pass transport details.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/stock-transfer') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="action" value="update_transfer">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Routing Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2"></i>Warehouse & Gate Pass Routing</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Transfer Memo No</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($transfer['transfer_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Batch Reference</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($transfer['batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Source Warehouse *</label>
                <input type="text" name="source_warehouse" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['source_warehouse']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Destination Warehouse *</label>
                <input type="text" name="dest_warehouse" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['dest_warehouse']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Vehicle / Delivery Van No</label>
                <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($transfer['vehicle_no']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Status *</label>
                <select name="status" class="form-select form-select-sm fw-bold" required>
                  <option value="draft" <?= $transfer['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                  <option value="dispatched" <?= $transfer['status'] === 'dispatched' ? 'selected' : '' ?>>Dispatched (In Transit)</option>
                  <option value="received" <?= $transfer['status'] === 'received' ? 'selected' : '' ?>>Received (Signed Off)</option>
                  <option value="reconciled" <?= $transfer['status'] === 'reconciled' ? 'selected' : '' ?>>Reconciled</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Fabric & Quantities -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2"></i>Fabric Details & Quantities</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Fabric Specification *</label>
                  <input type="text" name="fabric_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['fabric_name']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Shade / Color Name *</label>
                  <input type="text" name="shade_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['shade_name']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Total Packaged Rolls *</label>
                  <input type="number" name="rolls_count" class="form-control form-control-sm fw-bold" value="<?= (int)$transfer['rolls_count'] ?>" required min="1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Total Net Weight (Kg) *</label>
                  <input type="number" step="0.01" name="total_weight_kg" class="form-control form-control-sm fw-bold text-success" value="<?= (float)$transfer['total_weight_kg'] ?>" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Signatures & Authorization -->
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-pen me-2"></i>Sign-off & Custody Handover</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Dispatching Officer (Sender) *</label>
                  <input type="text" name="sender_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['sender_name']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Receiving Storekeeper (Receiver) *</label>
                  <input type="text" name="receiver_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($transfer['receiver_name']) ?>" required>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/stock-transfer') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Transfer Memo
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
