<?php
/**
 * Roll Formation, Barcode Generation & Thermal Packing Labels
 * Converting accepted finished bulk batch into packaged rolls with L01 labels
 * File: modules/dyeing/views/roll-packing.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Roll Formation & Packing';
$activeModule = 'roll-packing';

// Handle generating finished roll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pack_roll') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    
    $rollNo = 'ROL-' . date('y') . '-' . str_pad((string)rand(100, 9999), 5, '0', STR_PAD_LEFT);
    $weightKg = (float)($_POST['weight_kg'] ?? 24.5);
    $lengthM = (float)($_POST['length_meters'] ?? 85.0);
    $widthInches = (float)($_POST['width_inches'] ?? 48.0);
    $gsm = (int)($_POST['gsm'] ?? 160);
    $grade = $_POST['grade'] ?? 'Grade A';
    $packType = $_POST['packaging_type'] ?? 'Polythene Tubing + Shrink Film';

    Database::insert('inventory_rolls', [
        'company_id'          => 1,
        'roll_number'         => $rollNo,
        'lot_id'              => 1,
        'product_id'          => 3, // Dyed Fabric
        'weight_kg'           => $weightKg,
        'length_meters'       => $lengthM,
        'width_inches'        => $widthInches,
        'gsm'                 => $gsm,
        'grade'               => $grade,
        'warehouse_bin_id'    => 1,
        'owner_type'          => 'company',
        'quality_status'      => 'passed',
        'status'              => 'in_stock'
    ]);

    header('Location: ' . Url::to('/dyeing/roll-packing?packed=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$rolls = Database::fetchAll("
    SELECT r.*, p.name as product_name, p.sku
    FROM inventory_rolls r
    JOIN products p ON r.product_id = p.id
    ORDER BY r.id DESC
");

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
            <li class="breadcrumb-item active">Roll Formation & Packing</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-upc-scan text-primary me-2"></i>Roll Formation, Barcode Generation & Packing</h1>
        <p class="text-secondary small mb-0">Pack finished dyed rolls, generate thermal barcodes (Code128 / QR), track roll weight, length, grade and post to finished stock.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newRollModal">
          <i class="bi bi-plus-lg me-1"></i> Form & Pack New Roll
        </button>
      </div>
    </div>

    <!-- Rolls Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-primary me-2"></i>Packaged Finished Fabric Rolls Register</h2>
        <span class="small text-secondary"><?= count($rolls) ?> Packaged Rolls</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Roll Barcode / ID</th>
              <th>Product / Fabric</th>
              <th class="text-end">Net Weight (Kg)</th>
              <th class="text-end">Length (Meters)</th>
              <th class="text-center">Width / GSM</th>
              <th class="text-center">Quality Grade</th>
              <th>Bin Location</th>
              <th>Status</th>
              <th class="text-end">Print Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rolls as $r): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary">
                  <i class="bi bi-upc me-1 text-dark"></i><?= htmlspecialchars($r['roll_number']) ?>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($r['product_name']) ?></div>
                  <code class="text-secondary"><?= htmlspecialchars($r['sku']) ?></code>
                </td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($r['weight_kg'], 2) ?></td>
                <td class="text-end font-monospace"><?= number_format($r['length_meters'], 1) ?> m</td>
                <td class="text-center font-monospace"><?= $r['width_inches'] ?>" / <?= $r['gsm'] ?> GSM</td>
                <td class="text-center">
                  <span class="badge <?= ($r['grade'] ?? 'Grade A') === 'Grade A' ? 'badge-bw-dark' : 'badge-bw' ?>">
                    <?= htmlspecialchars($r['grade'] ?? 'Grade A') ?>
                  </span>
                </td>
                <td><span class="badge bg-light text-dark border">Finished Rack A-01</span></td>
                <td>
                  <span class="badge bg-success-subtle text-success text-uppercase">
                    <?= htmlspecialchars($r['status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <a href="<?= Url::to('/print/roll-label?roll=' . urlencode($r['roll_number'])) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print L01 Thermal Label">
                    <i class="bi bi-printer me-1"></i> Print L01 Label
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

<!-- New Packaged Roll Modal -->
<div class="modal fade" id="newRollModal" tabindex="-1" aria-labelledby="newRollModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="pack_roll">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newRollModalLabel"><i class="bi bi-upc-scan text-primary me-2"></i>Form Finished Fabric Roll</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Source Dyeing Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Packaging Type *</label>
              <select name="packaging_type" class="form-select form-select-sm" required>
                <option value="Polythene Tubing + Shrink Film">Polythene Tubing + Heat Shrink Film (Standard)</option>
                <option value="Double Layer Polythene">Double Layer Polythene (Export Ocean Freight)</option>
                <option value="Woven Bag Outer Wrap">Woven Bag Outer Wrap (Road Transport)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-primary">Roll Net Weight (Kg) *</label>
              <input type="number" step="0.01" name="weight_kg" class="form-control form-control-sm fw-bold" value="24.50" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Roll Length (Meters) *</label>
              <input type="number" step="0.1" name="length_meters" class="form-control form-control-sm" value="85.0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Width (Inches) *</label>
              <input type="number" step="0.1" name="width_inches" class="form-control form-control-sm" value="48.0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">GSM *</label>
              <input type="number" name="gsm" class="form-control form-control-sm" value="160" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Quality Grade *</label>
              <select name="grade" class="form-select form-select-sm" required>
                <option value="Grade A" selected>Grade A (ASTM D5430 &le; 20 Pts)</option>
                <option value="Grade B">Grade B (Commercial 21-28 Pts)</option>
                <option value="Grade C">Grade C (Second Quality)</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label fw-bold">Finished Store Location *</label>
              <input type="text" class="form-control form-control-sm" value="Finished Goods Warehouse — Bay 4, Rack R-12">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-printer me-1"></i> Form Roll & Print Label</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
