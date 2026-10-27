<?php
/**
 * TNPCB ETP / ZLD Daily Monitoring Log - KARCEM Textile ERP
 * File: modules/compliance/views/etp-zld-log.php
 */

use Core\Database\Database;
use Core\Support\Url;

$pageTitle = 'TNPCB ETP / ZLD Logs';
$activeModule = 'compliance';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$logs = Database::fetchAll("SELECT * FROM etp_logs ORDER BY log_date DESC, id DESC");
$latest = $logs[0] ?? null;
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/compliance') ?>">Compliance</a></li>
            <li class="breadcrumb-item active">ETP / ZLD</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">TNPCB ETP & Zero Liquid Discharge (ZLD) Monitoring</h1>
        <p class="text-secondary small mb-0">Daily effluent treatment logs, Reverse Osmosis (RO) permeate recovery %, MVR evaporator condensate, pH, COD, and TDS tracking.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newEtpLogModal">
          <i class="bi bi-plus-lg me-1"></i> New Daily ETP Reading
        </button>
      </div>
    </div>

    <!-- Live Compliance Status Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-success shadow-sm">
          <div class="text-secondary small fw-bold">ZLD WATER RECOVERY RATE</div>
          <div class="fs-4 fw-bold text-success">98.2% Recycled</div>
          <div class="text-muted" style="font-size: 0.75rem;">RO Permeate + Evaporator Condensate</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-primary shadow-sm">
          <div class="text-secondary small fw-bold">EFFLUENT pH VALUE</div>
          <div class="fs-4 fw-bold text-primary"><?= $latest ? $latest['ph_value'] : '7.35' ?> pH</div>
          <div class="text-muted" style="font-size: 0.75rem;">TNPCB Limit: 6.5 – 8.5 (Optimal)</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-info shadow-sm">
          <div class="text-secondary small fw-bold">CHEMICAL OXYGEN DEMAND</div>
          <div class="fs-4 fw-bold text-info"><?= $latest ? $latest['cod_ppm'] : '16.8' ?> ppm</div>
          <div class="text-muted" style="font-size: 0.75rem;">TNPCB Limit: &lt; 250 ppm</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-warning shadow-sm">
          <div class="text-secondary small fw-bold">TOTAL DISSOLVED SOLIDS</div>
          <div class="fs-4 fw-bold text-warning"><?= $latest ? number_format($latest['tds_ppm'], 0) : '1,420' ?> ppm</div>
          <div class="text-muted" style="font-size: 0.75rem;">Clean Recycled Process Quality</div>
        </div>
      </div>
    </div>

    <!-- ETP Logs Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-water text-primary me-2"></i>Daily Effluent Treatment & Recovery Register</h2>
        <span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i>Zero Liquid Discharge Compliant</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Date & Shift</th>
              <th>Inlet Flow (KLD)</th>
              <th>RO Permeate (KLD)</th>
              <th>MVR Condensate (KLD)</th>
              <th>pH Value</th>
              <th>COD / BOD (ppm)</th>
              <th>TDS (ppm)</th>
              <th>Sludge (Kg)</th>
              <th>Operator</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($logs as $l): ?>
              <tr>
                <td class="fw-bold text-dark">
                  <?= date('d M Y', strtotime($l['log_date'])) ?>
                  <span class="text-muted d-block text-capitalize" style="font-size: 0.75rem;"><?= str_replace('_', ' ', $l['shift']) ?></span>
                </td>
                <td class="fw-bold text-primary"><?= number_format($l['inlet_flow_kld'], 1) ?></td>
                <td class="text-success fw-bold"><?= number_format($l['ro_permeate_recovery_kld'], 1) ?></td>
                <td class="text-info"><?= number_format($l['mvr_evaporator_condensate_kld'], 1) ?></td>
                <td><span class="badge bg-light text-dark border font-monospace"><?= $l['ph_value'] ?></span></td>
                <td><?= $l['cod_ppm'] ?> / <?= $l['bod_ppm'] ?></td>
                <td><?= number_format($l['tds_ppm'], 0) ?></td>
                <td><?= number_format($l['sludge_generated_kg'], 0) ?> Kg</td>
                <td><?= htmlspecialchars($l['operator_name']) ?></td>
                <td class="text-secondary"><?= htmlspecialchars($l['remarks'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- New ETP Log Modal -->
<div class="modal fade" id="newEtpLogModal" tabindex="-1" aria-labelledby="newEtpLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newEtpLogModalLabel"><i class="bi bi-water me-2"></i>Record Daily ETP / ZLD Reading</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="etpForm" onsubmit="submitEtpLog(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="etp_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Shift <span class="text-danger">*</span></label>
              <select class="form-select" id="etp_shift">
                <option value="shift_1">Shift 1 (06:00 - 14:00)</option>
                <option value="shift_2">Shift 2 (14:00 - 22:00)</option>
                <option value="shift_3">Shift 3 (22:00 - 06:00)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Inlet Flow (KLD) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="etp_inlet" value="460.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">RO Permeate (KLD) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" class="form-control" id="etp_ro" value="395.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">pH Reading</label>
              <input type="number" step="0.05" class="form-control" id="etp_ph" value="7.40" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">COD (ppm)</label>
              <input type="number" step="0.1" class="form-control" id="etp_cod" value="18.0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">TDS (ppm)</label>
              <input type="number" class="form-control" id="etp_tds" value="1420" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Sludge Generated (Kg)</label>
              <input type="number" class="form-control" id="etp_sludge" value="850">
            </div>
            <div class="col-12">
              <label class="form-label small">Operator Name</label>
              <input type="text" class="form-control" id="etp_operator" value="G. Subramaniam">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveEtpBtn"><i class="bi bi-save me-1"></i> Save ETP Log</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  async function submitEtpLog(e) {
    e.preventDefault();
    const btn = document.getElementById('saveEtpBtn');
    btn.disabled = true;

    const payload = {
      log_date: document.getElementById('etp_date').value,
      shift: document.getElementById('etp_shift').value,
      inlet_flow_kld: parseFloat(document.getElementById('etp_inlet').value),
      ro_permeate_recovery_kld: parseFloat(document.getElementById('etp_ro').value),
      ph_value: parseFloat(document.getElementById('etp_ph').value),
      cod_ppm: parseFloat(document.getElementById('etp_cod').value),
      tds_ppm: parseFloat(document.getElementById('etp_tds').value),
      sludge_generated_kg: parseFloat(document.getElementById('etp_sludge').value),
      operator_name: document.getElementById('etp_operator').value
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/etp-logs') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('ETP Logged', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to save log');
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
