<?php
/**
 * Dyeing Batch Planning & Auto-Compatibility
 * Grouping eligible fabric rolls into optimized machine loads
 * File: modules/dyeing/views/batch-planning.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Batch Planning';
$activeModule = 'batch-planning';

// Handle POST to create batch from planned jobs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_batch') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $batchNo = 'BATCH-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $jobCardId = (int)($_POST['job_card_id'] ?? 1);
    $recipeId = (int)($_POST['recipe_id'] ?? 1);
    $machineId = (int)($_POST['machine_id'] ?? 1);
    $plannedKg = (float)($_POST['planned_weight_kg'] ?? 500);
    $loadedKg = $plannedKg;
    $liquorRatio = trim($_POST['liquor_ratio'] ?? '1:6');
    
    // Parse ratio to calculate bath volume
    $ratioNum = 6.0;
    if (preg_match('/1:([0-9\.]+)/', $liquorRatio, $m)) {
        $ratioNum = (float)$m[1];
    }
    $waterVolume = $plannedKg * $ratioNum;

    $companyId = (int)(\Core\Security\SessionManager::getUser()['company_id'] ?? 1);

    Database::beginTransaction();
    try {
        Database::insert('dye_batches', [
            'company_id'          => $companyId,
            'batch_no'            => $batchNo,
            'production_order_id' => $jobCardId,
            'dye_recipe_id'       => $recipeId,
            'machine_id'          => $machineId,
            'planned_weight_kg'   => $plannedKg,
            'actual_loaded_kg'    => $loadedKg,
            'water_volume_litres' => $waterVolume,
            'liquor_ratio'        => $liquorRatio,
            'operator_name'       => trim($_POST['operator_name'] ?? 'M. Ramanathan'),
            'status'              => 'planned'
        ]);

        // Update job card status to planned
        Database::getConnection()->prepare("UPDATE dyeing_job_cards SET status = 'planned' WHERE id = ?")->execute([$jobCardId]);
        Database::commit();
    } catch (\Throwable $e) {
        Database::rollBack();
        throw $e;
    }

    header('Location: ' . Url::to('/dyeing/batches?created=1'));
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$isNewForm = (isset($_GET['action']) && $_GET['action'] === 'new');
$presetJobCardId = (int)($_GET['job_card_id'] ?? 0);

$jobCards = Database::fetchAll("SELECT jc.*, c.name as customer_name FROM dyeing_job_cards jc JOIN customers c ON jc.customer_id = c.id WHERE jc.status IN ('approved', 'planned') ORDER BY jc.id DESC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY capacity DESC");
$recipes = Database::fetchAll("SELECT * FROM dye_recipes WHERE is_approved = 1 ORDER BY shade_name ASC");
$shades = Database::fetchAll("SELECT * FROM dyeing_shades ORDER BY shade_name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if ($isNewForm): ?>
      <!-- ========================================================================= -->
      <!-- FULL-PAGE FORM: NEW PRODUCTION BATCH (No Modals)                 -->
      <!-- ========================================================================= -->

      <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h1 class="h5 fw-bold text-dark mb-0">Create Production Dyeing Batch</h1>
              <span class="badge badge-bw">NEW BATCH</span>
            </div>
            <p class="text-secondary small mb-0 mt-1">Combine customer orders, verify vessel load capacity, liquor ratio, and calculate total bath volume.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
          <button type="submit" form="newBatchForm" class="btn btn-dark btn-sm">
            <i class="bi bi-calendar-check me-1"></i> Save Batch & Allocate Machine
          </button>
        </div>
      </div>

      <form id="newBatchForm" method="POST" action="<?= Url::to('/dyeing/batch-planning') ?>">
        <input type="hidden" name="action" value="create_batch">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <!-- Section A: Job Card & Shade Selection -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-card-checklist"></i> Section A — Select Approved Job Card & Shade Recipe</h2>
              <p class="cz-doc-section-desc">Select customer job order and bind the approved spectrophotometer laboratory recipe.</p>
            </div>
            <span class="badge badge-bw font-monospace">JOB SELECTION</span>
          </div>

          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">Select Approved Dyeing Job Card <span class="text-danger">*</span></label>
              <select name="job_card_id" id="bpJobSelect" class="form-select form-select-sm" required>
                <?php foreach ($jobCards as $jc): ?>
                  <option value="<?= $jc['id'] ?>" data-qty="<?= $jc['required_qty_kg'] ?>" <?= ($presetJobCardId === (int)$jc['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($jc['job_card_no']) ?> — <?= htmlspecialchars($jc['customer_name']) ?> (<?= htmlspecialchars($jc['fabric_name']) ?>, <?= $jc['required_qty_kg'] ?> Kg, <?= htmlspecialchars($jc['shade_name']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Approved Dye Recipe / Version <span class="text-danger">*</span></label>
              <select name="recipe_id" class="form-select form-select-sm" required>
                <?php foreach ($recipes as $rc): ?>
                  <option value="<?= $rc['id'] ?>">
                    <?= htmlspecialchars($rc['recipe_code']) ?> — <?= htmlspecialchars($rc['shade_name']) ?> (v<?= $rc['version_number'] ?? '1' ?>, <?= strtoupper($rc['dye_class'] ?? 'reactive') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Section B: Machine Allocation & Vessel Load -->
        <div class="cz-doc-section">
          <div class="cz-doc-section-head">
            <div>
              <h2 class="cz-doc-section-title"><i class="bi bi-hdd-rack"></i> Section B — Machine Vessel Allocation & Rated Capacity</h2>
              <p class="cz-doc-section-desc">Select soft flow vessel and verify minimum & maximum chamber load compliance.</p>
            </div>
            <span class="badge badge-bw font-monospace">VESSEL SPECS</span>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Dyeing Machine Vessel <span class="text-danger">*</span></label>
              <select name="machine_id" id="bpMachineSelect" class="form-select form-select-sm" required>
                <?php foreach ($machines as $m): ?>
                  <option value="<?= $m['id'] ?>" data-cap="<?= $m['capacity'] ?>" data-ratio="<?= $m['liquor_ratio'] ?>">
                    <?= htmlspecialchars($m['code']) ?> — <?= htmlspecialchars($m['name']) ?> (Rated <?= $m['capacity'] ?> Kg, Ratio <?= $m['liquor_ratio'] ?>, Status: <?= strtoupper($m['current_status']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Planned Batch Loading (Kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="planned_weight_kg" id="bpPlannedKg" class="form-control form-control-sm fw-bold font-monospace" value="500.00" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Liquor Ratio (L:R) <span class="text-danger">*</span></label>
              <select name="liquor_ratio" id="bpLiquorRatio" class="form-select form-select-sm font-monospace">
                <option value="1:5">1:5 (Ultra Low Bath)</option>
                <option value="1:6" selected>1:6 (Standard Soft Flow)</option>
                <option value="1:7">1:7 (Medium Load)</option>
                <option value="1:8">1:8 (High Circulation)</option>
                <option value="1:10">1:10 (Sample Vessel)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Calculated Bath Volume (Water)</label>
              <div class="input-group input-group-sm">
                <input type="number" id="bpWaterVolume" class="form-control font-monospace fw-bold bg-light" readonly value="3000">
                <span class="input-group-text">Litres</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Lead Operator / In-Charge <span class="text-danger">*</span></label>
              <input type="text" name="operator_name" class="form-control form-control-sm" value="M. Ramanathan (Senior Master Dyer)" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Target Loading Shift</label>
              <select class="form-select form-select-sm">
                <option value="Shift 1 (06:00 - 14:00)">Shift 1 (06:00 - 14:00)</option>
                <option value="Shift 2 (14:00 - 22:00)">Shift 2 (14:00 - 22:00)</option>
                <option value="Shift 3 (22:00 - 06:00)">Shift 3 (22:00 - 06:00)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <a href="<?= Url::to('/dyeing/batch-planning') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
          <button type="submit" class="btn btn-dark btn-sm px-4">
            <i class="bi bi-calendar-check me-1"></i> Create Batch & Proceed to Loading Sheet
          </button>
        </div>
      </form>

      <script>
      function updateBathCalc() {
        const kg = parseFloat(document.getElementById('bpPlannedKg')?.value) || 0;
        const ratioStr = document.getElementById('bpLiquorRatio')?.value || '1:6';
        let ratioNum = 6.0;
        const m = ratioStr.match(/1:([0-9\.]+)/);
        if (m) ratioNum = parseFloat(m[1]);
        const vol = Math.round(kg * ratioNum);
        const el = document.getElementById('bpWaterVolume');
        if (el) el.value = vol;
      }
      document.getElementById('bpPlannedKg')?.addEventListener('input', updateBathCalc);
      document.getElementById('bpLiquorRatio')?.addEventListener('change', updateBathCalc);
      document.getElementById('bpJobSelect')?.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const qty = opt.getAttribute('data-qty');
        if (qty) {
          const kgEl = document.getElementById('bpPlannedKg');
          if (kgEl) {
            kgEl.value = parseFloat(qty).toFixed(2);
            updateBathCalc();
          }
        }
      });
      </script>

    <?php else: ?>
      <!-- ========================================================================= -->
      <!-- DEDICATED REGISTER VIEW (Batch Planning & Capacity Overview)              -->
      <!-- ========================================================================= -->

      <!-- Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
              <li class="breadcrumb-item active">Batch Planning</li>
            </ol>
          </nav>
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-calendar-range text-dark me-2"></i>Dyeing Batch Planning & Machine Grouping</h1>
          <p class="text-secondary small mb-0">Group compatible customer orders, verify machine capacity (Min/Max load), liquor ratio, and reserve grey rolls.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= Url::to('/dyeing/batch-planning?action=new') ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Create Production Batch
          </a>
        </div>
      </div>

      <!-- Machine Capacity & Availability Cards -->
      <div class="row g-3 mb-4">
        <?php foreach ($machines as $m): ?>
          <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge badge-bw font-monospace"><?= htmlspecialchars($m['code']) ?></span>
                  <span class="badge <?= $m['current_status'] === 'running' ? 'badge-bw-dark' : 'badge-bw' ?>">
                    <?= strtoupper($m['current_status']) ?>
                  </span>
                </div>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($m['name']) ?></h6>
                <div class="row text-center g-2 mt-2 pt-2 border-top small">
                  <div class="col-4 bg-light rounded p-2">
                    <div class="text-secondary" style="font-size:0.7rem;">Rated Cap</div>
                    <div class="fw-bold"><?= $m['capacity'] ?> Kg</div>
                  </div>
                  <div class="col-4 bg-light rounded p-2">
                    <div class="text-secondary" style="font-size:0.7rem;">Liquor Ratio</div>
                    <div class="fw-bold font-monospace"><?= $m['liquor_ratio'] ?></div>
                  </div>
                  <div class="col-4 bg-light rounded p-2">
                    <div class="text-secondary" style="font-size:0.7rem;">Chambers</div>
                    <div class="fw-bold"><?= $m['spring_or_tubes'] ?? 2 ?> Ports</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Eligible Job Cards Table Ready for Batching -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-dark me-2"></i>Approved Job Cards Ready for Batch Allocation</h2>
          <span class="small text-secondary"><?= count($jobCards) ?> Eligible Orders</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Job Card No</th>
                <th>Customer / PO</th>
                <th>Fabric Details</th>
                <th>Shade & Route</th>
                <th class="text-end">Weight (Kg)</th>
                <th class="text-center">Rolls</th>
                <th>Due Date</th>
                <th>Status</th>
                <th class="text-end">Batch Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jobCards as $jc): ?>
                <tr>
                  <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($jc['job_card_no']) ?></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($jc['customer_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">PO: <?= htmlspecialchars($jc['customer_po'] ?: 'Direct PO') ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($jc['fabric_name']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem;">Lot: <?= htmlspecialchars($jc['grey_lot_no'] ?: 'Allocated') ?></div>
                  </td>
                  <td>
                    <span class="badge badge-bw font-monospace"><?= htmlspecialchars($jc['shade_code']) ?></span>
                    <div class="small fw-semibold"><?= htmlspecialchars($jc['shade_name']) ?></div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($jc['required_qty_kg'], 2) ?></td>
                  <td class="text-center fw-semibold"><?= $jc['roll_count'] ?></td>
                  <td><?= date('d M Y', strtotime($jc['required_date'])) ?></td>
                  <td>
                    <span class="badge <?= $jc['status'] === 'planned' ? 'badge-bw-dark' : 'badge-bw' ?>">
                      <?= strtoupper($jc['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="<?= Url::to('/dyeing/batch-planning?action=new&job_card_id=' . $jc['id']) ?>" class="btn btn-sm btn-dark">
                      <i class="bi bi-plus-circle me-1"></i> Plan Batch
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
