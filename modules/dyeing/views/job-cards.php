<?php
/**
 * Dyeing Order / Job Card
 * Customer order to technical dyeing production instruction
 * File: modules/dyeing/views/job-cards.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Job Cards';
$activeModule = 'job-cards';

// Handle POST to save new Job Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_job_card') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $jobCardNo = 'DJC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $customerId = (int)($_POST['customer_id'] ?? 1);
    $salesOrderRef = trim($_POST['sales_order_ref'] ?? '');
    $customerPo = trim($_POST['customer_po'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $requiredDate = $_POST['required_date'] ?? date('Y-m-d', strtotime('+5 days'));
    $jobType = $_POST['job_type'] ?? 'regular';
    $dyeingRoute = $_POST['dyeing_route'] ?? 'reactive';
    
    $fabricName = trim($_POST['fabric_name'] ?? '100% Combed Cotton Single Jersey 30s');
    $fabricComposition = trim($_POST['fabric_composition'] ?? '100% Combed Cotton');
    $greyLotNo = trim($_POST['grey_lot_no'] ?? '');
    $requiredQtyKg = (float)($_POST['required_qty_kg'] ?? 500);
    $rollCount = (int)($_POST['roll_count'] ?? 20);
    $targetGsm = (int)($_POST['target_gsm'] ?? 160);
    $targetWidth = (float)($_POST['target_width_inches'] ?? 48);
    
    $shadeCode = trim($_POST['shade_code'] ?? 'SH-NAV-01');
    $shadeName = trim($_POST['shade_name'] ?? 'Royal Navy Blue');
    $customerShadeRef = trim($_POST['customer_shade_ref'] ?? 'Pantone 19-4024 TCX');
    $fastnessSpec = trim($_POST['fastness_spec'] ?? 'Wash: 4-5, Rubbing: 4, Perspiration: 4');
    $specialFinish = trim($_POST['special_finish'] ?? 'Bio-Polishing Enzyme + Cationic Softener');
    $companyId = (int)(\Core\Security\SessionManager::getUser()['company_id'] ?? 1);

    Database::insert('dyeing_job_cards', [
        'company_id'         => $companyId,
        'job_card_no'        => $jobCardNo,
        'job_date'           => date('Y-m-d'),
        'customer_id'        => $customerId,
        'sales_order_ref'    => $salesOrderRef,
        'customer_po'        => $customerPo,
        'priority'           => $priority,
        'required_date'      => $requiredDate,
        'job_type'           => $jobType,
        'dyeing_route'       => $dyeingRoute,
        'fabric_name'        => $fabricName,
        'fabric_composition' => $fabricComposition,
        'grey_lot_no'        => $greyLotNo,
        'required_qty_kg'    => $requiredQtyKg,
        'roll_count'         => $rollCount,
        'target_gsm'         => $targetGsm,
        'target_width_inches'=> $targetWidth,
        'shade_code'         => $shadeCode,
        'shade_name'         => $shadeName,
        'customer_shade_ref' => $customerShadeRef,
        'fastness_spec'      => $fastnessSpec,
        'special_finish'     => $specialFinish,
        'status'             => 'approved'
    ]);

    header('Location: ' . Url::to('/dyeing/job-cards?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

// Safe queries: status = 'active' for customers
$customers = Database::fetchAll("SELECT * FROM customers WHERE status = 'active' ORDER BY name ASC");
$shades = Database::fetchAll("SELECT * FROM dyeing_shades WHERE is_active = 1 ORDER BY shade_name ASC");
$greyInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE qc_status = 'passed' ORDER BY id DESC");

$isNewForm = (isset($_GET['action']) && $_GET['action'] === 'new');

$jobCards = Database::fetchAll("
    SELECT jc.*, c.name as customer_name
    FROM dyeing_job_cards jc
    JOIN customers c ON jc.customer_id = c.id
    ORDER BY jc.id DESC
");

$totalKg = array_sum(array_column($jobCards, 'required_qty_kg'));
$urgentCount = count(array_filter($jobCards, fn($j) => in_array($j['priority'], ['urgent', 'high'])));
$inProdCount = count(array_filter($jobCards, fn($j) => $j['status'] === 'in_production'));
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if ($isNewForm): ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED FULL-PAGE FORM (Project-K Style - No Modal Popups)              -->
      <!-- ========================================================================= -->

      <!-- Document Sticky / Action Header -->
      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">Issue New Dyeing Job Card</h1>
              <span class="badge badge-bw">NEW DRAFT</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Transform customer PO and shade requirements into a technical batch-ready production order.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newJobCardForm" class="btn btn-dark btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save & Issue Job Card
          </button>
        </div>
      </div>

      <form id="newJobCardForm" method="POST" action="<?= Url::to('/dyeing/job-cards') ?>">
        <input type="hidden" name="action" value="save_job_card">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <!-- Section A: Commercial & Customer Order Details -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-building"></i> Section A — Commercial & Buyer Specifications</h2>
              <p class="cz-doc-section-desc">Map customer purchase order, delivery schedule, priority, and route parameters.</p>
            </div>
            <span class="badge badge-bw font-monospace">STEP 1 OF 4</span>
          </div>

          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Customer / Buyer <span class="text-danger">*</span></label>
              <select name="customer_id" class="form-select form-select-sm" required>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['city']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Customer PO Ref</label>
              <input type="text" name="customer_po" class="form-control form-control-sm" placeholder="e.g. PO-CP-9812">
            </div>
            <div class="col-md-4">
              <label class="form-label">Internal Sales Order Reference</label>
              <input type="text" name="sales_order_ref" class="form-control form-control-sm font-monospace" value="SO-2026-<?= rand(100000, 999999) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Priority Order <span class="text-danger">*</span></label>
              <select name="priority" class="form-select form-select-sm">
                <option value="normal">Normal (Standard Lead Time)</option>
                <option value="medium">Medium Priority</option>
                <option value="high">High Priority</option>
                <option value="urgent">Urgent (Express 24-48h)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Job Type <span class="text-danger">*</span></label>
              <select name="job_type" class="form-select form-select-sm">
                <option value="regular">Regular Production</option>
                <option value="sample">Sample Development</option>
                <option value="urgent">Urgent Shipment</option>
                <option value="reprocess">Reprocess / Re-dyeing</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Dyeing Route <span class="text-danger">*</span></label>
              <select name="dyeing_route" class="form-select form-select-sm" required>
                <option value="reactive">Reactive Dyeing (Cotton / Viscose / Modal)</option>
                <option value="disperse">Disperse Dyeing (Polyester / Microfibre)</option>
                <option value="vat">Vat Dyeing (High Fastness Workwear)</option>
                <option value="pigment">Pigment Dyeing</option>
                <option value="cationic">Cationic Dyeing (Acrylic)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Target Delivery Date <span class="text-danger">*</span></label>
              <input type="date" name="required_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+4 days')) ?>" required>
            </div>
          </div>
        </div>

        <!-- Section B: Fabric Details & Grey Lot Allocation -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-layers"></i> Section B — Fabric Specifications & Grey Lot Allocation</h2>
              <p class="cz-doc-section-desc">Select received grey rolls from warehouse and define finished GSM and width specs.</p>
            </div>
            <span class="badge badge-bw font-monospace">STEP 2 OF 4</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
              <input type="text" name="fabric_name" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s Ne" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fiber Composition <span class="text-danger">*</span></label>
              <input type="text" name="fabric_composition" class="form-control form-control-sm" value="100% Combed Cotton" required>
            </div>
            <div class="col-md-5">
              <label class="form-label">Grey Lot Allocation (QC Passed Inward Stock)</label>
              <select name="grey_lot_no" class="form-select form-select-sm">
                <option value="">-- Direct Allocation from Available QC Passed Grey Stock --</option>
                <?php foreach ($greyInwards as $gi): ?>
                  <option value="<?= htmlspecialchars($gi['grey_lot_no']) ?>">
                    <?= htmlspecialchars($gi['grey_lot_no']) ?> (<?= $gi['net_weight_kg'] ?> Kg, <?= htmlspecialchars($gi['fabric_code']) ?> · <?= htmlspecialchars($gi['supplier_name']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Required Net Quantity (Kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="required_qty_kg" class="form-control form-control-sm fw-bold" value="500.00" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Roll Count <span class="text-danger">*</span></label>
              <input type="number" name="roll_count" class="form-control form-control-sm" value="20" required>
            </div>
            <div class="col-md-1">
              <label class="form-label">GSM <span class="text-danger">*</span></label>
              <input type="number" name="target_gsm" class="form-control form-control-sm" value="160" required>
            </div>
            <div class="col-md-1">
              <label class="form-label">Width (") <span class="text-danger">*</span></label>
              <input type="number" step="0.1" name="target_width_inches" class="form-control form-control-sm" value="48.0" required>
            </div>
          </div>
        </div>

        <!-- Section C: Shade Target & Recipe Binding -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-palette"></i> Section C — Shade Specification & Lab Recipe Binding</h2>
              <p class="cz-doc-section-desc">Bind computerized master shade standard, Pantone reference, and light source parameters.</p>
            </div>
            <span class="badge badge-bw font-monospace">STEP 3 OF 4</span>
          </div>

          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Target Shade Standard <span class="text-danger">*</span></label>
              <select name="shade_code" id="jcShadeSelect" class="form-select form-select-sm" required>
                <?php foreach ($shades as $sh): ?>
                  <option value="<?= htmlspecialchars($sh['shade_code']) ?>" data-name="<?= htmlspecialchars($sh['shade_name']) ?>" data-pantone="<?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>">
                    <?= htmlspecialchars($sh['shade_name']) ?> (<?= htmlspecialchars($sh['shade_code']) ?> · <?= htmlspecialchars($sh['pantone_ref'] ?? '') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="shade_name" id="jcShadeName" value="Royal Navy Blue">
              <input type="hidden" name="customer_shade_ref" id="jcShadePantone" value="19-4024 TCX">
            </div>
            <div class="col-md-3">
              <label class="form-label">Light Source for Assessment</label>
              <input type="text" class="form-control form-control-sm" value="D65 (Daylight) / TL84 Dual Light" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Colour Fastness Requirements</label>
              <input type="text" name="fastness_spec" class="form-control form-control-sm" value="Wash: 4-5, Rubbing: 4, Perspiration: 4">
            </div>
          </div>
        </div>

        <!-- Section D: Special Finishing & Technical Requirements -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-gear-wide-connected"></i> Section D — Special Finishes & Technical Process Notes</h2>
              <p class="cz-doc-section-desc">Specify enzyme bio-polishing, stenter heat setting, compaction, and softening finishes.</p>
            </div>
            <span class="badge badge-bw font-monospace">STEP 4 OF 4</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Special Finishing Recipe / Treatments</label>
              <input type="text" name="special_finish" class="form-control form-control-sm" value="Bio-Polishing Acid Cellulase Enzyme + Cationic Micro-Silicone Softener">
            </div>
            <div class="col-md-6">
              <label class="form-label">Technical Instructions / Shrinkage Standards</label>
              <input type="text" class="form-control form-control-sm" value="Length shrinkage &le; 4.5%, Width shrinkage &le; 3.0%, Spirality &le; 3.5%">
            </div>
          </div>
        </div>

        <!-- Document Bottom Actions -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/job-cards') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-x-circle me-1"></i> Discard Changes
          </a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-check2-circle me-1"></i> Save & Issue Dyeing Job Card
          </button>
        </div>
      </form>

      <script>
      document.getElementById('jcShadeSelect')?.addEventListener('change', function(e) {
        const opt = this.options[this.selectedIndex];
        document.getElementById('jcShadeName').value = opt.getAttribute('data-name') || '';
        document.getElementById('jcShadePantone').value = opt.getAttribute('data-pantone') || '';
      });
      </script>

    <?php else: ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED REGISTER VIEW (List of Active Dyeing Job Cards)                 -->
      <!-- ========================================================================= -->

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
              <li class="breadcrumb-item active">Dyeing Job Cards</li>
            </ol>
          </nav>
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-card-checklist text-dark me-2"></i>Dyeing Order / Job Cards</h1>
          <p class="text-secondary small mb-0">Customer PO instructions, target specifications (GSM, width, fastness), route, and recipe binding.</p>
        </div>
        <div class="d-flex gap-2">
          <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Register
          </button>
          <button type="button" onclick="exportTableToCSV('jobCardsTable', 'dyeing_job_cards_register.csv')" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
          </button>
          <a href="<?= Url::to('/dyeing/job-cards?action=new') ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Create Job Card
          </a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> Dyeing Job Card created and approved. Ready for Batch Planning.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Summary KPI Row -->
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Job Cards</div>
            <div class="cz-stat-val"><?= count($jobCards) ?></div>
            <div class="text-secondary small mt-1">Active technical orders</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Total Weight Volume</div>
            <div class="cz-stat-val"><?= number_format($totalKg, 0) ?> <span class="fs-6 fw-normal text-secondary">Kg</span></div>
            <div class="text-secondary small mt-1">Dyeing order weight</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">In Production</div>
            <div class="cz-stat-val"><?= $inProdCount ?></div>
            <div class="text-secondary small mt-1">Running in vessels</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="cz-stat-card">
            <div class="cz-stat-lbl">Urgent / Priority</div>
            <div class="cz-stat-val text-dark"><?= $urgentCount ?></div>
            <div class="text-secondary small mt-1">Express delivery orders</div>
          </div>
        </div>
      </div>

      <!-- Job Cards List -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-folder-fill text-dark me-2"></i>Active Dyeing Job Card Register</h2>
          <span class="small text-secondary"><?= count($jobCards) ?> Job Cards</span>
        </div>
        <div class="table-responsive">
          <table id="jobCardsTable" class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Job Card No</th>
                <th>Customer / PO</th>
                <th>Fabric & Grey Lot</th>
                <th>Shade & Route</th>
                <th class="text-end">Required (Kg)</th>
                <th class="text-center">Rolls</th>
                <th>Spec (GSM/Width)</th>
                <th>Due Date</th>
                <th>Priority</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jobCards as $jc): ?>
                <tr>
                  <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($jc['job_card_no']) ?></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($jc['customer_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">PO: <?= htmlspecialchars($jc['customer_po'] ?: 'Direct Work Order') ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($jc['fabric_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Lot: <?= htmlspecialchars($jc['grey_lot_no'] ?: 'Stock Allocation Pending') ?></div>
                  </td>
                  <td>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($jc['shade_code']) ?></span>
                    <div class="small fw-semibold"><?= htmlspecialchars($jc['shade_name']) ?></div>
                    <span class="badge bg-light text-secondary border mt-1" style="font-size:0.65rem;"><?= strtoupper($jc['dyeing_route']) ?></span>
                  </td>
                  <td class="text-end fw-bold"><?= number_format($jc['required_qty_kg'], 2) ?></td>
                  <td class="text-center fw-semibold"><?= $jc['roll_count'] ?></td>
                  <td><?= $jc['target_gsm'] ?> GSM / <?= $jc['target_width_inches'] ?>"</td>
                  <td><?= date('d M Y', strtotime($jc['required_date'])) ?></td>
                  <td>
                    <span class="badge <?= $jc['priority'] === 'urgent' ? 'badge-bw-dark' : 'badge-bw' ?> text-uppercase" style="font-size:0.65rem;">
                      <?= htmlspecialchars($jc['priority']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= $jc['status'] === 'in_production' ? 'badge-bw-dark' : 'badge-bw' ?>">
                      <?= strtoupper(str_replace('_', ' ', $jc['status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?= Url::to('/dyeing/batch-planning?job_card_id=' . $jc['id']) ?>" class="btn btn-outline-dark" title="Plan into Batch">
                        <i class="bi bi-calendar-range"></i>
                      </a>
                      <a href="<?= Url::to('/print/sales-order?id=' . $jc['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print Job Card">
                        <i class="bi bi-printer"></i>
                      </a>
                    </div>
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
