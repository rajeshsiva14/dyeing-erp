<?php
/**
 * Dedicated Full-Page Form: Update Lab Dip & Record Spectrophotometer Trials
 * File: modules/dyeing/views/lab-dips/edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Lab Dip & Trial Results';
$activeModule = 'lab-dips';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$ldId = (int)($_GET['id'] ?? 0);

$request = Database::fetch("
    SELECT r.*, c.name as customer_name
    FROM lab_dip_requests r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.id = ? AND r.company_id = ?
", [$ldId, $companyId]);

if (!$request) {
    header('Location: ' . Url::to('/dyeing/lab-dips?error=not_found'));
    exit;
}

$trials = Database::fetchAll("SELECT * FROM lab_dip_trials WHERE lab_dip_id = ? ORDER BY trial_number ASC", [$ldId]);
$errors = [];

// Handle update header details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_lab_dip') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $shadeName = trim($_POST['shade_name'] ?? $request['shade_name']);
    $shadeCode = trim($_POST['shade_code'] ?? $request['shade_code']);
    $fabricType = trim($_POST['fabric_type'] ?? $request['fabric_type']);
    $lightSource = trim($_POST['light_source'] ?? $request['light_source']);
    $dueDate = $_POST['due_date'] ?? $request['due_date'];
    $status = $_POST['status'] ?? $request['status'];

    try {
        Database::getConnection()->prepare("
            UPDATE lab_dip_requests SET
                shade_name = ?,
                shade_code = ?,
                fabric_type = ?,
                light_source = ?,
                due_date = ?,
                status = ?
            WHERE id = ? AND company_id = ?
        ")->execute([
            $shadeName,
            $shadeCode,
            $fabricType,
            $lightSource,
            $dueDate,
            $status,
            $ldId,
            $companyId
        ]);

        header('Location: ' . Url::to('/dyeing/lab-dips/edit?id=' . $ldId . '&updated=1'));
        exit;
    } catch (\Throwable $e) {
        $errors[] = 'Failed to update request: ' . $e->getMessage();
    }
}

// Handle Add Trial Entry or Customer Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_trial') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $trialNum = (int)($_POST['trial_number'] ?? (count($trials) + 1));
    $deltaE = (float)($_POST['delta_e'] ?? 0.45);
    $result = $_POST['result'] ?? 'match';
    $feedback = trim($_POST['customer_feedback'] ?? 'Approved for bulk batch production');
    $isApproved = ($result === 'match') ? 1 : 0;

    $formulaJson = json_encode([
        ['dye' => trim($_POST['dye_1'] ?? 'Reactive Blue 21'), 'dose_pct' => (float)($_POST['dose_1'] ?? 1.25)],
        ['dye' => trim($_POST['dye_2'] ?? 'Reactive Red 195'), 'dose_pct' => (float)($_POST['dose_2'] ?? 0.15)],
        ['dye' => trim($_POST['dye_3'] ?? 'Reactive Yellow 145'), 'dose_pct' => (float)($_POST['dose_3'] ?? 0.08)],
    ]);

    Database::beginTransaction();
    try {
        Database::insert('lab_dip_trials', [
            'lab_dip_id'               => $ldId,
            'trial_number'             => $trialNum,
            'dyes_formula'             => $formulaJson,
            'delta_e'                  => $deltaE,
            'result'                   => $result,
            'submitted_to_customer_at' => date('Y-m-d'),
            'customer_feedback'        => $feedback,
            'is_approved'              => $isApproved
        ]);

        if ($isApproved) {
            Database::getConnection()->prepare("UPDATE lab_dip_requests SET status = 'approved' WHERE id = ? AND company_id = ?")->execute([$ldId, $companyId]);
        }

        Database::commit();
        header('Location: ' . Url::to('/dyeing/lab-dips/edit?id=' . $ldId . '&trial_added=1'));
        exit;
    } catch (\Throwable $e) {
        Database::rollBack();
        $errors[] = 'Failed to record trial: ' . $e->getMessage();
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/lab-dips') ?>">Lab Dips</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($request['request_no']) ?></li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-eyedropper text-dark me-2"></i>Lab Dip Shade Evaluation: <?= htmlspecialchars($request['shade_name']) ?></h1>
        <p class="text-secondary small mb-0">Record trial dyestuff formulation, spectrophotometer ΔE reading, and customer approval disposition.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Lab dip details updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['trial_added'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Spectrophotometer trial logged and approval disposition updated.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left Column: Lab Dip Details Update Form -->
      <div class="col-lg-5">
        <div class="cz-panel h-100">
          <div class="cz-panel-head">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2"></i>Lab Dip Specification</h2>
          </div>
          <form method="POST" action="" class="p-3">
            <input type="hidden" name="action" value="update_lab_dip">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="mb-3">
              <label class="form-label small fw-bold">Request Number</label>
              <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= htmlspecialchars($request['request_no']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Customer / Brand</label>
              <input type="text" class="form-control form-control-sm bg-light fw-bold" value="<?= htmlspecialchars($request['customer_name']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Shade Name *</label>
              <input type="text" name="shade_name" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($request['shade_name']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Pantone TCX / Shade Code *</label>
              <input type="text" name="shade_code" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($request['shade_code']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Fabric Substrate *</label>
              <input type="text" name="fabric_type" class="form-control form-control-sm" value="<?= htmlspecialchars($request['fabric_type']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Illuminant Light Source</label>
              <input type="text" name="light_source" class="form-control form-control-sm" value="<?= htmlspecialchars($request['light_source']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Target Due Date</label>
              <input type="date" name="due_date" class="form-control form-control-sm" value="<?= htmlspecialchars($request['due_date']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Approval Status</label>
              <select name="status" class="form-select form-select-sm fw-bold">
                <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending (In Queue)</option>
                <option value="trial_in_progress" <?= $request['status'] === 'trial_in_progress' ? 'selected' : '' ?>>Trial In Progress</option>
                <option value="submitted" <?= $request['status'] === 'submitted' ? 'selected' : '' ?>>Submitted to Buyer</option>
                <option value="approved" <?= $request['status'] === 'approved' ? 'selected' : '' ?>>Approved (Passed CMC Match)</option>
                <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Rejected (Off-Shade)</option>
              </select>
            </div>
            <button type="submit" class="btn btn-dark btn-sm w-100">
              <i class="bi bi-check2 me-1"></i> Update Lab Dip Info
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column: Trials Log & Add New Trial -->
      <div class="col-lg-7">
        <!-- Existing Trials Card -->
        <div class="cz-panel mb-4">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i>Recorded Trials History</h2>
            <span class="small text-secondary"><?= count($trials) ?> Trials</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Trial #</th>
                  <th>Dyestuff Formulation (% OWF)</th>
                  <th class="text-center">Spectro ΔE</th>
                  <th class="text-center">Visual Match</th>
                  <th>Customer Feedback</th>
                  <th class="text-center">Approved</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($trials)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-3 text-secondary">
                      No trials recorded yet. Use the form below to log Trial #1.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($trials as $t): ?>
                    <?php $dyes = json_decode($t['dyes_formula'] ?? '[]', true); ?>
                    <tr>
                      <td class="fw-bold font-monospace text-center fs-6">#<?= $t['trial_number'] ?></td>
                      <td>
                        <?php if (is_array($dyes)): ?>
                          <?php foreach ($dyes as $d): ?>
                            <span class="badge bg-light text-dark border me-1">
                              <?= htmlspecialchars($d['dye'] ?? '') ?>: <strong><?= number_format((float)($d['dose_pct'] ?? 0), 3) ?>%</strong>
                            </span>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <?= htmlspecialchars($t['dyes_formula']) ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-center font-monospace fw-bold <?= ($t['delta_e'] <= 0.8) ? 'text-success' : 'text-danger' ?> fs-6">
                        ΔE <?= number_format($t['delta_e'], 2) ?>
                      </td>
                      <td class="text-center">
                        <span class="badge <?= ($t['result'] === 'match') ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase">
                          <?= htmlspecialchars($t['result']) ?>
                        </span>
                      </td>
                      <td><?= htmlspecialchars($t['customer_feedback'] ?? 'Pending') ?></td>
                      <td class="text-center">
                        <?php if ($t['is_approved']): ?>
                          <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                        <?php else: ?>
                          <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Add New Trial Form Card -->
        <div class="cz-panel">
          <div class="cz-panel-head">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-plus-circle me-2"></i>Log New Spectrophotometer Trial</h2>
          </div>
          <form method="POST" action="" class="p-3">
            <input type="hidden" name="action" value="record_trial">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold">Trial Sequence # *</label>
                <input type="number" name="trial_number" class="form-control form-control-sm font-monospace fw-bold" value="<?= count($trials) + 1 ?>" required min="1">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Spectro ΔE CMC (2:1) *</label>
                <input type="number" step="0.01" name="delta_e" class="form-control form-control-sm fw-bold font-monospace" value="0.45" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Visual Comparison *</label>
                <select name="result" class="form-select form-select-sm fw-bold" required>
                  <option value="match" selected>Match (ΔE <= 0.80 Pass)</option>
                  <option value="light">Light (Needs Tone Depth)</option>
                  <option value="dark">Dark (Heavy Concentration)</option>
                  <option value="reddish">Reddish Cast (Tone Off)</option>
                  <option value="greenish">Greenish Cast</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>

              <!-- Dyes Formula (% OWF) -->
              <div class="col-12">
                <label class="form-label small fw-bold border-bottom pb-1 mb-2 d-block">Dyestuff Recipe (% OWF Dosing)</label>
              </div>
              <div class="col-md-8">
                <input type="text" name="dye_1" class="form-control form-control-sm" value="Reactive Blue 21 (Turquoise)" placeholder="Dye Component 1">
              </div>
              <div class="col-md-4">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.001" name="dose_1" class="form-control form-control-sm fw-bold font-monospace" value="1.250">
                  <span class="input-group-text">% OWF</span>
                </div>
              </div>

              <div class="col-md-8">
                <input type="text" name="dye_2" class="form-control form-control-sm" value="Reactive Red 195" placeholder="Dye Component 2">
              </div>
              <div class="col-md-4">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.001" name="dose_2" class="form-control form-control-sm fw-bold font-monospace" value="0.150">
                  <span class="input-group-text">% OWF</span>
                </div>
              </div>

              <div class="col-md-8">
                <input type="text" name="dye_3" class="form-control form-control-sm" value="Reactive Yellow 145" placeholder="Dye Component 3">
              </div>
              <div class="col-md-4">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.001" name="dose_3" class="form-control form-control-sm fw-bold font-monospace" value="0.080">
                  <span class="input-group-text">% OWF</span>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold">Customer Assessment / Feedback</label>
                <input type="text" name="customer_feedback" class="form-control form-control-sm" value="Approved for bulk batch production">
              </div>

              <div class="col-12 pt-2">
                <button type="submit" class="btn btn-dark btn-sm px-4">
                  <i class="bi bi-check2-circle me-1"></i> Save Trial & Update Approval
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
