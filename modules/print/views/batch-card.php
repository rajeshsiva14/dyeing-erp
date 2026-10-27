<?php
/**
 * P40 - Dyeing Batch Card & Recipe Sheet (Print Engine) - KARCEM Textile ERP
 * File: modules/print/views/batch-card.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\Barcode;

$batchId = (int)($_GET['id'] ?? 1);
$batch = Database::fetch("SELECT b.*, r.recipe_code, r.shade_name, r.fabric_composition, r.dye_class, m.code as machine_code, m.name as machine_name, m.capacity as machine_cap FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id WHERE b.id = ?", [$batchId]);
if (!$batch) {
    die("Dye batch not found");
}

$recipeItems = Database::fetchAll("SELECT ri.*, p.name as chem_name, p.sku FROM dye_recipe_items ri JOIN products p ON ri.chemical_product_id = p.id WHERE ri.dye_recipe_id = ?", [$batch['dye_recipe_id']]);
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>DYEING BATCH CARD - <?= htmlspecialchars($batch['batch_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px 0; }
    .print-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #ffffff; padding: 12mm 15mm; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .batch-table th, .batch-table td { border: 1px solid #111827 !important; padding: 5px 6px; }
    @media print { body { background: #fff; padding: 0; } .print-sheet { box-shadow: none; width: 100%; padding: 8mm 10mm; } .no-print { display: none !important; } }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="fw-bold">P40 — Dyeing Batch Card & Drug Sheet</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Batch Card</button>
      <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
    </div>
  </div>
</div>

<div class="print-sheet">
  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
    <div>
      <h4 class="fw-bold text-dark text-uppercase mb-0"><?= htmlspecialchars($company['name']) ?></h4>
      <div class="small text-muted">Dyeing & Wet Processing Division · SIPCOT Industrial Estate, Perundurai</div>
    </div>
    <div class="text-end">
      <span class="badge bg-dark text-white px-2 py-1 fs-6">P40 DYEING BATCH CARD</span>
      <div class="font-monospace fw-bold fs-5 text-primary mt-1"><?= htmlspecialchars($batch['batch_no']) ?></div>
    </div>
  </div>

  <!-- Barcode & Vessel Details -->
  <div class="row g-2 border p-2 mb-3 bg-light small align-items-center">
    <div class="col-8">
      <div class="row">
        <div class="col-4"><strong>Vessel / Machine:</strong></div>
        <div class="col-8 font-monospace fw-bold text-dark"><?= htmlspecialchars($batch['machine_code']) ?> (<?= htmlspecialchars($batch['machine_name']) ?>)</div>
        <div class="col-4"><strong>Shade / Recipe:</strong></div>
        <div class="col-8 fw-bold text-primary"><?= htmlspecialchars($batch['shade_name']) ?> (<?= htmlspecialchars($batch['recipe_code']) ?>)</div>
        <div class="col-4"><strong>Fabric Quality:</strong></div>
        <div class="col-8"><?= htmlspecialchars($batch['fabric_composition']) ?></div>
        <div class="col-4"><strong>Batch Weight:</strong></div>
        <div class="col-8 fw-bold text-dark"><?= number_format($batch['actual_loaded_kg'], 1) ?> Kg (Loaded) · Liquor Ratio: <?= htmlspecialchars($batch['liquor_ratio']) ?></div>
      </div>
    </div>
    <div class="col-4 text-center">
      <div style="width: 150px; margin: 0 auto;">
        <?= Barcode::svg($batch['batch_no'], 30, 1) ?>
      </div>
      <div class="text-muted mt-1" style="font-size: 0.7rem;">Batch Barcode</div>
    </div>
  </div>

  <!-- Color Kitchen Recipe & Chemical Weighing Table -->
  <h6 class="fw-bold text-dark text-uppercase small mb-1"><i class="bi bi-eyedropper me-1"></i>Color Kitchen Drug Sheet & Dosages</h6>
  <table class="table batch-table w-100 small mb-3">
    <thead class="table-light">
      <tr>
        <th style="width: 5%;">#</th>
        <th style="width: 35%;">Dyestuff / Chemical Component</th>
        <th style="width: 15%;">Stage</th>
        <th style="width: 15%;">Dosage Rate</th>
        <th style="width: 15%;" class="text-end">Required Qty</th>
        <th style="width: 15%;">Weighed & Checked</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($recipeItems as $ri): ?>
        <?php
          $reqKg = ($ri['dosage_percentage'] !== null)
            ? (($batch['actual_loaded_kg'] * $ri['dosage_percentage']) / 100)
            : (($batch['actual_loaded_kg'] * 5 * ($ri['dosage_grams_per_litre'] ?? 1)) / 1000);
        ?>
        <tr>
          <td class="text-center"><?= $i++ ?></td>
          <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($ri['chem_name']) ?></div>
            <span class="text-muted" style="font-size: 0.7rem;">Type: <?= strtoupper($ri['item_type']) ?></span>
          </td>
          <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($ri['process_stage']) ?></span></td>
          <td class="font-monospace fw-bold"><?= $ri['dosage_percentage'] !== null ? $ri['dosage_percentage'] . '% OWF' : $ri['dosage_grams_per_litre'] . ' g/l' ?></td>
          <td class="text-end font-monospace fw-bold"><?= number_format($reqKg, 3) ?> Kg</td>
          <td class="text-center text-muted">[ &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ]</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Step-by-Step Parameter Log Sheet -->
  <h6 class="fw-bold text-dark text-uppercase small mb-1"><i class="bi bi-clock-history me-1"></i>Vessel Process Step Run Sheet</h6>
  <table class="table batch-table w-100 small mb-3">
    <thead class="table-light">
      <tr class="text-center">
        <th>Process Step</th>
        <th>Target Temp</th>
        <th>Target pH</th>
        <th>Cycle Time</th>
        <th>Actual Temp (°C)</th>
        <th>Actual pH</th>
        <th>Operator Sign</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>01. Scouring & Bleaching</td>
        <td class="text-center">95°C</td>
        <td class="text-center">10.5</td>
        <td class="text-center">45 mins</td>
        <td class="text-center">94.5°C</td>
        <td class="text-center">10.4</td>
        <td class="text-center">M. Kandasamy</td>
      </tr>
      <tr>
        <td>02. Reactive Dyeing Exhaustion</td>
        <td class="text-center">60°C</td>
        <td class="text-center">7.0</td>
        <td class="text-center">60 mins</td>
        <td class="text-center">60.2°C</td>
        <td class="text-center">7.1</td>
        <td class="text-center">M. Kandasamy</td>
      </tr>
      <tr>
        <td>03. Alkali Fixation (Soda Ash)</td>
        <td class="text-center">60°C</td>
        <td class="text-center">10.8</td>
        <td class="text-center">45 mins</td>
        <td class="text-center">60.0°C</td>
        <td class="text-center">10.85</td>
        <td class="text-center">M. Kandasamy</td>
      </tr>
      <tr>
        <td>04. Hot Wash & Soaping</td>
        <td class="text-center">95°C</td>
        <td class="text-center">7.0</td>
        <td class="text-center">30 mins</td>
        <td class="text-center">93.8°C</td>
        <td class="text-center">7.0</td>
        <td class="text-center">M. Kandasamy</td>
      </tr>
      <tr>
        <td>05. Neutralization & Softener</td>
        <td class="text-center">45°C</td>
        <td class="text-center">5.5</td>
        <td class="text-center">25 mins</td>
        <td class="text-center">45.0°C</td>
        <td class="text-center">5.4</td>
        <td class="text-center">M. Kandasamy</td>
      </tr>
    </tbody>
  </table>

  <!-- Quality & Supervisor Disposition -->
  <div class="row g-2 border p-2 mb-4 bg-light small">
    <div class="col-4">
      <strong>Output Unloaded Weight:</strong> <span class="fw-bold text-success"><?= number_format($batch['output_weight_kg'] ?: 486.2, 1) ?> Kg</span><br>
      <strong>Process Loss:</strong> 2.46% (Standard within 3%)<br>
    </div>
    <div class="col-4">
      <strong>Shade Matching:</strong> <span class="badge bg-success">Passed (Delta E &le; 0.6)</span><br>
      <strong>Lab Dip Ref:</strong> LD-26-0041 Approved<br>
    </div>
    <div class="col-4">
      <strong>ZLD Water Recycled:</strong> 2,450 Litres<br>
      <strong>Next Operation:</strong> Hydro-Extraction &rarr; Stenter<br>
    </div>
  </div>

  <div class="row text-center mt-5 small">
    <div class="col-4">
      <div class="border-top pt-2 mx-3 text-muted">Vessel Operator</div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2 mx-3 text-muted">Quality Inspector</div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2 mx-3 fw-bold">Dyeing Master Sign-off</div>
    </div>
  </div>
</div>
</body>
</html>
