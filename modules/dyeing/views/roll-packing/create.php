<?php
/**
 * Dedicated Standalone Form: Form & Pack New Fabric Roll
 * File: modules/dyeing/views/roll-packing/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;
use Core\Services\InventoryService;

$pageTitle = 'Form & Pack New Fabric Roll';
$activeModule = 'roll-packing';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$postedBy = (int)($currentUser['id'] ?? 1);
$errors = [];

// Handle generating finished roll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pack_roll') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $weightKg = (float)($_POST['weight_kg'] ?? 0);
    $lengthM = (float)($_POST['length_meters'] ?? 0);
    $widthInches = (float)($_POST['width_inches'] ?? 48.0);
    $gsm = (int)($_POST['gsm'] ?? 160);
    $grade = trim($_POST['grade'] ?? 'A');
    $qualityGrade = str_contains($grade, 'B') ? 'B' : (str_contains($grade, 'C') ? 'C' : 'A');

    if ($batchId <= 0) $errors[] = 'Please select a Dyeing Production Batch.';
    if ($weightKg <= 0) $errors[] = 'Roll weight (Kg) must be greater than 0.';
    if ($lengthM <= 0) $errors[] = 'Roll length (Meters) must be greater than 0.';

    if (!$errors) {
        $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ? AND company_id = ?", [$batchId, $companyId]);
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        
        $rollNo = 'ROL-' . date('y') . '-' . str_pad((string)rand(100, 9999), 5, '0', STR_PAD_LEFT);
        $barcode = 'BAR-' . date('ymd') . '-' . str_pad((string)rand(100, 99999), 6, '0', STR_PAD_LEFT);
        $warehouseId = 3; // Finished Fabric Warehouse
        $binId = 1;

        Database::beginTransaction();
        try {
            $rollId = Database::insert('inventory_rolls', [
                'company_id'    => $companyId,
                'lot_id'        => 1,
                'roll_number'   => $rollNo,
                'barcode'       => $barcode,
                'product_id'    => 3, // Finished Dyed Fabric
                'weight_kg'     => $weightKg,
                'length_meters' => $lengthM,
                'width_inches'  => $widthInches,
                'gsm'           => $gsm,
                'quality_grade' => $qualityGrade,
                'shade_group'   => $batch['shade_name'] ?? 'Standard Shade',
                'warehouse_id'  => $warehouseId,
                'bin_id'        => $binId,
                'status'        => 'in_stock'
            ]);

            // Post immutable inventory movement to finished goods stock ledger
            InventoryService::postMovement([
                'company_id'        => $companyId,
                'warehouse_id'      => $warehouseId,
                'bin_id'            => $binId,
                'product_id'        => 3,
                'roll_id'           => $rollId,
                'lot_id'            => 1,
                'movement_type'     => 'production_receipt',
                'quantity_base_uom' => $weightKg,
                'unit_cost'         => 85.00,
                'source_type'       => 'dye_batches',
                'source_id'         => $batchId,
                'posted_by'         => $postedBy
            ]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/roll-packing?packed=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Roll formation failed: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("
    SELECT b.*, r.shade_name, r.recipe_code 
    FROM dye_batches b 
    JOIN dye_recipes r ON b.dye_recipe_id = r.id 
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
        <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Form & Pack New Fabric Roll</h1>
            <span class="badge badge-bw">THERMAL PACKING</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Convert accepted finished dyed fabric into packaged rolls, generate Code128 thermal barcode labels, and post to finished goods stock.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="packRollForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-upc-scan me-1"></i> Pack Roll & Generate Barcode
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

    <form id="packRollForm" method="POST" action="<?= Url::to('/dyeing/roll-packing-create') ?>">
      <input type="hidden" name="action" value="pack_roll">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-layers"></i> Section A — Production Batch Allocation</h2>
            <p class="cz-doc-section-desc">Select finished bulk dyeing batch to inherit shade specification and lot history.</p>
          </div>
          <span class="badge badge-bw font-monospace">BATCH LINK</span>
        </div>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Dyeing Production Batch <span class="text-danger">*</span></label>
            <select name="dye_batch_id" class="form-select form-select-sm" required>
              <option value="">-- Select Completed Batch --</option>
              <?php foreach ($batches as $b): ?>
                <option value="<?= $b['id'] ?>">
                  <?= htmlspecialchars($b['batch_no']) ?> &bull; <?= htmlspecialchars($b['shade_name']) ?> (Output: <?= number_format($b['output_weight_kg'] ?: $b['actual_loaded_kg'], 1) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Quality Inspection Grade <span class="text-danger">*</span></label>
            <select name="grade" class="form-select form-select-sm" required>
              <option value="A" selected>Grade A (Premium Export Standard - Defect score &le; 20 pts)</option>
              <option value="B">Grade B (Commercial Quality - Defect score 21-40 pts)</option>
              <option value="C">Grade C (Factory Seconds - Minor shading / defects)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-rulers"></i> Section B — Roll Weighment & Dimensional Specifications</h2>
            <p class="cz-doc-section-desc">Certified net weight, meter length, finished fabric width, and target GSM.</p>
          </div>
          <span class="badge badge-bw font-monospace">ROLL SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Net Roll Weight (Kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="weight_kg" class="form-control form-control-sm font-monospace fw-bold" value="24.50" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Measured Length (Meters) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="length_meters" class="form-control form-control-sm font-monospace fw-bold" value="85.0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Finished Width (Inches) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="width_inches" class="form-control form-control-sm font-monospace" value="48.0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Finished Fabric GSM <span class="text-danger">*</span></label>
            <input type="number" name="gsm" class="form-control form-control-sm font-monospace" value="160" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Finished Goods Warehouse Storage</label>
            <input type="text" class="form-control form-control-sm bg-light" value="Finished Goods Fabric Warehouse (Rack A-01, Bin B)" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Thermal Label Format</label>
            <input type="text" class="form-control form-control-sm bg-light" value="Standard L01 Thermal Barcode Label (Code128 + QR Roll Traceability)" readonly>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-upc-scan me-1"></i> Pack Roll & Generate Barcode
        </button>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
