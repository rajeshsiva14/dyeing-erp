<?php
/**
 * Dyeing Stock Transfer & Dispatch Handover
 * Transferring QC-approved finished fabric rolls from Dyeing to Finished Warehouse / Garments
 * File: modules/dyeing/views/stock-transfer.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Stock Transfer';
$activeModule = 'stock-transfer';

// Handle New Stock Transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_transfer') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $trNo = 'DST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 3, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    
    $srcWh = trim($_POST['source_warehouse'] ?? 'Dyeing Finished Floor');
    $destWh = trim($_POST['dest_warehouse'] ?? 'Garment Cutting Store / Packing');
    $fabric = trim($_POST['fabric_name'] ?? '100% Combed Cotton Single Jersey 30s');
    $shade = trim($_POST['shade_name'] ?? 'Royal Navy Blue');
    $rolls = (int)($_POST['rolls_count'] ?? 40);
    $weightKg = (float)($_POST['total_weight_kg'] ?? 955.0);
    $sender = trim($_POST['sender_name'] ?? 'S. Natarajan (Dyeing Floor Lead)');
    $receiver = trim($_POST['receiver_name'] ?? 'K. Palanisamy (Finished Stores)');
    $vehicle = trim($_POST['vehicle_no'] ?? 'TN-39-AT-1290');

    Database::insert('dyeing_stock_transfers', [
        'company_id'       => 1,
        'transfer_no'      => $trNo,
        'transfer_date'    => date('Y-m-d'),
        'source_warehouse' => $srcWh,
        'dest_warehouse'   => $destWh,
        'dye_batch_id'     => $batchId,
        'batch_no'         => $batchNo,
        'fabric_name'      => $fabric,
        'shade_name'       => $shade,
        'rolls_count'      => $rolls,
        'total_weight_kg'  => $weightKg,
        'sender_name'      => $sender,
        'receiver_name'    => $receiver,
        'vehicle_no'       => $vehicle,
        'status'           => 'dispatched'
    ]);

    header('Location: ' . Url::to('/dyeing/stock-transfer?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$transfers = Database::fetchAll("SELECT * FROM dyeing_stock_transfers ORDER BY id DESC");
$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Stock Transfers</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-truck text-primary me-2"></i>Dyeing Stock Transfer & Dispatch Handover</h1>
        <p class="text-secondary small mb-0">Hand over QC-approved finished rolls to Central Warehouse, Garment Cutting, or Customer Dispatch with gate pass audit.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newTransferModal">
          <i class="bi bi-plus-lg me-1"></i> New Transfer Memo
        </button>
      </div>
    </div>

    <!-- Stock Transfers Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-arrow-right-circle text-primary me-2"></i>Finished Fabric Transfer Memos</h2>
        <span class="small text-secondary"><?= count($transfers) ?> Transfers</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Transfer No</th>
              <th>Batch No</th>
              <th>Source &rarr; Destination Warehouse</th>
              <th>Fabric & Shade</th>
              <th class="text-center">Rolls</th>
              <th class="text-end">Total Weight (Kg)</th>
              <th>Sign-off (Sender / Receiver)</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transfers as $tr): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($tr['transfer_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($tr['batch_no']) ?></span></td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($tr['source_warehouse']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">&rarr; <?= htmlspecialchars($tr['dest_warehouse']) ?></div>
                </td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($tr['fabric_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($tr['shade_name']) ?></div>
                </td>
                <td class="text-center fw-bold"><?= $tr['rolls_count'] ?></td>
                <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format($tr['total_weight_kg'], 2) ?></td>
                <td>
                  <div class="small"><?= htmlspecialchars($tr['sender_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Rec: <?= htmlspecialchars($tr['receiver_name'] ?? 'Pending') ?></div>
                </td>
                <td>
                  <span class="badge <?= $tr['status'] === 'received' ? 'bg-success' : 'bg-primary' ?> text-uppercase">
                    <?= htmlspecialchars($tr['status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?= Url::to('/print/sales-order?id=1') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i> Delivery Challan
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Stock Transfer Modal -->
<div class="modal fade" id="newTransferModal" tabindex="-1" aria-labelledby="newTransferModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_transfer">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newTransferModalLabel"><i class="bi bi-truck text-primary me-2"></i>Issue Finished Fabric Transfer</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select QC-Approved Dyeing Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Source Warehouse *</label>
              <input type="text" name="source_warehouse" class="form-control form-control-sm" value="Dyeing Finished Floor" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Destination Warehouse *</label>
              <select name="dest_warehouse" class="form-select form-select-sm" required>
                <option value="Garment Cutting Store / Packing">Garment Cutting Store</option>
                <option value="Central Fabric Warehouse - Bay 4">Central Fabric Warehouse</option>
                <option value="Export Dispatch Bay">Export Dispatch Bay</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Fabric Name *</label>
              <input type="text" name="fabric_name" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Shade Name *</label>
              <input type="text" name="shade_name" class="form-control form-control-sm" value="Royal Navy Blue" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Rolls Count *</label>
              <input type="number" name="rolls_count" class="form-control form-control-sm" value="40" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-success">Total Weight (Kg) *</label>
              <input type="number" step="0.01" name="total_weight_kg" class="form-control form-control-sm fw-bold" value="955.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Vehicle No</label>
              <input type="text" name="vehicle_no" class="form-control form-control-sm" value="TN-39-AT-1290">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Sender (Dyeing Sign-off) *</label>
              <input type="text" name="sender_name" class="form-control form-control-sm" value="S. Natarajan (Dyeing Floor Lead)" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Receiver In-charge *</label>
              <input type="text" name="receiver_name" class="form-control form-control-sm" value="K. Palanisamy (Cutting In-charge)" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-truck me-1"></i> Authorize Transfer Memo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
