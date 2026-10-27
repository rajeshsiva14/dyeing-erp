<?php
/**
 * Gate, Security & Weighbridge Logistics - KARCEM Textile ERP
 * File: modules/gate/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Gate & Logistics Hub';
$activeModule = 'gate';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$gatePasses = Database::fetchAll("SELECT * FROM gate_passes ORDER BY id DESC LIMIT 10");
$weighbridge = Database::fetchAll("SELECT * FROM weighbridge_entries ORDER BY id DESC LIMIT 10");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-secondary-subtle text-dark p-3 rounded-circle fs-3"><i class="bi bi-truck"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Gate Security, Logistics & Automated Weighbridge</h1>
          <p class="text-secondary small mb-0">Mill & SIPCOT plant gate management: Returnable & Non-returnable gate passes, truck gross/tare weighbridge slips, vehicle inspection, and outward dispatch clearance.</p>
        </div>
      </div>
    </section>

    <!-- Gate Passes Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-shield-lock text-primary me-2"></i>Outward Gate Pass Log</h2>
        <span class="badge bg-dark text-white">Security Checkpoint</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Pass No</th>
              <th>Pass Type</th>
              <th>Material Category</th>
              <th>Issued To</th>
              <th>Vehicle Number</th>
              <th>Out Time</th>
              <th>Return Deadline</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php if (!empty($gatePasses)): ?>
              <?php foreach ($gatePasses as $gp): ?>
                <tr>
                  <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($gp['pass_no']) ?></td>
                  <td>
                    <span class="badge bg-<?= $gp['pass_type'] === 'returnable' ? 'warning text-dark' : 'secondary' ?>">
                      <?= strtoupper(htmlspecialchars(str_replace('_', ' ', $gp['pass_type']))) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($gp['category']) ?></td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($gp['issued_to']) ?></td>
                  <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($gp['vehicle_no']) ?></span></td>
                  <td><?= date('d M H:i', strtotime($gp['out_time'])) ?></td>
                  <td><?= $gp['expected_return_date'] ? date('d M Y', strtotime($gp['expected_return_date'])) : '-' ?></td>
                  <td><span class="badge-status status-<?= htmlspecialchars($gp['status']) ?>"><?= htmlspecialchars($gp['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No outward gate passes recorded today.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Weighbridge Scale Entries -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 text-success me-2"></i>Electronic Weighbridge Weighment Slips</h2>
        <span class="badge bg-success-subtle text-success">Calibrated Scale 60T</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Slip No</th>
              <th>Vehicle No</th>
              <th>Transporter</th>
              <th>Material</th>
              <th class="text-end">Gross (Kg)</th>
              <th class="text-end">Tare (Kg)</th>
              <th class="text-end">Net Weight (Kg)</th>
              <th>Linked Doc</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody class="small font-monospace">
            <?php if (!empty($weighbridge)): ?>
              <?php foreach ($weighbridge as $wb): ?>
                <tr>
                  <td class="fw-bold text-primary"><?= htmlspecialchars($wb['slip_no']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($wb['vehicle_no']) ?></span></td>
                  <td class="font-sans-serif"><?= htmlspecialchars($wb['transporter_name']) ?></td>
                  <td class="font-sans-serif"><?= htmlspecialchars($wb['material_type']) ?></td>
                  <td class="text-end"><?= number_format($wb['gross_weight_kg'], 1) ?></td>
                  <td class="text-end"><?= number_format($wb['tare_weight_kg'], 1) ?></td>
                  <td class="text-end fw-bold text-success fs-6"><?= number_format($wb['net_weight_kg'], 1) ?> Kg</td>
                  <td><span class="badge bg-secondary"><?= strtoupper($wb['linked_document_type']) ?> #<?= $wb['linked_document_id'] ?></span></td>
                  <td class="font-sans-serif text-muted"><?= date('d M H:i', strtotime($wb['weighment_time'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">All trucks cleared weighment. Tolerance checks active.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
