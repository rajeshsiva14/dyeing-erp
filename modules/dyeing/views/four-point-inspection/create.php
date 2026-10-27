<?php
/**
 * Dedicated Standalone Form: New ASTM D5430 4-Point Fabric Inspection
 * File: modules/dyeing/views/four-point-inspection/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'New 4-Point Fabric Inspection';
$activeModule = 'four-point-inspection';

$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);
$inspectorId = (int)($currentUser['id'] ?? 1);
$errors = [];

// Handle POST to save inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inspection') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $rollId = (int)($_POST['roll_id'] ?? 0);
    $meters = (float)($_POST['inspected_meters'] ?? 0);
    $width = (float)($_POST['width_inches'] ?? 48.0);
    $gsm = (int)($_POST['gsm'] ?? 160);
    $points = (int)($_POST['total_penalty_points'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? 'Uniform shade, minimal defects.');

    if ($rollId <= 0) $errors[] = 'Please select a Fabric Roll.';
    if ($meters <= 0) $errors[] = 'Inspected meters must be greater than 0.';
    if ($width <= 0) $errors[] = 'Fabric width (inches) must be greater than 0.';

    if (!$errors) {
        $lengthYds = $meters * 1.0936;
        $sqYds = ($lengthYds * $width) / 36;
        $pointsPer100 = ($sqYds > 0) ? round(($points * 100) / $sqYds, 2) : 0;

        if ($pointsPer100 <= 20) {
            $grade = 'A';
            $disposition = 'accepted';
        } elseif ($pointsPer100 <= 28) {
            $grade = 'B';
            $disposition = 'accepted';
        } elseif ($pointsPer100 <= 40) {
            $grade = 'C';
            $disposition = 'downgrade';
        } else {
            $grade = 'reject';
            $disposition = 'rejected';
        }

        $inspNo = 'INSP-' . date('Ymd') . '-' . str_pad((string)rand(100, 9999), 4, '0', STR_PAD_LEFT);

        Database::beginTransaction();
        try {
            $inspId = Database::insert('fabric_inspections', [
                'company_id'           => $companyId,
                'inspection_no'        => $inspNo,
                'roll_id'              => $rollId,
                'inspected_meters'     => $meters,
                'width_inches'         => $width,
                'gsm'                  => $gsm,
                'total_penalty_points' => $points,
                'points_per_100_sq_yd' => $pointsPer100,
                'quality_grade'        => $grade,
                'inspector_id'         => $inspectorId,
                'inspection_date'      => date('Y-m-d'),
                'disposition'          => $disposition,
                'remarks'              => $remarks
            ]);

            // Update roll grade
            Database::getConnection()->prepare("UPDATE inventory_rolls SET quality_grade = ? WHERE id = ?")->execute([
                $grade === 'reject' ? 'C' : $grade, $rollId
            ]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/four-point-inspection?created=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Failed to save inspection: ' . $e->getMessage();
        }
    }
}

$rolls = Database::fetchAll("SELECT r.*, p.name as product_name FROM inventory_rolls r JOIN products p ON r.product_id = p.id ORDER BY r.id DESC");

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/four-point-inspection') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Record ASTM D5430 4-Point Inspection</h1>
            <span class="badge badge-bw">NEW INSPECTION</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Automatic ASTM penalty points calculation per 100 square yards with instant grading.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/four-point-inspection') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newInspForm" class="btn btn-dark btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save Inspection & Grade
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

    <form id="newInspForm" method="POST" action="<?= Url::to('/dyeing/four-point-inspection/create') ?>">
      <?= CSRF::getField() ?>
      <input type="hidden" name="action" value="save_inspection">

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-rulers me-2 text-primary"></i>Roll & Defect Details</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Select Fabric Roll *</label>
                  <select name="roll_id" id="insp_roll_id" class="form-select form-select-sm" required>
                    <option value="">-- Choose Roll --</option>
                    <?php foreach ($rolls as $r): ?>
                      <option value="<?= $r['id'] ?>" data-width="<?= $r['width_inches'] ?>" data-length="<?= $r['length_meters'] ?>" data-gsm="<?= $r['gsm'] ?>">
                        <?= htmlspecialchars($r['roll_number']) ?> — <?= htmlspecialchars($r['product_name']) ?> (<?= number_format($r['weight_kg'], 1) ?> Kg)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-secondary fw-semibold">Inspected Length (Meters) *</label>
                  <input type="number" step="0.1" min="1" name="inspected_meters" id="insp_meters" class="form-control form-control-sm font-monospace fw-bold" required value="100.0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Cuttable Width (Inches) *</label>
                  <input type="number" step="0.5" min="1" name="width_inches" id="insp_width" class="form-control form-control-sm font-monospace" required value="60.0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Fabric GSM *</label>
                  <input type="number" name="gsm" id="insp_gsm" class="form-control form-control-sm font-monospace" required value="160">
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-secondary fw-semibold">Total Penalty Points *</label>
                  <input type="number" min="0" name="total_penalty_points" id="insp_points" class="form-control form-control-sm font-monospace fw-bold" required value="8">
                </div>
                <div class="col-12">
                  <label class="form-label small text-secondary fw-semibold">Inspector Remarks</label>
                  <textarea name="remarks" class="form-control form-control-sm" rows="2">Uniform shade, minimal defects. Approved for dispatch.</textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-award me-2 text-primary"></i>Live ASTM Scoring</h2>
            </div>
            <div class="p-3 text-center">
              <div class="text-secondary small mb-1">Calculated Points Score:</div>
              <div class="fs-4 fw-bold text-dark font-monospace mb-2" id="calc_points_val">7.33 pts / 100 sq yd</div>
              <div class="mb-3">
                <span class="badge bg-success fs-6" id="calc_grade_badge">GRADE A (ACCEPTED)</span>
              </div>
              <div class="p-2 rounded bg-light border small text-start text-secondary">
                <div><strong>Standard Limits:</strong></div>
                <div>Grade A: &le; 20 pts / 100 sq yd</div>
                <div>Grade B: 21 – 28 pts / 100 sq yd</div>
                <div>Grade C: 29 – 40 pts / 100 sq yd</div>
                <div>Reject: &gt; 40 pts / 100 sq yd</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>

<script>
function calcPoints() {
  const meters = parseFloat(document.getElementById('insp_meters')?.value) || 0;
  const width = parseFloat(document.getElementById('insp_width')?.value) || 0;
  const pts = parseInt(document.getElementById('insp_points')?.value) || 0;

  if (meters > 0 && width > 0) {
    const lengthYds = meters * 1.0936;
    const sqYds = (lengthYds * width) / 36;
    const ptsPer100 = (pts * 100) / sqYds;
    document.getElementById('calc_points_val').textContent = ptsPer100.toFixed(2) + ' pts / 100 sq yd';

    const badge = document.getElementById('calc_grade_badge');
    if (ptsPer100 <= 20) {
      badge.className = 'badge bg-success fs-6';
      badge.textContent = 'GRADE A (ACCEPTED)';
    } else if (ptsPer100 <= 28) {
      badge.className = 'badge bg-primary fs-6';
      badge.textContent = 'GRADE B (COMMERCIAL)';
    } else if (ptsPer100 <= 40) {
      badge.className = 'badge bg-warning text-dark fs-6';
      badge.textContent = 'GRADE C (DOWNGRADE)';
    } else {
      badge.className = 'badge bg-danger fs-6';
      badge.textContent = 'REJECTED';
    }
  }
}
document.getElementById('insp_meters')?.addEventListener('input', calcPoints);
document.getElementById('insp_width')?.addEventListener('input', calcPoints);
document.getElementById('insp_points')?.addEventListener('input', calcPoints);
document.getElementById('insp_roll_id')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (opt) {
    const w = opt.getAttribute('data-width');
    const l = opt.getAttribute('data-length');
    const g = opt.getAttribute('data-gsm');
    if (w) document.getElementById('insp_width').value = w;
    if (l) document.getElementById('insp_meters').value = l;
    if (g) document.getElementById('insp_gsm').value = g;
    calcPoints();
  }
});
calcPoints();
</script>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
