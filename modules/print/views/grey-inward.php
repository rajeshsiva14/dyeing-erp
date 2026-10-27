<?php
/**
 * P14 - Grey Fabric Inward & Verification Report (Print & TCPDF Engine)
 * File: modules/print/views/grey-inward.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\PdfReport;

$companyId = (int)($_SESSION['company_id'] ?? 1);
$inwardId = (int)($_GET['id'] ?? 0);
$inwardNo = trim($_GET['inward_no'] ?? '');

if ($inwardId > 0) {
    $inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inwardId, $companyId]);
} elseif ($inwardNo !== '') {
    $inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE inward_no = ? AND company_id = ?", [$inwardNo, $companyId]);
} else {
    $inward = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$companyId]);
}

if (!$inward) {
    http_response_code(404);
    die("Grey Fabric Inward record not found.");
}

$company = Database::fetch("SELECT * FROM companies WHERE id = ?", [$companyId]) ?: [
    'name' => 'KARCEM TEXTILES & DYEING MILLS (P) LTD',
    'gstin' => '33AABCK1234F1Z8',
    'address' => 'SF No. 248/1, SIPCOT Industrial Growth Estate, Perundurai, Erode - 638052, TN',
    'phone' => '+91 424 2258900',
    'email' => 'stores@karcemtextiles.com'
];

// Direct TCPDF PDF download or stream
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    PdfReport::renderGreyInwardPdf($inward, $company, 'I');
    exit;
}

$inwardNo = htmlspecialchars($inward['inward_no']);
$inwardDate = date('d M Y, H:i', strtotime($inward['inward_date']));
$supplierName = htmlspecialchars($inward['supplier_name']);
$sourceType = ucwords(str_replace('_', ' ', $inward['source_type']));
$challanNo = htmlspecialchars($inward['delivery_challan_no']);
$vehicleNo = htmlspecialchars($inward['vehicle_no'] ?: 'Direct Handover');
$receivedBy = htmlspecialchars($inward['received_by'] ?? 'Stores Officer');
$warehouse = htmlspecialchars($inward['warehouse_location'] ?? 'Grey Fabric Store - Bay A');
$qcStatus = strtoupper(str_replace('_', ' ', $inward['qc_status'] ?? 'INSPECTION PENDING'));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>P14 — Grey Fabric Inward Slip (<?= $inwardNo ?>) — KARCEM Textile ERP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap">
  <style>
    body {
      background-color: #F1F5F9;
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      color: #0F172A;
      padding: 24px 0;
    }
    .print-sheet {
      width: 210mm;
      min-height: 297mm;
      margin: 0 auto;
      background: #FFFFFF;
      padding: 16mm 18mm;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
      border-radius: 8px;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace;
      font-feature-settings: "tnum" 1, "lnum" 1;
    }
    .table-bordered th, .table-bordered td {
      border: 1px solid #CBD5E1 !important;
      padding: 8px 10px;
    }
    @media print {
      body {
        background: #FFFFFF !important;
        padding: 0 !important;
      }
      .print-sheet {
        box-shadow: none !important;
        border-radius: 0 !important;
        width: 100% !important;
        padding: 10mm !important;
        margin: 0 !important;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- Floating Print & PDF Action Toolbar -->
  <div class="container no-print mb-4" style="max-width: 210mm;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border">
      <div>
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <span class="ms-3 fw-bold text-dark">Form P14: Grey Fabric Inward & Weighment Slip</span>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/print/grey-inward?id=' . $inward['id'] . '&format=pdf') ?>" target="_blank" class="btn btn-primary btn-sm px-3 shadow-sm">
          <i class="bi bi-file-earmark-pdf me-1"></i> Download TCPDF PDF
        </a>
        <button type="button" onclick="window.print()" class="btn btn-dark btn-sm px-3">
          <i class="bi bi-printer me-1"></i> Browser Print
        </button>
      </div>
    </div>
  </div>

  <!-- Official P14 Printable Document Sheet -->
  <div class="print-sheet">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
      <div>
        <h1 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($company['name']) ?></h1>
        <div class="small text-secondary"><?= htmlspecialchars($company['address']) ?></div>
        <div class="small text-secondary">
          <b>GSTIN:</b> <?= htmlspecialchars($company['gstin']) ?> &bull; 
          <b>Phone:</b> <?= htmlspecialchars($company['phone']) ?> &bull; 
          <b>Email:</b> <?= htmlspecialchars($company['email']) ?>
        </div>
      </div>
      <div class="text-end">
        <div class="badge bg-primary fs-6 px-3 py-2 mb-1">FORM P14</div>
        <div class="fw-bold text-dark fs-5 font-mono"><?= $inwardNo ?></div>
        <div class="small text-secondary"><?= $inwardDate ?></div>
      </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-2 mb-4 p-3 bg-light rounded border small">
      <div class="col-6">
        <span class="text-secondary">Supplier / Source:</span>
        <div class="fw-bold text-dark"><?= $supplierName ?> <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;"><?= $sourceType ?></span></div>
      </div>
      <div class="col-6">
        <span class="text-secondary">Delivery Challan No:</span>
        <div class="fw-bold font-mono text-dark"><?= $challanNo ?></div>
      </div>
      <div class="col-4">
        <span class="text-secondary">Vehicle / Carrier:</span>
        <div class="fw-semibold text-dark"><?= $vehicleNo ?></div>
      </div>
      <div class="col-4">
        <span class="text-secondary">Warehouse Location:</span>
        <div class="fw-semibold text-dark"><?= $warehouse ?></div>
      </div>
      <div class="col-4">
        <span class="text-secondary">QC Disposition:</span>
        <div><span class="badge <?= ($inward['qc_status'] === 'passed') ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $qcStatus ?></span></div>
      </div>
    </div>

    <!-- Items & Weighment Table -->
    <h2 class="h6 fw-bold text-dark mb-2">FABRIC WEIGHMENT & LOT SPECIFICATION</h2>
    <table class="table table-bordered align-middle small mb-3">
      <thead class="table-dark">
        <tr>
          <th style="width: 50px;" class="text-center">S.No</th>
          <th>Fabric Code</th>
          <th>Description & Composition</th>
          <th>Lot Number</th>
          <th class="text-center">Rolls</th>
          <th class="text-end">Gross (Kg)</th>
          <th class="text-end">Tare (Kg)</th>
          <th class="text-end">Net (Kg)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-center fw-bold">1</td>
          <td class="fw-bold font-mono text-primary"><?= htmlspecialchars($inward['fabric_code']) ?></td>
          <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($inward['fabric_description']) ?></div>
            <div class="text-secondary small">
              <?= htmlspecialchars($inward['composition']) ?> &bull; 
              <?= $inward['gsm'] ?> GSM &bull; <?= $inward['width_inches'] ?>" &bull; 
              <?= ucfirst($inward['construction']) ?>
            </div>
          </td>
          <td class="font-mono fw-semibold"><?= htmlspecialchars($inward['grey_lot_no']) ?></td>
          <td class="text-center font-mono fw-bold fs-6"><?= $inward['received_rolls'] ?></td>
          <td class="text-end font-mono"><?= number_format($inward['gross_weight_kg'], 2) ?></td>
          <td class="text-end font-mono text-muted"><?= number_format($inward['tare_weight_kg'], 2) ?></td>
          <td class="text-end font-mono fw-bold text-dark fs-6"><?= number_format($inward['net_weight_kg'], 2) ?></td>
        </tr>
      </tbody>
      <tfoot class="table-light">
        <tr class="fw-bold">
          <td colspan="4" class="text-end">Weighment Summary Totals:</td>
          <td class="text-center font-mono"><?= $inward['received_rolls'] ?> Rolls</td>
          <td class="text-end font-mono"><?= number_format($inward['gross_weight_kg'], 2) ?> Kg</td>
          <td class="text-end font-mono text-muted"><?= number_format($inward['tare_weight_kg'], 2) ?> Kg</td>
          <td class="text-end font-mono text-primary fs-6"><?= number_format($inward['net_weight_kg'], 2) ?> Kg</td>
        </tr>
      </tfoot>
    </table>

    <!-- Remarks -->
    <div class="p-3 bg-light rounded border mb-4 small">
      <span class="text-secondary fw-bold">Inspection & Stores Notes:</span>
      <div class="text-dark mt-1"><?= htmlspecialchars($inward['remarks'] ?: 'Material received in sound condition. Rolls staged in inspection bay for ASTM D5430 4-point check.') ?></div>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 mt-5 text-center small">
      <div class="col-4">
        <div class="border-top pt-2">
          <div class="fw-bold text-dark"><?= $receivedBy ?></div>
          <div class="text-secondary" style="font-size: 0.72rem;">Stores Weighment Incharge</div>
        </div>
      </div>
      <div class="col-4">
        <div class="border-top pt-2">
          <div class="fw-bold text-dark">QC Inspector</div>
          <div class="text-secondary" style="font-size: 0.72rem;">Fabric Inspection Division</div>
        </div>
      </div>
      <div class="col-4">
        <div class="border-top pt-2">
          <div class="fw-bold text-dark">Authorized Signatory</div>
          <div class="text-secondary" style="font-size: 0.72rem;">Dyeing Plant General Manager</div>
        </div>
      </div>
    </div>

    <!-- Footer Audit Notice -->
    <div class="text-center small text-muted border-top pt-2 mt-5" style="font-size: 0.72rem;">
      Electronic Goods Inward Document (Form P14) generated from KARCEM Textile ERP &bull; Timestamp: <?= date('Y-m-d H:i:s') ?>
    </div>
  </div>

</body>
</html>
