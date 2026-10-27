<?php
/**
 * HR, Attendance & Production Labour Hub - KARCEM Textile ERP
 * File: modules/hr/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Support\NumberToWords;

$pageTitle = 'HR & Production Labour Hub';
$activeModule = 'hr';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$employees = Database::fetchAll("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id ORDER BY e.id ASC");
$totalEmp = count($employees);
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-people"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Human Resources, Attendance & Production Wages</h1>
          <p class="text-secondary small mb-0">Shift rotations, biometric attendance, contract labour, piece-rate wages (Knitting, Dyeing & Sewing output), PF/ESI statutory compliance and payroll.</p>
        </div>
      </div>
    </section>

    <!-- Employees Directory Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Mill & Factory Workers Directory</h2>
        <span class="badge bg-dark text-white"><?= $totalEmp ?> Active Personnel</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Emp Code</th>
              <th>Full Name</th>
              <th>Department</th>
              <th>Designation</th>
              <th>Wage Type</th>
              <th>Statutory UAN & ESI</th>
              <th>Joining Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($employees as $e): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($e['employee_code']) ?></td>
                <td class="fw-bold text-dark"><?= htmlspecialchars($e['full_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($e['dept_name']) ?></span></td>
                <td><?= htmlspecialchars($e['designation']) ?></td>
                <td>
                  <span class="badge bg-<?= $e['wage_type'] === 'piece_rate' ? 'warning text-dark' : ($e['wage_type'] === 'daily' ? 'info text-dark' : 'secondary') ?>">
                    <?= strtoupper(htmlspecialchars($e['wage_type'])) ?>
                  </span>
                </td>
                <td class="font-monospace text-muted" style="font-size: 0.72rem;">
                  UAN: <?= htmlspecialchars($e['uan_number'] ?? 'N/A') ?><br>
                  ESI: <?= htmlspecialchars($e['esi_number'] ?? 'N/A') ?>
                </td>
                <td><?= date('d M Y', strtotime($e['joining_date'])) ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($e['status']) ?>"><?= htmlspecialchars($e['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
