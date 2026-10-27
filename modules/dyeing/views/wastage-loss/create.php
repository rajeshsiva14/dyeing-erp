<?php
/**
 * Dedicated Full-Page Form: Record Dyeing Wastage & Process Loss Reconciliation
 * File: modules/dyeing/views/wastage-loss/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Process Loss';
$activeModule = 'wastage-loss';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle New Loss Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_loss') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $entryNo = 'WST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        $stage = $_POST['process_stage'] ?? 'stenter';
        $inputKg = (float)($_POST['input_kg'] ?? 1000);
        $outputKg = (float)($_POST['output_kg'] ?? 955);
        $sampleKg = (float)($_POST['sample_cut_kg'] ?? 2.5);
        $processWasteKg = (float)($_POST['process_waste_kg'] ?? 18.5);
        $damagedKg = (float)($_POST['damaged_fabric_kg'] ?? 0.0);
        $unaccountedKg = (float)($_POST['unaccounted_loss_kg'] ?? 24.0);
        $netLoss = max(0, $inputKg - $outputKg);
        $lossPct = ($inputKg > 0) ? ($netLoss / $inputKg) * 100 : 0;
        $reason = trim($_POST['loss_reason'] ?? 'Stenter edge trimming & moisture loss');
        $disposal = trim($_POST['disposal_method'] ?? 'Saleable Rags / Recycled Cotton Waste');
        $reportedBy = trim($_POST['reported_by'] ?? ($currentUser['name'] ?? 'S. Natarajan'));

        try {
            Database::insert('dyeing_wastage_entries', [
                'company_id'          => $companyId,
                'entry_no'            => $entryNo,
                'dye_batch_id'        => $batchId,
                'batch_no'            => $batchNo,
                'process_stage'       => $stage,
                'input_kg'            => $inputKg,
                'output_kg'           => $outputKg,
                'sample_cut_kg'       => $sampleKg,
                'process_waste_kg'    => $processWasteKg,
                'damaged_fabric_kg'   => $damagedKg,
                'unaccounted_loss_kg' => $unaccountedKg,
                'net_loss_kg'         => $netLoss,
                'loss_percentage'     => $lossPct,
                'loss_reason'         => $reason,
                'disposal_method'     => $disposal,
                'reported_by'         => $reportedBy
            ]);

            header('Location: ' . Url::to('/dyeing/wastage-loss?logged=1'));
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'Failed to record process loss: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/wastage-loss') ?>">Wastage & Loss</a></li>
            <li class="breadcrumb-item active">New Reconciliation</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-dark me-2"></i>Record Process Loss Reconciliation</h1>
        <p class="text-secondary small mb-0">Reconcile input vs output fabric mass balance, itemized trimming waste, end-bits, sample cuts and evaporation loss.</p>
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
      <input type="hidden" name="action" value="save_loss">
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
                <label class="form-label small fw-bold">Select Active Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateBatch(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-weight="<?= $b['actual_loaded_kg'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Process Stage / Operation *</label>
                <select name="process_stage" class="form-select form-select-sm fw-bold" required>
                  <option value="dyeing">Dyeing Vessel (Hydro / Liquor Loss)</option>
                  <option value="hydro">Hydro Extractor Dewatering</option>
                  <option value="drying">Conveyor Relax Drying</option>
                  <option value="stenter" selected>Stenter (Edge Trimming & Overfeed)</option>
                  <option value="compactor">Compactor & Heat-Setting</option>
                  <option value="inspection">Inspection & Roll Packing</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Reported By *</label>
                <input type="text" name="reported_by" class="form-control form-control-sm fw-bold" value="S. Natarajan (Dyeing Floor Lead)" required>
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
                  <input type="number" step="0.1" name="input_kg" id="wstInputKg" class="form-control form-control-sm fw-bold" value="1000.0" required oninput="calcNetLoss()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Finished Output Weight (Kg) *</label>
                  <input type="number" step="0.1" name="output_kg" id="wstOutputKg" class="form-control form-control-sm fw-bold text-success" value="955.0" required oninput="calcNetLoss()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Sample Swatch Cuts (Kg)</label>
                  <input type="number" step="0.1" name="sample_cut_kg" class="form-control form-control-sm" value="2.5">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Stenter Edge / Process Waste (Kg)</label>
                  <input type="number" step="0.1" name="process_waste_kg" class="form-control form-control-sm" value="18.5">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Damaged End-Bits / Scraps (Kg)</label>
                  <input type="number" step="0.1" name="damaged_fabric_kg" class="form-control form-control-sm" value="0.0">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Unaccounted / Evaporation Loss (Kg)</label>
                  <input type="number" step="0.1" name="unaccounted_loss_kg" id="wstUnaccountedKg" class="form-control form-control-sm" value="24.0">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-danger">Net Process Loss % (Calculated)</label>
                  <input type="text" id="wstLossPct" class="form-control form-control-sm bg-light font-monospace fw-bold text-danger" value="4.50%" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Reason & Specific Root Cause</label>
                  <input type="text" name="loss_reason" class="form-control form-control-sm" value="Stenter edge trimming & moisture evaporation during drying" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Waste Disposal / Resale Route</label>
                  <select name="disposal_method" class="form-select form-select-sm">
                    <option value="Saleable Rags / Recycled Cotton Waste" selected>Saleable Rags / Recycled Cotton Waste</option>
                    <option value="Spinning Mill Hard Waste Return">Spinning Mill Hard Waste Return</option>
                    <option value="Shredder / Thermal Energy Boiler Fuel">Shredder / Thermal Energy Boiler Fuel</option>
                    <option value="Authorized ETP Sludge Contractor">Authorized ETP Sludge Contractor</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/wastage-loss') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-pie-chart me-1"></i> Save Process Loss Reconciliation
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
  if (opt && opt.dataset.weight) {
    document.getElementById('wstInputKg').value = parseFloat(opt.dataset.weight).toFixed(1);
    calcNetLoss();
  }
}
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
