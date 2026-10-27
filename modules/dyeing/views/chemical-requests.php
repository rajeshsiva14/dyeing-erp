<?php
/**
 * Chemical Issue Request, Return & Actual Consumption
 * Color kitchen store indent, weighing scale verification, return and recipe variance
 * File: modules/dyeing/views/chemical-requests.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Services\InventoryService;

$pageTitle = 'Chemical Issue & Consumption';
$activeModule = 'chemical-requests';

// Handle New Chemical Issue Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_request') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $reqNo = 'CIR-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    $recipeId = $batch['dye_recipe_id'] ?? 1;

    Database::beginTransaction();
    try {
        $reqId = Database::insert('chemical_issue_requests', [
            'company_id'             => 1,
            'request_no'             => $reqNo,
            'dye_batch_id'           => $batchId,
            'batch_no'               => $batchNo,
            'recipe_id'              => $recipeId,
            'request_date'           => date('Y-m-d'),
            'required_time'          => date('H:i:s'),
            'requester_name'         => trim($_POST['requester_name'] ?? 'M. Ramanathan (Vessel Operator)'),
            'warehouse_id'           => 2,
            'total_chemicals_count'  => 3,
            'status'                 => 'approved',
            'approved_by'            => 'S. Natarajan (Dye House Mgr)'
        ]);

        $chemicalsToIssue = [
            ['id' => 6, 'name' => 'Glauber Salt (Sodium Sulphate)', 'type' => 'salt', 'qty' => 45.000, 'lot' => 'LOT-GLB-26-01', 'cost' => 18.50],
            ['id' => 7, 'name' => 'Soda Ash Light', 'type' => 'alkali', 'qty' => 18.000, 'lot' => 'LOT-SDA-26-05', 'cost' => 32.00],
            ['id' => 5, 'name' => 'Reactive Navy Blue ED-2B', 'type' => 'dye', 'qty' => 14.500, 'lot' => 'LOT-DY-NV-88', 'cost' => 420.00]
        ];

        foreach ($chemicalsToIssue as $chem) {
            Database::insert('chemical_issue_items', [
                'request_id'          => $reqId,
                'product_id'          => $chem['id'],
                'chemical_name'       => $chem['name'],
                'item_type'           => $chem['type'],
                'required_qty'        => $chem['qty'],
                'issued_qty'          => $chem['qty'],
                'returned_qty'        => 0.000,
                'actual_consumed_qty' => $chem['qty'],
                'uom'                 => 'Kg',
                'lot_number'          => $chem['lot']
            ]);

            // Post stock reduction movement to raw materials/chemical ledger
            InventoryService::postMovement([
                'company_id'        => 1,
                'warehouse_id'      => 2, // Dyes & Chemicals Warehouse
                'product_id'        => $chem['id'],
                'movement_type'     => 'production_issue',
                'quantity_base_uom' => -$chem['qty'],
                'unit_cost'         => $chem['cost'],
                'source_type'       => 'dye_batches',
                'source_id'         => $batchId,
                'posted_by'         => 1
            ]);
        }

        Database::commit();
        header('Location: ' . Url::to('/dyeing/chemical-requests?created=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errorMsg = $e->getMessage();
    }
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$requests = Database::fetchAll("SELECT * FROM chemical_issue_requests ORDER BY id DESC");
$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$selectedReqId = (int)($_GET['req_id'] ?? ($requests[0]['id'] ?? 1));
$items = Database::fetchAll("SELECT * FROM chemical_issue_items WHERE request_id = ? ORDER BY id ASC", [$selectedReqId]);
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Chemical Issue & Usage</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-cart-check text-primary me-2"></i>Chemical Store Issue, Return & Consumption</h1>
        <p class="text-secondary small mb-0">Color kitchen dispensing, barcode/lot scanning, tare weight verification, unconsumed returns, and recipe variance.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
          <i class="bi bi-plus-lg me-1"></i> New Store Issue Request
        </button>
      </div>
    </div>

    <!-- Active Chemical Issue Requests Table -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-inbox-fill text-primary me-2"></i>Chemical Issue Requests (Color Kitchen)</h2>
        <span class="small text-secondary"><?= count($requests) ?> Indents</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Request No</th>
              <th>Batch No</th>
              <th>Date & Time</th>
              <th>Requester</th>
              <th>Storehouse</th>
              <th class="text-center">Items</th>
              <th>Status</th>
              <th>Approved By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $req): ?>
              <tr class="<?= ($req['id'] == $selectedReqId) ? 'table-active' : '' ?>">
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($req['request_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($req['batch_no']) ?></span></td>
                <td><?= date('d M Y', strtotime($req['request_date'])) ?> <?= $req['required_time'] ?></td>
                <td><?= htmlspecialchars($req['requester_name']) ?></td>
                <td>Dyes & Chemicals Store (Bay 2)</td>
                <td class="text-center fw-bold"><?= $req['total_chemicals_count'] ?></td>
                <td>
                  <span class="badge bg-success-subtle text-success text-uppercase"><?= htmlspecialchars($req['status']) ?></span>
                </td>
                <td><?= htmlspecialchars($req['approved_by'] ?? 'Auto-approved') ?></td>
                <td class="text-end">
                  <a href="?req_id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-eye me-1"></i> View Lines
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detailed Material Issue, Return & Consumption Lines -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark">
          <i class="bi bi-check2-all text-primary me-2"></i>Issued Materials, Unused Returns & Actual Consumption Reconciliation
        </h2>
        <span class="small text-secondary">Formula: Net Used = Issued - Returned | Variance = Consumed - Planned</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Material / Chemical</th>
              <th>Type</th>
              <th>Lot Number</th>
              <th class="text-end">Required (Kg)</th>
              <th class="text-end">Issued (Kg)</th>
              <th class="text-end">Returned (Kg)</th>
              <th class="text-end">Actual Consumed (Kg)</th>
              <th class="text-center">Variance</th>
              <th class="text-end">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $totReq = 0; $totIss = 0; $totRet = 0; $totCons = 0;
              foreach ($items as $it): 
                $variance = $it['actual_consumed_qty'] - $it['required_qty'];
                $totReq += $it['required_qty'];
                $totIss += $it['issued_qty'];
                $totRet += $it['returned_qty'];
                $totCons += $it['actual_consumed_qty'];
            ?>
              <tr>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($it['chemical_name']) ?></div>
                </td>
                <td><span class="badge bg-<?= $it['item_type'] === 'dye' ? 'primary' : 'secondary' ?>"><?= strtoupper($it['item_type']) ?></span></td>
                <td><code class="font-monospace"><?= htmlspecialchars($it['lot_number'] ?: 'LOT-DISP-01') ?></code></td>
                <td class="text-end font-monospace"><?= number_format($it['required_qty'], 3) ?></td>
                <td class="text-end font-monospace fw-bold text-primary"><?= number_format($it['issued_qty'], 3) ?></td>
                <td class="text-end font-monospace text-warning"><?= number_format($it['returned_qty'], 3) ?></td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($it['actual_consumed_qty'], 3) ?></td>
                <td class="text-center font-monospace">
                  <?php if (abs($variance) < 0.001): ?>
                    <span class="badge bg-success-subtle text-success">0.000 (Exact)</span>
                  <?php elseif ($variance > 0): ?>
                    <span class="badge bg-danger-subtle text-danger">+<?= number_format($variance, 3) ?></span>
                  <?php else: ?>
                    <span class="badge bg-info-subtle text-info"><?= number_format($variance, 3) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Reconciled</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <th colspan="3" class="text-end fw-bold">Totals (Kg):</th>
              <th class="text-end fw-bold font-monospace"><?= number_format($totReq, 3) ?></th>
              <th class="text-end fw-bold font-monospace text-primary"><?= number_format($totIss, 3) ?></th>
              <th class="text-end fw-bold font-monospace text-warning"><?= number_format($totRet, 3) ?></th>
              <th class="text-end fw-bold font-monospace text-dark fs-6"><?= number_format($totCons, 3) ?></th>
              <th colspan="2"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Chemical Issue Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_request">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newRequestModalLabel"><i class="bi bi-cart-plus text-primary me-2"></i>Indent Chemical Store Materials</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> (<?= htmlspecialchars($b['shade_name']) ?> · <?= number_format($b['planned_weight_kg'], 0) ?> Kg)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Requester Name *</label>
              <input type="text" name="requester_name" class="form-control form-control-sm" value="M. Ramanathan (Vessel In-charge)" required>
            </div>
            <div class="col-12">
              <label class="form-label">Color Kitchen Dispensing Instructions</label>
              <textarea class="form-control form-control-sm" rows="3" placeholder="Ensure precision weighing scale (±0.01g for dyestuffs, ±5g for salts & alkali). Dissolve reactive dye in soft water at 50°C."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Submit Chemical Indent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
