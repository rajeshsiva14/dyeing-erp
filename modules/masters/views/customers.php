<?php
/**
 * Customer Master Directory - KARCEM Textile ERP
 * File: modules/masters/views/customers.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Security\SessionManager;
use Core\Security\CSRF;

SessionManager::start();
$currentUser = SessionManager::getUser();
$companyId = (int)($currentUser['company_id'] ?? 1);

$pageTitle = 'Customer Master Directory';
$activeModule = 'masters';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'domestic');
    $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
    $pan = strtoupper(trim($_POST['pan'] ?? ''));
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? $billingAddress);
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? 'Tamil Nadu');
    $pincode = trim($_POST['pincode'] ?? '');
    $creditLimit = (float)($_POST['credit_limit'] ?? 0);
    $paymentTerms = trim($_POST['payment_terms'] ?? 'Net 30');

    if (empty($code) || empty($name) || empty($billingAddress) || empty($city)) {
        $msg = 'Please fill all mandatory fields (Code, Name, Billing Address, City).';
        $msgType = 'danger';
    } else {
        try {
            Database::insert('customers', [
                'company_id'       => $companyId,
                'code'             => $code,
                'name'             => $name,
                'category'         => in_array($category, ['export', 'domestic', 'jobwork'], true) ? $category : 'domestic',
                'gstin'            => $gstin ?: null,
                'pan'              => $pan ?: null,
                'contact_person'   => $contactPerson ?: null,
                'phone'            => $phone ?: null,
                'email'            => $email ?: null,
                'billing_address'  => $billingAddress,
                'shipping_address' => $shippingAddress ?: $billingAddress,
                'city'             => $city,
                'state'            => $state,
                'state_code'       => '33',
                'pincode'          => $pincode,
                'currency'         => 'INR',
                'credit_limit'     => $creditLimit,
                'credit_days'      => 30,
                'current_balance'  => 0.00,
                'payment_terms'    => $paymentTerms,
                'status'           => 'active',
                'created_at'       => date('Y-m-d H:i:s')
            ]);
            $msg = "Customer '{$name}' created successfully.";
            $msgType = 'success';
        } catch (\Exception $e) {
            $msg = 'Failed to create customer: ' . $e->getMessage();
            $msgType = 'danger';
        }
    }
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$customers = Database::fetchAll("SELECT * FROM customers WHERE company_id = ? ORDER BY name ASC", [$companyId]);
if (empty($customers)) {
    $customers = Database::fetchAll("SELECT * FROM customers ORDER BY name ASC");
}
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <?php if (!empty($msg)): ?>
      <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/masters') ?>">Masters</a></li>
            <li class="breadcrumb-item active">Customers</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Customer Master Directory</h1>
        <p class="text-secondary small mb-0">Buyers, exporters, domestic brands, and jobwork customer accounts with GSTIN and credit limits.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
          <i class="bi bi-person-plus me-1"></i> New Customer
        </button>
      </div>
    </div>

    <!-- Customers Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Registered Customer Accounts</h2>
        <span class="small text-secondary"><?= count($customers) ?> Customers</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Code</th>
              <th>Customer Name</th>
              <th>Category</th>
              <th>GSTIN / PAN</th>
              <th>City & State</th>
              <th>Credit Limit</th>
              <th>Balance (₹)</th>
              <th>Terms</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($customers as $c): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($c['code']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                  <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($c['contact_person'] ?? '') ?> · <?= htmlspecialchars($c['phone'] ?? '') ?></span>
                </td>
                <td><span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($c['category']) ?></span></td>
                <td>
                  <span class="font-monospace fw-semibold text-dark"><?= htmlspecialchars($c['gstin'] ?? 'Unregistered') ?></span>
                  <?php if (!empty($c['pan'])): ?>
                    <span class="text-muted d-block" style="font-size: 0.72rem;">PAN: <?= htmlspecialchars($c['pan']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['state']) ?></td>
                <td class="font-monospace">₹<?= NumberToWords::formatIndian($c['credit_limit'], 0) ?></td>
                <td class="fw-bold font-monospace text-<?= $c['current_balance'] > 0 ? 'danger' : 'success' ?>">
                  ₹<?= NumberToWords::formatIndian($c['current_balance'], 2) ?>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($c['payment_terms'] ?? 'Net 30') ?></span></td>
                <td><span class="badge-status status-<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-labelledby="newCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newCustomerModalLabel"><i class="bi bi-person-plus me-2"></i>Add Customer Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="customerForm" method="POST" action="">
        <?= CSRF::getField() ?>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold">Customer Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="code" id="cust_code" placeholder="e.g. CUST-004" required>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-bold">Company / Customer Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="cust_name" placeholder="e.g. Tirupur Export Knits" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
              <select class="form-select" name="category" id="cust_category">
                <option value="domestic">Domestic Sale (Tamil Nadu)</option>
                <option value="export">Direct Export</option>
                <option value="jobwork">Job-Work Client</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">GSTIN (15 Digits)</label>
              <input type="text" class="form-control" name="gstin" id="cust_gstin" placeholder="33AAAAA0000A1Z5">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">PAN</label>
              <input type="text" class="form-control" name="pan" id="cust_pan" placeholder="AAAAA0000A">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Contact Person</label>
              <input type="text" class="form-control" name="contact_person" id="cust_contact" placeholder="Manager Name">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Phone Number</label>
              <input type="text" class="form-control" name="phone" id="cust_phone" placeholder="+91 98422 00000">
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Billing Address <span class="text-danger">*</span></label>
              <textarea class="form-control" name="billing_address" id="cust_address" rows="2" placeholder="Street, Industrial Area..." required></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="city" id="cust_city" value="Tirupur" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">State <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="state" id="cust_state" value="Tamil Nadu" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Pincode <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="pincode" id="cust_pincode" value="641602" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Credit Limit (₹)</label>
              <input type="number" step="1000" class="form-control" name="credit_limit" id="cust_credit_limit" value="1000000">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Terms</label>
              <input type="text" class="form-control" name="payment_terms" id="cust_terms" value="30 Days PDC">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveCustBtn"><i class="bi bi-save me-1"></i> Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
