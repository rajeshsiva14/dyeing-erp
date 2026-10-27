<?php
/**
 * Dedicated Full-Page Form: Create Dyeing Stock Transfer & Dispatch Memo
 * File: modules/dyeing/views/stock-transfer/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Create Stock Transfer Memo';
$activeModule = 'stock-transfer';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle New Stock Transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_transfer') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $trNo = 'DST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid completed dye batch.';
    }

    if (!$errors) {
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        $srcWh = trim($_POST['source_warehouse'] ?? 'Dyeing Finished Floor');
        $destWh = trim($_POST['dest_warehouse'] ?? 'Garment Cutting Store / Packing');
        $fabric = trim($_POST['fabric_name'] ?? '100% Combed Cotton Single Jersey 30s');
        $shade = trim($_POST['shade_name'] ?? 'Royal Navy Blue');
        $rolls = (int)($_POST['rolls_count'] ?? 40);
        $weightKg = (float)($_POST['total_weight_kg'] ?? 955.0);
        $sender = trim($_POST['sender_name'] ?? ($currentUser['name'] ?? 'S. Natarajan (Dyeing Floor Lead)'));
        $receiver = trim($_POST['receiver_name'] ?? 'K. Palanisamy (Finished Stores)');
        $vehicle = trim($_POST['vehicle_no'] ?? 'TN-39-AT-1290');

        try {
            Database::insert('dyeing_stock_transfers', [
                'company_id'       => $companyId,
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
        } catch (\Throwable $e) {
            $errors[] = 'Failed to create transfer: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
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
            <li class="breadcrumb-item active">New Transfer Memo</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-truck text-dark me-2"></i>Create Finished Fabric Transfer Memo</h1>
        <p class="text-secondary small mb-0">Dispatch finished rolls from Dyeing floor to central warehouse or garment sewing/cutting unit.</p>
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
      <input type="hidden" name="action" value="save_transfer">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Origin & Destination Warehouses -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2"></i>Warehouse & Gate Pass Routing</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Source Warehouse *</label>
                <select name="source_warehouse" class="form-select form-select-sm" required>
                  <option value="Dyeing Finished Floor" selected>Dyeing Finished Inspection Floor</option>
                  <option value="Finishing & Stenter Bay">Finishing & Stenter Bay</option>
                  <option value="Compacting / Heat Setting Floor">Compacting / Heat Setting Floor</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Destination Warehouse *</label>
                <select name="dest_warehouse" class="form-select form-select-sm" required>
                  <option value="Garment Cutting Store / Packing" selected>Garment Cutting Store / Unit 2</option>
                  <option value="Central Finished Goods Warehouse">Central Finished Goods Warehouse (Tirupur)</option>
                  <option value="Export Logistics / Container Bay">Export Logistics / Container Bay</option>
                  <option value="External Job-Work Customer">External Customer Warehouse</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Vehicle / Delivery Van No</label>
                <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace fw-bold" value="TN-39-AT-1290">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Dispatch Date</label>
                <input type="text" class="form-control form-control-sm bg-light" value="<?= date('d M Y') ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Fabric & Quantities -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2"></i>Batch Reference & Fabric Description</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Select Production Batch *</label>
                  <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateBatch(this)">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                      <option value="<?= $b['id'] ?>" data-shade="<?= htmlspecialchars($b['shade_name']) ?>" data-weight="<?= $b['actual_loaded_kg'] ?>">
                        <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Shade / Color Name *</label>
                  <input type="text" name="shade_name" id="trShadeName" class="form-control form-control-sm fw-bold" value="Royal Navy Blue" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Fabric Specification *</label>
                  <input type="text" name="fabric_name" class="form-control form-control-sm fw-bold" value="100% Combed Cotton Single Jersey 30s (160 GSM · 48&quot;)" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Total Packaged Rolls *</label>
                  <input type="number" name="rolls_count" class="form-control form-control-sm fw-bold" value="40" required min="1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Total Net Weight (Kg) *</label>
                  <input type="number" step="0.01" name="total_weight_kg" id="trWeightKg" class="form-control form-control-sm fw-bold text-success" value="955.00" required>
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
                  <input type="text" name="sender_name" class="form-control form-control-sm fw-bold" value="S. Natarajan (Dyeing Floor Lead)" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Receiving Storekeeper (Receiver) *</label>
                  <input type="text" name="receiver_name" class="form-control form-control-sm fw-bold" value="K. Palanisamy (Finished Stores)" required>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/stock-transfer') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-truck me-1"></i> Create Transfer Memo & Issue Gate Pass
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function populateBatch(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.shade) {
    document.getElementById('trShadeName').value = opt.dataset.shade;
  }
  if (opt && opt.dataset.weight) {
    document.getElementById('trWeightKg').value = parseFloat(opt.dataset.weight).toFixed(2);
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
