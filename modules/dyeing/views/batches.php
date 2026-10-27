<?php
/**
 * Dyeing Batches & Soft Flow Vessels - KARCEM Textile ERP
 * File: modules/dyeing/views/batches.php
 */

use Core\Database\Database;
use Core\Support\Url;

$pageTitle = 'Dyeing Batches & Vessel Floor';
$activeModule = 'batches';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$batches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name, r.liquor_ratio as std_lr, m.code as machine_code, m.name as machine_name, m.capacity as machine_cap FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC");
$recipes = Database::fetchAll("SELECT * FROM dye_recipes WHERE is_approved = 1 ORDER BY shade_name ASC");
$machines = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY code ASC");
$parameters = Database::fetchAll("SELECT * FROM dye_batch_parameters ORDER BY recorded_at DESC LIMIT 10");
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
            <li class="breadcrumb-item active">Dye Batches</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Dyeing Batch Cards & Machine Loading</h1>
        <p class="text-secondary small mb-0">High-temperature Soft Flow dyeing vessels, recipe execution, and real-time process parameter logging.</p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <button type="button" onclick="exportTableToCSV('batchesTable', 'dye_batches_register.csv')" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
        </button>
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newBatchModal">
          <i class="bi bi-plus-lg me-1"></i> Load New Batch
        </button>
      </div>
    </div>

    <!-- Active Vessels Status Header -->
    <div class="row g-3 mb-4">
      <?php foreach ($machines as $m): ?>
        <div class="col-md-6">
          <div class="card shadow-sm border-0">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-dark fs-6"><?= htmlspecialchars($m['code']) ?></span>
                  <span class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></span>
                </div>
                <span class="badge-status status-<?= htmlspecialchars($m['current_status']) ?>">
                  <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?= strtoupper(htmlspecialchars($m['current_status'])) ?>
                </span>
              </div>
              <div class="row text-center g-2 mt-1">
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Capacity</div>
                  <div class="fw-bold text-dark"><?= number_format($m['capacity'], 0) ?> Kg</div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Liquor Ratio</div>
                  <div class="fw-bold text-primary"><?= htmlspecialchars($m['liquor_ratio'] ?? '1:5') ?></div>
                </div>
                <div class="col-4 bg-light rounded p-2">
                  <div class="text-secondary" style="font-size: 0.75rem;">Operating Cost</div>
                  <div class="fw-bold text-dark">₹<?= number_format($m['hourly_operating_cost'], 0) ?>/hr</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Batches Table Panel -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-layers-fill text-primary me-2"></i>Dyeing Batch Records</h2>
        <span class="small text-secondary"><?= count($batches) ?> Batches Tracked</span>
      </div>
      <div class="table-responsive">
        <table id="batchesTable" class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Batch Number</th>
              <th>Vessel</th>
              <th>Shade & Recipe</th>
              <th>Planned (Kg)</th>
              <th>Loaded (Kg)</th>
              <th>Output (Kg)</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($batches as $b): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($b['batch_no']) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($b['machine_code']) ?></span></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($b['shade_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($b['recipe_code']) ?> · Ratio: <?= htmlspecialchars($b['liquor_ratio']) ?></span>
                </td>
                <td><?= number_format($b['planned_weight_kg'], 1) ?></td>
                <td class="fw-semibold text-dark"><?= number_format($b['actual_loaded_kg'], 1) ?></td>
                <td class="text-success fw-bold"><?= $b['output_weight_kg'] > 0 ? number_format($b['output_weight_kg'], 1) : '-' ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="openParamModal(<?= $b['id'] ?>, '<?= htmlspecialchars($b['batch_no']) ?>')" title="Record Temperature / pH reading">
                      <i class="bi bi-thermometer-half me-1"></i> Log Reading
                    </button>
                    <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P40 Batch Card">
                      <i class="bi bi-printer"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Recent Process Readings Log -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>Time-Series Process Parameter Logs (Temperature °C & pH)</h2>
        <span class="badge bg-success text-white">Live Floor Telemetry</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Batch ID</th>
              <th>Process Step</th>
              <th>Set / Actual Temp (°C)</th>
              <th>Set / Actual pH</th>
              <th>Cycle Time</th>
              <th>Logged At</th>
              <th>Operator Remarks</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($parameters as $p): ?>
              <tr>
                <td class="fw-bold">#BAT-<?= $p['dye_batch_id'] ?></td>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($p['process_step']) ?></td>
                <td>
                  <span class="text-muted"><?= $p['set_temperature_c'] ?>°C</span> &rarr;
                  <span class="fw-bold text-danger"><?= $p['actual_temperature_c'] ?>°C</span>
                </td>
                <td>
                  <span class="text-muted"><?= $p['set_ph'] ?></span> &rarr;
                  <span class="fw-bold text-primary"><?= $p['actual_ph'] ?></span>
                </td>
                <td><?= $p['cycle_time_minutes'] ?> mins</td>
                <td><?= date('d M H:i', strtotime($p['recorded_at'])) ?></td>
                <td class="text-secondary"><?= htmlspecialchars($p['operator_notes'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- Load New Batch Modal -->
<div class="modal fade" id="newBatchModal" tabindex="-1" aria-labelledby="newBatchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newBatchModalLabel"><i class="bi bi-droplet me-2"></i>Load Soft Flow Dyeing Batch</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="newBatchForm" onsubmit="submitNewBatch(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold">Dye Recipe / Shade <span class="text-danger">*</span></label>
              <select class="form-select" id="batch_recipe_id" required>
                <option value="">Select Recipe...</option>
                <?php foreach ($recipes as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['shade_name']) ?> (<?= $r['recipe_code'] ?> - <?= $r['dye_class'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Vessel / Machine <span class="text-danger">*</span></label>
              <select class="form-select" id="batch_machine_id" required>
                <option value="">Select Vessel...</option>
                <?php foreach ($machines as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Planned Weight (Kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="batch_weight_kg" value="500.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Liquor Ratio</label>
              <input type="text" class="form-control" id="batch_liquor_ratio" value="1:5">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Operator Name</label>
              <input type="text" class="form-control" id="batch_operator_name" value="M. Kandasamy">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="loadBatchBtn"><i class="bi bi-check-circle me-1"></i> Release & Load Vessel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Parameter Logging Modal -->
<div class="modal fade" id="paramModal" tabindex="-1" aria-labelledby="paramModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-primary text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="paramModalLabel"><i class="bi bi-thermometer-half me-2"></i>Record Process Parameters: <span id="paramBatchNo"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="paramForm" onsubmit="submitParam(event)">
        <input type="hidden" id="param_batch_id">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold">Process Step <span class="text-danger">*</span></label>
              <select class="form-select" id="param_step" required>
                <option value="Pre-treatment / Scouring">01. Pre-treatment / Scouring (95°C, pH 10.5)</option>
                <option value="Reactive Dyeing Exhaustion">02. Reactive Dyeing Exhaustion (60°C, pH 7.0)</option>
                <option value="Fixation with Soda Ash">03. Fixation with Soda Ash (60°C, pH 10.8)</option>
                <option value="Hot Wash & Soaping">04. Hot Wash & Soaping (95°C, pH 7.0)</option>
                <option value="Neutralization & Softener">05. Neutralization & Softener (45°C, pH 5.5)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Actual Temperature (°C) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="param_temp" value="60.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Actual pH <span class="text-danger">*</span></label>
              <input type="number" step="0.05" class="form-control" id="param_ph" value="7.00" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Cycle Duration (Minutes)</label>
              <input type="number" class="form-control" id="param_cycle" value="30" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Operator Inspection Notes</label>
              <input type="text" class="form-control" id="param_notes" placeholder="e.g. Liquor clear, shade uniform">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveParamBtn"><i class="bi bi-save me-1"></i> Save Reading</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openParamModal(batchId, batchNo) {
    document.getElementById('param_batch_id').value = batchId;
    document.getElementById('paramBatchNo').textContent = batchNo;
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('paramModal'));
    modal.show();
  }

  async function submitNewBatch(e) {
    e.preventDefault();
    const btn = document.getElementById('loadBatchBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading...';

    const payload = {
      dye_recipe_id: parseInt(document.getElementById('batch_recipe_id').value),
      machine_id: parseInt(document.getElementById('batch_machine_id').value),
      planned_weight_kg: parseFloat(document.getElementById('batch_weight_kg').value),
      liquor_ratio: document.getElementById('batch_liquor_ratio').value,
      operator_name: document.getElementById('batch_operator_name').value
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/dye-batches') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Batch Loaded', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to load batch');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Release & Load Vessel';
      }
    } catch (err) {
      alert('Error loading batch: ' + err.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Release & Load Vessel';
    }
  }

  async function submitParam(e) {
    e.preventDefault();
    const batchId = document.getElementById('param_batch_id').value;
    const btn = document.getElementById('saveParamBtn');
    btn.disabled = true;

    const payload = {
      process_step: document.getElementById('param_step').value,
      actual_temperature_c: parseFloat(document.getElementById('param_temp').value),
      actual_ph: parseFloat(document.getElementById('param_ph').value),
      cycle_time_minutes: parseInt(document.getElementById('param_cycle').value),
      operator_notes: document.getElementById('param_notes').value
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/dye-batches/') ?>' + batchId + '/parameters', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Reading Logged', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to log reading');
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
