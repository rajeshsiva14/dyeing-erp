<?php
/**
 * Dyeing Module Masters — Section 4 Configuration Master Forms
 * Maintain Fabrics, Dyeing Machines/Vessels, Dyes & Auxiliaries, Shades, Defects and Reason Codes
 * File: modules/dyeing/views/masters.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;

$pageTitle = 'Dyeing Module Masters Configuration';
$activeModule = 'masters';

$tab = $_GET['tab'] ?? 'shades';

// Handle adding new master item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    if ($_POST['action'] === 'add_shade') {
        $code = trim($_POST['shade_code'] ?? '');
        $name = trim($_POST['shade_name'] ?? '');
        $pantone = trim($_POST['pantone_ref'] ?? '');
        $hex = trim($_POST['hex_color'] ?? '#1e3a8a');
        $family = $_POST['shade_family'] ?? 'Navy/Blue';
        $tol = (float)($_POST['delta_e_tolerance'] ?? 0.80);
        $dClass = $_POST['dye_class'] ?? 'reactive';

        Database::insert('dyeing_shades', [
            'company_id'        => 1,
            'shade_code'        => $code,
            'shade_name'        => $name,
            'pantone_ref'       => $pantone,
            'hex_color'         => $hex,
            'shade_family'      => $family,
            'delta_e_tolerance' => $tol,
            'dye_class'         => $dClass
        ]);
        header('Location: ' . Url::to('/dyeing/masters?tab=shades&success=1'));
        exit;
    }

    if ($_POST['action'] === 'add_defect') {
        $code = trim($_POST['defect_code'] ?? '');
        $name = trim($_POST['defect_name'] ?? '');
        $cat = $_POST['category'] ?? 'dyeing_process';
        $sev = $_POST['severity'] ?? 'major';
        $pts = (int)($_POST['four_point_penalty'] ?? 2);

        Database::insert('dyeing_defects', [
            'defect_code'        => $code,
            'defect_name'        => $name,
            'category'           => $cat,
            'severity'           => $sev,
            'four_point_penalty' => $pts
        ]);
        header('Location: ' . Url::to('/dyeing/masters?tab=defects&success=1'));
        exit;
    }
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$shades = Database::fetchAll("SELECT * FROM dyeing_shades ORDER BY id DESC");
$defects = Database::fetchAll("SELECT * FROM dyeing_defects ORDER BY id ASC");
$reasons = Database::fetchAll("SELECT * FROM dyeing_reasons ORDER BY id ASC");
$vessels = Database::fetchAll("SELECT * FROM machines WHERE process_type = 'soft_flow_dyeing' ORDER BY code ASC");
$chemicals = Database::fetchAll("SELECT * FROM products WHERE category = 'dyes_chemical' ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Masters Configuration</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Dyeing Module Masters Configuration</h1>
        <p class="text-secondary small mb-0">Master definitions for Color Shades, Dyeing Vessels, Dyes/Auxiliaries, 4-Point Defects, and Process Reasons.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#newShadeModal">
          <i class="bi bi-plus-lg me-1"></i> Add Shade Standard
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 small border-bottom pb-3">
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'shades' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=shades">
          <i class="bi bi-palette me-1"></i> 1. Color Shades & Pantone Master
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'vessels' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=vessels">
          <i class="bi bi-hdd-rack me-1"></i> 2. Soft Flow Dyeing Vessels
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'chemicals' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=chemicals">
          <i class="bi bi-eyedropper me-1"></i> 3. Dyes & Auxiliary Chemicals
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'defects' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=defects">
          <i class="bi bi-shield-x me-1"></i> 4. 4-Point Defect Codes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $tab === 'reasons' ? 'active bg-dark' : 'text-dark bg-white border' ?>" href="?tab=reasons">
          <i class="bi bi-list-check me-1"></i> 5. Wastage & Reprocess Reasons
        </a>
      </li>
    </ul>

    <?php if ($tab === 'shades'): ?>
      <!-- Shades Master Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-palette text-primary me-2"></i>Approved Color Palette & Shade Standards</h2>
          <span class="small text-secondary"><?= count($shades) ?> Shades Registered</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Swatch</th>
                <th>Shade Code</th>
                <th>Shade Name</th>
                <th>Pantone TCX Ref</th>
                <th>Shade Family</th>
                <th>Dye Class</th>
                <th class="text-center">ΔE Tolerance</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shades as $sh): ?>
                <tr>
                  <td>
                    <span class="d-inline-block rounded border shadow-sm" style="width: 24px; height: 24px; background-color: <?= htmlspecialchars($sh['hex_color']) ?>;"></span>
                  </td>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($sh['shade_code']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($sh['shade_name']) ?></td>
                  <td><code><?= htmlspecialchars($sh['pantone_ref'] ?: 'N/A') ?></code></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sh['shade_family']) ?></span></td>
                  <td><span class="badge bg-dark text-uppercase"><?= htmlspecialchars($sh['dye_class']) ?></span></td>
                  <td class="text-center font-monospace">&le; <?= number_format($sh['delta_e_tolerance'], 2) ?></td>
                  <td><span class="badge bg-success-subtle text-success">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'vessels'): ?>
      <!-- Vessels Master Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-hdd-rack text-primary me-2"></i>Soft Flow Dyeing Vessels Master</h2>
          <span class="small text-secondary"><?= count($vessels) ?> Vessels Configured</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Vessel Code</th>
                <th>Machine Name / Model</th>
                <th class="text-end">Rated Capacity (Kg)</th>
                <th class="text-center">Liquor Ratio</th>
                <th class="text-end">Hourly Cost (₹)</th>
                <th>Current Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vessels as $v): ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($v['code']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($v['name']) ?></td>
                  <td class="text-end font-monospace fw-bold"><?= number_format($v['capacity'], 0) ?> Kg</td>
                  <td class="text-center font-monospace"><span class="badge bg-light text-dark border"><?= htmlspecialchars($v['liquor_ratio'] ?? '1:6') ?></span></td>
                  <td class="text-end font-monospace">₹<?= number_format($v['hourly_operating_cost'], 0) ?></td>
                  <td>
                    <span class="badge bg-<?= $v['current_status'] === 'running' ? 'success' : 'secondary' ?>">
                      <?= strtoupper($v['current_status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'chemicals'): ?>
      <!-- Chemicals Master Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-eyedropper text-primary me-2"></i>Dyestuffs & Auxiliary Chemicals Store Master</h2>
          <span class="small text-secondary"><?= count($chemicals) ?> Store Items</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>SKU</th>
                <th>Chemical / Dye Name</th>
                <th>Storage Category</th>
                <th>Base UOM</th>
                <th>HSN / SAC</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($chemicals as $ch): ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($ch['sku']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($ch['name']) ?></td>
                  <td><span class="badge bg-light text-dark border">Color Kitchen Store</span></td>
                  <td>Kg</td>
                  <td><code>3204</code></td>
                  <td><span class="badge bg-success-subtle text-success">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'defects'): ?>
      <!-- Defects Master Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-shield-x text-primary me-2"></i>ASTM D5430 4-Point Fabric Defect Penalties Master</h2>
          <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#newDefectModal">
            <i class="bi bi-plus me-1"></i> Add Defect
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Defect Code</th>
                <th>Defect Name</th>
                <th>Category</th>
                <th>Severity</th>
                <th class="text-center">4-Point Penalty</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($defects as $df): ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($df['defect_code']) ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($df['defect_name']) ?></td>
                  <td><span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $df['category']) ?></span></td>
                  <td>
                    <span class="badge <?= $df['severity'] === 'critical' ? 'bg-danger' : ($df['severity'] === 'major' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                      <?= strtoupper($df['severity']) ?>
                    </span>
                  </td>
                  <td class="text-center font-monospace fw-bold fs-6 text-danger"><?= $df['four_point_penalty'] ?> Pts</td>
                  <td><span class="badge bg-success-subtle text-success">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'reasons'): ?>
      <!-- Reasons Master Table -->
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-list-check text-primary me-2"></i>Process Wastage, Downtime & Reprocess Reasons</h2>
          <span class="small text-secondary"><?= count($reasons) ?> Reasons</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Reason Code</th>
                <th>Type</th>
                <th>Reason Name / Description</th>
                <th>Department</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reasons as $rs): ?>
                <tr>
                  <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($rs['reason_code']) ?></td>
                  <td>
                    <span class="badge bg-<?= $rs['reason_type'] === 'reprocess' ? 'danger' : ($rs['reason_type'] === 'wastage' ? 'warning text-dark' : 'secondary') ?> text-uppercase">
                      <?= htmlspecialchars($rs['reason_type']) ?>
                    </span>
                  </td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($rs['reason_name']) ?></td>
                  <td><?= htmlspecialchars($rs['department']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- New Shade Modal -->
<div class="modal fade" id="newShadeModal" tabindex="-1" aria-labelledby="newShadeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="add_shade">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newShadeModalLabel"><i class="bi bi-palette text-primary me-2"></i>Add Color Shade Standard</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Shade Code *</label>
              <input type="text" name="shade_code" class="form-control form-control-sm font-monospace" placeholder="e.g. SH-RED-07" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Shade Name *</label>
              <input type="text" name="shade_name" class="form-control form-control-sm" placeholder="e.g. Ruby Crimson" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Pantone TCX Ref</label>
              <input type="text" name="pantone_ref" class="form-control form-control-sm" placeholder="19-1762 TCX">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Hex Color Swatch</label>
              <input type="color" name="hex_color" class="form-control form-control-color w-100" value="#991b1b">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Shade Family</label>
              <select name="shade_family" class="form-select form-select-sm">
                <option value="Navy/Blue">Navy/Blue</option>
                <option value="Black/Grey">Black/Grey</option>
                <option value="Red/Maroon" selected>Red/Maroon</option>
                <option value="Green/Olive">Green/Olive</option>
                <option value="Yellow/Orange">Yellow/Orange</option>
                <option value="White/Bleach">White/Bleach</option>
                <option value="Pastel/Light">Pastel/Light</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">ΔE Tolerance</label>
              <input type="number" step="0.05" name="delta_e_tolerance" class="form-control form-control-sm" value="0.80">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Save Shade</button>
        </div>
      </form>
<!-- New Defect Modal -->
<div class="modal fade" id="newDefectModal" tabindex="-1" aria-labelledby="newDefectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="action" value="add_defect">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

        <div class="modal-header bg-dark text-white p-3">
          <h5 class="modal-title fs-6 fw-bold" id="newDefectModalLabel"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Add Fabric / Dyeing Defect</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 small">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Defect Code *</label>
              <input type="text" name="defect_code" class="form-control form-control-sm font-monospace" placeholder="e.g. DEF-DY-09" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Defect Name *</label>
              <input type="text" name="defect_name" class="form-control form-control-sm" placeholder="e.g. Uneven Dyeing / Patchiness" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Category *</label>
              <select name="category" class="form-select form-select-sm" required>
                <option value="fabric_construction">Fabric Construction / Grey Flaw</option>
                <option value="dyeing_process" selected>Dyeing Process Flaw</option>
                <option value="finishing">Finishing / Stenter Flaw</option>
                <option value="stain_contamination">Stain / Oil Contamination</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Severity *</label>
              <select name="severity" class="form-select form-select-sm" required>
                <option value="minor">Minor</option>
                <option value="major" selected>Major</option>
                <option value="critical">Critical</option>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-bold">ASTM D5430 4-Point Penalty Points *</label>
              <select name="four_point_penalty" class="form-select form-select-sm" required>
                <option value="1">1 Point (Defect up to 3 inches)</option>
                <option value="2" selected>2 Points (Defect 3 to 6 inches)</option>
                <option value="3">3 Points (Defect 6 to 9 inches)</option>
                <option value="4">4 Points (Defect over 9 inches or Hole/Cut)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top p-3 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-check-lg me-1"></i> Save Defect Standard</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
