<?php
/**
 * Dedicated Standalone Form: Issue Subcontract Job-Work Challan
 * File: modules/dyeing/views/job-work/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Issue Job-Work Challan';
$activeModule = 'job-work';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$errors = [];

// Handle POST to save outward challan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_challan') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

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

    if (empty($partnerName)) $errors[] = 'Please specify the Subcontract Job-Worker / Dyeing Mill.';
    if ($dispatchedWeight <= 0) $errors[] = 'Dispatched weight (Kg) must be greater than 0.';

    if (!$errors) {
        $challanNo = 'JWC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);

        Database::beginTransaction();
        try {
            Database::insert('dyeing_job_work_challans', [
                'company_id'           => $companyId,
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

            Database::commit();
            header('Location: ' . Url::to('/dyeing/job-work?created=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Failed to create challan: ' . $e->getMessage();
        }
    }
}

$jobWorkers = Database::fetchAll("SELECT * FROM job_workers WHERE status = 'active' ORDER BY name ASC");

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Issue Subcontract Job-Work Delivery Challan</h1>
            <span class="badge badge-bw">GST ANNEXURE-II / SECTION 143</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Dispatches grey/semi-processed fabric to external dyeing, bio-wash or compacting mills.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/job-work') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newChallanForm" class="btn btn-dark btn-sm">
          <i class="bi bi-send-check me-1"></i> Dispatch Fabric & Print Challan
        </button>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form id="newChallanForm" method="POST" action="<?= Url::to('/dyeing/job-work/create') ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="save_challan">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-truck me-2 text-primary"></i>Subcontractor & Consignment Details</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Processor / Subcontract Mill *</label>
                  <select name="partner_name" class="form-select form-select-sm" required>
                    <option value="">-- Choose Job-Worker --</option>
                    <?php if (!empty($jobWorkers)): ?>
                      <?php foreach ($jobWorkers as $jw): ?>
                        <option value="<?= htmlspecialchars($jw['name']) ?>">
                          <?= htmlspecialchars($jw['name']) ?> (<?= htmlspecialchars($jw['city'] ?? 'Tirupur') ?>)
                        </option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="Sri Murugan Tex Processors">Sri Murugan Tex Processors</option>
                      <option value="Lakshmi Soft-Flow Dyeing">Lakshmi Soft-Flow Dyeing</option>
                      <option value="Perundurai Eco Processors">Perundurai Eco Processors</option>
                    <?php endif; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Process Requested *</label>
                  <select name="process_type" class="form-select form-select-sm">
                    <option value="Soft-Flow Dyeing">Soft-Flow Dyeing</option>
                    <option value="Bio-Polishing & Wash">Bio-Polishing & Wash</option>
                    <option value="Open-Width Compacting">Open-Width Compacting</option>
                    <option value="Mercerization">Mercerization</option>
                    <option value="Yarn Package Dyeing">Yarn Package Dyeing</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Fabric Description *</label>
                  <input type="text" name="fabric_description" class="form-control form-control-sm" required value="100% Combed Cotton Single Jersey 30s">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Target Shade / Color *</label>
                  <input type="text" name="required_shade" class="form-control form-control-sm" required value="Deep Navy (PANTONE 19-3921 TCX)">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Grey Lot / Batch No</label>
                  <input type="text" name="grey_lot_no" class="form-control form-control-sm font-monospace" value="LOT-2026-<?= rand(100, 999) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Dispatched Rolls</label>
                  <input type="number" min="1" name="dispatched_rolls" class="form-control form-control-sm font-monospace fw-bold" value="24">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Dispatched Weight (Kg) *</label>
                  <input type="number" step="0.1" min="1" name="dispatched_weight_kg" class="form-control form-control-sm font-monospace fw-bold" required value="620.0">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock me-2 text-primary"></i>Commercial & Logistics</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Expected Return Date</label>
                <input type="date" name="expected_return_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+4 days')) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Agreed Process Rate (₹ / Kg)</label>
                <input type="number" step="0.5" min="0" name="process_rate_per_kg" class="form-control form-control-sm font-monospace fw-bold" value="55.00">
              </div>
              <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Vehicle / Lorry Number</label>
                <input type="text" name="vehicle_no" class="form-control form-control-sm font-monospace" placeholder="TN-38-BZ-4412" value="TN-38-BZ-4412">
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
