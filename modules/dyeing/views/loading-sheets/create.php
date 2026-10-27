<?php
/**
 * Dedicated Full-Page Form: Record Machine Loading Sheet & Vessel Safety Verification
 * File: modules/dyeing/views/loading-sheets/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Record Machine Loading Sheet';
$activeModule = 'loading-sheets';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$currentUser = SessionManager::getUser();
$errors = [];

// Handle Save Machine Loading Sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_loading') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $loadNo = 'MLS-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $batchId = (int)($_POST['dye_batch_id'] ?? 0);
    $batch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$batchId]);
    
    if (!$batch) {
        $errors[] = 'Please select a valid dye batch.';
    }

    if (!$errors) {
        $batchNo = $batch['batch_no'] ?? 'BATCH-2026-0001';
        $machineId = (int)($_POST['machine_id'] ?? ($batch['machine_id'] ?? 1));
        $plannedKg = (float)($batch['planned_weight_kg'] ?? 500);
        $actualLoadedKg = (float)($_POST['actual_loaded_kg'] ?? $plannedKg);
        $rollsCount = (int)($_POST['rolls_count'] ?? 20);
        $operatorName = trim($_POST['operator_name'] ?? ($currentUser['name'] ?? 'M. Ramanathan'));
        $supervisorName = trim($_POST['supervisor_name'] ?? 'S. Natarajan');
        $cleaningVerified = isset($_POST['cleaning_verified']) ? 1 : 0;
        $safetyChecked = isset($_POST['safety_checked']) ? 1 : 0;

        try {
            Database::insert('machine_loading_sheets', [
                'company_id'          => $companyId,
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
        } catch (\Throwable $e) {
            $errors[] = 'Failed to record loading sheet: ' . $e->getMessage();
        }
    }
}

$batches = Database::fetchAll("SELECT b.*, m.name as machine_name, r.shade_name FROM dye_batches b JOIN machines m ON b.machine_id = m.id JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' OR machine_type = 'dyeing' ORDER BY code ASC");
if (empty($machines)) {
    $machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/loading-sheets') ?>">Loading Sheets</a></li>
            <li class="breadcrumb-item active">New Loading Sheet</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-box-arrow-in-down-right text-dark me-2"></i>Record Machine Loading Sheet</h1>
        <p class="text-secondary small mb-0">Record vessel fabric loading, roll count verification, lint filter cleaning, and safety clearance.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/loading-sheets') ?>" class="btn btn-outline-secondary btn-sm">
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
      <input type="hidden" name="action" value="save_loading">
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
                <label class="form-label small fw-bold">Select Planned Batch *</label>
                <select name="dye_batch_id" class="form-select form-select-sm" required onchange="populateBatch(this)">
                  <option value="">-- Choose Batch --</option>
                  <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-weight="<?= $b['planned_weight_kg'] ?>" data-machine="<?= $b['machine_id'] ?>">
                      <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['planned_weight_kg'], 0) ?> Kg)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Vessel / Machine Assigned *</label>
                <select name="machine_id" id="mlsMachineSelect" class="form-select form-select-sm" required>
                  <?php foreach ($machines as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Operator Name *</label>
                <input type="text" name="operator_name" class="form-control form-control-sm fw-bold" value="M. Ramanathan" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Floor Supervisor / Lead *</label>
                <input type="text" name="supervisor_name" class="form-control form-control-sm fw-bold" value="S. Natarajan" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Fabric Loading & Checklists -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 me-2"></i>Loaded Quantities & Vessel Safety Checklist</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-success">Actual Loaded Weight (Kg) *</label>
                  <input type="number" step="0.1" name="actual_loaded_kg" id="mlsLoadedKg" class="form-control form-control-sm fw-bold text-success fs-6" value="500.0" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Total Fabric Rolls Count *</label>
                  <input type="number" name="rolls_count" class="form-control form-control-sm fw-bold" value="20" required min="1">
                </div>
                <div class="col-12">
                  <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold text-dark small mb-3">Pre-Loading Vessel Hygiene & Safety Verification</h6>
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" name="cleaning_verified" value="1" id="chkClean" checked>
                      <label class="form-check-label small fw-semibold" for="chkClean">
                        Vessel interior washed down, nozzle filters cleaned, no residual color from previous bath.
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="safety_checked" value="1" id="chkSafety" checked>
                      <label class="form-check-label small fw-semibold" for="chkSafety">
                        Vessel lid pneumatic lock, pressure release valve, and emergency circulation stop tested.
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/loading-sheets') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-box-arrow-in-down-right me-1"></i> Confirm Vessel Loading & Advance Batch
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
    document.getElementById('mlsLoadedKg').value = parseFloat(opt.dataset.weight).toFixed(1);
  }
  if (opt && opt.dataset.machine) {
    document.getElementById('mlsMachineSelect').value = opt.dataset.machine;
  }
}
</script>

<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
