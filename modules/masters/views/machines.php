<?php
/**
 * Machine & Work Centre Master - KARCEM Textile ERP
 * File: modules/masters/views/machines.php
 */

use Core\Database\Database;
use Core\Support\Url;

$pageTitle = 'Machine & Work Centre Master';
$activeModule = 'masters';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$machines = Database::fetchAll("SELECT * FROM machines ORDER BY code ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/masters') ?>">Masters</a></li>
            <li class="breadcrumb-item active">Machines</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Machine & Work Centre Master</h1>
        <p class="text-secondary small mb-0">Production machinery across Spinning, Knitting, Soft Flow Dyeing, Stenters, Compactors, and Sewing lines with capacity and costing rates.</p>
      </div>
    </div>

    <!-- Machines Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-cpu text-primary me-2"></i>Plant Machinery & Capacity Specs</h2>
        <span class="small text-secondary"><?= count($machines) ?> Machines Installed</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Machine Code</th>
              <th>Name & Process Type</th>
              <th>Manufacturer & Model</th>
              <th>Capacity</th>
              <th>Liquor Ratio / Setting</th>
              <th class="text-end">Hourly Cost (₹)</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($machines as $m): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($m['code']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                  <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;"><?= strtoupper(str_replace('_', ' ', $m['process_type'])) ?></span>
                </td>
                <td>
                  <div class="text-dark"><?= htmlspecialchars($m['manufacturer'] ?? '-') ?></div>
                  <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($m['model_number'] ?? '') ?> (Year: <?= $m['year_of_make'] ?>)</span>
                </td>
                <td class="fw-bold font-monospace"><?= number_format($m['capacity'], 0) ?> <?= htmlspecialchars($m['capacity_uom']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['liquor_ratio'] ?? 'N/A') ?></span></td>
                <td class="text-end fw-bold font-monospace">₹<?= number_format($m['hourly_operating_cost'], 2) ?>/hr</td>
                <td><span class="badge-status status-<?= htmlspecialchars($m['current_status']) ?>"><?= htmlspecialchars($m['current_status']) ?></span></td>
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
