<?php
/**
 * L01 - Thermal Fabric Roll Barcode Label (Print Engine)
 * Direct Browser Thermal (100mm x 75mm) + TSPL/ESC-POS Hardware Stream Generator
 * File: modules/print/views/roll-label.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\Barcode;

$rollId = (int)($_GET['id'] ?? 0);
$rollNo = trim($_GET['roll'] ?? '');
$lotNo = trim($_GET['lot'] ?? '');

$rollSelect = "
    SELECT r.*, p.name as product_name, p.sku, l.lot_number, l.origin_ref
    FROM inventory_rolls r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN inventory_lots l ON r.lot_id = l.id";

$roll = null;
$isGreyLot = false;
if ($rollId > 0) {
    $roll = Database::fetch($rollSelect . " WHERE r.id = ?", [$rollId]);
} elseif ($rollNo !== '') {
    $roll = Database::fetch($rollSelect . " WHERE r.roll_number = ?", [$rollNo]);
} elseif ($lotNo !== '') {
    // Finished-roll lot first, then a grey fabric inward lot (grey-inward register links here)
    $roll = Database::fetch($rollSelect . " WHERE l.lot_number = ? ORDER BY r.id ASC LIMIT 1", [$lotNo]);
    if (!$roll) {
        $grey = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE grey_lot_no = ? ORDER BY id DESC LIMIT 1", [$lotNo]);
        if ($grey) {
            $isGreyLot = true;
            $roll = [
                'id'            => 0,
                'roll_number'   => $grey['grey_lot_no'],
                'barcode'       => $grey['grey_lot_no'],
                'product_name'  => $grey['fabric_description'],
                'lot_number'    => $grey['grey_lot_no'],
                'weight_kg'     => $grey['net_weight_kg'],
                'length_meters' => null,
                'width_inches'  => $grey['width_inches'],
                'gsm'           => $grey['gsm'],
                'quality_grade' => null,
                'shade_group'   => null,
                'received_rolls' => $grey['received_rolls'],
                'supplier_name' => $grey['supplier_name'],
                'created_at'    => $grey['inward_date'],
            ];
        }
    }
} else {
    // No identifier: preview with the latest roll
    $roll = Database::fetch($rollSelect . " ORDER BY r.id DESC LIMIT 1");
}

if (!$roll) {
    http_response_code(404);
    $what = $rollId > 0 ? "id {$rollId}" : ($rollNo !== '' ? "roll '{$rollNo}'" : ($lotNo !== '' ? "lot '{$lotNo}'" : 'any roll'));
    die('No label data found for ' . htmlspecialchars($what) . '. Generate rolls in Roll Packing first.');
}

$labelQuery = $isGreyLot ? 'lot=' . urlencode($lotNo) : 'id=' . (int)$roll['id'];

$company = Database::fetch("SELECT * FROM companies WHERE id = 1");

// Hardware Thermal Direct Stream (TSPL / ZPL / ESC-POS Raw Generators)
$rawCommand = '';
$format = $_GET['format'] ?? '';
$rNo = $roll['roll_number'] ?? 'R-01';
$bCode = $roll['barcode'] ?? $rNo;
$grade = $isGreyLot ? 'GREY' : ($roll['quality_grade'] ?? 'A');
$wt = number_format($roll['weight_kg'] ?? 24.5, 2);
$len = number_format($roll['length_meters'] ?? 85.0, 1);
$compName = strtoupper($company['name'] ?? 'KARCEM TEXTILES');
$pName = substr($roll['product_name'] ?? 'Finished Single Jersey', 0, 32);

if ($format === 'tspl') {
    $rawCommand  = "SIZE 100 mm, 75 mm\r\n";
    $rawCommand .= "GAP 3 mm, 0 mm\r\n";
    $rawCommand .= "DIRECTION 1\r\n";
    $rawCommand .= "CLS\r\n";
    $rawCommand .= "TEXT 40,25,\"3\",0,1,1,\"{$compName}\"\r\n";
    $rawCommand .= "TEXT 40,65,\"2\",0,1,1,\"ROLL: {$rNo} | GRADE: {$grade}\"\r\n";
    $rawCommand .= "TEXT 40,105,\"2\",0,1,1,\"FABRIC: {$pName}\"\r\n";
    $rawCommand .= "TEXT 40,145,\"2\",0,1,1,\"NET WT: {$wt} KG | LEN: {$len} M\"\r\n";
    $rawCommand .= "BARCODE 40,195,\"128\",70,1,0,2,2,\"{$bCode}\"\r\n";
    $rawCommand .= "PRINT 1,1\r\n";
} elseif ($format === 'zpl') {
    // Zebra Programming Language (ZPL II)
    $rawCommand  = "^XA\r\n";
    $rawCommand .= "^PW800\r\n";
    $rawCommand .= "^LL600\r\n";
    $rawCommand .= "^FO50,40^A0N,35,35^FD{$compName}^FS\r\n";
    $rawCommand .= "^FO50,90^A0N,28,28^FDROLL: {$rNo}  |  GRADE: {$grade}^FS\r\n";
    $rawCommand .= "^FO50,130^A0N,25,25^FDFABRIC: {$pName}^FS\r\n";
    $rawCommand .= "^FO50,170^A0N,28,28^FDNET WT: {$wt} KG  |  LEN: {$len} M^FS\r\n";
    $rawCommand .= "^FO50,220^BY2,3,70^BCN,70,Y,N,N^FD{$bCode}^FS\r\n";
    $rawCommand .= "^FO550,220^BQN,2,4^FDQA,{$bCode}^FS\r\n";
    $rawCommand .= "^XZ\r\n";
} elseif ($format === 'escpos') {
    // ESC/POS Thermal Barcode Commands
    $rawCommand  = "\x1B\x40"; // ESC @ (Initialize)
    $rawCommand .= "\x1B\x61\x01"; // Center align
    $rawCommand .= "{$compName}\n";
    $rawCommand .= "--------------------------------\n";
    $rawCommand .= "\x1B\x61\x00"; // Left align
    $rawCommand .= "ROLL NO : {$rNo} [GRADE: {$grade}]\n";
    $rawCommand .= "FABRIC  : {$pName}\n";
    $rawCommand .= "NET WT  : {$wt} KG  LEN: {$len} M\n";
    $rawCommand .= "\x1D\x68\x46"; // GS h 70 (height 70)
    $rawCommand .= "\x1D\x77\x02"; // GS w 2 (width 2)
    $rawCommand .= "\x1D\x6B\x49" . chr(strlen($bCode)) . $bCode; // GS k Code128
    $rawCommand .= "\n\n\x1D\x56\x00"; // GS V 0 (Cut paper)
}

if ($rawCommand !== '') {
    // If direct socket print requested to hardware printer IP
    if (isset($_GET['print_ip']) && filter_var($_GET['print_ip'], FILTER_VALIDATE_IP)) {
        $port = (int)($_GET['port'] ?? 9100);
        $fp = @fsockopen($_GET['print_ip'], $port, $errno, $errstr, 3);
        if ($fp) {
            fwrite($fp, $rawCommand);
            fclose($fp);
            echo "<script>alert('Print command sent successfully to {$_GET['print_ip']}:{$port}'); window.close();</script>";
            exit;
        } else {
            die("Could not connect to printer at {$_GET['print_ip']}:{$port} - Error: {$errstr} ({$errno})");
        }
    }

    header('Content-Type: application/octet-stream; charset=utf-8');
    header('Content-Disposition: attachment; filename="label_' . ($roll['roll_number'] ?? 'roll') . '.' . ($format === 'zpl' ? 'zpl' : 'prn') . '"');
    echo $rawCommand;
    exit;
}
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
      padding: 2mm 4mm;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      page-break-inside: avoid;
      overflow: hidden;
    }
    .label-codes { display: flex; align-items: center; gap: 3mm; }
    .label-codes .code-linear { flex: 1 1 auto; min-width: 0; }
    .label-codes .code-qr { flex: 0 0 12.5mm; }
    @media print {
      @page { size: 100mm 75mm; margin: 0; }
      body { background: #fff; padding: 0; margin: 0; }
      .label-box { box-shadow: none; margin: 0; width: 100mm; height: 75mm; border: 1.5px solid #000; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 100mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm gap-2">
    <div class="small fw-bold">L01 — Thermal Roll Label</div>
    <div class="d-flex gap-1 align-items-center">
      <div class="btn-group btn-group-sm">
        <a href="?<?= $labelQuery ?>&format=tspl" class="btn btn-outline-dark" title="Download TSPL raw print file">TSPL</a>
        <a href="?<?= $labelQuery ?>&format=zpl" class="btn btn-outline-dark" title="Download Zebra ZPL file">ZPL</a>
        <a href="?<?= $labelQuery ?>&format=escpos" class="btn btn-outline-dark" title="Download ESC/POS raw file">ESC/POS</a>
      </div>
      <button class="btn btn-dark btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print
      </button>
      <a href="<?= Url::to('/dyeing/roll-packing') ?>" class="btn btn-outline-secondary btn-sm" title="Back to Roll Packing">
        <i class="bi bi-arrow-left"></i>
      </a>
    </div>
  </div>
</div>

<div class="label-box">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center border-bottom pb-1">
    <div>
      <div class="fw-bold text-uppercase text-nowrap" style="font-size: 0.74rem;"><?= htmlspecialchars($company['name'] ?? 'KARCEM TEXTILES') ?></div>
      <div class="text-muted" style="font-size: 0.6rem;">TIRUPUR MILL · TN 33 · ISO 9001:2015</div>
    </div>
    <div class="text-end">
      <span class="badge bg-dark text-white px-2 py-1" style="font-size: 0.7rem;"><?= $isGreyLot ? 'GREY LOT' : 'GRADE ' . htmlspecialchars($roll['quality_grade'] ?? 'A') ?></span>
    </div>
  </div>

  <!-- Fabric Specs -->
  <div class="my-0">
    <div class="fw-bold text-dark text-truncate" style="font-size: 0.78rem;"><?= htmlspecialchars($roll['product_name'] ?? '') ?></div>
    <div class="row gx-1 gy-0 small text-dark mt-1" style="font-size: 0.68rem; line-height: 1.35;">
      <?php if ($isGreyLot): ?>
      <div class="col-6"><strong>Grey Lot:</strong> <span class="font-monospace text-primary"><?= htmlspecialchars($roll['roll_number']) ?></span></div>
      <div class="col-6"><strong>Rolls:</strong> <?= (int)$roll['received_rolls'] ?></div>
      <div class="col-12 text-truncate"><strong>Source:</strong> <?= htmlspecialchars($roll['supplier_name']) ?></div>
      <div class="col-6"><strong>GSM:</strong> <?= (int)$roll['gsm'] ?></div>
      <div class="col-6"><strong>Width:</strong> <?= htmlspecialchars((string)$roll['width_inches']) ?>"</div>
      <?php else: ?>
      <div class="col-6"><strong>Roll No:</strong> <span class="font-monospace text-primary"><?= htmlspecialchars($roll['roll_number']) ?></span></div>
      <div class="col-6"><strong>Lot Ref:</strong> <?= htmlspecialchars($roll['lot_number'] ?? '-') ?></div>
      <div class="col-6"><strong>Shade:</strong> <?= htmlspecialchars($roll['shade_group'] ?? '-') ?></div>
      <div class="col-6"><strong>GSM:</strong> <?= $roll['gsm'] !== null ? (int)$roll['gsm'] . ' &plusmn; 3%' : '-' ?></div>
      <div class="col-6"><strong>Width:</strong> <?= $roll['width_inches'] !== null ? htmlspecialchars((string)$roll['width_inches']) . '" Dia' : '-' ?></div>
      <div class="col-6"><strong>Length:</strong> <?= $roll['length_meters'] !== null ? number_format((float)$roll['length_meters'], 1) . ' Mtrs' : '-' ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Weighment Summary (gross weight is not recorded per roll, so only net weight and date are printed) -->
  <div class="d-flex justify-content-between align-items-center bg-light border rounded" style="padding: 1px 4px;">
    <div class="text-center" style="width: 50%;">
      <span class="text-secondary" style="font-size: 0.65rem; display: block;">NET WEIGHT</span>
      <span class="fw-bold text-dark font-monospace" style="font-size: 1.05rem;"><?= number_format((float)$roll['weight_kg'], 2) ?> <small style="font-size: 0.75rem;">Kg</small></span>
    </div>
    <div class="text-center border-start" style="width: 50%;">
      <span class="text-secondary" style="font-size: 0.65rem; display: block;"><?= $isGreyLot ? 'RECEIVED ON' : 'PACKED ON' ?></span>
      <span class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;"><?= !empty($roll['created_at']) ? htmlspecialchars(date('d-m-Y', strtotime($roll['created_at']))) : '-' ?></span>
    </div>
  </div>

  <!-- Dual Barcode (Code128 laser + QR smartphone) -->
  <div class="label-codes">
    <div class="code-linear"><?= Barcode::svg((string)($roll['barcode'] ?? $roll['roll_number']), 26, 1) ?></div>
    <div class="code-qr"><?= Barcode::qrSvg((string)($roll['barcode'] ?? $roll['roll_number']), 46) ?></div>
  </div>
</div>

<?php if (isset($_GET['autoprint']) && $_GET['autoprint'] === '1'): ?>
<script>
window.addEventListener('load', () => window.print());
</script>
<?php endif; ?>
</body>
</html>
