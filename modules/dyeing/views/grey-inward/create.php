<?php
/**
 * Dedicated Standalone Form: Register New Grey Fabric Inward
 * File: modules/dyeing/views/grey-inward-create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Register Grey Fabric Inward';
$activeModule = 'grey-inward';

$errors = [];
$initialLines = [['construction' => 'knitted']];

// Document sequence generator
$nextSeqNo = function (int $companyId, string $type, string $prefix, int $padding): string {
    $fy = date('Y') . '-' . substr((string)((int)date('Y') + 1), -2);
    Database::execute(
        "INSERT IGNORE INTO document_sequences (company_id, document_type, prefix, next_number, padding, financial_year) VALUES (?, ?, ?, 1, ?, ?)",
        [$companyId, $type, $prefix, $padding, $fy]
    );
    return Database::getNextDocumentNumber($type, $companyId);
};

// Handle POST to save inward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inward') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
    $sourceTypes = ['knitting', 'weaving', 'external_supplier', 'transfer', 'customer_jobwork'];
    $constructions = ['knitted', 'woven', 'other'];

    $sourceType = in_array($_POST['source_type'] ?? '', $sourceTypes, true) ? $_POST['source_type'] : '';
    $supplierName = trim($_POST['supplier_name'] ?? '');
    $challanNo = trim($_POST['delivery_challan_no'] ?? '');
    $poRef = trim($_POST['po_or_order_ref'] ?? '');
    $receivedBy = trim($_POST['received_by'] ?? '');
    $warehouse = trim($_POST['warehouse_location'] ?? '');
    $vehicleNo = trim($_POST['vehicle_no'] ?? '');

    if ($sourceType === '') $errors[] = 'Select a valid source type.';
    if ($supplierName === '') $errors[] = 'Supplier / source unit is required.';
    if ($challanNo === '') $errors[] = 'Delivery challan number is required.';
    if ($receivedBy === '') $errors[] = 'Received By is required.';
    if ($warehouse === '') $errors[] = 'Warehouse storage location is required.';

    $rawLines = is_array($_POST['lines'] ?? null) ? array_values($_POST['lines']) : [];
    if (!$rawLines) $errors[] = 'Add at least one fabric line.';

    $lines = [];
    $seenLots = [];
    foreach ($rawLines as $i => $raw) {
        $n = $i + 1;
        $raw = is_array($raw) ? $raw : [];
        $ln = [
            'fabric_code'        => trim($raw['fabric_code'] ?? ''),
            'fabric_description' => trim($raw['fabric_description'] ?? ''),
            'construction'       => in_array($raw['construction'] ?? '', $constructions, true) ? $raw['construction'] : 'knitted',
            'composition'        => trim($raw['composition'] ?? ''),
            'grey_lot_no'        => trim($raw['grey_lot_no'] ?? ''),
            'received_rolls'     => (int)($raw['received_rolls'] ?? 0),
            'gross_weight_kg'    => round((float)($raw['gross_weight_kg'] ?? 0), 2),
            'tare_weight_kg'     => round((float)($raw['tare_weight_kg'] ?? 0), 2),
            'width_inches'       => (float)($raw['width_inches'] ?? 0),
            'gsm'                => (int)($raw['gsm'] ?? 0),
            'remarks'            => trim($raw['remarks'] ?? ''),
        ];
        $ln['net_weight_kg'] = round($ln['gross_weight_kg'] - $ln['tare_weight_kg'], 2);
        $initialLines[$i] = $raw;

        if ($ln['fabric_code'] === '') $errors[] = "Fabric {$n}: fabric code is required.";
        if ($ln['fabric_description'] === '') $errors[] = "Fabric {$n}: description is required.";
        if ($ln['composition'] === '') $errors[] = "Fabric {$n}: composition is required.";
        if ($ln['received_rolls'] < 1) $errors[] = "Fabric {$n}: received rolls must be at least 1.";
        if ($ln['gross_weight_kg'] <= 0) $errors[] = "Fabric {$n}: gross weight must be greater than 0.";
        if ($ln['tare_weight_kg'] < 0) $errors[] = "Fabric {$n}: tare weight cannot be negative.";
        if ($ln['net_weight_kg'] <= 0) $errors[] = "Fabric {$n}: tare weight must be less than gross weight.";
        if ($ln['width_inches'] <= 0) $errors[] = "Fabric {$n}: width must be greater than 0.";
        if ($ln['gsm'] <= 0) $errors[] = "Fabric {$n}: GSM must be greater than 0.";

        if ($ln['grey_lot_no'] !== '') {
            $key = strtolower($ln['grey_lot_no']);
            if (isset($seenLots[$key])) {
                $errors[] = "Fabric {$n}: lot {$ln['grey_lot_no']} is repeated in this inward.";
            } elseif (Database::fetch("SELECT id FROM grey_fabric_inwards WHERE company_id = ? AND grey_lot_no = ? LIMIT 1", [$companyId, $ln['grey_lot_no']])) {
                $errors[] = "Fabric {$n}: lot {$ln['grey_lot_no']} already exists.";
            }
            $seenLots[$key] = true;
        }
        $lines[] = $ln;
    }

    if (!$errors) {
        Database::beginTransaction();
        try {
            $inwardBase = $nextSeqNo($companyId, 'grey_inward', 'GIN-' . date('Y') . '-', 4);
            $inwardDate = date('Y-m-d H:i:s');

            foreach ($lines as $i => $ln) {
                if ($ln['grey_lot_no'] === '') {
                    do {
                        $ln['grey_lot_no'] = $nextSeqNo($companyId, 'grey_lot', 'LOT-GRY-' . date('y') . '-', 4);
                        $taken = Database::fetch("SELECT id FROM grey_fabric_inwards WHERE company_id = ? AND grey_lot_no = ? LIMIT 1", [$companyId, $ln['grey_lot_no']]);
                    } while ($taken);
                }

                Database::insert('grey_fabric_inwards', [
                    'company_id'          => $companyId,
                    'inward_no'           => $inwardBase . '-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT),
                    'inward_date'         => $inwardDate,
                    'source_type'         => $sourceType,
                    'supplier_name'       => $supplierName,
                    'delivery_challan_no' => $challanNo,
                    'po_or_order_ref'     => $poRef,
                    'vehicle_no'          => $vehicleNo,
                    'received_by'         => $receivedBy,
                    'warehouse_location'  => $warehouse,
                    'fabric_code'         => $ln['fabric_code'],
                    'fabric_description'  => $ln['fabric_description'],
                    'construction'        => $ln['construction'],
                    'composition'         => $ln['composition'],
                    'grey_lot_no'         => $ln['grey_lot_no'],
                    'received_rolls'      => $ln['received_rolls'],
                    'gross_weight_kg'     => $ln['gross_weight_kg'],
                    'tare_weight_kg'      => $ln['tare_weight_kg'],
                    'net_weight_kg'       => $ln['net_weight_kg'],
                    'width_inches'        => $ln['width_inches'],
                    'gsm'                 => $ln['gsm'],
                    'qc_status'           => 'inspection_pending',
                    'remarks'             => $ln['remarks'],
                ]);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log('grey-inward-create save failed: ' . $e->getMessage());
            $errors[] = 'Could not save the inward. Nothing was recorded; please try again.';
        }

        if (!$errors) {
            header('Location: ' . Url::to('/dyeing/grey-inward?success=' . count($lines)));
            exit;
        }
    }
}

// Previously received fabrics for datalist
$fabricCatalog = Database::fetchAll(
    "SELECT fabric_code, MAX(fabric_description) AS fabric_description, MAX(construction) AS construction, MAX(composition) AS composition, MAX(gsm) AS gsm, MAX(width_inches) AS width_inches
     FROM grey_fabric_inwards GROUP BY fabric_code ORDER BY fabric_code"
);

$h = fn(string $k, string $d = ''): string => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES, 'UTF-8');
$sel = fn(string $k, string $v, bool $default = false): string => (($_POST[$k] ?? ($default ? $v : null)) === $v) ? 'selected' : '';

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Document Head -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Register Grey Fabric Inward</h1>
            <span class="badge badge-bw">NEW INWARD</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Dedicated form to record incoming grey rolls with gross, tare, net weighment, storage allocation, and lot tracking.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newInwardForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Receive Fabric & Send to QC
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> The inward could not be saved. Please correct:</div>
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="newInwardForm" method="POST" action="<?= Url::to('/dyeing/grey-inward-create') ?>">
      <input type="hidden" name="action" value="save_inward">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Source & Transport Details -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-truck"></i> Section A — Source & Supplier Details</h2>
            <p class="cz-doc-section-desc">Knitting source mill, delivery challan details, vehicle registration, and storage bay.</p>
          </div>
          <span class="badge badge-bw font-monospace">SOURCE SPECS</span>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Source Type <span class="text-danger">*</span></label>
            <select name="source_type" class="form-select form-select-sm" required>
              <option value="knitting" <?= $sel('source_type', 'knitting', true) ?>>Knitting Unit</option>
              <option value="weaving" <?= $sel('source_type', 'weaving') ?>>Weaving Unit</option>
              <option value="external_supplier" <?= $sel('source_type', 'external_supplier') ?>>External Yarn / Fabric Supplier</option>
              <option value="transfer" <?= $sel('source_type', 'transfer') ?>>Inter-Unit Transfer</option>
              <option value="customer_jobwork" <?= $sel('source_type', 'customer_jobwork') ?>>Customer-Owned Jobwork</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Supplier / Knitting Source Unit <span class="text-danger">*</span></label>
            <input type="text" name="supplier_name" value="<?= $h('supplier_name') ?>" class="form-control form-control-sm" placeholder="e.g. Sri Gomathi Knitting Mills, Tirupur" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Delivery Challan No <span class="text-danger">*</span></label>
            <input type="text" name="delivery_challan_no" value="<?= $h('delivery_challan_no') ?>" class="form-control form-control-sm font-monospace" placeholder="DC-2026-XXXX" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">PO / Order Ref</label>
            <input type="text" name="po_or_order_ref" value="<?= $h('po_or_order_ref') ?>" class="form-control form-control-sm" placeholder="PO-2026-XXXX">
          </div>
          <div class="col-md-4">
            <label class="form-label">Vehicle No</label>
            <input type="text" name="vehicle_no" value="<?= $h('vehicle_no') ?>" class="form-control form-control-sm font-monospace" placeholder="TN-39-AA-1234">
          </div>
          <div class="col-md-6">
            <label class="form-label">Warehouse Storage Location <span class="text-danger">*</span></label>
            <select name="warehouse_location" class="form-select form-select-sm" required>
              <option value="Grey Fabric Store - Bay A" <?= $sel('warehouse_location', 'Grey Fabric Store - Bay A', true) ?>>Grey Fabric Store - Bay A</option>
              <option value="Grey Fabric Store - Bay B" <?= $sel('warehouse_location', 'Grey Fabric Store - Bay B') ?>>Grey Fabric Store - Bay B</option>
              <option value="Customer Material Hold - Bay C" <?= $sel('warehouse_location', 'Customer Material Hold - Bay C') ?>>Customer Material Hold - Bay C (Jobwork)</option>
              <option value="Quarantine Holding Area" <?= $sel('warehouse_location', 'Quarantine Holding Area') ?>>Quarantine Holding Area</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Received By <span class="text-danger">*</span></label>
            <input type="text" name="received_by" class="form-control form-control-sm" value="<?= $h('received_by', 'P. Murugan (Stores Officer)') ?>" required>
          </div>
        </div>
      </div>

      <!-- Section B: Fabric Lines -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-box-seam"></i> Section B — Fabrics Received on this Challan</h2>
            <p class="cz-doc-section-desc">Add one card per fabric / lot. Each fabric gets its own lot number and goes to QC separately. Net weight = gross − tare.</p>
          </div>
          <span class="badge badge-bw font-monospace" id="lineCountBadge">1 FABRIC</span>
        </div>

        <datalist id="fabricCodes">
          <?php foreach ($fabricCatalog as $fc): ?>
            <option value="<?= htmlspecialchars($fc['fabric_code']) ?>"><?= htmlspecialchars($fc['fabric_description']) ?></option>
          <?php endforeach; ?>
        </datalist>

        <div id="fabricLines" class="d-flex flex-column gap-3"></div>

        <button type="button" id="addFabricBtn" class="btn btn-outline-dark btn-sm mt-3">
          <i class="bi bi-plus-circle me-1"></i> Add Another Fabric
        </button>
      </div>

      <!-- Live Totals Telemetry -->
      <div class="border rounded-3 p-3 bg-white mb-4 shadow-sm">
        <div class="row text-center g-2 align-items-center">
          <div class="col-6 col-md-2 border-end"><div class="text-secondary small">Total Fabrics</div><div class="fw-bold font-monospace fs-5 text-dark" id="totFabrics">0</div></div>
          <div class="col-6 col-md-2 border-end"><div class="text-secondary small">Total Rolls</div><div class="fw-bold font-monospace fs-5 text-dark" id="totRolls">0</div></div>
          <div class="col-4 col-md-2 border-end"><div class="text-secondary small">Gross Wt (Kg)</div><div class="fw-bold font-monospace fs-5 text-dark" id="totGross">0.00</div></div>
          <div class="col-4 col-md-2 border-end"><div class="text-secondary small">Tare Wt (Kg)</div><div class="fw-bold font-monospace fs-5 text-muted" id="totTare">0.00</div></div>
          <div class="col-4 col-md-4"><div class="text-secondary small">Certified Net Weight</div><div class="fw-bold font-monospace fs-4 text-primary" id="totNet">0.00 <span class="fs-6 fw-normal text-muted">Kg</span></div></div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <a href="<?= Url::to('/dyeing/grey-inward') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Receive Fabric & Send to QC
        </button>
      </div>
    </form>

    <!-- Template for Fabric Line -->
    <template id="fabricLineTpl">
      <div class="border rounded-3 bg-white fabric-line shadow-sm">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light rounded-top-3">
          <div class="fw-bold small"><i class="bi bi-layers me-1"></i> Fabric <span class="line-no"></span></div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary js-dup" title="Duplicate this fabric"><i class="bi bi-copy"></i></button>
            <button type="button" class="btn btn-outline-danger js-del" title="Remove this fabric"><i class="bi bi-trash"></i></button>
          </div>
        </div>
        <div class="row g-3 p-3">
          <div class="col-md-3">
            <label class="form-label">Fabric Code <span class="text-danger">*</span></label>
            <input type="text" data-f="fabric_code" list="fabricCodes" class="form-control form-control-sm font-monospace" placeholder="FAB-SJ-160" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fabric Description <span class="text-danger">*</span></label>
            <input type="text" data-f="fabric_description" class="form-control form-control-sm" placeholder="100% Combed Cotton Single Jersey 30s Ne" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Construction</label>
            <select data-f="construction" class="form-select form-select-sm">
              <option value="knitted">Knitted</option>
              <option value="woven">Woven</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Composition <span class="text-danger">*</span></label>
            <input type="text" data-f="composition" class="form-control form-control-sm" placeholder="100% Combed Cotton" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Grey Lot No</label>
            <input type="text" data-f="grey_lot_no" class="form-control form-control-sm font-monospace" placeholder="Auto-generated if blank">
          </div>
          <div class="col-md-3">
            <label class="form-label">Received Rolls <span class="text-danger">*</span></label>
            <input type="number" data-f="received_rolls" class="form-control form-control-sm js-calc font-monospace" min="1" step="1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Gross Wt (Kg) <span class="text-danger">*</span></label>
            <input type="number" data-f="gross_weight_kg" class="form-control form-control-sm js-calc font-monospace" min="0" step="0.01" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tare Wt (Kg)</label>
            <input type="number" data-f="tare_weight_kg" class="form-control form-control-sm js-calc font-monospace" min="0" step="0.01">
          </div>
          <div class="col-md-3">
            <label class="form-label">Net Wt (Kg)</label>
            <input type="text" class="form-control form-control-sm fw-bold bg-light js-net font-monospace" readonly tabindex="-1" value="0.00">
          </div>
          <div class="col-md-3">
            <label class="form-label">Width (Inches) <span class="text-danger">*</span></label>
            <input type="number" data-f="width_inches" class="form-control form-control-sm font-monospace" min="0" step="0.1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Target GSM <span class="text-danger">*</span></label>
            <input type="number" data-f="gsm" class="form-control form-control-sm font-monospace" min="0" step="1" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Remarks / Inspection Notes</label>
            <input type="text" data-f="remarks" class="form-control form-control-sm" placeholder="e.g. Clean rolls, sound condition">
          </div>
        </div>
      </div>
    </template>

    <script>
    (function () {
      const catalog = <?= json_encode(array_column($fabricCatalog, null, 'fabric_code'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>;
      const initial = <?= json_encode(array_values($initialLines), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const box = document.getElementById('fabricLines');
      const tpl = document.getElementById('fabricLineTpl');
      let seq = 0;

      const num = (el) => parseFloat(el?.value) || 0;

      function renumber() {
        const cards = box.querySelectorAll('.fabric-line');
        cards.forEach((c, i) => {
          c.querySelector('.line-no').textContent = i + 1;
          c.querySelector('.js-del').disabled = cards.length === 1;
        });
        document.getElementById('lineCountBadge').textContent = cards.length + (cards.length === 1 ? ' FABRIC' : ' FABRICS');
      }

      function recalc() {
        let rolls = 0, gross = 0, tare = 0;
        box.querySelectorAll('.fabric-line').forEach((c) => {
          const g = num(c.querySelector('[data-f=gross_weight_kg]'));
          const t = num(c.querySelector('[data-f=tare_weight_kg]'));
          const net = g - t;
          const netEl = c.querySelector('.js-net');
          netEl.value = net.toFixed(2);
          netEl.classList.toggle('text-danger', net < 0);
          rolls += parseInt(c.querySelector('[data-f=received_rolls]').value, 10) || 0;
          gross += g; tare += t;
        });
        document.getElementById('totFabrics').textContent = box.querySelectorAll('.fabric-line').length;
        document.getElementById('totRolls').textContent = rolls;
        document.getElementById('totGross').textContent = gross.toFixed(2);
        document.getElementById('totTare').textContent = tare.toFixed(2);
        document.getElementById('totNet').textContent = (gross - tare).toFixed(2);
      }

      function addLine(values) {
        const idx = seq++;
        const card = tpl.content.firstElementChild.cloneNode(true);
        card.querySelectorAll('[data-f]').forEach((el) => {
          const f = el.dataset.f;
          el.name = 'lines[' + idx + '][' + f + ']';
          if (values && values[f] !== undefined && values[f] !== null) el.value = values[f];
        });
        card.querySelector('.js-del').addEventListener('click', () => { card.remove(); renumber(); recalc(); });
        card.querySelector('.js-dup').addEventListener('click', () => {
          const copy = {};
          card.querySelectorAll('[data-f]').forEach((el) => { copy[el.dataset.f] = el.value; });
          copy.grey_lot_no = '';
          addLine(copy);
        });
        card.querySelectorAll('.js-calc').forEach((el) => el.addEventListener('input', recalc));
        card.querySelector('[data-f=fabric_code]').addEventListener('change', (e) => {
          const hit = catalog[e.target.value];
          if (!hit) return;
          [['fabric_description'], ['construction'], ['composition'], ['gsm'], ['width_inches']].forEach(([f]) => {
            const el = card.querySelector('[data-f=' + f + ']');
            if (el && !el.value && hit[f]) el.value = hit[f];
          });
        });
        box.appendChild(card);
        renumber(); recalc();
        return card;
      }

      document.getElementById('addFabricBtn').addEventListener('click', () => {
        const card = addLine({ construction: 'knitted' });
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.querySelector('[data-f=fabric_code]').focus();
      });

      initial.forEach((v) => addLine(v));
    })();
    </script>
  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
