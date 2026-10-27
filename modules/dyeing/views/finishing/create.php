<?php
/**
 * Dedicated Full-Page Form: Record Finishing Machine Run
 * File: modules/dyeing/views/finishing/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Finishing Run';
$activeModule = 'finishing';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle adding finishing entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_finishing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $machineId = (int)($_POST['machine_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
        $procType = $_POST['process_type'] ?? 'hydro_extraction';
        $inWeight = (float)($_POST['input_weight_kg'] ?? 1000);
        $outWeight = (float)($_POST['output_weight_kg'] ?? 600);
        $lossPct = ($inWeight > 0) ? max(0, (($inWeight - $outWeight) / $inWeight) * 100) : 0;
        $overfeed = (float)($_POST['overfeed_pct'] ?? 15.0);
        $width = (float)($_POST['finished_width_inches'] ?? 48.0);
        $gsm = (int)($_POST['finished_gsm'] ?? 160);
        $shrinkL = (float)($_POST['shrinkage_length_pct'] ?? 3.5);
        $shrinkW = (float)($_POST['shrinkage_width_pct'] ?? 2.8);
        $op = trim($_POST['operator_name'] ?? ($currentUser['name'] ?? 'M. Palanisamy'));

        try {
            Database::insert('finishing_entries', [
                'dye_batch_id'          => $batchId,
                'machine_id'            => $machineId,
                'process_type'          => $procType,
                'input_weight_kg'       => $inWeight,
                'output_weight_kg'      => $outWeight,
                'process_loss_pct'      => $lossPct,
                'overfeed_pct'          => $overfeed,
                'finished_width_inches' => $width,
                'finished_gsm'          => $gsm,
                'shrinkage_length_pct'  => $shrinkL,
                'shrinkage_width_pct'   => $shrinkW,
                'operator_name'         => $op
            ]);

            header('Location: ' . Url::to('/dyeing/finishing?success=1'));
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'Failed to record finishing run: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$finishMachines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('hydro_extractor', 'relax_dryer', 'stenter', 'compactor') OR machine_type IN ('finishing', 'stenter', 'dryer') ORDER BY code ASC");
if (empty($finishMachines)) {
    $finishMachines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/finishing') ?>">Finishing Operations</a></li>
            <li class="breadcrumb-item active">New Run</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-water text-dark me-2"></i>Record Finishing Machine Run</h1>
        <p class="text-secondary small mb-0">Record Hydro extraction moisture removal, relax dryer curing, stenter heat-setting & overfeed.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/finishing') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="save_finishing">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Machine & Batch -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cpu me-2"></i>Machine & Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Production Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateBatchWeight(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-weight="<?= $b['actual_loaded_kg'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Finishing Machine Unit *</label>
                <select name="machine_id" class="form-select form-select-sm" required>
                  <?php foreach ($finishMachines as $fm): ?>
                    <option value="<?= $fm['id'] ?>">
                      <?= htmlspecialchars($fm['code']) ?> — <?= htmlspecialchars($fm['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Finishing Operation Stage *</label>
                <select name="process_type" class="form-select form-select-sm fw-bold" required>
                  <option value="hydro_extraction" selected>1. Hydro Extraction (Centrifugal Water Dewatering)</option>
                  <option value="drying">2. Relax Conveyor Dryer (Moisture Evaporation)</option>
                  <option value="stenter">3. Stenter Machine (Overfeed, Width & Heat-Setting)</option>
                  <option value="compactor">4. Felt Compactor (Residual Shrinkage & Soft Handle)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Operator Name *</label>
                <input type="text" name="operator_name" class="form-control form-control-sm fw-bold" value="M. Palanisamy" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Parameters & Weights -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 me-2"></i>Weight In/Out & Moisture Loss</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Input Wet Weight (Kg) *</label>
                  <input type="number" step="0.1" name="input_weight_kg" id="finInputKg" class="form-control form-control-sm fw-bold" value="1000.0" required oninput="calcLoss()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Output Weight (Kg) *</label>
                  <input type="number" step="0.1" name="output_weight_kg" id="finOutputKg" class="form-control form-control-sm fw-bold text-success" value="620.0" required oninput="calcLoss()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Water Loss % (Calculated)</label>
                  <input type="text" id="finLossPct" class="form-control form-control-sm bg-light font-monospace fw-bold text-danger" value="38.0%" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Stenter Overfeed %</label>
                  <input type="number" step="0.1" name="overfeed_pct" class="form-control form-control-sm" value="15.0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Finished Width (Inches) *</label>
                  <input type="number" step="0.1" name="finished_width_inches" class="form-control form-control-sm" value="48.0" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Finished Target GSM *</label>
                  <input type="number" name="finished_gsm" class="form-control form-control-sm" value="160" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Shrinkage Length %</label>
                  <input type="number" step="0.1" name="shrinkage_length_pct" class="form-control form-control-sm" value="3.5">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Shrinkage Width %</label>
                  <input type="number" step="0.1" name="shrinkage_width_pct" class="form-control form-control-sm" value="2.8">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/finishing') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-water me-1"></i> Save Finishing Operation
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function populateBatchWeight(sel) {
  var opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.weight) {
    document.getElementById('finInputKg').value = parseFloat(opt.dataset.weight).toFixed(1);
    calcLoss();
  }
}
function calcLoss() {
  var inW = parseFloat(document.getElementById('finInputKg').value) || 0;
  var outW = parseFloat(document.getElementById('finOutputKg').value) || 0;
  if (inW > 0) {
    var loss = ((inW - outW) / inW) * 100;
    document.getElementById('finLossPct').value = loss.toFixed(1) + '%';
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
