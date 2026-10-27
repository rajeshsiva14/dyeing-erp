<?php
/**
 * External Dyeing Job-Work Challan & Returns
 * Subcontract outward fabric to external dyeing mills, Mercerizers and Compactors
 * File: modules/dyeing/views/job-work.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Job-Work Challans';
$activeModule = 'job-work';

// Handle POST to issue outward challan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_challan') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $challanNo = 'JWC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $partnerName = trim($_POST['partner_name'] ?? '');
    $fabricDesc = trim($_POST['fabric_description'] ?? '');
    $greyLotNo = trim($_POST['grey_lot_no'] ?? '');
    $dispatchedRolls = (int)($_POST['dispatched_rolls'] ?? 1);
    $dispatchedWeight = (float)($_POST['dispatched_weight_kg'] ?? 0);
    $processType = trim($_POST['process_type'] ?? 'Dyeing');
    $requiredShade = trim($_POST['required_shade'] ?? '');
    $expectedReturn = $_POST['expected_return_date'] ?? date('Y-m-d', strtotime('+4 days'));
    $ratePerKg = (float)($_POST['process_rate_per_kg'] ?? 0);
    $vehicleNo = trim($_POST['vehicle_no'] ?? '');

    Database::insert('dyeing_job_work_challans', [
        'company_id'           => 1,
        'challan_no'           => $challanNo,
        'challan_date'         => date('Y-m-d'),
        'partner_name'         => $partnerName,
        'fabric_description'  => $fabricDesc,
        'grey_lot_no'          => $greyLotNo,
        'dispatched_rolls'     => $dispatchedRolls,
        'dispatched_weight_kg' => $dispatchedWeight,
        'process_type'         => $processType,
        'required_shade'       => $requiredShade,
        'expected_return_date' => $expectedReturn,
        'process_rate_per_kg'  => $ratePerKg,
        'vehicle_no'           => $vehicleNo,
        'status'               => 'dispatched'
    ]);

    header('Location: ' . Url::to('/dyeing/job-work?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$isNewForm = (isset($_GET['action']) && $_GET['action'] === 'new');

$challans = Database::fetchAll("SELECT * FROM dyeing_job_work_challans ORDER BY id DESC");

// Safe query: status = 'active'
$jobWorkers = Database::fetchAll("SELECT * FROM job_workers WHERE status = 'active' ORDER BY name ASC");
if (empty($jobWorkers)) {
    $jobWorkers = [
        ['id' => 1, 'name' => 'Senthil Textile Processors, Perundurai SIPCOT', 'gstin' => '33AABCS1234F1Z1', 'code' => 'JW-001'],
        ['id' => 2, 'name' => 'Kavin Wet Processors & Mercerizers, Erode', 'gstin' => '33AABCK5678M1Z2', 'code' => 'JW-002']
    ];
}

$totalDispatchedKg = array_sum(array_column($challans, 'dispatched_weight_kg'));
$totalRolls = array_sum(array_column($challans, 'dispatched_rolls'));
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if ($isNewForm): ?>
      <!-- ========================================================================= -->
      <!-- FULL-PAGE FORM: NEW OUTWARD CHALLAN (No Modals)                 -->
      <!-- ========================================================================= -->

      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">Issue Outward Job-Work Challan</h1>
              <span class="badge badge-bw">NEW CHALLAN</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Generate statutory delivery challan for external processing (Mercerizing, Soft Flow Dyeing, Compacting).</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newChallanForm" class="btn btn-dark btn-sm">
            <i class="bi bi-truck me-1"></i> Issue Challan & Dispatch
          </button>
        </div>
      </div>

      <form id="newChallanForm" method="POST" action="<?= Url::to('/dyeing/job-work') ?>">
        <input type="hidden" name="action" value="save_challan">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <!-- Section A: Subcontractor & Logistics -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-building"></i> Section A — External Processor & Logistics</h2>
              <p class="cz-doc-section-desc">Select approved job-work partner, return deadline, and transport vehicle.</p>
            </div>
            <span class="badge badge-bw font-monospace">PARTNER SPECS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Subcontract Processor / Job-Worker <span class="text-danger">*</span></label>
              <select name="partner_name" class="form-select form-select-sm" required>
                <?php foreach ($jobWorkers as $jw): ?>
                  <option value="<?= htmlspecialchars($jw['name']) ?>"><?= htmlspecialchars($jw['name']) ?> (<?= htmlspecialchars($jw['gstin'] ?? 'TN-GST') ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Expected Return Date <span class="text-danger">*</span></label>
              <input type="date" name="expected_return_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+4 days')) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Vehicle / Transport Reg No</label>
              <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace" value="TN-39-BT-4890">
            </div>
          </div>
        </div>

        <!-- Section B: Material & Dispatched Weight -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-layers"></i> Section B — Dispatched Material & Quantities</h2>
              <p class="cz-doc-section-desc">Fabric description, internal grey lot reference, roll count, and gross weight.</p>
            </div>
            <span class="badge badge-bw font-monospace">FABRIC DETAILS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
              <input type="text" name="fabric_description" class="form-control form-control-sm" value="100% Cotton 2/1 Twill Bottom Weight 240 GSM" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Grey Lot Reference <span class="text-danger">*</span></label>
              <input type="text" name="grey_lot_no" class="form-control form-control-sm font-monospace" value="LOT-GRY-26-<?= rand(1000,9999) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Dispatched Rolls <span class="text-danger">*</span></label>
              <input type="number" name="dispatched_rolls" class="form-control form-control-sm" value="18" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Dispatched Net Weight (Kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="dispatched_weight_kg" class="form-control form-control-sm fw-bold" value="471.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Target Shade / Color <span class="text-danger">*</span></label>
              <input type="text" name="required_shade" class="form-control form-control-sm" value="Military Olive (SH-OLV-03)" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Subcontract Process Route <span class="text-danger">*</span></label>
              <select name="process_type" class="form-select form-select-sm" required>
                <option value="Vat Continuous Dyeing">Vat Continuous Dyeing</option>
                <option value="Yarn Mercerizing">Yarn Mercerizing</option>
                <option value="Fabric Bio-Enzyme Wash">Fabric Bio-Enzyme Wash</option>
                <option value="Felt Calender Compacting">Felt Calender Compacting</option>
                <option value="Stenter Finishing">Stenter Heat Setting & Finishing</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Agreed Job-Work Rate / Kg (₹) <span class="text-danger">*</span></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" name="process_rate_per_kg" class="form-control" value="38.00" required>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-truck me-1"></i> Issue Outward Delivery Challan
          </button>
        </div>
      </form>

    <?php else: ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED REGISTER VIEW (Job-Work Challans Register)                       -->
      <!-- ========================================================================= -->

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
              <li class="breadcrumb-item active">Job-Work Challans</li>
            </ol>
          </nav>
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-arrow-left-right text-dark me-2"></i>External Dyeing Job-Work Challan & Returns</h1>
          <p class="text-secondary small mb-0">Track outward fabric to external dyeing processors, monitor expected return dates, weight loss %, and service bills.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= Url::to('/dyeing/job-work?action=new') ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New Outward Challan
          </a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> Outward Job-Work Challan issued and material dispatched for external processing.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Summary KPI Row -->
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Outward Challans</div>
            <div class="cz-stat-val"><?= count($challans) ?></div>
            <div class="text-secondary small mt-1">Dispatched work orders</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Dispatched Quantity</div>
            <div class="cz-stat-val"><?= number_format($totalDispatchedKg, 2) ?> <span class="fs-6 fw-normal text-secondary">Kg</span></div>
            <div class="text-secondary small mt-1"><?= $totalRolls ?> fabric rolls</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Approved Processors</div>
            <div class="cz-stat-val"><?= count($jobWorkers) ?></div>
            <div class="text-secondary small mt-1">Subcontract wet processing units</div>
          </div>
        </div>
      </div>

      <!-- Challans Register Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-truck text-dark me-2"></i>Subcontract Dyeing Challan Register</h2>
          <span class="small text-secondary"><?= count($challans) ?> Challans</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Challan No</th>
                <th>Processor / Partner</th>
                <th>Fabric & Grey Lot</th>
                <th>Shade & Process</th>
                <th class="text-center">Rolls</th>
                <th class="text-end">Dispatched (Kg)</th>
                <th class="text-end">Received (Kg)</th>
                <th class="text-end">Rate / Kg (₹)</th>
                <th>Return Due</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($challans as $ch): ?>
                <tr>
                  <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($ch['challan_no']) ?></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($ch['partner_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Vehicle: <?= htmlspecialchars($ch['vehicle_no'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($ch['fabric_description']) ?></div>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($ch['grey_lot_no']) ?></span>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($ch['required_shade']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;"><?= htmlspecialchars($ch['process_type']) ?></div>
                  </td>
                  <td class="text-center fw-bold"><?= $ch['dispatched_rolls'] ?></td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($ch['dispatched_weight_kg'], 2) ?></td>
                  <td class="text-end font-monospace"><?= number_format($ch['received_weight_kg'] ?? 0, 2) ?></td>
                  <td class="text-end font-monospace">₹<?= number_format($ch['process_rate_per_kg'], 2) ?></td>
                  <td><?= date('d M Y', strtotime($ch['expected_return_date'])) ?></td>
                  <td>
                    <span class="badge badge-bw-dark text-uppercase"><?= htmlspecialchars($ch['status']) ?></span>
                  </td>
                  <td class="text-end">
                    <a href="<?= Url::to('/print/sales-order?id=1') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-printer me-1"></i> Print Challan
                    </a>
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
