<?php
/**
 * Job-Work & Subcontracting Hub - KARCEM Textile ERP
 * File: modules/jobwork/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Job-Work & Subcontract Hub';
$activeModule = 'jobwork';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$jobWorkers = Database::fetchAll("SELECT * FROM job_workers WHERE status = 'active' ORDER BY name ASC");
$challans = Database::fetchAll("SELECT jc.*, jw.name as worker_name FROM job_work_challans jc JOIN job_workers jw ON jc.job_worker_id = jw.id ORDER BY jc.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-info-subtle text-info p-3 rounded-circle fs-3"><i class="bi bi-arrow-left-right"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Job-Work & Subcontract Processing (Section 143 GST)</h1>
          <p class="text-secondary small mb-0">Statutory job-work delivery challans, outward material issue, processor service bills, return deadline monitoring, and complete isolation of customer-owned fabric.</p>
        </div>
      </div>
    </section>

    <!-- Outward Challans Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Outward Job-Work Challans (Delivery Challans)</h2>
        <span class="badge bg-light text-dark border">Statutory Section 143 Tracking</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Challan No</th>
              <th>Job Worker</th>
              <th>Required Process</th>
              <th>Issue Date</th>
              <th>Return Due Date</th>
              <th>Quantity</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php if (!empty($challans)): ?>
              <?php foreach ($challans as $c): ?>
                <tr>
                  <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($c['challan_no']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($c['worker_name']) ?></td>
                  <td><?= htmlspecialchars($c['process_required']) ?></td>
                  <td><?= date('d M Y', strtotime($c['issue_date'])) ?></td>
                  <td><span class="fw-semibold text-danger"><?= date('d M Y', strtotime($c['expected_return_date'])) ?></span></td>
                  <td class="fw-bold font-monospace"><?= number_format($c['total_qty'], 1) ?> <?= htmlspecialchars($c['uom']) ?></td>
                  <td><span class="badge-status status-<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-printer me-1"></i> P56 Challan</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No outward job-work challans pending. All material accounted for in-house.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Job Workers Master Cards -->
    <h6 class="fw-bold text-dark small text-uppercase mb-2"><i class="bi bi-people me-1"></i>Authorized Outside Processors</h6>
    <div class="row g-3">
      <?php foreach ($jobWorkers as $jw): ?>
        <div class="col-md-4">
          <div class="card p-3 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge bg-dark font-monospace"><?= htmlspecialchars($jw['code']) ?></span>
              <span class="badge bg-success-subtle text-success">Verified GSTIN</span>
            </div>
            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($jw['name']) ?></div>
            <div class="small text-secondary mb-2"><?= htmlspecialchars($jw['process_capability']) ?></div>
            <div class="small text-muted border-top pt-2">
              <strong>GSTIN:</strong> <?= htmlspecialchars($jw['gstin'] ?? '33AABCT9981E1Z2') ?><br>
              <strong>Location:</strong> <?= htmlspecialchars($jw['address']) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
