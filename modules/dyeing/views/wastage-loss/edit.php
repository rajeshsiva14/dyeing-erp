<?php
/**
 * Dedicated Full-Page Form: Update Dyeing Wastage & Process Loss Reconciliation
 * File: modules/dyeing/views/wastage-loss/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Process Loss';
$activeModule = 'wastage-loss';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$entryId = (int)($_GET['id'] ?? 0);

$entry = Database::fetch("SELECT * FROM dyeing_wastage_entries WHERE id = ?", [$entryId]);
if (!$entry) {
    header('Location: ' . Url::to('/dyeing/wastage-loss?error=not_found'));
    exit;
}

$errors = [];

// Handle update process loss
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_loss') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $stage = $_POST['process_stage'] ?? $entry['process_stage'];
    $inputKg = (float)($_POST['input_kg'] ?? $entry['input_kg']);
    $outputKg = (float)($_POST['output_kg'] ?? $entry['output_kg']);
    $sampleKg = (float)($_POST['sample_cut_kg'] ?? $entry['sample_cut_kg']);
    $processWasteKg = (float)($_POST['process_waste_kg'] ?? $entry['process_waste_kg']);
    $damagedKg = (float)($_POST['damaged_fabric_kg'] ?? $entry['damaged_fabric_kg']);
    $unaccountedKg = (float)($_POST['unaccounted_loss_kg'] ?? $entry['unaccounted_loss_kg']);
    $netLoss = max(0, $inputKg - $outputKg);
    $lossPct = ($inputKg > 0) ? ($netLoss / $inputKg) * 100 : 0;
    $reason = trim($_POST['loss_reason'] ?? $entry['loss_reason']);
    $disposal = trim($_POST['disposal_method'] ?? $entry['disposal_method']);
    $reportedBy = trim($_POST['reported_by'] ?? $entry['reported_by']);

    try {
        Database::getConnection()->prepare("
            UPDATE dyeing_wastage_entries SET
                process_stage = ?,
                input_kg = ?,
                output_kg = ?,
                sample_cut_kg = ?,
                process_waste_kg = ?,
                damaged_fabric_kg = ?,
                unaccounted_loss_kg = ?,
                net_loss_kg = ?,
                loss_percentage = ?,
                loss_reason = ?,
                disposal_method = ?,
                reported_by = ?
            WHERE id = ?
        ")->execute([
            $stage,
            $inputKg,
            $outputKg,
            $sampleKg,
            $processWasteKg,
            $damagedKg,
            $unaccountedKg,
            $netLoss,
            $lossPct,
            $reason,
            $disposal,
            $reportedBy,
            $entryId
        ]);

        header('Location: ' . Url::to('/dyeing/wastage-loss?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update process loss: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/wastage-loss') ?>">Wastage & Loss</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($entry['entry_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Process Loss #<?= htmlspecialchars($entry['entry_no']) ?></h1>
        <p class="text-secondary small mb-0">Update input/output weights, itemized scrap breakdown, loss reasoning, and waste disposal channel.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/wastage-loss') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="update_loss">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Target Batch Card -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers me-2"></i>Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Entry Number</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($entry['entry_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Dye Batch No</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($entry['batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Process Stage / Operation *</label>
                <select name="process_stage" class="form-select form-select-sm fw-bold" required>
                  <option value="dyeing" <?= $entry['process_stage'] === 'dyeing' ? 'selected' : '' ?>>Dyeing Vessel (Hydro / Liquor Loss)</option>
                  <option value="hydro" <?= $entry['process_stage'] === 'hydro' ? 'selected' : '' ?>>Hydro Extractor Dewatering</option>
                  <option value="drying" <?= $entry['process_stage'] === 'drying' ? 'selected' : '' ?>>Conveyor Relax Drying</option>
                  <option value="stenter" <?= $entry['process_stage'] === 'stenter' ? 'selected' : '' ?>>Stenter (Edge Trimming & Overfeed)</option>
                  <option value="compactor" <?= $entry['process_stage'] === 'compactor' ? 'selected' : '' ?>>Compactor & Heat-Setting</option>
                  <option value="inspection" <?= $entry['process_stage'] === 'inspection' ? 'selected' : '' ?>>Inspection & Roll Packing</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Reported By *</label>
                <input type="text" name="reported_by" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($entry['reported_by']) ?>" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Mass Balance & Loss Breakdown -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-calculator me-2"></i>Mass Balance & Yield Loss Breakdown</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Input Loaded Weight (Kg) *</label>
                  <input type="number" step="0.1" name="input_kg" id="wstInputKg" class="form-control form-control-sm fw-bold" value="<?= (float)$entry['input_kg'] ?>" required oninput="calcNetLoss()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Finished Output Weight (Kg) *</label>
                  <input type="number" step="0.1" name="output_kg" id="wstOutputKg" class="form-control form-control-sm fw-bold text-success" value="<?= (float)$entry['output_kg'] ?>" required oninput="calcNetLoss()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Sample Swatch Cuts (Kg)</label>
                  <input type="number" step="0.1" name="sample_cut_kg" class="form-control form-control-sm" value="<?= (float)$entry['sample_cut_kg'] ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Stenter Edge / Process Waste (Kg)</label>
                  <input type="number" step="0.1" name="process_waste_kg" class="form-control form-control-sm" value="<?= (float)$entry['process_waste_kg'] ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Damaged End-Bits / Scraps (Kg)</label>
                  <input type="number" step="0.1" name="damaged_fabric_kg" class="form-control form-control-sm" value="<?= (float)$entry['damaged_fabric_kg'] ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Unaccounted / Evaporation Loss (Kg)</label>
                  <input type="number" step="0.1" name="unaccounted_loss_kg" id="wstUnaccountedKg" class="form-control form-control-sm" value="<?= (float)$entry['unaccounted_loss_kg'] ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-danger">Net Process Loss % (Calculated)</label>
                  <input type="text" id="wstLossPct" class="form-control form-control-sm bg-light font-monospace fw-bold text-danger" value="<?= number_format($entry['loss_percentage'], 2) ?>%" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Reason & Specific Root Cause</label>
                  <input type="text" name="loss_reason" class="form-control form-control-sm" value="<?= htmlspecialchars($entry['loss_reason']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Waste Disposal / Resale Route</label>
                  <select name="disposal_method" class="form-select form-select-sm">
                    <option value="Saleable Rags / Recycled Cotton Waste" <?= $entry['disposal_method'] === 'Saleable Rags / Recycled Cotton Waste' ? 'selected' : '' ?>>Saleable Rags / Recycled Cotton Waste</option>
                    <option value="Spinning Mill Hard Waste Return" <?= $entry['disposal_method'] === 'Spinning Mill Hard Waste Return' ? 'selected' : '' ?>>Spinning Mill Hard Waste Return</option>
                    <option value="Shredder / Thermal Energy Boiler Fuel" <?= $entry['disposal_method'] === 'Shredder / Thermal Energy Boiler Fuel' ? 'selected' : '' ?>>Shredder / Thermal Energy Boiler Fuel</option>
                    <option value="Authorized ETP Sludge Contractor" <?= $entry['disposal_method'] === 'Authorized ETP Sludge Contractor' ? 'selected' : '' ?>>Authorized ETP Sludge Contractor</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/wastage-loss') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Process Loss Entry
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function calcNetLoss() {
  var inKg = parseFloat(document.getElementById('wstInputKg').value) || 0;
  var outKg = parseFloat(document.getElementById('wstOutputKg').value) || 0;
  if (inKg > 0) {
    var netLoss = Math.max(0, inKg - outKg);
    var pct = (netLoss / inKg) * 100;
    document.getElementById('wstLossPct').value = pct.toFixed(2) + '%';
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
