<?php
/**
 * Dedicated Full-Page Form: Update Machine Loading Sheet & Vessel Safety Verification
 * File: modules/dyeing/views/loading-sheets/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Machine Loading Sheet';
$activeModule = 'loading-sheets';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$lsId = (int)($_GET['id'] ?? 0);

$sheet = Database::fetch("SELECT * FROM machine_loading_sheets WHERE id = ?", [$lsId]);
if (!$sheet) {
    header('Location: ' . Url::to('/dyeing/loading-sheets?error=not_found'));
    exit;
}

$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' OR machine_type = 'dyeing' ORDER BY code ASC");
if (empty($machines)) {
    $machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC LIMIT 10");
}

$errors = [];

// Handle update loading sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_loading') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $machineId = (int)($_POST['machine_id'] ?? $sheet['machine_id']);
    $actualLoadedKg = (float)($_POST['actual_loaded_kg'] ?? $sheet['actual_loaded_kg']);
    $rollsCount = (int)($_POST['rolls_count'] ?? $sheet['rolls_count']);
    $operatorName = trim($_POST['operator_name'] ?? $sheet['operator_name']);
    $supervisorName = trim($_POST['supervisor_name'] ?? $sheet['supervisor_name']);
    $cleaningVerified = isset($_POST['cleaning_verified']) ? 1 : 0;
    $safetyChecked = isset($_POST['safety_checked']) ? 1 : 0;

    try {
        Database::getConnection()->prepare("
            UPDATE machine_loading_sheets SET
                machine_id = ?,
                actual_loaded_kg = ?,
                rolls_count = ?,
                operator_name = ?,
                supervisor_name = ?,
                cleaning_verified = ?,
                safety_checked = ?
            WHERE id = ?
        ")->execute([
            $machineId,
            $actualLoadedKg,
            $rollsCount,
            $operatorName,
            $supervisorName,
            $cleaningVerified,
            $safetyChecked,
            $lsId
        ]);

        // Update batch actual loaded weight
        Database::getConnection()->prepare("UPDATE dye_batches SET actual_loaded_kg = ? WHERE id = ?")->execute([$actualLoadedKg, $sheet['dye_batch_id']]);

        header('Location: ' . Url::to('/dyeing/loading-sheets?updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update loading sheet: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/loading-sheets') ?>">Loading Sheets</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($sheet['loading_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-dark me-2"></i>Update Machine Loading Sheet #<?= htmlspecialchars($sheet['loading_no']) ?></h1>
        <p class="text-secondary small mb-0">Update vessel assignment, loaded kilogram weighment, and safety checklists.</p>
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
      <input type="hidden" name="action" value="update_loading">
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
                <label class="form-label small fw-bold">Loading Sheet No</label>
                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($sheet['loading_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Batch Reference</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($sheet['batch_no']) ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Vessel / Machine Assigned *</label>
                <select name="machine_id" class="form-select form-select-sm" required>
                  <?php foreach ($machines as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= ($m['id'] == $sheet['machine_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Operator Name *</label>
                <input type="text" name="operator_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($sheet['operator_name']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Floor Supervisor / Lead *</label>
                <input type="text" name="supervisor_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($sheet['supervisor_name']) ?>" required>
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
                  <input type="number" step="0.1" name="actual_loaded_kg" class="form-control form-control-sm fw-bold text-success fs-6" value="<?= (float)$sheet['actual_loaded_kg'] ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Total Fabric Rolls Count *</label>
                  <input type="number" name="rolls_count" class="form-control form-control-sm fw-bold" value="<?= (int)$sheet['rolls_count'] ?>" required min="1">
                </div>
                <div class="col-12">
                  <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold text-dark small mb-3">Pre-Loading Vessel Hygiene & Safety Verification</h6>
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" name="cleaning_verified" value="1" id="chkClean" <?= $sheet['cleaning_verified'] ? 'checked' : '' ?>>
                      <label class="form-check-label small fw-semibold" for="chkClean">
                        Vessel interior washed down, nozzle filters cleaned, no residual color from previous bath.
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="safety_checked" value="1" id="chkSafety" <?= $sheet['safety_checked'] ? 'checked' : '' ?>>
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
                <i class="bi bi-check2-circle me-1"></i> Update Machine Loading Sheet
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
