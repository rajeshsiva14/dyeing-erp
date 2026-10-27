<?php
/**
 * Grey Fabric Inspection & Quality Disposition
 * Physical inspection of grey rolls before batch allocation
 * File: modules/dyeing/views/grey-inspection.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Grey Fabric Inspection';
$activeModule = 'grey-inspection';

// Handle POST to save inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $inspectionNo = 'GQC-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $inwardId = (int)($_POST['inward_id'] ?? 1);
    $inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ?", [$inwardId]);
    $inwardNo = $inward['inward_no'] ?? 'GIN-2026-0001';
    
    $measuredGsm = (int)($_POST['measured_gsm'] ?? 160);
    $measuredWidth = (float)($_POST['measured_width_inches'] ?? 48.0);
    $holeTear = (int)($_POST['hole_tear_count'] ?? 0);
    $oilStain = (int)($_POST['oil_stain_count'] ?? 0);
    $knittingDefects = (int)($_POST['knitting_defect_count'] ?? 0);
    $contamination = $_POST['contamination_found'] ?? 'no';
    $shadeVariation = trim($_POST['shade_variation_obs'] ?? 'Uniform');
    $defectGrade = $_POST['defect_grade'] ?? 'Grade A';
    
    $inspectedRolls = (int)($_POST['inspected_rolls'] ?? 20);
    $acceptedRolls = (int)($_POST['accepted_rolls'] ?? 20);
    $rejectedRolls = (int)($_POST['rejected_rolls'] ?? 0);
    $holdRolls = (int)($_POST['hold_rolls'] ?? 0);
    
    $acceptedWeight = (float)($_POST['accepted_weight_kg'] ?? 500);
    $rejectedWeight = (float)($_POST['rejected_weight_kg'] ?? 0);
    $disposition = $_POST['qc_disposition'] ?? 'accepted';
    $comments = trim($_POST['inspector_comments'] ?? '');
    $companyId = (int)(\Core\Security\SessionManager::getUser()['company_id'] ?? 1);

    Database::beginTransaction();
    try {
        Database::insert('grey_fabric_inspections', [
            'company_id'            => $companyId,
            'inspection_no'         => $inspectionNo,
            'inward_id'             => $inwardId,
            'inward_no'             => $inwardNo,
            'inspection_date'       => date('Y-m-d'),
            'inspector_name'        => trim($_POST['inspector_name'] ?? 'K. Arumugam (QC Lead)'),
            'fabric_code'           => $inward['fabric_code'] ?? 'FAB-SJ-160',
            'grey_lot_no'           => $inward['grey_lot_no'] ?? 'LOT-GRY-26-001',
            'measured_gsm'          => $measuredGsm,
            'measured_width_inches' => $measuredWidth,
            'hole_tear_count'       => $holeTear,
            'oil_stain_count'       => $oilStain,
            'knitting_defect_count' => $knittingDefects,
            'contamination_found'   => $contamination,
            'shade_variation_obs'   => $shadeVariation,
            'defect_grade'          => $defectGrade,
            'inspected_rolls'       => $inspectedRolls,
            'accepted_rolls'        => $acceptedRolls,
            'rejected_rolls'        => $rejectedRolls,
            'hold_rolls'            => $holdRolls,
            'accepted_weight_kg'    => $acceptedWeight,
            'rejected_weight_kg'    => $rejectedWeight,
            'qc_disposition'        => $disposition,
            'inspector_comments'    => $comments
        ]);

        // Update inward QC status
        $newInwardStatus = ($disposition === 'accepted') ? 'passed' : (($disposition === 'rejected') ? 'rejected' : 'partially_passed');
        Database::getConnection()->prepare("UPDATE grey_fabric_inwards SET qc_status = ? WHERE id = ?")->execute([$newInwardStatus, $inwardId]);
        Database::commit();
    } catch (\Throwable $e) {
        Database::rollBack();
        throw $e;
    }

    header('Location: ' . Url::to('/dyeing/grey-inspection?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$inspections = Database::fetchAll("SELECT * FROM grey_fabric_inspections ORDER BY id DESC");
$pendingInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE qc_status = 'inspection_pending' ORDER BY id DESC");
$allInwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards ORDER BY id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Grey Fabric Inspection</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-search text-primary me-2"></i>Grey Fabric Inspection & Quality Disposition</h1>
        <p class="text-secondary small mb-0">Check physical loop structure, GSM, holes, oil contamination, and decide usable vs hold vs rejected.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newInspectionModal">
          <i class="bi bi-shield-plus me-1"></i> New Grey Inspection
        </button>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Grey Inspection recorded and inward status updated. Usable stock released for Batch Planning.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Pending Inwards Alert Banner -->
    <?php if (!empty($pendingInwards)): ?>
      <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 p-3 border-0 shadow-sm">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-circle-fill fs-5 text-warning"></i>
          <div>
            <strong><?= count($pendingInwards) ?> Grey Inward(s) awaiting Inspection:</strong>
            <span class="small text-muted ms-2"><?php foreach($pendingInwards as $pi) echo htmlspecialchars($pi['inward_no']) . ' (' . $pi['fabric_code'] . ') '; ?></span>
          </div>
        </div>
        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#newInspectionModal">Inspect Now</button>
      </div>
    <?php endif; ?>

    <!-- Inspections Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Grey Fabric QC Disposition Register</h2>
        <span class="small text-secondary"><?= count($inspections) ?> Completed Inspections</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Inspection No</th>
              <th>Inward Ref</th>
              <th>Fabric & Lot</th>
              <th>Measured GSM/Width</th>
              <th>Defect Counts</th>
              <th class="text-center">Grade</th>
              <th class="text-end">Accepted (Kg)</th>
              <th class="text-end">Rejected (Kg)</th>
              <th>Disposition</th>
              <th>Inspector</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inspections as $insp): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($insp['inspection_no']) ?></td>
                <td><code><?= htmlspecialchars($insp['inward_no']) ?></code></td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($insp['fabric_code']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Lot: <?= htmlspecialchars($insp['grey_lot_no']) ?></div>
                </td>
                <td>
                  <div><?= $insp['measured_gsm'] ?> GSM</div>
                  <div class="text-secondary" style="font-size:0.75rem;"><?= $insp['measured_width_inches'] ?>" Width</div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">Holes: <?= $insp['hole_tear_count'] ?></span>
                  <span class="badge bg-light text-dark border">Stains: <?= $insp['oil_stain_count'] ?></span>
                  <span class="badge bg-light text-dark border">Knits: <?= $insp['knitting_defect_count'] ?></span>
                </td>
                <td class="text-center">
                  <span class="badge <?= $insp['defect_grade'] === 'Grade A' ? 'bg-success' : ($insp['defect_grade'] === 'Grade B' ? 'bg-info' : 'bg-danger') ?>">
                    <?= htmlspecialchars($insp['defect_grade']) ?>
                  </span>
                </td>
                <td class="text-end fw-bold text-success"><?= number_format($insp['accepted_weight_kg'], 2) ?></td>
                <td class="text-end text-danger"><?= number_format($insp['rejected_weight_kg'], 2) ?></td>
                <td>
                  <span class="badge <?= $insp['qc_disposition'] === 'accepted' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= strtoupper(htmlspecialchars($insp['qc_disposition'])) ?>
                  </span>
                </td>
                <td class="small"><?= htmlspecialchars($insp['inspector_name']) ?></td>
                <td class="text-end">
                  <a href="<?= Url::to('/dyeing/batch-planning?lot=' . urlencode($insp['grey_lot_no'])) ?>" class="btn btn-sm btn-outline-dark" title="Plan for Batch">
                    <i class="bi bi-calendar-plus me-1"></i> Plan Batch
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

<!-- New Inspection Modal -->
<div class="modal fade" id="newInspectionModal" tabindex="-1" aria-labelledby="newInspectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_inspection">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newInspectionModalLabel"><i class="bi bi-search text-primary me-2"></i>Record Grey Fabric Inspection</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Inward Document *</label>
              <select name="inward_id" id="inspInwardSelect" class="form-select form-select-sm" required>
                <?php foreach ($allInwards as $inw): ?>
                  <option value="<?= $inw['id'] ?>" data-net="<?= $inw['net_weight_kg'] ?>" data-rolls="<?= $inw['received_rolls'] ?>" data-gsm="<?= $inw['gsm'] ?>" data-width="<?= $inw['width_inches'] ?>">
                    <?= htmlspecialchars($inw['inward_no']) ?> — <?= htmlspecialchars($inw['fabric_description']) ?> (<?= $inw['net_weight_kg'] ?> Kg, <?= $inw['received_rolls'] ?> Rolls)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Inspector Name *</label>
              <input type="text" name="inspector_name" class="form-control form-control-sm" value="K. Arumugam (Senior QC Lead)" required>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Physical Measurements & Defect Counts</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">Measured GSM *</label>
              <input type="number" name="measured_gsm" id="inspGsm" class="form-control form-control-sm" value="162" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Measured Width (Inches) *</label>
              <input type="number" step="0.1" name="measured_width_inches" id="inspWidth" class="form-control form-control-sm" value="48.2" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Hole / Tear Count</label>
              <input type="number" name="hole_tear_count" class="form-control form-control-sm" value="0" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Oil / Stain Count</label>
              <input type="number" name="oil_stain_count" class="form-control form-control-sm" value="0" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Knitting Defect Count</label>
              <input type="number" name="knitting_defect_count" class="form-control form-control-sm" value="1" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Contamination Found</label>
              <select name="contamination_found" class="form-select form-select-sm">
                <option value="no">None (Pass)</option>
                <option value="minor">Minor Foreign Fibre</option>
                <option value="severe">Severe Contamination</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Defect Grade *</label>
              <select name="defect_grade" class="form-select form-select-sm" required>
                <option value="Grade A">Grade A (Premium)</option>
                <option value="Grade B">Grade B (Commercial)</option>
                <option value="Grade C">Grade C (Second Quality)</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Inspection Disposition & Quantities</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">Inspected Rolls *</label>
              <input type="number" name="inspected_rolls" id="inspRolls" class="form-control form-control-sm" value="20" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-success">Accepted Rolls *</label>
              <input type="number" name="accepted_rolls" class="form-control form-control-sm" value="20" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-success">Accepted Weight (Kg) *</label>
              <input type="number" step="0.01" name="accepted_weight_kg" id="inspAcceptedKg" class="form-control form-control-sm fw-bold" value="500.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-danger">Rejected Weight (Kg)</label>
              <input type="number" step="0.01" name="rejected_weight_kg" class="form-control form-control-sm" value="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Final QC Disposition *</label>
              <select name="qc_disposition" class="form-select form-select-sm" required>
                <option value="accepted">Accepted (Release for Dyeing)</option>
                <option value="concession">Accepted with Concession</option>
                <option value="hold">Hold for Manager Review</option>
                <option value="rejected">Rejected (Return to Supplier)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Inspector Comments / Observations</label>
              <input type="text" name="inspector_comments" class="form-control form-control-sm" value="Good loop density, skewing under 2.5%, no fly contamination.">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-shield-check me-1"></i> Submit QC Disposition</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
