<?php
/**
 * ASTM 4-Point Fabric Quality Inspection - KARCEM Textile ERP
 * File: modules/dyeing/views/four-point-inspection.php
 */

use Core\Database\Database;
use Core\Support\Url;

$pageTitle = '4-Point Fabric Inspection';
$activeModule = 'four-point-inspection';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$inspections = Database::fetchAll("SELECT fi.*, r.roll_number, r.weight_kg, p.name as product_name, u.full_name as inspector_name FROM fabric_inspections fi JOIN inventory_rolls r ON fi.roll_id = r.id JOIN products p ON r.product_id = p.id JOIN users u ON fi.inspector_id = u.id ORDER BY fi.id DESC");
$rolls = Database::fetchAll("SELECT r.*, p.name as product_name FROM inventory_rolls r JOIN products p ON r.product_id = p.id ORDER BY r.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">4-Point Quality Inspection</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">ASTM D5430 4-Point System Fabric Inspection</h1>
        <p class="text-secondary small mb-0">International standard grading for knitted and woven fabric rolls based on penalty points per 100 square yards.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newInspectionModal">
          <i class="bi bi-plus-lg me-1"></i> New Roll Inspection
        </button>
      </div>
    </div>

    <!-- Standards Reference Card -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-success shadow-sm">
          <div class="text-secondary small fw-bold">GRADE A (PREMIUM)</div>
          <div class="fs-4 fw-bold text-success">&le; 20 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Export Eligible</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-primary shadow-sm">
          <div class="text-secondary small fw-bold">GRADE B (COMMERCIAL)</div>
          <div class="fs-4 fw-bold text-primary">21 – 28 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Domestic Standard</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-warning shadow-sm">
          <div class="text-secondary small fw-bold">GRADE C (DOWNGRADE)</div>
          <div class="fs-4 fw-bold text-warning">29 – 40 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Per 100 sq yds · Concession / Second</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-danger shadow-sm">
          <div class="text-secondary small fw-bold">REJECTION</div>
          <div class="fs-4 fw-bold text-danger">&gt; 40 Points</div>
          <div class="text-muted" style="font-size: 0.75rem;">Or Holes &gt; 2 Inches · Scrap Disposition</div>
        </div>
      </div>
    </div>

    <!-- Inspection Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-success me-2"></i>Inspected Fabric Roll Certificates</h2>
        <span class="small text-secondary"><?= count($inspections) ?> Rolls Certified</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Inspection No</th>
              <th>Roll Barcode</th>
              <th>Fabric & Specification</th>
              <th>Length & Width</th>
              <th>Penalty Points</th>
              <th>Points / 100 Sq Yd</th>
              <th>Grade</th>
              <th>Wash Fastness</th>
              <th>Disposition</th>
              <th class="text-end">Certificate</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($inspections as $fi): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($fi['inspection_no']) ?></td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($fi['roll_number']) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($fi['product_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;"><?= $fi['gsm'] ?> GSM · <?= number_format($fi['weight_kg'], 1) ?> Kg</span>
                </td>
                <td><?= number_format($fi['inspected_meters'], 1) ?>m &times; <?= $fi['width_inches'] ?>"</td>
                <td class="fw-bold"><?= $fi['total_penalty_points'] ?> pts</td>
                <td>
                  <span class="fw-bold text-dark"><?= number_format($fi['points_per_100_sq_yd'], 2) ?></span>
                </td>
                <td>
                  <span class="badge bg-<?= $fi['quality_grade'] === 'A' ? 'success' : ($fi['quality_grade'] === 'B' ? 'primary' : 'warning') ?> fs-6">
                    Grade <?= htmlspecialchars($fi['quality_grade']) ?>
                  </span>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($fi['fastness_wash'] ?? '4-5') ?></span></td>
                <td><span class="badge-status status-<?= htmlspecialchars($fi['disposition']) ?>"><?= htmlspecialchars($fi['disposition']) ?></span></td>
                <td class="text-end">
                  <a href="<?= Url::to('/print/fabric-inspection?id=' . $fi['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print P46 Quality Certificate">
                    <i class="bi bi-printer me-1"></i> P46 Cert
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- New Inspection Modal -->
<div class="modal fade" id="newInspectionModal" tabindex="-1" aria-labelledby="newInspectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newInspectionModalLabel"><i class="bi bi-shield-check me-2"></i>Record 4-Point Roll Inspection</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="inspectionForm" onsubmit="submitInspection(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold">Select Fabric Roll <span class="text-danger">*</span></label>
              <select class="form-select" id="insp_roll_id" required>
                <option value="">Select Roll...</option>
                <?php foreach ($rolls as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['roll_number']) ?> (<?= $r['weight_kg'] ?> Kg · <?= htmlspecialchars($r['product_name']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Inspected Length (Meters) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="insp_meters" value="60.0" required oninput="calcPoints()">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Fabric Width (Inches) <span class="text-danger">*</span></label>
              <input type="number" step="0.5" class="form-control" id="insp_width" value="72.0" required oninput="calcPoints()">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Measured GSM</label>
              <input type="number" class="form-control" id="insp_gsm" value="180">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Total Penalty Points <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="insp_points" value="6" required oninput="calcPoints()">
            </div>

            <!-- Calculation Preview -->
            <div class="col-12 bg-light p-3 rounded">
              <div class="d-flex justify-content-between align-items-center">
                <span class="small text-secondary">ASTM Calculated Score:</span>
                <span class="fs-5 fw-bold text-primary" id="calc_points_val">11.43 pts / 100 sq yd</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="small text-secondary">Determined Grade:</span>
                <span class="badge bg-success fs-6" id="calc_grade_badge">GRADE A (ACCEPTED)</span>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small">Inspector Remarks</label>
              <input type="text" class="form-control" id="insp_remarks" value="Uniform shade, minimal defects. Approved for cutting / dispatch.">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveInspBtn"><i class="bi bi-save me-1"></i> Save Inspection & Grade</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function calcPoints() {
    const meters = parseFloat(document.getElementById('insp_meters').value) || 0;
    const width = parseFloat(document.getElementById('insp_width').value) || 0;
    const pts = parseInt(document.getElementById('insp_points').value) || 0;

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
      } else {
        badge.className = 'badge bg-warning text-dark fs-6';
        badge.textContent = 'GRADE C (DOWNGRADE)';
      }
    }
  }

  async function submitInspection(e) {
    e.preventDefault();
    const btn = document.getElementById('saveInspBtn');
    btn.disabled = true;

    const payload = {
      roll_id: parseInt(document.getElementById('insp_roll_id').value),
      inspected_meters: parseFloat(document.getElementById('insp_meters').value),
      width_inches: parseFloat(document.getElementById('insp_width').value),
      gsm: parseInt(document.getElementById('insp_gsm').value),
      total_penalty_points: parseInt(document.getElementById('insp_points').value),
      remarks: document.getElementById('insp_remarks').value
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/fabric-inspections') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Inspection Saved', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to save inspection');
        btn.disabled = false;
      }
    } catch (err) {
      alert('Error: ' + err.message);
      btn.disabled = false;
    }
  }
</script>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
