<?php
/**
 * Dedicated Full-Page Form: Update Finishing Machine Run
 * File: modules/dyeing/views/finishing/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Finishing Run';
$activeModule = 'finishing';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$entryId = (int)($_GET['id'] ?? 0);

$entry = Database::fetch("SELECT * FROM finishing_entries WHERE id = ?", [$entryId]);
if (!$entry) {
    header('Location: ' . Url::to('/dyeing/finishing?error=not_found'));
    exit;
}

$batch = Database::fetch("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id WHERE b.id = ?", [$entry['dye_batch_id']]);
$finishMachines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('hydro_extractor', 'relax_dryer', 'stenter', 'compactor') OR machine_type IN ('finishing', 'stenter', 'dryer') ORDER BY code ASC");
if (empty($finishMachines)) {
    $finishMachines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
}

$errors = [];

// Handle update finishing entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_finishing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $machineId = (int)($_POST['machine_id'] ?? $entry['machine_id']);
    $procType = $_POST['process_type'] ?? $entry['process_type'];
    $inWeight = (float)($_POST['input_weight_kg'] ?? $entry['input_weight_kg']);
    $outWeight = (float)($_POST['output_weight_kg'] ?? $entry['output_weight_kg']);
    $lossPct = ($inWeight > 0) ? max(0, (($inWeight - $outWeight) / $inWeight) * 100) : 0;
    $overfeed = (float)($_POST['overfeed_pct'] ?? $entry['overfeed_pct']);
    $width = (float)($_POST['finished_width_inches'] ?? $entry['finished_width_inches']);
    $gsm = (int)($_POST['finished_gsm'] ?? $entry['finished_gsm']);
    $shrinkL = (float)($_POST['shrinkage_length_pct'] ?? $entry['shrinkage_length_pct']);
    $shrinkW = (float)($_POST['shrinkage_width_pct'] ?? $entry['shrinkage_width_pct']);
    $op = trim($_POST['operator_name'] ?? $entry['operator_name']);

    try {
        Database::getConnection()->prepare("
            UPDATE finishing_entries SET
                machine_id = ?,
                process_type = ?,
                input_weight_kg = ?,
                output_weight_kg = ?,
                process_loss_pct = ?,
                overfeed_pct = ?,
                finished_width_inches = ?,
                finished_gsm = ?,
                shrinkage_length_pct = ?,
                shrinkage_width_pct = ?,
                operator_name = ?
            WHERE id = ?
        ")->execute([
            $machineId,
            $procType,
            $inWeight,
            $outWeight,
            $lossPct,
            $overfeed,
            $width,
            $gsm,
            $shrinkL,
            $shrinkW,
            $op,
            $entryId
        ]);

        header('Location: ' . Url::to('/dyeing/finishing?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update finishing run: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/finishing') ?>">Finishing Operations</a></li>
            <li class="breadcrumb-item active">Edit Run #<?= $entryId ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Finishing Machine Run</h1>
        <p class="text-secondary small mb-0">Update moisture extraction weights, stenter overfeed %, finished width, GSM, and shrinkage data.</p>
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
      <input type="hidden" name="action" value="update_finishing">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Machine & Batch Reference -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cpu me-2"></i>Machine & Batch Reference</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Production Batch</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($batch['batch_no'] ?? '') ?> (<?= htmlspecialchars($batch['shade_name'] ?? '') ?>)" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Finishing Machine Unit *</label>
                <select name="machine_id" class="form-select form-select-sm" required>
                  <?php foreach ($finishMachines as $fm): ?>
                    <option value="<?= $fm['id'] ?>" <?= ($fm['id'] == $entry['machine_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($fm['code']) ?> — <?= htmlspecialchars($fm['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Finishing Operation Stage *</label>
                <select name="process_type" class="form-select form-select-sm fw-bold" required>
                  <option value="hydro_extraction" <?= $entry['process_type'] === 'hydro_extraction' ? 'selected' : '' ?>>1. Hydro Extraction (Centrifugal Water Dewatering)</option>
                  <option value="drying" <?= $entry['process_type'] === 'drying' ? 'selected' : '' ?>>2. Relax Conveyor Dryer (Moisture Evaporation)</option>
                  <option value="stenter" <?= $entry['process_type'] === 'stenter' ? 'selected' : '' ?>>3. Stenter Machine (Overfeed, Width & Heat-Setting)</option>
                  <option value="compactor" <?= $entry['process_type'] === 'compactor' ? 'selected' : '' ?>>4. Felt Compactor (Residual Shrinkage & Soft Handle)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Operator Name *</label>
                <input type="text" name="operator_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($entry['operator_name']) ?>" required>
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
                  <input type="number" step="0.1" name="input_weight_kg" id="finInputKg" class="form-control form-control-sm fw-bold" value="<?= (float)$entry['input_weight_kg'] ?>" required oninput="calcLoss()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Output Weight (Kg) *</label>
                  <input type="number" step="0.1" name="output_weight_kg" id="finOutputKg" class="form-control form-control-sm fw-bold text-success" value="<?= (float)$entry['output_weight_kg'] ?>" required oninput="calcLoss()">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Water Loss % (Calculated)</label>
                  <input type="text" id="finLossPct" class="form-control form-control-sm bg-light font-monospace fw-bold text-danger" value="<?= number_format($entry['process_loss_pct'], 1) ?>%" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Stenter Overfeed %</label>
                  <input type="number" step="0.1" name="overfeed_pct" class="form-control form-control-sm" value="<?= (float)$entry['overfeed_pct'] ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Finished Width (Inches) *</label>
                  <input type="number" step="0.1" name="finished_width_inches" class="form-control form-control-sm" value="<?= (float)$entry['finished_width_inches'] ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Finished Target GSM *</label>
                  <input type="number" name="finished_gsm" class="form-control form-control-sm" value="<?= (int)$entry['finished_gsm'] ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Shrinkage Length %</label>
                  <input type="number" step="0.1" name="shrinkage_length_pct" class="form-control form-control-sm" value="<?= (float)$entry['shrinkage_length_pct'] ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Shrinkage Width %</label>
                  <input type="number" step="0.1" name="shrinkage_width_pct" class="form-control form-control-sm" value="<?= (float)$entry['shrinkage_width_pct'] ?>">
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/finishing') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-check2-circle me-1"></i> Update Finishing Run
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
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
