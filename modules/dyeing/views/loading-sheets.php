<?php
/**
 * Machine Loading Sheet & Vessel Safety Verification
 * Physical fabric roll loading into soft flow vessel, cleaning verification & operator sign-off
 * File: modules/dyeing/views/loading-sheets.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Machine Loading Sheet';
$activeModule = 'loading-sheets';

// Handle Save Machine Loading Sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_loading') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $loadNo = 'MLS-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 1);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
    $machineId = $batch['machine_id'] ?? 1;
    
    $plannedKg = (float)($batch['planned_weight_kg'] ?? 500);
    $actualLoadedKg = (float)($_POST['actual_loaded_kg'] ?? $plannedKg);
    $rollsCount = (int)($_POST['rolls_count'] ?? 20);
    $operatorName = trim($_POST['operator_name'] ?? 'M. Ramanathan');
    $supervisorName = trim($_POST['supervisor_name'] ?? 'S. Natarajan');
    $cleaningVerified = isset($_POST['cleaning_verified']) ? 1 : 0;
    $safetyChecked = isset($_POST['safety_checked']) ? 1 : 0;

    Database::insert('machine_loading_sheets', [
        'company_id'          => 1,
        'loading_no'          => $loadNo,
        'dye_batch_id'        => $batchId,
        'batch_no'            => $batchNo,
        'machine_id'          => $machineId,
        'operator_name'       => $operatorName,
        'supervisor_name'     => $supervisorName,
        'cleaning_verified'   => $cleaningVerified,
        'safety_checked'      => $safetyChecked,
        'planned_weight_kg'   => $plannedKg,
        'actual_loaded_kg'    => $actualLoadedKg,
        'rolls_count'         => $rollsCount,
        'loading_start'       => date('Y-m-d H:i:s', strtotime('-40 minutes')),
        'loading_finish'      => date('Y-m-d H:i:s')
    ]);

    // Update batch actual loaded weight and advance status to loaded
    Database::getConnection()->prepare("UPDATE dye_batches SET actual_loaded_kg = ?, status = 'loaded' WHERE id = ?")->execute([$actualLoadedKg, $batchId]);

    header('Location: ' . Url::to('/dyeing/loading-sheets?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$loadingSheets = Database::fetchAll("
    SELECT ls.*, m.code as machine_code, m.name as machine_name, r.shade_name
    FROM machine_loading_sheets ls
    JOIN machines m ON ls.machine_id = m.id
    JOIN dye_batches b ON ls.dye_batch_id = b.id
    LEFT JOIN dye_recipes r ON b.dye_recipe_id = r.id
    ORDER BY ls.id DESC
");

$batches = Database::fetchAll("SELECT b.*, m.name as machine_name, r.shade_name FROM dye_batches b JOIN machines m ON b.machine_id = m.id JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Machine Loading Sheet</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-box-arrow-in-right text-primary me-2"></i>Machine Loading Sheet & Vessel Inspection</h1>
        <p class="text-secondary small mb-0">Record physical roll loading, nozzle alignment, lifter reel checks, vessel cleaning and operator verification.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newLoadingModal">
          <i class="bi bi-plus-lg me-1"></i> Record Machine Loading
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Machine Loading Sheet recorded. Batch status advanced to 'Loaded'. Ready for liquor fill & pre-treatment.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Loading Sheets Register Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Vessel Loading Verification Register</h2>
        <span class="small text-secondary"><?= count($loadingSheets) ?> Loading Records</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Loading No</th>
              <th>Batch No</th>
              <th>Machine / Vessel</th>
              <th>Shade</th>
              <th class="text-end">Planned (Kg)</th>
              <th class="text-end">Actual Loaded (Kg)</th>
              <th class="text-center">Rolls</th>
              <th>Cleaning Status</th>
              <th>Safety Check</th>
              <th>Operator / Supervisor</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($loadingSheets as $ls): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($ls['loading_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($ls['batch_no']) ?></span></td>
                <td>
                  <span class="badge bg-light text-dark border"><?= htmlspecialchars($ls['machine_code']) ?></span>
                  <span class="ms-1 fw-semibold"><?= htmlspecialchars($ls['machine_name']) ?></span>
                </td>
                <td><?= htmlspecialchars($ls['shade_name'] ?? 'Royal Navy') ?></td>
                <td class="text-end font-monospace"><?= number_format($ls['planned_weight_kg'], 2) ?></td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($ls['actual_loaded_kg'], 2) ?></td>
                <td class="text-center fw-bold"><?= $ls['rolls_count'] ?></td>
                <td>
                  <?php if ($ls['cleaning_verified']): ?>
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Clean Verified</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Unverified</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($ls['safety_checked']): ?>
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check me-1"></i>Passed</span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger">Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($ls['operator_name']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Sup: <?= htmlspecialchars($ls['supervisor_name']) ?></div>
                </td>
                <td class="text-end">
                  <a href="<?= Url::to('/dyeing/production-entry?batch_id=' . $ls['dye_batch_id']) ?>" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-broadcast me-1"></i> Floor Log
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

<!-- New Machine Loading Modal -->
<div class="modal fade" id="newLoadingModal" tabindex="-1" aria-labelledby="newLoadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_loading">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newLoadingModalLabel"><i class="bi bi-box-arrow-in-right text-primary me-2"></i>Record Vessel Fabric Loading</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Production Batch *</label>
              <select name="dye_batch_id" id="loadBatchSelect" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>" data-planned="<?= $b['planned_weight_kg'] ?>">
                    <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= htmlspecialchars($b['machine_name']) ?> · <?= number_format($b['planned_weight_kg'], 0) ?> Kg)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-primary">Actual Loaded Weight (Kg) *</label>
              <input type="number" step="0.01" name="actual_loaded_kg" id="loadActualKg" class="form-control form-control-sm fw-bold" value="500.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Rolls Count Loaded *</label>
              <input type="number" name="rolls_count" class="form-control form-control-sm" value="20" required>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Mandatory Pre-Loading Verification Checkpoints</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="form-check p-2 border rounded bg-light">
                <input class="form-check-input ms-1" type="checkbox" name="cleaning_verified" id="chkClean" checked required>
                <label class="form-check-label fw-bold ms-2 text-dark" for="chkClean">
                  Vessel Cleaning Verified
                </label>
                <div class="text-secondary small ms-4">Vessel walls, heat exchanger and lint filters free of previous dyestuff/chemical deposits.</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check p-2 border rounded bg-light">
                <input class="form-check-input ms-1" type="checkbox" name="safety_checked" id="chkSafety" checked required>
                <label class="form-check-label fw-bold ms-2 text-dark" for="chkSafety">
                  Nozzle Pressure & Safety Clear
                </label>
                <div class="text-secondary small ms-4">Main circulation pump seal intact, lid interlock engaged, pressure safety relief valve tested.</div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Primary Loading Operator *</label>
              <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Ramanathan (Vessel Lead)" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Shift Supervisor Sign-off *</label>
              <input type="text" name="supervisor_name" class="form-control form-control-sm" value="S. Natarajan (Dye House Mgr)" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Confirm Loading & Advance Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('loadBatchSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const planned = opt.getAttribute('data-planned');
  if (planned) document.getElementById('loadActualKg').value = planned;
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
