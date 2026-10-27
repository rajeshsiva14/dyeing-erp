<?php
/**
 * Dedicated Standalone Form: New Batch Dosing Formulation & Calculation
 * File: modules/dyeing/views/recipe-calculation/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Compute Recipe Chemical Dosing';
$activeModule = 'recipe-calculation';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle POST to compute and store or forward to chemical indent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_calculation') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $batchId = (int)($_POST['batch_id'] ?? 0);
    $fabricKg = (float)($_POST['fabric_weight_kg'] ?? 0);
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');

    if ($batchId <= 0) $errors[] = 'Please select a Dyeing Production Batch.';
    if ($fabricKg <= 0) $errors[] = 'Fabric weight (Kg) must be greater than 0.';

    if (!$errors) {
        $ratioNum = 6.0;
        if (preg_match('/1:([0-9\.]+)/', $liquorRatio, $m)) {
            $ratioNum = (float)$m[1];
        }
        $waterVol = $fabricKg * $ratioNum;

        Database::query("
            UPDATE dye_batches
            SET planned_weight_kg = ?, actual_loaded_kg = ?, liquor_ratio = ?, water_volume_litres = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ", [$fabricKg, $fabricKg, $liquorRatio, $waterVol, $batchId, $companyId]);

        header('Location: ' . Url::to('/dyeing/recipe-calculation?batch_id=' . $batchId . '&computed=1'));
        exit;
    }
}

$allBatches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id WHERE b.company_id = ? ORDER BY b.id DESC", [$companyId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/recipe-calculation') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Compute Batch Recipe & Chemical Dosing</h1>
            <span class="badge badge-bw">DOSING FORMULATION</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Computes grams/litre auxiliaries and % OWF dyes into precise dispenser kilograms.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/recipe-calculation') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="calcForm" class="btn btn-dark btn-sm">
          <i class="bi bi-calculator me-1"></i> Compute Dosing Quantities
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

    <form id="calcForm" method="POST" action="<?= Url::to('/dyeing/recipe-calculation/create') ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="save_calculation">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Batch Parameters & Formulation Basis</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Select Production Batch *</label>
                  <select name="batch_id" id="calcBatchSelect" class="form-select form-select-sm" required>
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($allBatches as $ab): ?>
                      <option value="<?= $ab['id'] ?>" data-weight="<?= $ab['actual_loaded_kg'] ?: $ab['planned_weight_kg'] ?>" data-lr="<?= htmlspecialchars($ab['liquor_ratio'] ?? '1:6') ?>">
                        <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= number_format($ab['actual_loaded_kg'] ?: $ab['planned_weight_kg'], 1) ?> Kg)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Fabric Weight (Kg) *</label>
                  <input type="number" step="0.1" min="1" name="fabric_weight_kg" id="calcFabricKg" class="form-control form-control-sm font-monospace fw-bold" required value="500.0">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Material Liquor Ratio (MLR)</label>
                  <select name="liquor_ratio" id="calcLr" class="form-select form-select-sm font-monospace">
                    <option value="1:5">1:5 (Eco Jet)</option>
                    <option value="1:6" selected>1:6 (Standard Soft-Flow)</option>
                    <option value="1:8">1:8 (High Bath Ratio)</option>
                    <option value="1:10">1:10 (Winch)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Calculated Bath Volume (Litres)</label>
                  <input type="text" id="calcBathVol" class="form-control form-control-sm bg-light font-monospace fw-bold text-end text-primary" readonly value="3000 L">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Formulation Standards</h2>
            </div>
            <div class="p-3 small text-secondary">
              <div class="mb-2"><strong>Dyestuff Formula (% OWF):</strong><br><code>Material (Kg) = Fabric Wt (Kg) × (% OWF ÷ 100)</code></div>
              <div class="mb-2"><strong>Chemical Auxiliaries (g/L):</strong><br><code>Material (Kg) = (Bath Vol L × Dosage g/L) ÷ 1000</code></div>
              <div class="mt-3 p-2 bg-light rounded border text-muted">
                Liquor ratio directly influences chemical dosage for all liquid chemicals and salt/soda dosing.
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function updateCalc() {
  const kg = parseFloat(document.getElementById('calcFabricKg')?.value) || 0;
  const lrStr = document.getElementById('calcLr')?.value || '1:6';
  let ratio = 6.0;
  const m = lrStr.match(/1:([0-9\.]+)/);
  if (m) ratio = parseFloat(m[1]);
  const vol = Math.round(kg * ratio);
  const el = document.getElementById('calcBathVol');
  if (el) el.value = vol + ' L';
}
document.getElementById('calcFabricKg')?.addEventListener('input', updateCalc);
document.getElementById('calcLr')?.addEventListener('change', updateCalc);
document.getElementById('calcBatchSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (opt) {
    const w = opt.getAttribute('data-weight');
    const lr = opt.getAttribute('data-lr');
    if (w) document.getElementById('calcFabricKg').value = parseFloat(w).toFixed(1);
    if (lr) document.getElementById('calcLr').value = lr;
    updateCalc();
  }
});
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
