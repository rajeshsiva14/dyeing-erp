<?php
/**
 * Dyeing Process Sheet & Stage Completion Verification
 * Dedicated Register View
 * File: modules/dyeing/views/process-sheets/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Process Sheets & Stage Completion';
$activeModule = 'process-sheets';

$batchId = (int)($_GET['batch_id'] ?? 0);
if ($batchId > 0) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$batchId]);
}
if (empty($batch)) {
    $batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code, m.name as machine_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC LIMIT 1");
}

$allBatches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");

// Seed standard 7-stage reactive route if empty
$stages = $batch ? Database::fetchAll("SELECT * FROM dyeing_process_stages WHERE process_sheet_id = ? ORDER BY stage_no ASC", [$batch['id']]) : [];
if ($batch && empty($stages)) {
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
            <li class="breadcrumb-item active">Process Sheets</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text text-dark me-2"></i>Dyeing Process Sheet & Stage Completion</h1>
        <p class="text-secondary small mb-0">Controlled 7-stage reactive route: Bio-polishing, bleaching, reactive dyeing, fixation, hot wash and softening.</p>
      </div>
      <div class="d-flex gap-2">
        <?php if ($batch): ?>
          <a href="<?= Url::to('/dyeing/process-sheets/create?batch_id=' . $batch['id']) ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Custom Stage
          </a>
          <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $batch['id']) ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-broadcast me-1"></i> Live Production Floor Entry
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['completed'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Stage signed off and parameter telemetry logged.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['stage_added'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Custom process stage appended to batch sheet.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Active Batch Header Card -->
    <?php if ($batch): ?>
      <div class="cz-panel mb-4 p-3">
        <div class="row align-items-center g-3">
          <div class="col-md-4">
            <label class="small text-secondary fw-bold">Select Active Dyeing Batch:</label>
            <select class="form-select form-select-sm" onchange="location.href='?batch_id='+this.value">
              <?php foreach ($allBatches as $ab): ?>
                <option value="<?= $ab['id'] ?>" <?= ($ab['id'] == ($batch['id'] ?? 0)) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ab['batch_no']) ?> (<?= htmlspecialchars($ab['shade_name']) ?> · <?= number_format($ab['actual_loaded_kg'], 0) ?> Kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small">Vessel & Recipe Code</div>
            <div class="fw-bold text-dark"><?= htmlspecialchars($batch['machine_code']) ?> · <?= htmlspecialchars($batch['recipe_code']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small">Batch Liquor Volume</div>
            <div class="fw-bold text-dark font-monospace"><?= number_format($batch['water_volume_litres'], 0) ?> Litres (LR <?= htmlspecialchars($batch['liquor_ratio']) ?>)</div>
          </div>
          <div class="col-md-2 text-md-end">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-printer me-1"></i> Print Route
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Process Stages Table (Row Click navigates to edit/signoff page) -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 text-dark me-2"></i>Stage Verification & Clearance Table</h2>
        <span class="small text-secondary"><?= count($stages) ?> Defined Process Stages (Click any row to sign-off / edit stage)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="stageTable">
          <thead class="table-light">
            <tr>
              <th style="width: 50px;">Stage</th>
              <th>Process Stage & Description</th>
              <th class="text-center">Target & Act Temp</th>
              <th class="text-center">Target & Act pH</th>
              <th class="text-center">Target Mins</th>
              <th>Chemical Additions / Dosing</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stages as $stg): ?>
              <?php $editUrl = Url::to('/dyeing/process-sheets/edit?id=' . $stg['id']); ?>
              <tr class="cz-clickable-row" data-href="<?= $editUrl ?>" title="Click anywhere to update or sign-off stage parameters">
                <td class="text-center fw-bold">
                  <?php if ($stg['status'] === 'completed'): ?>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                  <?php elseif ($stg['status'] === 'in_progress'): ?>
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                  <?php else: ?>
                    <span class="badge bg-secondary rounded-circle p-2"><?= $stg['stage_no'] ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-6">
                    <a href="<?= $editUrl ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($stg['stage_name']) ?></a>
                  </div>
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
                  <a href="<?= $editUrl ?>" class="btn btn-sm btn-dark" onclick="event.stopPropagation();">
                    <i class="bi bi-check-circle me-1"></i> Sign-off / Edit
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
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
