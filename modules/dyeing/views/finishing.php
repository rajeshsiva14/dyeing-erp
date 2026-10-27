<?php
/**
 * Finishing Department (Hydro Extractor, Dryer & Stenter / Compactor)
 * Moisture extraction, conveyor drying, width/GSM setting, shrinkage control and fabric hand feel
 * File: modules/dyeing/views/finishing.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Finishing (Hydro, Dryer, Stenter)';
$activeModule = 'finishing';

// Handle adding finishing entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_finishing') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $machineId = (int)($_POST['machine_id'] ?? 3);
    $procType = $_POST['process_type'] ?? 'hydro_extraction';
    $inWeight = (float)($_POST['input_weight_kg'] ?? 1000);
    $outWeight = (float)($_POST['output_weight_kg'] ?? 600);
    $lossPct = max(0, (($inWeight - $outWeight) / $inWeight) * 100);
    $overfeed = (float)($_POST['overfeed_pct'] ?? 15.0);
    $width = (float)($_POST['finished_width_inches'] ?? 48.0);
    $gsm = (int)($_POST['finished_gsm'] ?? 160);
    $shrinkL = (float)($_POST['shrinkage_length_pct'] ?? 3.5);
    $shrinkW = (float)($_POST['shrinkage_width_pct'] ?? 2.8);
    $op = trim($_POST['operator_name'] ?? 'M. Palanisamy');

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
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$entries = Database::fetchAll("
    SELECT fe.*, b.batch_no, r.shade_name, m.code as machine_code, m.name as machine_name
    FROM finishing_entries fe
    JOIN dye_batches b ON fe.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    JOIN machines m ON fe.machine_id = m.id
    ORDER BY fe.id DESC
");

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$finishMachines = Database::fetchAll("SELECT * FROM machines WHERE process_type IN ('hydro_extractor', 'relax_dryer', 'stenter', 'compactor') ORDER BY code ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Finishing Operations</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-water text-primary me-2"></i>Finishing (Hydro Extractor, Relax Dryer, Stenter)</h1>
        <p class="text-secondary small mb-0">Track water extraction % removal, chamber drying temps, stenter overfeed, width, GSM, and shrinkage control.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newFinishModal">
          <i class="bi bi-plus-lg me-1"></i> Record Finishing Run
        </button>
      </div>
    </div>

    <!-- Active Finishing Register Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-sliders2-vertical text-primary me-2"></i>Finishing Department Production Log</h2>
        <span class="small text-secondary"><?= count($entries) ?> Finishing Runs</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Timestamp</th>
              <th>Batch No</th>
              <th>Finishing Stage</th>
              <th>Machine</th>
              <th class="text-end">Input Wt (Kg)</th>
              <th class="text-end">Output Wt (Kg)</th>
              <th class="text-center">Moisture / Loss %</th>
              <th class="text-center">Finished Width</th>
              <th class="text-center">Finished GSM</th>
              <th class="text-center">Shrinkage (L / W)</th>
              <th>Operator</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $fe): ?>
              <tr>
                <td class="font-monospace text-secondary"><?= date('d M H:i', strtotime($fe['created_at'])) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($fe['batch_no']) ?></span></td>
                <td>
                  <span class="badge bg-primary text-uppercase" style="font-size:0.7rem;">
                    <?= str_replace('_', ' ', $fe['process_type']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= htmlspecialchars($fe['machine_code']) ?></span>
                  <span class="ms-1 fw-semibold"><?= htmlspecialchars($fe['machine_name']) ?></span>
                </td>
                <td class="text-end font-monospace"><?= number_format($fe['input_weight_kg'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($fe['output_weight_kg'], 2) ?></td>
                <td class="text-center font-monospace">
                  <span class="badge bg-info-subtle text-info fw-bold"><?= number_format($fe['process_loss_pct'], 1) ?>%</span>
                </td>
                <td class="text-center font-monospace"><?= $fe['finished_width_inches'] ?>"</td>
                <td class="text-center font-monospace fw-bold text-primary"><?= $fe['finished_gsm'] ?> GSM</td>
                <td class="text-center font-monospace small">
                  L: <strong><?= $fe['shrinkage_length_pct'] ?? 3.5 ?>%</strong> · W: <strong><?= $fe['shrinkage_width_pct'] ?? 2.8 ?>%</strong>
                </td>
                <td><?= htmlspecialchars($fe['operator_name'] ?? 'M. Palanisamy') ?></td>
                <td class="text-end">
                  <a href="<?= Url::to('/dyeing/inspection?batch_id=' . $fe['dye_batch_id']) ?>" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-shield-check me-1"></i> 4-Point QC &rarr;
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

<!-- New Finishing Run Modal (Forms 21, 22, 23) -->
<div class="modal fade" id="newFinishModal" tabindex="-1" aria-labelledby="newFinishModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_finishing">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newFinishModalLabel"><i class="bi bi-water text-primary me-2"></i>Record Finishing Run</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Process Category *</label>
              <select name="process_type" class="form-select form-select-sm" required>
                <option value="hydro_extraction">Hydro Extractor (Centrifuge)</option>
                <option value="drying">Relax Conveyor Dryer</option>
                <option value="stenter" selected>Stenter Finishing Machine</option>
                <option value="compactor">Tubular / Open Compactor</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Finishing Machine *</label>
              <select name="machine_id" class="form-select form-select-sm" required>
                <?php foreach ($finishMachines as $fm): ?>
                  <option value="<?= $fm['id'] ?>"><?= htmlspecialchars($fm['code']) ?> — <?= htmlspecialchars($fm['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Weights & Parameters</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">Input Wet Wt (Kg) *</label>
              <input type="number" step="0.01" name="input_weight_kg" id="finInputKg" class="form-control form-control-sm" value="1000.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-primary">Output Finished Wt (Kg) *</label>
              <input type="number" step="0.01" name="output_weight_kg" id="finOutputKg" class="form-control form-control-sm fw-bold" value="955.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Stenter Overfeed %</label>
              <input type="number" step="0.1" name="overfeed_pct" class="form-control form-control-sm" value="15.0">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Chamber Temp (°C)</label>
              <input type="number" class="form-control form-control-sm" value="165">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Finished Width (Inches) *</label>
              <input type="number" step="0.1" name="finished_width_inches" class="form-control form-control-sm" value="48.0" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Finished GSM *</label>
              <input type="number" name="finished_gsm" class="form-control form-control-sm fw-bold text-primary" value="160" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Shrinkage Length (%)</label>
              <input type="number" step="0.1" name="shrinkage_length_pct" class="form-control form-control-sm" value="3.5">
            </div>
            <div class="col-md-3">
              <label class="form-label">Shrinkage Width (%)</label>
              <input type="number" step="0.1" name="shrinkage_width_pct" class="form-control form-control-sm" value="2.8">
            </div>
            <div class="col-md-12">
              <label class="form-label fw-bold">Operator In-charge *</label>
              <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Palanisamy (Senior Stenter Operator)" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Save Finishing Log</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
