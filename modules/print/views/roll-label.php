<?php
/**
 * L01 - Thermal Fabric Roll Barcode Label (Print Engine)
 * File: modules/print/views/roll-label.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\Barcode;

$rollId = (int)($_GET['id'] ?? 1);
$roll = Database::fetch("SELECT r.*, p.name as product_name, p.sku, l.lot_number, l.origin_ref FROM inventory_rolls r JOIN products p ON r.product_id = p.id JOIN inventory_lots l ON r.lot_id = l.id WHERE r.id = ?", [$rollId]);
if (!$roll) {
    die("Roll not found");
}
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>L01 ROLL LABEL - <?= htmlspecialchars($roll['roll_number']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; padding: 20px 0; font-family: 'Segoe UI', system-ui, sans-serif; }
    .label-box {
      width: 100mm;
      height: 75mm;
      margin: 0 auto;
      background: #ffffff;
      border: 2px solid #111827;
      padding: 3mm 4mm;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    @media print {
      body { background: #fff; padding: 0; }
      .label-box { box-shadow: none; margin: 0; width: 100mm; height: 75mm; border: 1px solid #000; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 100mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm">
    <div class="small fw-bold">L01 — Thermal Roll Label</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print (Thermal)</button>
      <a href="<?= Url::to('/inventory/rolls') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
    </div>
  </div>
</div>

<div class="label-box">
  <div class="d-flex justify-content-between align-items-center border-bottom pb-1">
    <div>
      <div class="fw-bold text-uppercase" style="font-size: 0.85rem;"><?= htmlspecialchars($company['name']) ?></div>
      <div class="text-muted" style="font-size: 0.65rem;">TIRUPUR MILL · TN 33</div>
    </div>
    <div class="text-end">
      <span class="badge bg-dark text-white" style="font-size: 0.8rem;">GRADE <?= htmlspecialchars($roll['quality_grade']) ?></span>
    </div>
  </div>

  <div class="my-1">
    <div class="fw-bold text-dark text-truncate" style="font-size: 0.8rem;"><?= htmlspecialchars($roll['product_name']) ?></div>
    <div class="row g-1 small text-dark mt-1" style="font-size: 0.72rem;">
      <div class="col-6"><strong>Lot:</strong> <?= htmlspecialchars($roll['lot_number']) ?></div>
      <div class="col-6"><strong>Shade:</strong> <?= htmlspecialchars($roll['shade_group'] ?? 'Band A') ?></div>
      <div class="col-6"><strong>GSM:</strong> <?= $roll['gsm'] ?> &plusmn; 3%</div>
      <div class="col-6"><strong>Width:</strong> <?= $roll['width_inches'] ?>" Dia</div>
      <div class="col-6"><strong>Length:</strong> <?= number_format($roll['length_meters'], 1) ?> Mtrs</div>
      <div class="col-6"><strong>Date:</strong> <?= date('d/m/Y') ?></div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center bg-light p-1 border rounded">
    <div class="text-center" style="width: 50%;">
      <span class="text-secondary" style="font-size: 0.65rem; display: block;">NET WEIGHT</span>
      <span class="fw-bold fs-5 text-dark font-monospace"><?= number_format($roll['weight_kg'], 2) ?> <small class="fs-6">Kg</small></span>
    </div>
    <div class="text-center border-start" style="width: 50%;">
      <span class="text-secondary" style="font-size: 0.65rem; display: block;">GROSS WEIGHT</span>
      <span class="fw-bold fs-5 text-dark font-monospace"><?= number_format($roll['weight_kg'] + 0.35, 2) ?> <small class="fs-6">Kg</small></span>
    </div>
  </div>

  <div class="text-center pt-1">
    <div style="width: 180px; margin: 0 auto;">
      <?= Barcode::svg($roll['barcode'], 22, 1) ?>
    </div>
  </div>
</div>
</body>
</html>
