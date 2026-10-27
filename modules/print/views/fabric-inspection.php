<?php
/**
 * P46 - ASTM 4-Point System Fabric Inspection Certificate (Print Engine)
 * File: modules/print/views/fabric-inspection.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\Barcode;

$inspId = (int)($_GET['id'] ?? 1);
$insp = Database::fetch("SELECT fi.*, r.roll_number, r.barcode, r.weight_kg, p.name as product_name, p.sku, u.full_name as inspector_name FROM fabric_inspections fi JOIN inventory_rolls r ON fi.roll_id = r.id JOIN products p ON r.product_id = p.id JOIN users u ON fi.inspector_id = u.id WHERE fi.id = ?", [$inspId]);
if (!$insp) {
    die("Inspection record not found");
}

$defects = Database::fetchAll("SELECT * FROM fabric_inspection_defects WHERE inspection_id = ?", [$inspId]);
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>FABRIC INSPECTION CERTIFICATE - <?= htmlspecialchars($insp['inspection_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px 0; }
    .print-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #ffffff; padding: 15mm; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .insp-table th, .insp-table td { border: 1px solid #111827 !important; padding: 6px 8px; }
    @media print { body { background: #fff; padding: 0; } .print-sheet { box-shadow: none; width: 100%; padding: 10mm; } .no-print { display: none !important; } }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="fw-bold">P46 — ASTM 4-Point System Fabric Quality Certificate</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Certificate</button>
      <a href="<?= Url::to('/dyeing/inspection') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
    </div>
  </div>
</div>

<div class="print-sheet">
  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
    <div>
      <h4 class="fw-bold text-dark text-uppercase mb-0"><?= htmlspecialchars($company['name']) ?></h4>
      <div class="small text-muted">Central Quality Assurance & Physical Testing Laboratory · Tirupur</div>
    </div>
    <div class="text-end">
      <span class="badge bg-success text-white px-3 py-1 fs-6">P46 QUALITY CERTIFICATE</span>
      <div class="font-monospace fw-bold fs-5 text-primary mt-1"><?= htmlspecialchars($insp['inspection_no']) ?></div>
      <div class="small text-muted">Date: <?= date('d M Y', strtotime($insp['inspection_date'])) ?></div>
    </div>
  </div>

  <div class="row g-2 border p-2 mb-3 bg-light small">
    <div class="col-8">
      <strong>Fabric Product:</strong> <?= htmlspecialchars($insp['product_name']) ?><br>
      <strong>Roll Barcode:</strong> <span class="font-monospace fw-bold"><?= htmlspecialchars($insp['roll_number']) ?></span> (<?= number_format($insp['weight_kg'], 1) ?> Kg)<br>
      <strong>Dimensions:</strong> <?= number_format($insp['inspected_meters'], 1) ?> Meters Length &times; <?= $insp['width_inches'] ?>" Width<br>
      <strong>Weight Density (GSM):</strong> <?= $insp['gsm'] ?> GSM (Tolerance &plusmn; 3%)<br>
    </div>
    <div class="col-4 text-center">
      <div class="p-2 border bg-white rounded">
        <div class="small text-secondary fw-bold">CERTIFIED GRADE</div>
        <div class="display-6 fw-bold text-<?= $insp['quality_grade'] === 'A' ? 'success' : 'primary' ?>">
          GRADE <?= htmlspecialchars($insp['quality_grade']) ?>
        </div>
        <div class="small text-muted"><?= number_format($insp['points_per_100_sq_yd'], 2) ?> pts / 100 sq yd</div>
      </div>
    </div>
  </div>

  <h6 class="fw-bold text-dark small text-uppercase mb-1"><i class="bi bi-list-check me-1"></i>Defect Mapping & Penalty Points (ASTM D5430)</h6>
  <table class="table insp-table w-100 small mb-3">
    <thead class="table-light">
      <tr>
        <th>Meter Position</th>
        <th>Defect Classification</th>
        <th>Defect Size (Inches)</th>
        <th class="text-end">Penalty Points Assigned</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($defects)): ?>
        <?php foreach ($defects as $d): ?>
          <tr>
            <td class="font-monospace"><?= number_format($d['meter_position'], 2) ?> m</td>
            <td class="text-capitalize fw-semibold"><?= htmlspecialchars(str_replace('_', ' ', $d['defect_type'])) ?></td>
            <td><?= $d['defect_size_inches'] ?>"</td>
            <td class="text-end font-monospace fw-bold text-danger"><?= $d['points_assigned'] ?> pts</td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4" class="text-center text-muted">No major defects found across roll length.</td>
        </tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="table-light">
        <th colspan="3" class="text-end fw-bold">Total Penalty Points:</th>
        <th class="text-end font-monospace fw-bold"><?= $insp['total_penalty_points'] ?> pts</th>
      </tr>
      <tr>
        <th colspan="3" class="text-end fw-bold">ASTM Points per 100 Sq Yd:</th>
        <th class="text-end font-monospace fw-bold text-primary"><?= number_format($insp['points_per_100_sq_yd'], 2) ?></th>
      </tr>
    </tfoot>
  </table>

  <!-- Fastness & Lab Results -->
  <h6 class="fw-bold text-dark small text-uppercase mb-1"><i class="bi bi-eyedropper me-1"></i>Color Fastness & Dimensional Stability Tests</h6>
  <div class="row g-2 border p-2 mb-4 bg-light small text-center">
    <div class="col-3">
      <div class="text-secondary">Washing Fastness (ISO 105 C06)</div>
      <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($insp['fastness_wash'] ?? '4-5') ?></div>
    </div>
    <div class="col-3">
      <div class="text-secondary">Dry Rubbing Fastness</div>
      <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($insp['fastness_rub_dry'] ?? '4-5') ?></div>
    </div>
    <div class="col-3">
      <div class="text-secondary">Wet Rubbing Fastness</div>
      <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($insp['fastness_rub_wet'] ?? '4') ?></div>
    </div>
    <div class="col-3">
      <div class="text-secondary">Inspection Disposition</div>
      <div class="fw-bold text-success fs-6 text-uppercase"><?= htmlspecialchars($insp['disposition']) ?></div>
    </div>
  </div>

  <div class="row text-center mt-5 small">
    <div class="col-6">
      <div class="border-top pt-2 mx-4 text-muted">Inspected by: <?= htmlspecialchars($insp['inspector_name']) ?></div>
    </div>
    <div class="col-6">
      <div class="border-top pt-2 mx-4 fw-bold">QA Manager Approval & Stamp</div>
    </div>
  </div>
</div>
</body>
</html>
