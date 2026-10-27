<?php
/**
 * Dyeing Process Sheet & Stage Completion Verification
 * Stage-by-stage process parameters, temperature ramps, pH control, and stage clearance
 * File: modules/dyeing/views/process-sheets.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Process Sheets & Stage Completion';
$activeModule = 'process-sheets';

// Handle Stage Completion Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_stage') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $stageId = (int)($_POST['stage_id'] ?? 1);
    $actualTemp = (float)($_POST['actual_temperature_c'] ?? 60.0);
    $actualPh = (float)($_POST['actual_ph'] ?? 11.2);
    $actualMins = (int)($_POST['actual_duration_mins'] ?? 45);
    $status = $_POST['status'] ?? 'completed';

    Database::getConnection()->prepare("
        UPDATE dyeing_process_stages
        SET actual_temperature_c = ?, actual_ph = ?, actual_duration_mins = ?, status = ?, completed_at = NOW()
        WHERE id = ?
    ")->execute([$actualTemp, $actualPh, $actualMins, $status, $stageId]);

    header('Location: ' . Url::to('/dyeing/process-sheets?completed=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$batchId = (int)($_GET['batch_id'] ?? 1);
$batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$batchId]);
if (!$batch) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id LIMIT 1");
}

// Check or create process sheet stages for this batch
$stages = Database::fetchAll("SELECT * FROM dyeing_process_stages WHERE process_sheet_id = ? ORDER BY stage_no ASC", [$batch['id']]);
if (empty($stages)) {
    // Seed standard 7-stage reactive route
    $defaultStages = [
        ['no' => 1, 'name' => '1. Pre-treatment & Bio-Polishing', 'temp' => 60.0, 'ph' => 4.5, 'mins' => 45, 'chem' => 'Cellulase Enzyme (0.8%) + Acetic Acid (0.5 g/L)', 'inst' => 'Run at 60°C for 45 mins to eliminate surface hairiness/pills.'],
        ['no' => 2, 'name' => '2. Bleaching & Scouring', 'temp' => 95.0, 'ph' => 10.5, 'mins' => 40, 'chem' => 'Caustic Soda Flakes + Hydrogen Peroxide 50% + Stabilizer', 'inst' => 'Ramp at 2.5°C/min to 95°C. Check core whiteness / absorbency.'],
        ['no' => 3, 'name' => '3. Hot Rinse & Peroxide Killer', 'temp' => 60.0, 'ph' => 7.0, 'mins' => 20, 'chem' => 'Catalase Enzyme (Peroxide Neutralizer)', 'inst' => 'Ensure zero residual peroxide before dyestuff addition.'],
        ['no' => 4, 'name' => '4. Reactive Dye Dosing & Migration', 'temp' => 60.0, 'ph' => 8.5, 'mins' => 30, 'chem' => 'Reactive Dyestuffs + Glauber Salt (45 Kg)', 'inst' => 'Linear dosing over 20 mins. Allow dyestuff to migrate uniformly.'],
        ['no' => 5, 'name' => '5. Alkali Fixation (Soda Ash Dosing)', 'temp' => 60.0, 'ph' => 11.2, 'mins' => 60, 'chem' => 'Soda Ash Light (18 Kg) in 3 progressive steps', 'inst' => 'Crucial fixation hold. Maintain pH 11.0 - 11.4 for full covalent bonding.'],
        ['no' => 6, 'name' => '6. Neutralization & Hot Soaping', 'temp' => 95.0, 'ph' => 7.0, 'mins' => 35, 'chem' => 'Soaping Agent (Polymeric Washing-off)', 'inst' => 'Strip unfixed hydrolyzed reactive dye. Clear bath liquor required.'],
        ['no' => 7, 'name' => '7. Softening & Hydro Extractor Handover', 'temp' => 40.0, 'ph' => 5.5, 'mins' => 20, 'chem' => 'Cationic / Silicone Softener (1.5%)', 'inst' => 'Target soft, supple handle. Ready for vessel unloading to Hydro.'],
    ];
    foreach ($defaultStages as $ds) {
        Database::insert('dyeing_process_stages', [
            'process_sheet_id'       => $batch['id'],
            'stage_no'               => $ds['no'],
            'stage_name'             => $ds['name'],
            'target_temperature_c'   => $ds['temp'],
            'actual_temperature_c'   => $ds['temp'],
            'target_ph'              => $ds['ph'],
            'actual_ph'              => $ds['ph'],
            'target_duration_mins'   => $ds['mins'],
            'actual_duration_mins'   => $ds['mins'],
            'chemical_addition'      => $ds['chem'],
            'operator_instruction'   => $ds['inst'],
            'status'                 => ($ds['no'] <= 3) ? 'completed' : (($ds['no'] == 4) ? 'in_progress' : 'pending'),
            'completed_at'           => ($ds['no'] <= 3) ? date('Y-m-d H:i:s') : null
        ]);
    }
    $stages = Database::fetchAll("SELECT * FROM dyeing_process_stages WHERE process_sheet_id = ? ORDER BY stage_no ASC", [$batch['id']]);
}
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Process Sheets</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>Dyeing Process Sheet & Stage Completion</h1>
        <p class="text-secondary small mb-0">Controlled 7-stage reactive route: Bio-polishing, bleaching, reactive dyeing, fixation, hot wash and softening.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $batch['id']) ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-broadcast me-1"></i> Live Production Floor Entry
        </a>
      </div>
    </div>

    <!-- Active Batch Header Card -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-3">
        <div class="row align-items-center g-3">
          <div class="col-md-3">
            <div class="text-secondary small fw-bold">Active Batch</div>
            <div class="fs-5 fw-bold font-monospace text-primary"><?= htmlspecialchars($batch['batch_no']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small fw-bold">Vessel / Machine</div>
            <div class="fw-bold text-dark"><?= htmlspecialchars($batch['machine_code']) ?> — <?= htmlspecialchars($batch['machine_name']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small fw-bold">Recipe & Shade</div>
            <div class="fw-bold text-dark"><?= htmlspecialchars($batch['recipe_code']) ?> · <?= htmlspecialchars($batch['shade_name']) ?></div>
          </div>
          <div class="col-md-3 text-md-end">
            <span class="badge bg-primary text-uppercase px-3 py-2 fs-6"><?= htmlspecialchars($batch['status']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Process Stages Timeline & Verification Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Stage-wise Process Execution & Verification</h2>
        <span class="small text-secondary">Enforces strict step completion before advancing to subsequent stages</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th style="width: 50px;">Stage</th>
              <th>Process Step Description</th>
              <th class="text-center">Target Temp</th>
              <th class="text-center">Target pH</th>
              <th class="text-center">Duration</th>
              <th>Chemical Dosing / Auxiliary</th>
              <th>Status</th>
              <th class="text-end">Verification & Sign-off</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stages as $stg): ?>
              <tr class="<?= $stg['status'] === 'in_progress' ? 'table-warning' : ($stg['status'] === 'completed' ? 'table-light' : '') ?>">
                <td class="fw-bold text-center">
                  <?php if ($stg['status'] === 'completed'): ?>
                    <span class="badge bg-success rounded-circle p-2"><i class="bi bi-check"></i></span>
                  <?php elseif ($stg['status'] === 'in_progress'): ?>
                    <span class="badge bg-warning text-dark rounded-circle p-2"><i class="bi bi-hourglass-split"></i></span>
                  <?php else: ?>
                    <span class="badge bg-secondary rounded-circle p-2"><?= $stg['stage_no'] ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($stg['stage_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($stg['operator_instruction'] ?? '') ?></div>
                </td>
                <td class="text-center font-monospace">
                  <span class="badge bg-light text-dark border"><?= $stg['target_temperature_c'] ?>°C</span>
                  <?php if ($stg['actual_temperature_c']): ?>
                    <div class="text-success small fw-bold">Act: <?= $stg['actual_temperature_c'] ?>°C</div>
                  <?php endif; ?>
                </td>
                <td class="text-center font-monospace">
                  <span class="badge bg-light text-dark border">pH <?= $stg['target_ph'] ?></span>
                  <?php if ($stg['actual_ph']): ?>
                    <div class="text-primary small fw-bold">Act: <?= $stg['actual_ph'] ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-center font-monospace">
                  <span class="badge bg-light text-dark border"><?= $stg['target_duration_mins'] ?> Mins</span>
                </td>
                <td>
                  <div class="small fw-semibold"><?= htmlspecialchars($stg['chemical_addition'] ?? 'None') ?></div>
                </td>
                <td>
                  <span class="badge <?= $stg['status'] === 'completed' ? 'bg-success' : ($stg['status'] === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary') ?> text-uppercase">
                    <?= htmlspecialchars($stg['status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <?php if ($stg['status'] !== 'completed'): ?>
                    <button class="btn btn-sm btn-dark verify-stage-btn"
                            data-stage-id="<?= $stg['id'] ?>"
                            data-stage-name="<?= htmlspecialchars($stg['stage_name']) ?>"
                            data-target-temp="<?= $stg['target_temperature_c'] ?>"
                            data-target-ph="<?= $stg['target_ph'] ?>"
                            data-target-mins="<?= $stg['target_duration_mins'] ?>"
                            data-bs-toggle="modal" data-bs-target="#verifyStageModal">
                      <i class="bi bi-check-circle me-1"></i> Complete Stage
                    </button>
                  <?php else: ?>
                    <span class="text-muted small"><i class="bi bi-clock-history me-1"></i><?= date('H:i', strtotime($stg['completed_at'] ?? 'now')) ?> Completed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Stage Completion Modal -->
<div class="modal fade" id="verifyStageModal" tabindex="-1" aria-labelledby="verifyStageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="complete_stage">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <input type="hidden" name="stage_id" id="modalStageId" value="">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="verifyStageModalLabel"><i class="bi bi-check2-circle text-primary me-2"></i>Sign-off Stage Completion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <h6 class="fw-bold text-primary mb-3" id="modalStageName">Stage Verification</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">Actual Temp (°C) *</label>
              <input type="number" step="0.1" name="actual_temperature_c" id="modalActualTemp" class="form-control form-control-sm fw-bold" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Actual pH *</label>
              <input type="number" step="0.1" name="actual_ph" id="modalActualPh" class="form-control form-control-sm fw-bold" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Actual Duration (Mins) *</label>
              <input type="number" name="actual_duration_mins" id="modalActualMins" class="form-control form-control-sm fw-bold" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Stage Disposition Decision *</label>
              <select name="status" class="form-select form-select-sm" required>
                <option value="completed">Completed (Parameters Met Acceptance Criteria)</option>
                <option value="deviation">Completed with Approved Deviation</option>
                <option value="hold">Hold for Supervisor Inspection</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Authorize & Release Next Stage</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.verify-stage-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('modalStageId').value = this.getAttribute('data-stage-id');
    document.getElementById('modalStageName').textContent = this.getAttribute('data-stage-name');
    document.getElementById('modalActualTemp').value = this.getAttribute('data-target-temp');
    document.getElementById('modalActualPh').value = this.getAttribute('data-target-ph');
    document.getElementById('modalActualMins').value = this.getAttribute('data-target-mins');
  });
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
