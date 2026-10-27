<?php
/**
 * Reprocess / Re-dye Request & Root Cause Corrective Action
 * Controlled reprocessing workflow for off-shade or patchy dyeing batches
 * File: modules/dyeing/views/reprocess-requests.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Reprocess / Re-dye Requests';
$activeModule = 'reprocess-requests';

// Handle New Reprocess Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_reprocess') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $reqNo = 'RPR-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $origBatchId = (int)($_POST['original_batch_id'] ?? 1);
    $origBatch = Database::fetch("SELECT * FROM dye_batches WHERE id = ?", [$origBatchId]);
    $origBatchNo = $origBatch['batch_no'] ?? 'BATCH-2026-0001';
    
    $defectCat = $_POST['defect_category'] ?? 'off_shade';
    $failedCrit = trim($_POST['failed_criteria'] ?? 'Delta E > 1.2 under D65');
    $affectedRolls = (int)($_POST['affected_rolls'] ?? 20);
    $affectedKg = (float)($_POST['affected_weight_kg'] ?? 500);
    $rootCause = trim($_POST['root_cause'] ?? 'Alkali fixation dosing too fast causing uneven dyestuff migration');
    $correctiveAction = trim($_POST['corrective_action'] ?? 'Level stripping with hydrosulphite and re-shade top-up 10%');
    $revisedRecipe = trim($_POST['revised_recipe_code'] ?? 'RCP-NAV-01-R1');
    $extraCost = (float)($_POST['additional_chem_cost'] ?? 3500.00);
    $targetMachine = (int)($_POST['target_machine_id'] ?? 1);

    Database::insert('dyeing_reprocess_requests', [
        'company_id'           => 1,
        'request_no'           => $reqNo,
        'original_batch_id'    => $origBatchId,
        'original_batch_no'    => $origBatchNo,
        'defect_category'      => $defectCat,
        'failed_criteria'      => $failedCrit,
        'affected_rolls'       => $affectedRolls,
        'affected_weight_kg'   => $affectedKg,
        'root_cause'           => $rootCause,
        'corrective_action'    => $correctiveAction,
        'revised_recipe_code'  => $revisedRecipe,
        'additional_chem_cost' => $extraCost,
        'reprocess_attempt'    => 1,
        'target_machine_id'    => $targetMachine,
        'status'               => 'approved',
        'approved_by'          => 'S. Natarajan (Dye House Mgr)'
    ]);

    // Update original batch status to reprocess
    Database::getConnection()->prepare("UPDATE dye_batches SET status = 'reprocess', reprocess_count = reprocess_count + 1 WHERE id = ?")->execute([$origBatchId]);

    header('Location: ' . Url::to('/dyeing/reprocess-requests?success=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$reprocessRequests = Database::fetchAll("
    SELECT r.*, m.name as machine_name, m.code as machine_code
    FROM dyeing_reprocess_requests r
    JOIN machines m ON r.target_machine_id = m.id
    ORDER BY r.id DESC
");

$batches = Database::fetchAll("SELECT b.*, r.shade_name FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id ORDER BY b.id DESC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY code ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Reprocess Requests</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-arrow-repeat text-primary me-2"></i>Reprocess / Re-dye Request & Corrective Action</h1>
        <p class="text-secondary small mb-0">Root cause analysis (RCA), corrective chemical recipes, additional cost tracking, and re-dyeing authorization.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newReprocessModal">
          <i class="bi bi-plus-lg me-1"></i> New Reprocess Request
        </button>
      </div>
    </div>

    <!-- Reprocess Register Table -->
    <div class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Controlled Re-dyeing & Reprocess Authorizations</h2>
        <span class="small text-secondary"><?= count($reprocessRequests) ?> Reprocess Requests</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Request No</th>
              <th>Original Batch</th>
              <th>Defect Category</th>
              <th>Failed Criteria</th>
              <th class="text-end">Affected (Kg)</th>
              <th>Root Cause Summary</th>
              <th>Revised Recipe</th>
              <th class="text-end">Extra Chem Cost (₹)</th>
              <th>Status</th>
              <th>Approved By</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reprocessRequests as $rpr): ?>
              <tr>
                <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($rpr['request_no']) ?></td>
                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($rpr['original_batch_no']) ?></span></td>
                <td><span class="badge bg-danger-subtle text-danger text-uppercase"><?= str_replace('_', ' ', $rpr['defect_category']) ?></span></td>
                <td><?= htmlspecialchars($rpr['failed_criteria']) ?></td>
                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($rpr['affected_weight_kg'], 2) ?></td>
                <td>
                  <div class="small fw-semibold"><?= htmlspecialchars($rpr['root_cause']) ?></div>
                  <div class="text-secondary" style="font-size:0.75rem;">Action: <?= htmlspecialchars($rpr['corrective_action']) ?></div>
                </td>
                <td><code class="font-monospace text-primary"><?= htmlspecialchars($rpr['revised_recipe_code']) ?></code></td>
                <td class="text-end font-monospace fw-bold text-danger">₹<?= number_format($rpr['additional_chem_cost'], 2) ?></td>
                <td><span class="badge bg-success text-uppercase"><?= htmlspecialchars($rpr['status']) ?></span></td>
                <td class="small"><?= htmlspecialchars($rpr['approved_by']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- New Reprocess Modal -->
<div class="modal fade" id="newReprocessModal" tabindex="-1" aria-labelledby="newReprocessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="save_reprocess">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newReprocessModalLabel"><i class="bi bi-arrow-repeat text-danger me-2"></i>Issue Reprocess / Re-dye Authorization</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Select Original Failed Batch *</label>
              <select name="original_batch_id" id="rprBatchSelect" class="form-select form-select-sm" required>
                <?php foreach ($batches as $b): ?>
                  <option value="<?= $b['id'] ?>" data-kg="<?= $b['actual_loaded_kg'] ?>">
                    <?= htmlspecialchars($b['batch_no']) ?> — <?= htmlspecialchars($b['shade_name']) ?> (<?= number_format($b['actual_loaded_kg'], 0) ?> Kg)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Defect Category *</label>
              <select name="defect_category" class="form-select form-select-sm" required>
                <option value="off_shade">Off-Shade (Delta E &gt; 1.2 under D65)</option>
                <option value="patchy">Patchy / Uneven Color Strike</option>
                <option value="shade_variation">Side-to-Side / End-to-End Listing</option>
                <option value="stains">Chemical Spot / Oil Stain</option>
                <option value="poor_fastness">Poor Wash / Rubbing Fastness</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Failed Quality Criteria *</label>
              <input type="text" name="failed_criteria" class="form-control form-control-sm" value="Delta E = 1.45 under D65 (Too Reddish tone)" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-danger">Affected Kg *</label>
              <input type="number" step="0.01" name="affected_weight_kg" id="rprAffectedKg" class="form-control form-control-sm fw-bold" value="500.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Affected Rolls</label>
              <input type="number" name="affected_rolls" class="form-control form-control-sm" value="20" required>
            </div>
          </div>

          <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Root Cause Analysis & Corrective Formulation</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-12">
              <label class="form-label fw-bold">Root Cause Investigation *</label>
              <textarea name="root_cause" class="form-control form-control-sm" rows="2" required>Soda Ash alkali was dosed too quickly (over 10 mins instead of progressive 3-step 30 mins hold), causing premature localized fixation.</textarea>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-bold">Corrective Action Plan *</label>
              <textarea name="corrective_action" class="form-control form-control-sm" rows="2" required>Level stripping with 1.5 g/L Hydrosulphite at 80°C, hot wash neutralise, followed by top-up shading with 0.08% Reactive Yellow 145.</textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Revised Recipe Code *</label>
              <input type="text" name="revised_recipe_code" class="form-control form-control-sm font-monospace text-primary fw-bold" value="RCP-NAV-01-R1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Target Reprocess Machine *</label>
              <select name="target_machine_id" class="form-select form-select-sm" required>
                <?php foreach ($machines as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-danger">Additional Chemical Cost (₹) *</label>
              <input type="number" step="0.01" name="additional_chem_cost" class="form-control form-control-sm fw-bold" value="3850.00" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-arrow-repeat me-1"></i> Authorize Reprocess Batch</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('rprBatchSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const kg = opt.getAttribute('data-kg');
  if (kg) document.getElementById('rprAffectedKg').value = kg;
});
</script>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
