<?php
/**
 * Dyeing Wastage & Process Loss Reconciliation
 * Itemized breakdown: seam cuts, stenter edge trimming, sample cut-outs, moisture loss
 * File: modules/dyeing/views/wastage-loss.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Wastage & Process Loss';
$activeModule = 'wastage-loss';

// Handle New Loss Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_loss') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $entryNo = 'WST-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
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
    $reportedBy = trim($_POST['reported_by'] ?? 'S. Natarajan');

    Database::insert('dyeing_wastage_entries', [
        'company_id'          => 1,
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
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$losses = Database::fetchAll("
    SELECT w.*, r.shade_name
    FROM dyeing_wastage_entries w
    JOIN dye_batches b ON w.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY w.id DESC
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
            <li class="breadcrumb-item active">Wastage & Loss</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-primary me-2"></i>Dyeing Wastage & Process Loss Reconciliation</h1>
        <p class="text-secondary small mb-0">Reconcile input weight against output weight: distinguish end-bits, stenter edge trimming, sample cuts, and moisture loss.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newLossModal">
          <i class="bi bi-plus-lg me-1"></i> Record Process Loss
        </button>
      </div>
    </div>

    <!-- Wastage Register Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clipboard-data text-primary me-2"></i>Process Loss & Yield Reconciliation Register</h2>
        <span class="small text-secondary"><?= count($losses) ?> Reconciled Records</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Entry No</th>
              <th>Batch No</th>
              <th>Stage</th>
              <th class="text-end">Input (Kg)</th>
              <th class="text-end">Output (Kg)</th>
              <th class="text-end">Net Loss (Kg)</th>
              <th class="text-center">Loss %</th>
              <th>Loss Breakdown (Samples/Trims/Damage)</th>
              <th>Reason & Recovery</th>
              <th>Reported By</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($losses as $l): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($l['entry_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($l['batch_no']) ?></span></td>
                <td><span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($l['process_stage']) ?></span></td>
                <td class="text-end font-monospace"><?= number_format($l['input_kg'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($l['output_kg'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-danger"><?= number_format($l['net_loss_kg'], 2) ?></td>
                <td class="text-center font-monospace">
                  <span class="badge <?= ($l['loss_percentage'] <= 6.0) ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= number_format($l['loss_percentage'], 2) ?>%
                  </span>
                </td>
                <td>
                  <div class="text-secondary" style="font-size:0.75rem;">
                    Trims: <strong><?= $l['process_waste_kg'] ?> Kg</strong> · Cuts: <strong><?= $l['sample_cut_kg'] ?> Kg</strong> · Dmg: <strong><?= $l['damaged_fabric_kg'] ?> Kg</strong>
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($l['loss_reason']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Disposal: <?= htmlspecialchars($l['disposal_method']) ?></div>
                </td>
                <td><?= htmlspecialchars($l['reported_by']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Loss Modal -->
<div class="modal fade" id="newLossModal" tabindex="-1" aria-labelledby="newLossModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_loss">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newLossModalLabel"><i class="bi bi-pie-chart text-primary me-2"></i>Record Process Wastage & Loss Reconciliation</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" id="lossBatchSelect" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>" data-loaded="<?= $b['actual_loaded_kg'] ?>">
                    <?= htmlspecialchars($b['batch_no']) ?> (Loaded: <?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Process Stage *</label>
              <select name="process_stage" class="form-select form-select-sm" required>
                <option value="dyeing">Dyeing Vessel (Seam & End Bits)</option>
                <option value="hydro">Hydro Extractor</option>
                <option value="drying">Relax Conveyor Dryer</option>
                <option value="stenter" selected>Stenter Finishing (Edge Trimming / Pin Holes)</option>
                <option value="compactor">Compactor Finishing</option>
                <option value="inspection">Final 4-Point Inspection (Defect Cut-outs)</option>
              </select>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Weights & Yield Reconciliation</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">Fabric Input Weight (Kg) *</label>
              <input type="number" step="0.01" name="input_kg" id="lossInputKg" class="form-control form-control-sm" value="1007.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-success">Fabric Output Weight (Kg) *</label>
              <input type="number" step="0.01" name="output_kg" id="lossOutputKg" class="form-control form-control-sm fw-bold" value="955.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-danger">Calculated Net Loss (Kg)</label>
              <input type="number" step="0.01" id="lossNetKg" class="form-control form-control-sm bg-light fw-bold text-danger" readonly value="52.00">
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Itemized Loss Breakdown</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label">Sample Cut-outs (Kg)</label>
              <input type="number" step="0.01" name="sample_cut_kg" class="form-control form-control-sm" value="2.50">
            </div>
            <div class="col-md-3">
              <label class="form-label">Process Waste / Trims (Kg)</label>
              <input type="number" step="0.01" name="process_waste_kg" class="form-control form-control-sm" value="18.50">
            </div>
            <div class="col-md-3">
              <label class="form-label">Damaged Fabric (Kg)</label>
              <input type="number" step="0.01" name="damaged_fabric_kg" class="form-control form-control-sm" value="0.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Moisture / Shrinkage Loss (Kg)</label>
              <input type="number" step="0.01" name="unaccounted_loss_kg" class="form-control form-control-sm" value="31.00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Loss Reason / Justification *</label>
              <input type="text" name="loss_reason" class="form-control form-control-sm" value="Moisture evaporation during stenter drying + selvage pin trimming." required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Disposal / Salvage Channel *</label>
              <input type="text" name="disposal_method" class="form-control form-control-sm" value="Saleable Cotton Rags / Open End Spinning Feedstock" required>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-bold">Reported By *</label>
              <input type="text" name="reported_by" class="form-control form-control-sm" value="S. Natarajan (Dye House Technical Mgr)" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Confirm & Reconcile Loss</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calcLoss() {
  const inp = parseFloat(document.getElementById('lossInputKg')?.value) || 0;
  const out = parseFloat(document.getElementById('lossOutputKg')?.value) || 0;
  const loss = Math.max(0, inp - out);
  const netEl = document.getElementById('lossNetKg');
  if (netEl) netEl.value = loss.toFixed(2);
}
document.getElementById('lossInputKg')?.addEventListener('input', calcLoss);
document.getElementById('lossOutputKg')?.addEventListener('input', calcLoss);
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
