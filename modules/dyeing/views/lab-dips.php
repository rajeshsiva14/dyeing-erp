<?php
/**
 * Lab Dip Request, Trial Entry & Shade Approvals
 * Computerized shade matching, spectrophotometer ΔE, L*a*b* readings and customer approval
 * File: modules/dyeing/views/lab-dips.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Lab Dip & Shade Approvals';
$activeModule = 'lab-dips';

// Handle new Lab Dip Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_lab_dip') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $reqNo = 'LAB-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $customerId = (int)($_POST['customer_id'] ?? 1);
    $shadeName = trim($_POST['shade_name'] ?? '');
    $shadeCode = trim($_POST['shade_code'] ?? 'SH-NAV-01');
    $fabricType = trim($_POST['fabric_type'] ?? '100% Combed Cotton Single Jersey 30s');
    $lightSource = trim($_POST['light_source'] ?? 'D65 / TL84');
    $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+3 days'));

    Database::insert('lab_dip_requests', [
        'company_id'   => 1,
        'request_no'   => $reqNo,
        'customer_id'  => $customerId,
        'shade_name'   => $shadeName,
        'shade_code'   => $shadeCode,
        'fabric_type'  => $fabricType,
        'light_source' => $lightSource,
        'due_date'     => $dueDate,
        'status'       => 'trial_in_progress'
    ]);

    header('Location: ' . Url::to('/dyeing/lab-dips?created=1'));
    exit;
}

// Handle Trial Entry or Customer Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_trial') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $labDipId = (int)($_POST['lab_dip_id'] ?? 1);
    $trialNum = (int)($_POST['trial_number'] ?? 1);
    $deltaE = (float)($_POST['delta_e'] ?? 0.45);
    $result = $_POST['result'] ?? 'match';
    $feedback = trim($_POST['customer_feedback'] ?? 'Approved for bulk batch production');
    $isApproved = ($result === 'match') ? 1 : 0;

    $formulaJson = json_encode([
        ['dye' => trim($_POST['dye_1'] ?? 'Reactive Blue 21'), 'dose_pct' => (float)($_POST['dose_1'] ?? 1.25)],
        ['dye' => trim($_POST['dye_2'] ?? 'Reactive Red 195'), 'dose_pct' => (float)($_POST['dose_2'] ?? 0.15)],
        ['dye' => trim($_POST['dye_3'] ?? 'Reactive Yellow 145'), 'dose_pct' => (float)($_POST['dose_3'] ?? 0.08)],
    ]);

    Database::insert('lab_dip_trials', [
        'lab_dip_id'               => $labDipId,
        'trial_number'             => $trialNum,
        'dyes_formula'             => $formulaJson,
        'delta_e'                  => $deltaE,
        'result'                   => $result,
        'submitted_to_customer_at' => date('Y-m-d'),
        'customer_feedback'        => $feedback,
        'is_approved'              => $isApproved
    ]);

    if ($isApproved) {
        Database::getConnection()->prepare("UPDATE lab_dip_requests SET status = 'approved' WHERE id = ?")->execute([$labDipId]);
    }

    header('Location: ' . Url::to('/dyeing/lab-dips?trial_added=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$action = $_GET['action'] ?? '';
$isNewRequestForm = ($action === 'new');
$isTrialForm = ($action === 'trial');
$presetLabDipId = (int)($_GET['lab_dip_id'] ?? 0);

$requests = Database::fetchAll("
    SELECT r.*, c.name as customer_name
    FROM lab_dip_requests r
    JOIN customers c ON r.customer_id = c.id
    ORDER BY r.id DESC
");

$trials = Database::fetchAll("
    SELECT t.*, r.request_no, r.shade_name, r.shade_code, c.name as customer_name
    FROM lab_dip_trials t
    JOIN lab_dip_requests r ON t.lab_dip_id = r.id
    JOIN customers c ON r.customer_id = c.id
    ORDER BY t.id DESC
");

// Safe query: status = 'active' for customers
$customers = Database::fetchAll("SELECT * FROM customers WHERE status = 'active' ORDER BY name ASC");
$shades = Database::fetchAll("SELECT * FROM dyeing_shades WHERE is_active = 1 ORDER BY shade_name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if ($isNewRequestForm): ?>
      <!-- ========================================================================= -->
      <!-- FULL-PAGE FORM: NEW LAB DIP REQUEST (No Modals)                  -->
      <!-- ========================================================================= -->

      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">New Lab Dip Shade Request</h1>
              <span class="badge badge-bw">LAB REQUEST</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Initiate spectrophotometer matching, customer substrate trials, and recipe development.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newLabDipForm" class="btn btn-dark btn-sm">
            <i class="bi bi-check-lg me-1"></i> Submit to Dye Lab
          </button>
        </div>
      </div>

      <form id="newLabDipForm" method="POST" action="<?= Url::to('/dyeing/lab-dips') ?>">
        <input type="hidden" name="action" value="save_lab_dip">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-eyedropper"></i> Customer & Target Shade Parameters</h2>
              <p class="cz-doc-section-desc">Specify buyer color standard, reference code, light viewing booth requirements, and turnaround target.</p>
            </div>
            <span class="badge badge-bw font-monospace">LAB DIP SPECS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Customer / Buyer <span class="text-danger">*</span></label>
              <select name="customer_id" class="form-select form-select-sm" required>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Shade Name <span class="text-danger">*</span></label>
              <input type="text" name="shade_name" class="form-control form-control-sm" placeholder="e.g. Royal Navy Blue" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Target Shade Code / Pantone <span class="text-danger">*</span></label>
              <input type="text" name="shade_code" class="form-control form-control-sm font-monospace" value="SH-NAV-01 (19-4024 TCX)" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Primary Light Source <span class="text-danger">*</span></label>
              <select name="light_source" class="form-select form-select-sm">
                <option value="D65 / TL84 Dual Light" selected>D65 (Daylight 6500K) + TL84 (Point of Sale)</option>
                <option value="D65 (Artificial Daylight)">D65 Only (North Sky Daylight)</option>
                <option value="TL84 (Store Light)">TL84 Only (European Retail)</option>
                <option value="CWF (Cool White Fluorescent)">CWF (US Market Standard)</option>
                <option value="U30 / Horizon">U30 / Horizon Incandescent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Target Lab Due Date <span class="text-danger">*</span></label>
              <input type="date" name="due_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Substrate / Fabric Specification <span class="text-danger">*</span></label>
              <input type="text" name="fabric_type" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s Ne (160 GSM, Bio-Washed)" required>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-check2-circle me-1"></i> Submit Lab Dip Request
          </button>
        </div>
      </form>

    <?php elseif ($isTrialForm): ?>
      <!-- ========================================================================= -->
      <!-- FULL-PAGE FORM: RECORD TRIAL & SHADE MATCHING (No Modals)    -->
      <!-- ========================================================================= -->

      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">Record Lab Trial & Shade Matching</h1>
              <span class="badge badge-bw">TRIAL ENTRY</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Record spectrophotometer reading (&Delta;E, L*a*b*), trial recipe dosing % OWF, and customer disposition.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newTrialForm" class="btn btn-dark btn-sm">
            <i class="bi bi-check-circle me-1"></i> Save Trial & Approve
          </button>
        </div>
      </div>

      <form id="newTrialForm" method="POST" action="<?= Url::to('/dyeing/lab-dips') ?>">
        <input type="hidden" name="action" value="record_trial">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <!-- Section A: Target Request Selection & Spectro Measurement -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-flask"></i> Section A — Lab Request & Spectrophotometer Reading</h2>
              <p class="cz-doc-section-desc">Select parent request and enter spectrophotometer &Delta;E CMC (2:1) color deviation tolerance.</p>
            </div>
            <span class="badge badge-bw font-monospace">SPECTRO METRICS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Select Lab Dip Request <span class="text-danger">*</span></label>
              <select name="lab_dip_id" class="form-select form-select-sm" required>
                <?php foreach ($requests as $req): ?>
                  <option value="<?= $req['id'] ?>" <?= ($presetLabDipId === (int)$req['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($req['request_no']) ?> — <?= htmlspecialchars($req['shade_name']) ?> (<?= htmlspecialchars($req['customer_name']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Trial Version / Number <span class="text-danger">*</span></label>
              <input type="number" name="trial_number" class="form-control form-control-sm font-monospace" value="1" min="1" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Spectrophotometer &Delta;E (CMC 2:1) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="delta_e" class="form-control form-control-sm fw-bold font-monospace" value="0.42" required>
            </div>
          </div>
        </div>

        <!-- Section B: Dye Recipe Formulation -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-droplet-half"></i> Section B — Dye Recipe Formulation (% OWF)</h2>
              <p class="cz-doc-section-desc">Trichromatic primary dyestuff dosing on weight of fabric (% OWF).</p>
            </div>
            <span class="badge badge-bw font-monospace">RECIPE % OWF</span>
          </div>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Primary Dyestuff 1</label>
              <input type="text" name="dye_1" class="form-control form-control-sm" value="Reactive Blue ED-2B (Trichromatic Primary)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Dose % OWF</label>
              <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="dose_1" class="form-control font-monospace" value="1.450">
                <span class="input-group-text">% OWF</span>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label">Secondary Dyestuff 2</label>
              <input type="text" name="dye_2" class="form-control form-control-sm" value="Reactive Red 195 (Shade Modifier)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Dose % OWF</label>
              <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="dose_2" class="form-control font-monospace" value="0.180">
                <span class="input-group-text">% OWF</span>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label">Tertiary Dyestuff 3</label>
              <input type="text" name="dye_3" class="form-control form-control-sm" value="Reactive Yellow 145 (Tone Adjustment)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Dose % OWF</label>
              <div class="input-group input-group-sm">
                <input type="number" step="0.001" name="dose_3" class="form-control font-monospace" value="0.065">
                <span class="input-group-text">% OWF</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section C: Visual Match & Customer Approval -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-patch-check"></i> Section C — Visual Assessment & Customer Disposition</h2>
              <p class="cz-doc-section-desc">Color cabinet comparison result and customer QA approval record.</p>
            </div>
            <span class="badge badge-bw font-monospace">APPROVAL STATUS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Visual Comparison Result <span class="text-danger">*</span></label>
              <select name="result" class="form-select form-select-sm">
                <option value="match" selected>Match (&Delta;E &le; 0.8 — Acceptable Standard)</option>
                <option value="light">Slightly Light (Add Dyes)</option>
                <option value="dark">Slightly Dark (Reduce Dyes)</option>
                <option value="reddish">Reddish Tone Shift</option>
                <option value="greenish">Greenish Tone Shift</option>
                <option value="rejected">Rejected (Off-Shade)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Customer Feedback / Reference</label>
              <input type="text" name="customer_feedback" class="form-control form-control-sm" value="Approved by Buyer QA on 1st Trial (D65 Swatch signed)">
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-check2-circle me-1"></i> Save Trial & Approve Recipe
          </button>
        </div>
      </form>

    <?php else: ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED REGISTER VIEW (Lab Dip Requests & Approvals Register)           -->
      <!-- ========================================================================= -->

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
              <li class="breadcrumb-item active">Lab Dip & Shade Approvals</li>
            </ol>
          </nav>
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-eyedropper text-dark me-2"></i>Lab Dip Request, Trial & Shade Approval</h1>
          <p class="text-secondary small mb-0">Record spectrophotometer readings (ΔE, L*a*b*), trial recipes, light sources (D65/TL84/CWF), and customer approvals.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= Url::to('/dyeing/lab-dips?action=new') ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New Lab Dip Request
          </a>
          <a href="<?= Url::to('/dyeing/lab-dips?action=trial') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-flask me-1"></i> Record Trial Entry
          </a>
        </div>
      </div>

      <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> Lab Dip request registered successfully. Assigned to Dye Lab Spectro team.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['trial_added'])): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> Trial result & formulation saved. Status updated.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Active Lab Dip Requests Table -->
      <div class="cz-panel mb-4">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-inboxes text-dark me-2"></i>Lab Dip Requests & Approvals</h2>
          <span class="small text-secondary"><?= count($requests) ?> Requests</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Request No</th>
                <th>Customer / Buyer</th>
                <th>Shade Name & Code</th>
                <th>Fabric / Substrate</th>
                <th>Light Source</th>
                <th>Due Date</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requests as $r): ?>
                <tr>
                  <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($r['request_no']) ?></td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($r['customer_name']) ?></td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($r['shade_name']) ?></div>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($r['shade_code']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($r['fabric_type']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['light_source']) ?></span></td>
                  <td><?= date('d M Y', strtotime($r['due_date'])) ?></td>
                  <td>
                    <span class="badge <?= $r['status'] === 'approved' ? 'badge-bw-dark' : 'badge-bw' ?>">
                      <?= strtoupper(str_replace('_', ' ', $r['status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="<?= Url::to('/dyeing/lab-dips?action=trial&lab_dip_id=' . $r['id']) ?>" class="btn btn-sm btn-outline-dark">
                      <i class="bi bi-plus-circle me-1"></i> Add Trial
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Trials & Formulation History Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-dark me-2"></i>Lab Trials & Customer Approvals</h2>
          <span class="small text-secondary"><?= count($trials) ?> Recorded Trials</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Request No</th>
                <th>Customer</th>
                <th>Shade</th>
                <th class="text-center">Trial #</th>
                <th>Dye Formulation (% OWF)</th>
                <th class="text-center">&Delta;E Reading</th>
                <th>Visual Result</th>
                <th>Customer Feedback</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trials as $t): ?>
                <?php 
                  $dyes = json_decode($t['dyes_formula'], true) ?? [];
                ?>
                <tr>
                  <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($t['request_no']) ?></td>
                  <td><?= htmlspecialchars($t['customer_name']) ?></td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($t['shade_name']) ?></div>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($t['shade_code']) ?></span>
                  </td>
                  <td class="text-center fw-bold">#<?= $t['trial_number'] ?></td>
                  <td>
                    <?php foreach ($dyes as $d): ?>
                      <div class="font-monospace" style="font-size:0.75rem;">
                        &bull; <?= htmlspecialchars($d['dye']) ?>: <strong><?= $d['dose_pct'] ?>%</strong>
                      </div>
                    <?php endforeach; ?>
                  </td>
                  <td class="text-center">
                    <span class="badge <?= $t['delta_e'] <= 0.8 ? 'badge-bw-dark' : 'badge-bw' ?> font-monospace">
                      &Delta;E <?= number_format($t['delta_e'], 2) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= strtoupper($t['result']) ?></span>
                  </td>
                  <td class="small text-secondary"><?= htmlspecialchars($t['customer_feedback']) ?></td>
                  <td>
                    <?php if ($t['is_approved']): ?>
                      <span class="badge badge-bw-dark"><i class="bi bi-check-circle me-1"></i>APPROVED</span>
                    <?php else: ?>
                      <span class="badge badge-bw">PENDING</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>

  </div>
</main>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
