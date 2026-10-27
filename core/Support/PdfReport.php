<?php
namespace Core\Support;

/**
 * Modern TCPDF Enterprise PDF Document Generator
 * Generates official textile dyeing mill print forms, receipts, and quality certificates.
 */
class PdfReport {

    private static function initTcpdf(): void {
        $tcpdfPath = dirname(__DIR__, 2) . '/assets/TCPDF-main/tcpdf.php';
        if (!class_exists('TCPDF')) {
            require_once $tcpdfPath;
        }
    }

    /**
     * Create a standard configured TCPDF instance for Textile Dyeing ERP
     */
    public static function create(string $title, string $orientation = 'P', string $format = 'A4'): \TCPDF {
        self::initTcpdf();

        $pdf = new \TCPDF($orientation, 'mm', $format, true, 'UTF-8', false);

        $pdf->SetCreator('KARCEM Textile ERP');
        $pdf->SetAuthor('KARCEM Textiles & Dyeing Mills');
        $pdf->SetTitle($title);
        $pdf->SetSubject('Official ERP Production & Quality Document');
        $pdf->SetKeywords('Textile, Dyeing, ERP, KARCEM, Production, Quality');

        // Margins
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 15);

        // Header & Footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFont('helvetica', '', 9);

        return $pdf;
    }

    /**
     * Stream a Grey Fabric Inward & Verification Report (P14) directly as TCPDF PDF
     */
    public static function renderGreyInwardPdf(array $inward, array $company, string $dest = 'I'): void {
        $title = 'Grey Inward Receipt — ' . ($inward['inward_no'] ?? 'GIN');
        $pdf = self::create($title, 'P', 'A4');
        $pdf->AddPage();

        $companyName    = $company['name'] ?? 'KARCEM TEXTILES & DYEING MILLS (P) LTD';
        $companyGstin   = $company['gstin'] ?? '33AABCK1234F1Z8';
        $companyAddress = $company['address'] ?? 'SF No. 248/1, SIPCOT Industrial Growth Estate, Perundurai, Erode - 638052, TN';
        $companyPhone   = $company['phone'] ?? '+91 424 2258900';
        $companyEmail   = $company['email'] ?? 'stores@karcemtextiles.com';

        $inwardNo    = htmlspecialchars($inward['inward_no'] ?? 'GIN-2026-0001');
        $inwardDate  = date('d-M-Y H:i', strtotime($inward['inward_date'] ?? 'now'));
        $supplier    = htmlspecialchars($inward['supplier_name'] ?? 'Knitting Division');
        $sourceType  = strtoupper(str_replace('_', ' ', $inward['source_type'] ?? 'Knitting'));
        $dcNo        = htmlspecialchars($inward['delivery_challan_no'] ?? '-');
        $vehicleNo   = htmlspecialchars($inward['vehicle_no'] ?: 'Direct Handover');
        $receivedBy  = htmlspecialchars($inward['received_by'] ?? 'Stores Incharge');
        $warehouse   = htmlspecialchars($inward['warehouse_location'] ?? 'Grey Store - Bay A');
        $qcStatus    = strtoupper(str_replace('_', ' ', $inward['qc_status'] ?? 'INSPECTION PENDING'));

        $fabricDesc  = htmlspecialchars($inward['fabric_description'] ?? '100% Cotton Knitted Single Jersey');
        $fabricCode  = htmlspecialchars($inward['fabric_code'] ?? 'FAB-SJ-160');
        $lotNo       = htmlspecialchars($inward['grey_lot_no'] ?? '-');
        $rolls       = (int)($inward['received_rolls'] ?? 0);
        $grossKg     = number_format((float)($inward['gross_weight_kg'] ?? 0), 2);
        $tareKg      = number_format((float)($inward['tare_weight_kg'] ?? 0), 2);
        $netKg       = number_format((float)($inward['net_weight_kg'] ?? 0), 2);
        $width       = $inward['width_inches'] ?? '-';
        $gsm         = $inward['gsm'] ?? '-';
        $remarks     = htmlspecialchars($inward['remarks'] ?: 'Material received in sound condition. Rolls tagged and staged for QC 4-point inspection.');

        $html = <<<EOD
<style>
  table.header-box { width: 100%; border-bottom: 2px solid #1E293B; padding-bottom: 6px; }
  h1 { font-size: 15pt; color: #0F172A; font-weight: bold; margin: 0; }
  .subhead { font-size: 8.5pt; color: #475569; }
  .doc-title { font-size: 12pt; font-weight: bold; color: #1D4ED8; text-align: right; }
  .doc-meta { font-size: 8pt; color: #334155; text-align: right; }
  table.meta-table { width: 100%; margin-top: 8px; border: 1px solid #CBD5E1; }
  table.meta-table td { padding: 4px 6px; font-size: 8.5pt; }
  .bg-th { background-color: #F1F5F9; font-weight: bold; color: #0F172A; }
  table.data-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
  table.data-table th { background-color: #1E293B; color: #FFFFFF; font-weight: bold; font-size: 8pt; padding: 5px; text-align: center; border: 1px solid #1E293B; }
  table.data-table td { font-size: 8.5pt; padding: 5px; border: 1px solid #CBD5E1; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .fw-bold { font-weight: bold; }
  table.sign-table { width: 100%; margin-top: 25px; }
  table.sign-table td { font-size: 8pt; padding: 10px 4px 0 4px; text-align: center; }
</style>

<table class="header-box" cellpadding="0" cellspacing="0">
  <tr>
    <td width="65%">
      <h1>{$companyName}</h1>
      <div class="subhead">{$companyAddress}</div>
      <div class="subhead"><b>GSTIN:</b> {$companyGstin} &bull; <b>Tel:</b> {$companyPhone} &bull; <b>Email:</b> {$companyEmail}</div>
    </td>
    <td width="35%" style="text-align: right;">
      <div class="doc-title">GOODS INWARD RECEIPT</div>
      <div class="doc-meta"><b>Form P14</b> &bull; Grey Fabric Inward &amp; Weighment</div>
      <div class="doc-meta"><b>Date:</b> {$inwardDate}</div>
      <div class="doc-meta"><b>Inward No:</b> <span style="font-size: 10pt; font-weight: bold; color: #0F172A;">{$inwardNo}</span></div>
    </td>
  </tr>
</table>

<table class="meta-table" cellpadding="4" cellspacing="0">
  <tr>
    <td width="20%" class="bg-th">Supplier / Source:</td>
    <td width="30%"><b>{$supplier}</b> ({$sourceType})</td>
    <td width="20%" class="bg-th">Delivery Challan No:</td>
    <td width="30%"><b>{$dcNo}</b></td>
  </tr>
  <tr>
    <td class="bg-th">Vehicle / Carrier:</td>
    <td>{$vehicleNo}</td>
    <td class="bg-th">Warehouse Location:</td>
    <td>{$warehouse}</td>
  </tr>
  <tr>
    <td class="bg-th">Received By:</td>
    <td>{$receivedBy}</td>
    <td class="bg-th">QC Disposition:</td>
    <td><b>{$qcStatus}</b></td>
  </tr>
</table>

<div style="font-size: 9.5pt; font-weight: bold; margin-top: 10px; color: #0F172A;">FABRIC WEIGHMENT &amp; SPECIFICATION DETAILS</div>

<table class="data-table" cellpadding="4" cellspacing="0">
  <thead>
    <tr>
      <th width="8%">S.No</th>
      <th width="15%">Fabric Code</th>
      <th width="32%">Fabric Description &amp; Construction</th>
      <th width="15%">Lot Number</th>
      <th width="8%">Rolls</th>
      <th width="11%" class="text-right">Gross (Kg)</th>
      <th width="11%" class="text-right">Net (Kg)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="text-center">1</td>
      <td><b>{$fabricCode}</b></td>
      <td>{$fabricDesc}<br/><span style="font-size: 7.5pt; color: #475569;">Target GSM: {$gsm} &bull; Width: {$width}"</span></td>
      <td class="text-center"><b>{$lotNo}</b></td>
      <td class="text-center fw-bold">{$rolls}</td>
      <td class="text-right">{$grossKg}</td>
      <td class="text-right fw-bold" style="color: #0F172A;">{$netKg}</td>
    </tr>
  </tbody>
  <tfoot>
    <tr style="background-color: #F8FAFC;">
      <td colspan="4" class="text-right fw-bold">Total Weighment Summary:</td>
      <td class="text-center fw-bold">{$rolls} Rolls</td>
      <td class="text-right"><b>{$grossKg}</b> Kg</td>
      <td class="text-right fw-bold" style="color: #1D4ED8; font-size: 9pt;">{$netKg} Kg</td>
    </tr>
    <tr>
      <td colspan="4" style="font-size: 8pt;"><b>Tare Deduction Breakdown:</b> Total Gross: {$grossKg} Kg &minus; Tare Weight: {$tareKg} Kg</td>
      <td colspan="3" class="text-right" style="font-size: 8pt;"><b>Certified Net Weight: {$netKg} Kg</b></td>
    </tr>
  </tfoot>
</table>

<table style="width: 100%; margin-top: 8px; border: 1px solid #E2E8F0; background-color: #F8FAFC;" cellpadding="4">
  <tr>
    <td style="font-size: 8pt; color: #334155;">
      <b>Remarks / Inspection Notes:</b> {$remarks}
    </td>
  </tr>
</table>

<table class="sign-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%">
      _____________________________
      <br/><b>Stores Weighment Incharge</b>
      <br/><span style="font-size: 7pt; color: #64748B;">Tare &amp; Gross Verified</span>
    </td>
    <td width="33%">
      _____________________________
      <br/><b>QC Inspection Officer</b>
      <br/><span style="font-size: 7pt; color: #64748B;">4-Point Quality Status</span>
    </td>
    <td width="34%">
      _____________________________
      <br/><b>Authorized Dye House Signatory</b>
      <br/><span style="font-size: 7pt; color: #64748B;">Batch Allocation Clearance</span>
    </td>
  </tr>
</table>

<div style="margin-top: 18px; font-size: 7pt; color: #94A3B8; text-align: center; border-top: 1px dashed #CBD5E1; padding-top: 4px;">
  This document is an electronically generated Goods Inward &amp; Weighment Slip from KARCEM Textile ERP (Form P14). Valid without seal under audit compliance.
</div>
EOD;

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($inwardNo . '_Goods_Inward_Slip.pdf', $dest);
    }
}
