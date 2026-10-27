<?php
/**
 * Quality Assurance & Testing Lab Hub - KARCEM Textile ERP
 * File: modules/quality/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Quality Assurance Hub';
$activeModule = 'quality';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$inspectionsCount = (int)Database::fetch("SELECT COUNT(*) FROM fabric_inspections")['COUNT(*)'];
$ncrs = Database::fetchAll("SELECT * FROM nonconformance_reports ORDER BY id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-success-subtle text-success p-3 rounded-circle fs-3"><i class="bi bi-shield-check"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Quality Assurance, Testing Lab & Compliance</h1>
          <p class="text-secondary small mb-0">Physical testing lab, ASTM D5430 4-point fabric inspection, ISO color fastness ratings, Non-Conformance Reports (NCR), CAPA tracking, and instrument calibrations.</p>
        </div>
      </div>
    </section>

    <!-- Cards Grid -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success text-white">ASTM D5430</span>
                <i class="bi bi-award fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">4-Point Fabric Inspection</h5>
              <p class="text-secondary small">Penalty points calculation per 100 sq yds, Grade A/B/C assignment, and printable P46 inspection certificate.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $inspectionsCount ?> Rolls Certified</span>
              <a href="<?= Url::to('/dyeing/inspection') ?>" class="btn btn-sm btn-dark">Open 4-Point &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-danger-subtle text-danger">Corrective Action</span>
                <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
              </div>
              <h5 class="fw-bold text-dark">Non-Conformance Reports (NCR)</h5>
              <p class="text-secondary small">Root cause analysis (5-Why), corrective & preventive actions (CAPA), and closure approval.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">0 Open Defect Holds</span>
              <a href="<?= Url::to('/dyeing/inspection') ?>" class="btn btn-sm btn-outline-dark">NCR Register</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Fastness Lab</span>
                <i class="bi bi-droplet-half fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Color Fastness & Testing</h5>
              <p class="text-secondary small">Wash fastness, dry/wet rub crockmeter, perspiration, pilling, tensile strength, and shrinkage %.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="badge bg-success text-white">ISO 105 Compliant</span>
              <a href="<?= Url::to('/dyeing/inspection') ?>" class="btn btn-sm btn-outline-dark">Test Lab</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
