<?php
/**
 * Dedicated Standalone Form: Update Finished Fabric Roll
 * File: modules/dyeing/views/roll-packing/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Fabric Roll Details';
$activeModule = 'roll-packing';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$errors = [];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$roll = Database::fetch("
    SELECT r.*, p.name as product_name, p.sku, b.name as bin_name
    FROM inventory_rolls r
    JOIN products p ON r.product_id = p.id
    LEFT JOIN warehouse_bins b ON r.bin_id = b.id
    WHERE r.id = ? AND r.company_id = ?
", [$id, $companyId]);

if (!$roll) {
    header('Location: ' . Url::to('/dyeing/roll-packing'));
    exit;
}

$bins = Database::fetchAll("SELECT * FROM warehouse_bins ORDER BY bin_code ASC");

// Handle Update Roll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_roll') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $weightKg = (float)($_POST['weight_kg'] ?? 0);
    $lengthM = (float)($_POST['length_meters'] ?? 0);
    $widthInches = (float)($_POST['width_inches'] ?? 48.0);
    $gsm = (int)($_POST['gsm'] ?? 160);
    $grade = trim($_POST['quality_grade'] ?? 'A');
    $binId = (int)($_POST['bin_id'] ?? 1);
    $status = trim($_POST['status'] ?? 'in_stock');

    if ($weightKg <= 0) $errors[] = 'Roll weight (Kg) must be greater than 0.';
    if ($lengthM <= 0) $errors[] = 'Roll length (Meters) must be greater than 0.';

    if (!$errors) {
        Database::query("
            UPDATE inventory_rolls
            SET weight_kg = ?, length_meters = ?, width_inches = ?, gsm = ?, quality_grade = ?, bin_id = ?, status = ?
            WHERE id = ? AND company_id = ?
        ", [$weightKg, $lengthM, $widthInches, $gsm, $grade, $binId, $status, $id, $companyId]);

        header('Location: ' . Url::to('/dyeing/roll-packing?updated=1'));
        exit;
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
        <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Fabric Roll Specifications</h1>
            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($roll['roll_number']) ?></span>
            <span class="badge bg-secondary"><?= htmlspecialchars($roll['quality_grade']) ?> GRADE</span>
          </div>
          <p class="text-secondary small mb-0 mt-1"><?= htmlspecialchars($roll['product_name']) ?> · Barcode: <?= htmlspecialchars($roll['barcode']) ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <a href="<?= Url::to('/print/roll-label?id=' . $roll['id'] . '&roll=' . urlencode($roll['roll_number'])) ?>" target="_blank" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-printer me-1"></i> Print Thermal Label
        </a>
        <button type="submit" form="editRollForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Roll Changes
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

    <form id="editRollForm" method="POST" action="<?= Url::to('/dyeing/roll-packing/edit?id=' . $id) ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="update_roll">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Roll Dimensions & Inspection Quality</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Net Weight (Kg) *</label>
                  <input type="number" step="0.01" min="0.1" name="weight_kg" class="form-control form-control-sm font-monospace fw-bold" required value="<?= htmlspecialchars($roll['weight_kg']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Length (Meters) *</label>
                  <input type="number" step="0.1" min="1" name="length_meters" class="form-control form-control-sm font-monospace fw-bold" required value="<?= htmlspecialchars($roll['length_meters']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Cuttable Width (Inches)</label>
                  <input type="number" step="0.5" name="width_inches" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($roll['width_inches']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Finished GSM</label>
                  <input type="number" name="gsm" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($roll['gsm']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Quality Grade</label>
                  <select name="quality_grade" class="form-select form-select-sm">
                    <option value="A" <?= ($roll['quality_grade'] === 'A') ? 'selected' : '' ?>>Grade A (Export / Prime)</option>
                    <option value="B" <?= ($roll['quality_grade'] === 'B') ? 'selected' : '' ?>>Grade B (Commercial / Seconds)</option>
                    <option value="C" <?= ($roll['quality_grade'] === 'C') ? 'selected' : '' ?>>Grade C (Down-graded)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2 text-primary"></i>Warehouse Allocation</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Storage Bin / Rack Location</label>
                <select name="bin_id" class="form-select form-select-sm">
                  <?php foreach ($bins as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($b['id'] == $roll['bin_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($b['bin_code']) ?> (<?= htmlspecialchars($b['zone']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Inventory Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="in_stock" <?= ($roll['status'] === 'in_stock') ? 'selected' : '' ?>>In Stock / Available</option>
                  <option value="reserved" <?= ($roll['status'] === 'reserved') ? 'selected' : '' ?>>Reserved for Cutting/Packing</option>
                  <option value="dispatched" <?= ($roll['status'] === 'dispatched') ? 'selected' : '' ?>>Dispatched / Invoiced</option>
                </select>
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
