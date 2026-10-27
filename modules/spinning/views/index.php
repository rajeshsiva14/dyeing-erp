<?php
/**
 * Spinning Mill Operations Hub - KARCEM Textile ERP
 * File: modules/spinning/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Spinning Mill Operations';
$activeModule = 'spinning';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$fibreLots = Database::fetchAll("SELECT fl.*, s.name as supplier_name FROM fibre_lots fl JOIN suppliers s ON fl.supplier_id = s.id ORDER BY fl.id DESC");
$spinningBatches = Database::fetchAll("SELECT * FROM spinning_batches ORDER BY id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-bullseye"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Spinning Mill & Fibre Processing</h1>
          <p class="text-secondary small mb-0">Cotton bale inward, HVI testing (Micronaire, Staple length, Trash %), mixing plans, Blowroom &rarr; Carding &rarr; Combing &rarr; Ring Frame &rarr; Autoconer winding into hosiery yarn cones.</p>
        </div>
      </div>
    </section>

    <!-- Cotton Bales & Fibre Inventory -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-box text-primary me-2"></i>Cotton Bale Inward & HVI Quality Register</h2>
        <span class="badge bg-light text-dark border">Raw Cotton Godown</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Lot Number</th>
              <th>Cotton Variety</th>
              <th>Supplier</th>
              <th>Bale Count</th>
              <th>Total Net Weight</th>
              <th>Micronaire</th>
              <th>Staple Length</th>
              <th>Trash %</th>
              <th>Moisture %</th>
            </tr>
          </thead>
          <tbody class="small font-monospace">
            <?php foreach ($fibreLots as $fl): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($fl['lot_no']) ?></td>
                <td class="font-sans-serif fw-bold text-dark"><?= htmlspecialchars($fl['cotton_type']) ?></td>
                <td class="font-sans-serif"><?= htmlspecialchars($fl['supplier_name']) ?></td>
                <td><?= $fl['bale_count'] ?> Bales</td>
                <td class="fw-bold"><?= number_format($fl['total_weight_kg'], 1) ?> Kg</td>
                <td><span class="badge bg-success-subtle text-success"><?= $fl['micronaire'] ?> &mu;g/in</span></td>
                <td><?= $fl['staple_length_mm'] ?> mm</td>
                <td><?= $fl['trash_pct'] ?>%</td>
                <td><?= $fl['moisture_pct'] ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Spinning Process Stages Grid -->
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-3">
            <span class="badge bg-secondary-subtle text-secondary mb-2">Stage 01</span>
            <h6 class="fw-bold text-dark">Mixing & Blowroom</h6>
            <p class="small text-secondary mb-2">Fibre blend percentages, recipe mixing, bale plucking, and coarse trash extraction.</p>
            <div class="small fw-semibold text-dark">Waste: 2.8% Blowroom Droppings</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-3">
            <span class="badge bg-secondary-subtle text-secondary mb-2">Stage 02</span>
            <h6 class="fw-bold text-dark">Carding & Combing</h6>
            <p class="small text-secondary mb-2">Rieter C70 high-production carding sliver, short-fibre extraction (Comber noil 16%).</p>
            <div class="small fw-semibold text-dark">Sliver: 4.5 ktex uniform delivery</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-3">
            <span class="badge bg-primary text-white mb-2">Stage 03</span>
            <h6 class="fw-bold text-dark">Ring Frame & Autoconer</h6>
            <p class="small text-secondary mb-2">30s Ne Combed Hosiery spinning, spindle speeds 18,500 RPM, electronic yarn clearing.</p>
            <div class="small fw-semibold text-success">Output: 1.89 Kg / cone packed</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
