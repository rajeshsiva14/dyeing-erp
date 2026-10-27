<?php
/**
 * Dedicated Standalone Form: New Chemical Issue Indent
 * File: modules/dyeing/views/chemical-requests/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;
use Core\Services\InventoryService;

$pageTitle = 'New Chemical Issue Indent';
$activeModule = 'chemical-requests';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle New Chemical Issue Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_request') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $reqNo = 'CIR-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $requesterName = trim($_POST['requester_name'] ?? 'M. Ramanathan (Vessel Operator)');

    if ($batchId <= 0) {
        $errors[] = 'Please select a Dyeing Production Batch.';
    }

    if (!$errors) {
        $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ? AND company_id = ?", [$batchId, $companyId]);
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        $recipeId = $batch['dye_recipe_id'] ?? 1;

        Database::beginTransaction();
        try {
            $reqId = Database::insert('chemical_issue_requests', [
                'company_id'             => $companyId,
                'request_no'             => $reqNo,
                'dye_batch_id'           => $batchId,
                'batch_no'               => $batchNo,
                'recipe_id'              => $recipeId,
                'request_date'           => date('Y-m-d'),
                'required_time'          => date('H:i:s'),
                'requester_name'         => $requesterName,
                'warehouse_id'           => 2,
                'total_chemicals_count'  => 3,
                'status'                 => 'approved',
                'approved_by'            => $currentUser['name'] ?? 'Dye House Manager'
            ]);

            $recipe = Database::fetch("SELECT * FROM dye_recipes WHERE id = ?", [$recipeId]);
            $fabricWeight = (float)($batch['planned_weight_kg'] ?? ($batch['actual_loaded_kg'] ?? 500.0));
            $ratioStr = $recipe['liquor_ratio'] ?? '1:6';
            $ratioParts = explode(':', $ratioStr);
            $liquorRatio = isset($ratioParts[1]) ? (float)$ratioParts[1] : 6.0;
            if ($liquorRatio <= 0) $liquorRatio = 6.0;
            $bathVolumeLiters = $fabricWeight * $liquorRatio;

            $recipeItems = Database::fetchAll("
                SELECT ri.*, p.name as product_name, p.standard_cost, p.sku
                FROM dye_recipe_items ri
                JOIN products p ON ri.chemical_product_id = p.id
                WHERE ri.dye_recipe_id = ?
            ", [$recipeId]);

            if (empty($recipeItems)) {
                $recipeItems = [
                    ['chemical_product_id' => 6, 'product_name' => 'Glauber Salt (Sodium Sulphate)', 'item_type' => 'chemical', 'dosage_percentage' => null, 'dosage_grams_per_litre' => 20.0, 'standard_cost' => 18.50],
                    ['chemical_product_id' => 7, 'product_name' => 'Soda Ash Light', 'item_type' => 'chemical', 'dosage_percentage' => null, 'dosage_grams_per_litre' => 10.0, 'standard_cost' => 32.00],
                    ['chemical_product_id' => 5, 'product_name' => 'Reactive Blue RGB 250%', 'item_type' => 'dye', 'dosage_percentage' => 2.5, 'dosage_grams_per_litre' => null, 'standard_cost' => 420.00],
                ];
            }

            Database::update('chemical_issue_requests', ['total_chemicals_count' => count($recipeItems)], 'id = :id', ['id' => $reqId]);

            foreach ($recipeItems as $ri) {
                $chemId = (int)$ri['chemical_product_id'];
                $chemName = $ri['product_name'];
                $itemType = $ri['item_type'] ?? 'chemical';
                $unitCost = (float)($ri['standard_cost'] ?? 50.00);

                if (!empty($ri['dosage_percentage']) && (float)$ri['dosage_percentage'] > 0) {
                    $calcQty = round(($fabricWeight * (float)$ri['dosage_percentage']) / 100, 3);
                } elseif (!empty($ri['dosage_grams_per_litre']) && (float)$ri['dosage_grams_per_litre'] > 0) {
                    $calcQty = round(($bathVolumeLiters * (float)$ri['dosage_grams_per_litre']) / 1000, 3);
                } else {
                    $calcQty = 5.000;
                }

                $issuedQty = $calcQty;
                $returnedQty = 0.000;
                $consumedQty = $issuedQty - $returnedQty;

                Database::insert('chemical_issue_items', [
                    'request_id'          => $reqId,
                    'chemical_product_id' => $chemId,
                    'chemical_name'       => $chemName,
                    'item_type'           => $itemType,
                    'lot_number'          => 'LOT-CHM-' . date('y') . '-' . rand(100, 999),
                    'required_qty'        => $calcQty,
                    'issued_qty'          => $issuedQty,
                    'returned_qty'        => $returnedQty,
                    'actual_consumed_qty' => $consumedQty,
                    'unit_cost'           => $unitCost,
                    'total_cost'          => round($consumedQty * $unitCost, 2),
                    'status'              => 'issued'
                ]);

                // Deduct stock from chemical warehouse ledger
                InventoryService::postMovement([
                    'company_id'        => $companyId,
                    'warehouse_id'      => 2,
                    'product_id'        => $chemId,
                    'movement_type'     => 'production_issue',
                    'quantity_base_uom' => -$issuedQty,
                    'unit_cost'         => $unitCost,
                    'source_type'       => 'chemical_issue_requests',
                    'source_id'         => $reqId,
                    'posted_by'         => (int)($currentUser['id'] ?? 1)
                ]);
            }

            Database::commit();
            header('Location: ' . Url::to('/dyeing/chemical-requests?success=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Issue failed: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("
    SELECT b.*, r.shade_name, r.recipe_code, m.code as machine_code 
    FROM dye_batches b 
    JOIN dye_recipes r ON b.dye_recipe_id = r.id 
    JOIN machines m ON b.machine_id = m.id 
    WHERE b.company_id = ?
    ORDER BY b.id DESC
", [$companyId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Action Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">New Chemical Issue Indent</h1>
            <span class="badge badge-bw">COLOR KITCHEN</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Dispense dyestuffs, auxiliaries, electrolytes, and alkalis directly from Chemical Store Bay 2 for an active dyeing batch.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newChemReqForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Generate & Dispense Indent
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="newChemReqForm" method="POST" action="<?= Url::to('/dyeing/chemical-requests-create') ?>">
      <input type="hidden" name="action" value="save_request">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-cart-plus"></i> Section A — Batch & Store Selection</h2>
            <p class="cz-doc-section-desc">Select active dyeing vessel batch to automatically pull recipe dyestuff percentages and liquor volume.</p>
          </div>
          <span class="badge badge-bw font-monospace">INDENT SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Dyeing Production Batch <span class="text-danger">*</span></label>
            <select name="dye_batch_id" class="form-select form-select-sm" required>
              <option value="">-- Select Active Batch --</option>
              <?php foreach ($batches as $b): ?>
                <option value="<?= $b['id'] ?>">
                  <?= htmlspecialchars($b['batch_no']) ?> &bull; <?= htmlspecialchars($b['shade_name']) ?> (<?= $b['machine_code'] ?> &bull; <?= number_format($b['actual_loaded_kg'], 1) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Requester / Vessel Operator <span class="text-danger">*</span></label>
            <input type="text" name="requester_name" value="<?= htmlspecialchars($currentUser['name'] ?? 'M. Ramanathan (Vessel Operator)') ?>" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Source Chemical Storehouse</label>
            <input type="text" class="form-control form-control-sm bg-light" value="Central Dyes & Auxiliaries Store (Bay 2 - Climate Controlled)" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Dispensing Method & Precision</label>
            <input type="text" class="form-control form-control-sm bg-light" value="Computerized Scale Tare Verification & Barcode Lot Scan" readonly>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/chemical-requests') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Generate & Dispense Indent
        </button>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
