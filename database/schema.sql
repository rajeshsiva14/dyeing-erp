-- =====================================================================
-- Integrated Textile Manufacturing ERP - Complete Schema
-- Database: textile_erp
-- Tailored for Tamil Nadu Textile Manufacturers (Spinning, Weaving, Dyeing, Garments)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS textile_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE textile_erp;

-- ---------------------------------------------------------------------
-- 1. Master Data & Administration
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(200) NOT NULL,
    gstin VARCHAR(15) NOT NULL,
    pan VARCHAR(10) NULL,
    cin VARCHAR(25) NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL DEFAULT 'Tamil Nadu',
    state_code VARCHAR(2) NOT NULL DEFAULT '33',
    pincode VARCHAR(10) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    website VARCHAR(100) NULL,
    base_currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    currency_symbol VARCHAR(5) NOT NULL DEFAULT '₹',
    financial_year_start DATE NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    branch_type ENUM('head_office', 'mill', 'dyeing_unit', 'garment_unit', 'warehouse', 'sales_office') NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL DEFAULT 'Tamil Nadu',
    gstin VARCHAR(15) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    CONSTRAINT fk_branches_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(64) NOT NULL,
    description VARCHAR(255) NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    username VARCHAR(64) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'staff',
    role_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    designation VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uoms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(16) NOT NULL UNIQUE,
    name VARCHAR(64) NOT NULL,
    uom_type ENUM('weight', 'length', 'volume', 'count', 'packaging') NOT NULL DEFAULT 'weight',
    decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 2,
    is_base TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uom_conversions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_uom_id BIGINT UNSIGNED NOT NULL,
    to_uom_id BIGINT UNSIGNED NOT NULL,
    factor DECIMAL(18, 6) NOT NULL,
    UNIQUE KEY uk_uom_pair (from_uom_id, to_uom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tax_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    description VARCHAR(150) NOT NULL,
    tax_type ENUM('gst', 'igst', 'exempt', 'zero_rated') NOT NULL DEFAULT 'gst',
    hsn_sac VARCHAR(16) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    cgst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    sgst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    igst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    effective_from DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    category ENUM('export', 'domestic', 'jobwork') NOT NULL DEFAULT 'domestic',
    gstin VARCHAR(15) NULL,
    pan VARCHAR(10) NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    billing_address TEXT NOT NULL,
    shipping_address TEXT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL DEFAULT 'Tamil Nadu',
    state_code VARCHAR(2) NOT NULL DEFAULT '33',
    pincode VARCHAR(10) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    credit_limit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    credit_days INT NOT NULL DEFAULT 30,
    current_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    payment_terms VARCHAR(100) DEFAULT 'Net 30',
    status ENUM('active', 'inactive', 'on_hold') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    supplier_type ENUM('fibre', 'yarn', 'grey_fabric', 'dyes_chemicals', 'accessories', 'machinery', 'job_worker') NOT NULL,
    gstin VARCHAR(15) NULL,
    pan VARCHAR(10) NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL DEFAULT 'Tamil Nadu',
    state_code VARCHAR(2) NOT NULL DEFAULT '33',
    payment_terms VARCHAR(100) DEFAULT 'Net 30',
    msme_registered TINYINT(1) NOT NULL DEFAULT 0,
    msme_number VARCHAR(32) NULL,
    current_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    warehouse_type ENUM('raw_cotton', 'yarn_store', 'grey_fabric_store', 'dyes_chemicals', 'dyes_store', 'finished_goods', 'trims_accessories', 'quarantine', 'scrap') NOT NULL,
    address TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warehouse_bins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(64) NOT NULL,
    is_quarantine TINYINT(1) NOT NULL DEFAULT 0,
    capacity_kg DECIMAL(12,2) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    INDEX (warehouse_id),
    CONSTRAINT fk_bins_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS machines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(120) NOT NULL,
    process_type ENUM('blowroom', 'carding', 'drawing', 'roving', 'ring_frame', 'winding', 'knitting', 'weaving', 'soft_flow_dyeing', 'cabinet_dyeing', 'hydro_extractor', 'relax_dryer', 'stenter', 'compactor', 'cutting', 'sewing') NOT NULL,
    model_number VARCHAR(64) NULL,
    manufacturer VARCHAR(100) NULL,
    year_of_make INT NULL,
    capacity DECIMAL(12,2) NOT NULL,
    capacity_uom VARCHAR(16) NOT NULL DEFAULT 'Kg',
    spring_or_tubes INT NULL,
    liquor_ratio VARCHAR(20) NULL,
    hourly_operating_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    current_status ENUM('available', 'running', 'maintenance', 'idle', 'breakdown') NOT NULL DEFAULT 'available',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX (company_id),
    INDEX (process_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(64) NOT NULL,
    name VARCHAR(200) NOT NULL,
    category ENUM('cotton_fibre', 'yarn', 'grey_fabric', 'dyed_fabric', 'finished_fabric', 'garment', 'dyes_chemical', 'trims', 'consumable') NOT NULL,
    sub_category VARCHAR(100) NULL,
    base_uom_id BIGINT UNSIGNED NOT NULL,
    tax_code_id BIGINT UNSIGNED NULL,
    hsn_sac VARCHAR(16) NOT NULL,
    standard_cost DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    standard_price DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    -- Textile technical attributes
    yarn_count VARCHAR(32) NULL, -- e.g. '30s Ne Combed', '40s Ne Compact'
    fabric_composition VARCHAR(100) NULL, -- '100% Cotton', '95/5 Cotton Spandex'
    fabric_gsm INT NULL, -- e.g. 180, 220
    fabric_width_inches INT NULL, -- e.g. 72 inch
    fabric_construction VARCHAR(64) NULL, -- 'Single Jersey', 'Interlock', 'Rib 1x1'
    shade_family VARCHAR(64) NULL, -- 'Navy Blue', 'Olive Green'
    reorder_level DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    current_stock DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (sku),
    INDEX (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    prefix VARCHAR(20) NOT NULL,
    next_number INT UNSIGNED NOT NULL DEFAULT 1,
    padding INT UNSIGNED NOT NULL DEFAULT 6,
    financial_year VARCHAR(10) NOT NULL,
    UNIQUE KEY uk_doc_seq (company_id, document_type, financial_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(120) NULL,
    action VARCHAR(64) NOT NULL,
    entity_type VARCHAR(64) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (entity_type, entity_id),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Commercial & Sales Management
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS enquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    enquiry_no VARCHAR(64) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    enquiry_date DATE NOT NULL,
    target_date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    remarks TEXT NULL,
    status ENUM('open', 'quoted', 'converted', 'closed', 'cancelled') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiry_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquiry_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    specification TEXT NULL,
    target_price DECIMAL(14,2) NULL,
    CONSTRAINT fk_enquiry_items_header FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    quotation_no VARCHAR(64) NOT NULL UNIQUE,
    enquiry_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    quotation_date DATE NOT NULL,
    valid_until DATE NOT NULL,
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    terms_conditions TEXT NULL,
    status ENUM('draft', 'sent', 'approved', 'rejected', 'expired') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    rate DECIMAL(14,4) NOT NULL,
    discount_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax_code_id BIGINT UNSIGNED NULL,
    tax_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    delivery_weeks INT NULL,
    CONSTRAINT fk_quotation_items_header FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    customer_po_no VARCHAR(64) NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_date DATE NOT NULL,
    promised_delivery_date DATE NOT NULL,
    order_type ENUM('export', 'domestic', 'jobwork') NOT NULL DEFAULT 'domestic',
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    payment_terms VARCHAR(100) NULL,
    shipping_address TEXT NULL,
    status ENUM('draft', 'submitted', 'confirmed', 'in_production', 'partially_dispatched', 'completed', 'cancelled', 'on_hold') NOT NULL DEFAULT 'draft',
    current_revision INT NOT NULL DEFAULT 1,
    credit_approved TINYINT(1) NOT NULL DEFAULT 1,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (customer_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    shade_code VARCHAR(32) NULL,
    shade_name VARCHAR(100) NULL,
    size_breakdown JSON NULL, -- e.g. {"S": 100, "M": 200, "L": 200, "XL": 100}
    ordered_qty DECIMAL(14,2) NOT NULL,
    produced_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    qc_accepted_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    dispatched_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    uom_id BIGINT UNSIGNED NOT NULL,
    unit_price DECIMAL(14,4) NOT NULL,
    tax_code_id BIGINT UNSIGNED NULL,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    delivery_date DATE NOT NULL,
    INDEX (sales_order_id),
    CONSTRAINT fk_sales_order_items FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_order_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    revision_no INT NOT NULL,
    reason TEXT NOT NULL,
    changes_json JSON NOT NULL,
    authorized_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Production Planning & BOM
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS bom_headers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    bom_no VARCHAR(64) NOT NULL UNIQUE,
    product_id BIGINT UNSIGNED NOT NULL,
    version INT NOT NULL DEFAULT 1,
    base_qty DECIMAL(14,2) NOT NULL DEFAULT 1.00,
    uom_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bom_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bom_header_id BIGINT UNSIGNED NOT NULL,
    component_product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(14,4) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    waste_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    process_stage ENUM('spinning', 'knitting', 'weaving', 'dyeing', 'finishing', 'cutting', 'sewing', 'packing') NOT NULL,
    CONSTRAINT fk_bom_items_header FOREIGN KEY (bom_header_id) REFERENCES bom_headers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS routing_headers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    route_type ENUM('cotton_to_garment', 'yarn_to_fabric', 'grey_to_dyed_fabric', 'fabric_to_garment', 'jobwork_dyeing') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS routing_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routing_id BIGINT UNSIGNED NOT NULL,
    sequence INT NOT NULL,
    operation_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(32) NOT NULL,
    standard_machine_hours DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    standard_labour_hours DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    is_subcontract TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_routing_ops_header FOREIGN KEY (routing_id) REFERENCES routing_headers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS production_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    sales_order_id BIGINT UNSIGNED NULL,
    sales_order_item_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    bom_header_id BIGINT UNSIGNED NULL,
    routing_id BIGINT UNSIGNED NULL,
    planned_qty DECIMAL(14,2) NOT NULL,
    actual_output_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    rejected_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    uom_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    due_date DATE NOT NULL,
    stage ENUM('spinning', 'knitting', 'dyeing', 'finishing', 'garmenting', 'completed') NOT NULL DEFAULT 'dyeing',
    status ENUM('planned', 'released', 'in_progress', 'completed', 'closed', 'cancelled') NOT NULL DEFAULT 'planned',
    released_by BIGINT UNSIGNED NULL,
    released_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (sales_order_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS material_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    production_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NULL,
    reserved_qty DECIMAL(14,4) NOT NULL,
    issued_qty DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    uom_id BIGINT UNSIGNED NOT NULL,
    status ENUM('reserved', 'partially_issued', 'fully_issued', 'cancelled') NOT NULL DEFAULT 'reserved',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Procurement & Stores
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS purchase_requisitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    pr_no VARCHAR(64) NOT NULL UNIQUE,
    department_id BIGINT UNSIGNED NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    request_date DATE NOT NULL,
    required_by_date DATE NOT NULL,
    reason TEXT NULL,
    status ENUM('draft', 'submitted', 'approved', 'po_created', 'rejected') NOT NULL DEFAULT 'draft',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_requisition_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requisition_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    requested_qty DECIMAL(14,2) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    estimated_rate DECIMAL(14,2) NULL,
    CONSTRAINT fk_pr_items_header FOREIGN KEY (requisition_id) REFERENCES purchase_requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    po_no VARCHAR(64) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    po_date DATE NOT NULL,
    delivery_date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',
    exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    payment_terms VARCHAR(100) NULL,
    delivery_address TEXT NULL,
    status ENUM('draft', 'submitted', 'approved', 'partially_received', 'received', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (supplier_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    ordered_qty DECIMAL(14,2) NOT NULL,
    received_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    accepted_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    uom_id BIGINT UNSIGNED NOT NULL,
    unit_price DECIMAL(14,4) NOT NULL,
    tax_code_id BIGINT UNSIGNED NULL,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_po_items_header FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    grn_no VARCHAR(64) NOT NULL UNIQUE,
    purchase_order_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_challan_no VARCHAR(64) NULL,
    supplier_invoice_no VARCHAR(64) NULL,
    vehicle_no VARCHAR(32) NULL,
    gate_entry_no VARCHAR(64) NULL,
    receipt_date DATE NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    total_weight_gross DECIMAL(12,2) NULL,
    total_weight_net DECIMAL(12,2) NULL,
    status ENUM('draft', 'inspected', 'posted', 'rejected') NOT NULL DEFAULT 'draft',
    inspected_by BIGINT UNSIGNED NULL,
    posted_by BIGINT UNSIGNED NULL,
    posted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (purchase_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipt_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    po_item_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    received_qty DECIMAL(14,2) NOT NULL,
    accepted_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    quarantine_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    rejected_qty DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    uom_id BIGINT UNSIGNED NOT NULL,
    supplier_lot_no VARCHAR(64) NULL,
    internal_lot_no VARCHAR(64) NULL,
    unit_rate DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    CONSTRAINT fk_grn_items_header FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Inventory & Lots (Immutable Ledger)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS inventory_lots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    lot_number VARCHAR(64) NOT NULL UNIQUE,
    product_id BIGINT UNSIGNED NOT NULL,
    supplier_lot_number VARCHAR(64) NULL,
    owner_type ENUM('company', 'customer', 'job_worker') NOT NULL DEFAULT 'company',
    owner_id BIGINT UNSIGNED NULL,
    origin_type ENUM('purchase', 'spinning', 'knitting', 'weaving', 'dyeing', 'customer_inward') NOT NULL,
    origin_ref VARCHAR(64) NULL,
    total_qty DECIMAL(14,4) NOT NULL,
    available_qty DECIMAL(14,4) NOT NULL,
    reserved_qty DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    uom_id BIGINT UNSIGNED NOT NULL,
    unit_cost DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    status ENUM('quarantine', 'accepted', 'hold', 'rejected', 'consumed') NOT NULL DEFAULT 'quarantine',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (product_id),
    INDEX (lot_number),
    INDEX (owner_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_rolls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    roll_number VARCHAR(64) NOT NULL UNIQUE,
    barcode VARCHAR(64) NOT NULL UNIQUE,
    product_id BIGINT UNSIGNED NOT NULL,
    weight_kg DECIMAL(10,2) NOT NULL,
    length_meters DECIMAL(10,2) NULL,
    width_inches DECIMAL(6,2) NULL,
    gsm INT NULL,
    quality_grade ENUM('A', 'B', 'C', 'rework', 'rejection') NOT NULL DEFAULT 'A',
    shade_group VARCHAR(32) NULL, -- e.g. 'Band A', 'Band B'
    warehouse_id BIGINT UNSIGNED NOT NULL,
    bin_id BIGINT UNSIGNED NULL,
    status ENUM('in_stock', 'issued_to_dyeing', 'issued_to_cutting', 'dispatched', 'quarantined') NOT NULL DEFAULT 'in_stock',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (lot_id),
    INDEX (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    bin_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NULL,
    roll_id BIGINT UNSIGNED NULL,
    owner_type ENUM('company', 'customer', 'job_worker') NOT NULL DEFAULT 'company',
    owner_id BIGINT UNSIGNED NULL,
    movement_type ENUM('grn_receipt', 'production_issue', 'production_return', 'production_receipt', 'dispatch', 'stock_transfer_in', 'stock_transfer_out', 'adjustment_plus', 'adjustment_minus', 'scrap_sale') NOT NULL,
    quantity_base_uom DECIMAL(18,4) NOT NULL, -- Positive for in, Negative for out
    unit_cost DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    total_cost DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    source_type VARCHAR(64) NOT NULL, -- e.g. 'goods_receipts', 'dye_batches', 'dispatches'
    source_id BIGINT UNSIGNED NOT NULL,
    source_line_id BIGINT UNSIGNED NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    posted_by BIGINT UNSIGNED NULL,
    INDEX (company_id),
    INDEX (product_id),
    INDEX (warehouse_id),
    INDEX (lot_id),
    INDEX (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    adjustment_no VARCHAR(64) NOT NULL UNIQUE,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    reason_code VARCHAR(32) NOT NULL,
    description TEXT NULL,
    status ENUM('draft', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    approved_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_adjustment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adjustment_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NULL,
    current_qty DECIMAL(14,4) NOT NULL,
    adjusted_qty DECIMAL(14,4) NOT NULL,
    variance_qty DECIMAL(14,4) NOT NULL,
    CONSTRAINT fk_adj_items_header FOREIGN KEY (adjustment_id) REFERENCES stock_adjustments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Spinning Module
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fibre_lots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    lot_no VARCHAR(64) NOT NULL UNIQUE,
    cotton_type VARCHAR(64) NOT NULL, -- Shankar-6, MCU-5, Giza, Suvin
    bale_count INT NOT NULL,
    total_weight_kg DECIMAL(12,2) NOT NULL,
    moisture_pct DECIMAL(5,2) NOT NULL DEFAULT 8.00,
    trash_pct DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    staple_length_mm DECIMAL(5,2) NOT NULL,
    micronaire DECIMAL(4,2) NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS spinning_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    batch_no VARCHAR(64) NOT NULL UNIQUE,
    target_count VARCHAR(32) NOT NULL, -- 30s CH, 40s Comp
    mixing_plan_name VARCHAR(100) NOT NULL,
    input_fibre_kg DECIMAL(12,2) NOT NULL,
    output_yarn_kg DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    waste_kg DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    yield_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    status ENUM('mixing', 'blowroom', 'carding', 'combing', 'drawing', 'roving', 'ring_frame', 'winding', 'completed') NOT NULL DEFAULT 'mixing',
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS spinning_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    stage ENUM('blowroom', 'carding', 'drawing', 'roving', 'ring_frame', 'winding') NOT NULL,
    machine_id BIGINT UNSIGNED NOT NULL,
    shift ENUM('shift_1', 'shift_2', 'shift_3') NOT NULL,
    input_kg DECIMAL(10,2) NOT NULL,
    output_kg DECIMAL(10,2) NOT NULL,
    waste_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    breakage_rate DECIMAL(6,2) NULL,
    operator_name VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_spin_ops_batch FOREIGN KEY (batch_id) REFERENCES spinning_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Knitting & Weaving Module
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS knitting_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    production_order_id BIGINT UNSIGNED NULL,
    fabric_type VARCHAR(100) NOT NULL, -- Single Jersey, Rib, Interlock
    diameter_gauge VARCHAR(32) NOT NULL, -- 30" x 24GG
    yarn_product_id BIGINT UNSIGNED NOT NULL,
    yarn_lot_no VARCHAR(64) NOT NULL,
    yarn_count VARCHAR(32) NOT NULL,
    target_gsm INT NOT NULL,
    target_width_inches INT NOT NULL,
    machine_id BIGINT UNSIGNED NOT NULL,
    planned_rolls INT NOT NULL,
    planned_weight_kg DECIMAL(12,2) NOT NULL,
    produced_rolls INT NOT NULL DEFAULT 0,
    produced_weight_kg DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weaving_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    production_order_id BIGINT UNSIGNED NULL,
    fabric_construction VARCHAR(100) NOT NULL, -- e.g. 40x40 / 132x72
    warp_yarn_id BIGINT UNSIGNED NOT NULL,
    weft_yarn_id BIGINT UNSIGNED NOT NULL,
    loom_machine_id BIGINT UNSIGNED NOT NULL,
    planned_meters DECIMAL(12,2) NOT NULL,
    produced_meters DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Dyeing & Wet Processing Module
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS lab_dip_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    request_no VARCHAR(64) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    shade_name VARCHAR(100) NOT NULL,
    shade_code VARCHAR(64) NOT NULL,
    fabric_type VARCHAR(100) NOT NULL,
    light_source VARCHAR(64) DEFAULT 'D65 / TL84',
    due_date DATE NOT NULL,
    status ENUM('pending', 'trial_in_progress', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_dip_trials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lab_dip_id BIGINT UNSIGNED NOT NULL,
    trial_number INT NOT NULL,
    dyes_formula JSON NOT NULL, -- e.g. [{"dye": "Reactive Blue 21", "dose_pct": 1.25}, ...]
    delta_e DECIMAL(5,2) NULL,
    result ENUM('match', 'light', 'dark', 'reddish', 'greenish', 'rejected') NOT NULL DEFAULT 'match',
    submitted_to_customer_at DATE NULL,
    customer_feedback TEXT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_trials_lab_dip FOREIGN KEY (lab_dip_id) REFERENCES lab_dip_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dye_recipes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    recipe_code VARCHAR(64) NOT NULL,
    shade_name VARCHAR(100) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    fabric_composition VARCHAR(100) NOT NULL,
    liquor_ratio VARCHAR(20) NOT NULL DEFAULT '1:6',
    dye_class ENUM('reactive', 'disperse', 'vat', 'pigment', 'direct') NOT NULL DEFAULT 'reactive',
    revision_no INT NOT NULL DEFAULT 1,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    approved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (recipe_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dye_recipe_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dye_recipe_id BIGINT UNSIGNED NOT NULL,
    chemical_product_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('dye', 'chemical', 'auxiliary') NOT NULL,
    dosage_percentage DECIMAL(8,4) NULL, -- % on weight of fabric (OWF)
    dosage_grams_per_litre DECIMAL(8,2) NULL, -- g/l
    process_stage ENUM('scouring_bleaching', 'dyeing', 'washing', 'soaping', 'fixing', 'softening') NOT NULL,
    CONSTRAINT fk_recipe_items_header FOREIGN KEY (dye_recipe_id) REFERENCES dye_recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dye_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    batch_no VARCHAR(64) NOT NULL UNIQUE,
    production_order_id BIGINT UNSIGNED NULL,
    dye_recipe_id BIGINT UNSIGNED NOT NULL,
    machine_id BIGINT UNSIGNED NOT NULL,
    planned_weight_kg DECIMAL(10,2) NOT NULL,
    actual_loaded_kg DECIMAL(10,2) NOT NULL,
    output_weight_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    water_volume_litres DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    liquor_ratio VARCHAR(20) NOT NULL DEFAULT '1:6',
    operator_name VARCHAR(100) NULL,
    process_start_time DATETIME NULL,
    process_end_time DATETIME NULL,
    reprocess_count INT NOT NULL DEFAULT 0,
    original_batch_id BIGINT UNSIGNED NULL,
    status ENUM('planned', 'loaded', 'dosing', 'dyeing', 'washing', 'unloaded', 'hydro', 'drying', 'compacted', 'completed', 'reprocess') NOT NULL DEFAULT 'planned',
    rejection_reason TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (batch_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dye_batch_parameters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dye_batch_id BIGINT UNSIGNED NOT NULL,
    process_step VARCHAR(64) NOT NULL,
    set_temperature_c DECIMAL(5,1) NOT NULL,
    actual_temperature_c DECIMAL(5,1) NOT NULL,
    set_ph DECIMAL(4,2) NOT NULL,
    actual_ph DECIMAL(4,2) NOT NULL,
    cycle_time_minutes INT NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    operator_notes VARCHAR(255) NULL,
    CONSTRAINT fk_batch_params FOREIGN KEY (dye_batch_id) REFERENCES dye_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemical_consumptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dye_batch_id BIGINT UNSIGNED NOT NULL,
    chemical_product_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NULL,
    planned_qty DECIMAL(10,3) NOT NULL,
    actual_qty DECIMAL(10,3) NOT NULL,
    uom VARCHAR(16) NOT NULL DEFAULT 'Kg',
    recorded_by VARCHAR(100) NULL,
    CONSTRAINT fk_chem_consump_batch FOREIGN KEY (dye_batch_id) REFERENCES dye_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finishing_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dye_batch_id BIGINT UNSIGNED NOT NULL,
    machine_id BIGINT UNSIGNED NOT NULL,
    process_type ENUM('hydro_extraction', 'drying', 'stenter', 'compactor') NOT NULL,
    input_weight_kg DECIMAL(10,2) NOT NULL,
    output_weight_kg DECIMAL(10,2) NOT NULL,
    process_loss_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    overfeed_pct DECIMAL(5,2) NULL,
    finished_width_inches DECIMAL(6,2) NOT NULL,
    finished_gsm INT NOT NULL,
    shrinkage_length_pct DECIMAL(4,2) NULL,
    shrinkage_width_pct DECIMAL(4,2) NULL,
    operator_name VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Garments Module
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS garment_styles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    style_number VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(200) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    season VARCHAR(32) NOT NULL, -- Spring/Summer, Autumn/Winter
    sam_minutes DECIMAL(6,2) NOT NULL, -- Standard Allowed Minutes for labour costing
    tech_pack_ref VARCHAR(64) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cutting_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    cutting_no VARCHAR(64) NOT NULL UNIQUE,
    production_order_id BIGINT UNSIGNED NULL,
    style_id BIGINT UNSIGNED NOT NULL,
    fabric_lot_id BIGINT UNSIGNED NOT NULL,
    planned_pieces INT NOT NULL,
    marker_length_meters DECIMAL(8,2) NOT NULL,
    layers_count INT NOT NULL,
    actual_cut_pieces INT NOT NULL DEFAULT 0,
    end_bits_scrap_kg DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS garment_bundles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cutting_order_id BIGINT UNSIGNED NOT NULL,
    bundle_no VARCHAR(64) NOT NULL UNIQUE,
    barcode VARCHAR(64) NOT NULL UNIQUE,
    size VARCHAR(16) NOT NULL,
    color VARCHAR(32) NOT NULL,
    quantity INT NOT NULL,
    current_line VARCHAR(32) NULL,
    status ENUM('cut', 'issued_to_sewing', 'sewing_done', 'checking', 'packed') NOT NULL DEFAULT 'cut',
    CONSTRAINT fk_bundles_cutting FOREIGN KEY (cutting_order_id) REFERENCES cutting_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sewing_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    line_no VARCHAR(32) NOT NULL,
    shift ENUM('shift_1', 'shift_2') NOT NULL,
    style_id BIGINT UNSIGNED NOT NULL,
    bundle_id BIGINT UNSIGNED NOT NULL,
    good_pieces INT NOT NULL,
    defect_pieces INT NOT NULL DEFAULT 0,
    rework_pieces INT NOT NULL DEFAULT 0,
    operator_count INT NOT NULL,
    hours_worked DECIMAL(5,2) NOT NULL DEFAULT 8.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. Quality & Inspection
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fabric_inspections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    inspection_no VARCHAR(64) NOT NULL UNIQUE,
    roll_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    inspected_meters DECIMAL(8,2) NOT NULL,
    width_inches DECIMAL(6,2) NOT NULL,
    gsm INT NOT NULL,
    total_penalty_points INT NOT NULL DEFAULT 0,
    points_per_100_sq_yd DECIMAL(6,2) NOT NULL DEFAULT 0.00, -- 4-Point System Formula
    quality_grade ENUM('A', 'B', 'C', 'reject') NOT NULL DEFAULT 'A',
    fastness_wash VARCHAR(16) NULL, -- e.g. '4-5'
    fastness_rub_dry VARCHAR(16) NULL,
    fastness_rub_wet VARCHAR(16) NULL,
    inspector_id BIGINT UNSIGNED NOT NULL,
    inspection_date DATE NOT NULL,
    disposition ENUM('accepted', 'rework', 'downgrade', 'rejected') NOT NULL DEFAULT 'accepted',
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fabric_inspection_defects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id BIGINT UNSIGNED NOT NULL,
    meter_position DECIMAL(8,2) NOT NULL,
    defect_type ENUM('hole', 'stain', 'knot', 'thick_thin', 'shade_variation', 'skewing', 'snag', 'lycra_drop') NOT NULL,
    defect_size_inches DECIMAL(5,2) NOT NULL,
    points_assigned INT NOT NULL, -- 1, 2, 3, or 4 points
    CONSTRAINT fk_defects_inspection FOREIGN KEY (inspection_id) REFERENCES fabric_inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nonconformance_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    ncr_no VARCHAR(64) NOT NULL UNIQUE,
    process_stage VARCHAR(64) NOT NULL,
    reference_doc VARCHAR(64) NOT NULL,
    defect_description TEXT NOT NULL,
    root_cause TEXT NULL,
    corrective_action TEXT NULL,
    preventive_action TEXT NULL,
    status ENUM('open', 'under_review', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    raised_by BIGINT UNSIGNED NOT NULL,
    closed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. Job-Work & Subcontract
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS job_workers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(150) NOT NULL,
    process_capability VARCHAR(100) NOT NULL, -- 'Dyeing', 'Compacting', 'Printing', 'Embroidery'
    gstin VARCHAR(15) NULL,
    address TEXT NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_work_challans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    challan_no VARCHAR(64) NOT NULL UNIQUE,
    job_worker_id BIGINT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    expected_return_date DATE NOT NULL,
    process_required VARCHAR(100) NOT NULL,
    total_qty DECIMAL(12,2) NOT NULL,
    uom VARCHAR(16) NOT NULL DEFAULT 'Kg',
    status ENUM('issued', 'partially_received', 'completed', 'overdue') NOT NULL DEFAULT 'issued',
    vehicle_no VARCHAR(32) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_work_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    receipt_no VARCHAR(64) NOT NULL UNIQUE,
    job_work_challan_id BIGINT UNSIGNED NOT NULL,
    receipt_date DATE NOT NULL,
    received_qty DECIMAL(12,2) NOT NULL,
    wastage_qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    uom VARCHAR(16) NOT NULL DEFAULT 'Kg',
    service_rate DECIMAL(10,2) NOT NULL,
    service_bill_amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. Gate, Logistics & Weighbridge
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gate_passes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    pass_no VARCHAR(64) NOT NULL UNIQUE,
    pass_type ENUM('returnable', 'non_returnable') NOT NULL DEFAULT 'non_returnable',
    category ENUM('finished_goods', 'job_work_outward', 'waste_scrap', 'machinery_repair', 'sample') NOT NULL,
    issued_to VARCHAR(150) NOT NULL,
    vehicle_no VARCHAR(32) NOT NULL,
    driver_name VARCHAR(100) NULL,
    driver_phone VARCHAR(30) NULL,
    security_officer VARCHAR(100) NOT NULL,
    out_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE NULL,
    actual_return_time DATETIME NULL,
    status ENUM('active', 'returned', 'closed', 'overdue') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weighbridge_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    slip_no VARCHAR(64) NOT NULL UNIQUE,
    vehicle_no VARCHAR(32) NOT NULL,
    transporter_name VARCHAR(100) NULL,
    material_type VARCHAR(100) NOT NULL,
    gross_weight_kg DECIMAL(12,2) NOT NULL,
    tare_weight_kg DECIMAL(12,2) NOT NULL,
    net_weight_kg DECIMAL(12,2) NOT NULL,
    linked_document_type ENUM('grn', 'dispatch', 'scrap') NOT NULL,
    linked_document_id BIGINT UNSIGNED NULL,
    weighment_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. Compliance, ETP & Environment (Tamil Nadu/Tirupur ZLD)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS etp_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    shift ENUM('shift_1', 'shift_2', 'shift_3') NOT NULL,
    inlet_flow_kld DECIMAL(10,2) NOT NULL, -- Kilo litres per day
    outlet_flow_kld DECIMAL(10,2) NOT NULL,
    ro_permeate_recovery_kld DECIMAL(10,2) NOT NULL, -- RO water recovered
    mvr_evaporator_condensate_kld DECIMAL(10,2) NOT NULL,
    ph_value DECIMAL(4,2) NOT NULL,
    cod_ppm DECIMAL(8,2) NOT NULL, -- Chemical Oxygen Demand
    bod_ppm DECIMAL(8,2) NOT NULL, -- Biochemical Oxygen Demand
    tds_ppm DECIMAL(8,2) NOT NULL, -- Total Dissolved Solids
    sludge_generated_kg DECIMAL(10,2) NOT NULL,
    operator_name VARCHAR(100) NOT NULL,
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boiler_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    boiler_code VARCHAR(32) NOT NULL,
    fuel_type ENUM('firewood', 'briquettes', 'coal', 'agro_waste') NOT NULL,
    fuel_consumed_kg DECIMAL(10,2) NOT NULL,
    steam_generated_kg DECIMAL(10,2) NOT NULL,
    steam_to_fuel_ratio DECIMAL(5,2) NOT NULL,
    operating_pressure_bar DECIMAL(5,2) NOT NULL,
    operating_hours DECIMAL(5,2) NOT NULL,
    certified_boiler_attendant VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS statutory_licences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    licence_type VARCHAR(100) NOT NULL, -- 'TNPCB Consent to Operate (CTO)', 'Factory Licence', 'Boiler Registration'
    licence_number VARCHAR(100) NOT NULL,
    issuing_authority VARCHAR(150) NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    renewal_reminder_days INT NOT NULL DEFAULT 60,
    status ENUM('valid', 'expiring_soon', 'expired', 'under_renewal') NOT NULL DEFAULT 'valid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 14. HR, Attendance & Production Wages
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(32) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    designation VARCHAR(100) NOT NULL,
    wage_type ENUM('monthly', 'daily', 'piece_rate') NOT NULL DEFAULT 'monthly',
    base_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    uan_number VARCHAR(20) NULL,
    esi_number VARCHAR(20) NULL,
    joining_date DATE NOT NULL,
    bank_account_no VARCHAR(32) NULL,
    bank_ifsc VARCHAR(16) NULL,
    status ENUM('active', 'inactive', 'terminated') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    in_time TIME NULL,
    out_time TIME NULL,
    hours_worked DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    overtime_hours DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    status ENUM('present', 'absent', 'half_day', 'on_leave', 'weekly_off') NOT NULL DEFAULT 'present',
    UNIQUE KEY uk_emp_att (employee_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS piece_rate_wages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    wage_date DATE NOT NULL,
    production_order_id BIGINT UNSIGNED NULL,
    operation_name VARCHAR(100) NOT NULL, -- e.g. Overlock Sewing, Cone Winding
    pieces_completed INT NOT NULL,
    rate_per_piece DECIMAL(8,2) NOT NULL,
    total_wage DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 15. Billing, Accounts & Financial Statements
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    account_code VARCHAR(32) NOT NULL UNIQUE,
    account_name VARCHAR(150) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    parent_account_id BIGINT UNSIGNED NULL,
    is_reconciliation TINYINT(1) NOT NULL DEFAULT 0,
    current_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    invoice_no VARCHAR(64) NOT NULL UNIQUE,
    sales_order_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    irn_number VARCHAR(100) NULL, -- E-Invoice IRN
    qr_code_data TEXT NULL,
    eway_bill_no VARCHAR(32) NULL,
    subtotal DECIMAL(18,2) NOT NULL,
    cgst_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    sgst_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    igst_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(18,2) NOT NULL,
    amount_paid DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    balance_amount DECIMAL(18,2) NOT NULL,
    status ENUM('draft', 'posted', 'partially_paid', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    posted_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (customer_id),
    INDEX (invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_invoice_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    uom_id BIGINT UNSIGNED NOT NULL,
    rate DECIMAL(14,4) NOT NULL,
    tax_code_id BIGINT UNSIGNED NULL,
    hsn_sac VARCHAR(16) NOT NULL,
    cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_inv_items_header FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    receipt_no VARCHAR(64) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    receipt_date DATE NOT NULL,
    payment_mode ENUM('neft', 'rtgs', 'cheque', 'upi', 'cash') NOT NULL DEFAULT 'neft',
    reference_number VARCHAR(64) NULL, -- Bank UTR / Cheque No
    amount DECIMAL(18,2) NOT NULL,
    unallocated_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    bank_account VARCHAR(64) NULL,
    status ENUM('draft', 'posted', 'cleared', 'cancelled') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS receipt_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_receipt_id BIGINT UNSIGNED NOT NULL,
    sales_invoice_id BIGINT UNSIGNED NOT NULL,
    allocated_amount DECIMAL(18,2) NOT NULL,
    CONSTRAINT fk_alloc_receipt FOREIGN KEY (customer_receipt_id) REFERENCES customer_receipts(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_invoice FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    bill_no VARCHAR(64) NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    goods_receipt_id BIGINT UNSIGNED NULL,
    bill_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(18,2) NOT NULL,
    tax_total DECIMAL(18,2) NOT NULL,
    grand_total DECIMAL(18,2) NOT NULL,
    amount_paid DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    balance_amount DECIMAL(18,2) NOT NULL,
    status ENUM('draft', 'posted', 'partially_paid', 'paid') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    payment_no VARCHAR(64) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    payment_mode ENUM('neft', 'rtgs', 'cheque', 'bank_transfer') NOT NULL DEFAULT 'neft',
    reference_number VARCHAR(64) NULL,
    amount DECIMAL(18,2) NOT NULL,
    status ENUM('draft', 'posted', 'cleared') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    journal_no VARCHAR(64) NOT NULL UNIQUE,
    entry_date DATE NOT NULL,
    source_type VARCHAR(64) NULL, -- 'sales_invoice', 'customer_receipt', 'payroll'
    source_id BIGINT UNSIGNED NULL,
    narration TEXT NOT NULL,
    total_debit DECIMAL(18,2) NOT NULL,
    total_credit DECIMAL(18,2) NOT NULL,
    status ENUM('draft', 'posted', 'reversed') NOT NULL DEFAULT 'draft',
    posted_by BIGINT UNSIGNED NULL,
    posted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    debit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    credit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    line_narration VARCHAR(255) NULL,
    CONSTRAINT fk_journal_lines_header FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 16. Print Templates & Document Logs
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS print_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(64) NOT NULL, -- 'sales_order', 'tax_invoice', 'batch_card'
    name VARCHAR(120) NOT NULL,
    paper_size ENUM('A4_portrait', 'A4_landscape', 'A5', 'thermal_3inch', 'thermal_4inch') NOT NULL DEFAULT 'A4_portrait',
    is_default TINYINT(1) NOT NULL DEFAULT 1,
    header_html TEXT NULL,
    body_html TEXT NOT NULL,
    footer_html TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS print_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(64) NOT NULL,
    document_id BIGINT UNSIGNED NOT NULL,
    printed_by BIGINT UNSIGNED NOT NULL,
    copy_label VARCHAR(64) NOT NULL DEFAULT 'Original for Recipient',
    reprint_reason VARCHAR(255) NULL,
    printed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- DYEING ERP MODULE SCHEMA EXTENSIONS (18 WORKFLOW TABLES)

-- =====================================================================
-- 17. KARCEM DYEING ERP MODULE WORKFLOW EXTENSIONS (18 TABLES)
-- =====================================================================

-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: textile_erp
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

--
-- Table structure for table `chemical_issue_items`
--

DROP TABLE IF EXISTS `chemical_issue_items`;

CREATE TABLE `chemical_issue_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `chemical_name` varchar(150) NOT NULL,
  `item_type` enum('dye','chemical','salt','alkali','auxiliary') NOT NULL,
  `required_qty` decimal(10,3) NOT NULL,
  `issued_qty` decimal(10,3) NOT NULL DEFAULT 0.000,
  `returned_qty` decimal(10,3) NOT NULL DEFAULT 0.000,
  `actual_consumed_qty` decimal(10,3) NOT NULL DEFAULT 0.000,
  `uom` varchar(16) NOT NULL DEFAULT 'Kg',
  `lot_number` varchar(64) DEFAULT NULL,
  `variance_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `chemical_issue_requests`
--

DROP TABLE IF EXISTS `chemical_issue_requests`;

CREATE TABLE `chemical_issue_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `request_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `request_date` date NOT NULL,
  `required_time` time NOT NULL,
  `requester_name` varchar(100) NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL DEFAULT 2,
  `total_chemicals_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','submitted','approved','partially_issued','fully_issued','closed') NOT NULL DEFAULT 'draft',
  `approved_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_no` (`request_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_batch_costings`
--

DROP TABLE IF EXISTS `dyeing_batch_costings`;

CREATE TABLE `dyeing_batch_costings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `costing_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `grey_fabric_cost` decimal(12,2) NOT NULL,
  `dyes_chemicals_cost` decimal(12,2) NOT NULL,
  `water_cost` decimal(10,2) NOT NULL,
  `steam_fuel_cost` decimal(10,2) NOT NULL,
  `power_electricity_cost` decimal(10,2) NOT NULL,
  `direct_labour_cost` decimal(10,2) NOT NULL,
  `machine_overhead_cost` decimal(10,2) NOT NULL,
  `reprocess_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_batch_cost` decimal(12,2) NOT NULL,
  `accepted_output_kg` decimal(10,2) NOT NULL,
  `cost_per_kg_input` decimal(8,2) NOT NULL,
  `cost_per_kg_accepted` decimal(8,2) NOT NULL,
  `selling_rate_per_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `gross_margin_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `costing_no` (`costing_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_batch_qcs`
--

DROP TABLE IF EXISTS `dyeing_batch_qcs`;

CREATE TABLE `dyeing_batch_qcs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `qc_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspector_name` varchar(100) NOT NULL,
  `shade_match` enum('approved','within_tolerance','rejected') NOT NULL DEFAULT 'approved',
  `side_to_side_variation` enum('none','acceptable','rejectable') NOT NULL DEFAULT 'none',
  `end_to_end_variation` enum('none','acceptable','rejectable') NOT NULL DEFAULT 'none',
  `measured_gsm` int(11) NOT NULL,
  `measured_width_inches` decimal(6,2) NOT NULL,
  `wash_fastness` varchar(16) NOT NULL DEFAULT '4-5',
  `rubbing_fastness` varchar(16) NOT NULL DEFAULT '4',
  `perspiration_fastness` varchar(16) NOT NULL DEFAULT '4',
  `ph_after_wash` decimal(4,2) NOT NULL DEFAULT 6.50,
  `accepted_kg` decimal(10,2) NOT NULL,
  `hold_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rejected_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_disposition` enum('accepted','partially_accepted','hold','rejected','reprocess') NOT NULL DEFAULT 'accepted',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qc_no` (`qc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_defects`
--

DROP TABLE IF EXISTS `dyeing_defects`;

CREATE TABLE `dyeing_defects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `defect_code` varchar(32) NOT NULL,
  `defect_name` varchar(100) NOT NULL,
  `category` enum('fabric_construction','dyeing_process','finishing','stain_contamination') NOT NULL,
  `severity` enum('minor','major','critical') NOT NULL DEFAULT 'minor',
  `four_point_penalty` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `defect_code` (`defect_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_job_cards`
--

DROP TABLE IF EXISTS `dyeing_job_cards`;

CREATE TABLE `dyeing_job_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `job_card_no` varchar(64) NOT NULL,
  `job_date` date NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `sales_order_ref` varchar(64) DEFAULT NULL,
  `customer_po` varchar(64) DEFAULT NULL,
  `priority` enum('normal','medium','high','urgent') NOT NULL DEFAULT 'normal',
  `required_date` date NOT NULL,
  `job_type` enum('regular','sample','urgent','reprocess') NOT NULL DEFAULT 'regular',
  `dyeing_route` enum('reactive','disperse','vat','pigment','acid','cationic') NOT NULL DEFAULT 'reactive',
  `fabric_id` bigint(20) unsigned DEFAULT NULL,
  `fabric_name` varchar(150) NOT NULL,
  `fabric_composition` varchar(100) NOT NULL,
  `grey_lot_no` varchar(64) DEFAULT NULL,
  `required_qty_kg` decimal(10,2) NOT NULL,
  `roll_count` int(11) NOT NULL DEFAULT 0,
  `target_gsm` int(11) NOT NULL,
  `target_width_inches` decimal(6,2) NOT NULL,
  `shade_code` varchar(64) NOT NULL,
  `shade_name` varchar(100) NOT NULL,
  `customer_shade_ref` varchar(100) DEFAULT NULL,
  `fastness_spec` varchar(100) DEFAULT 'Wash: 4-5, Rubbing: 4, Perspiration: 4',
  `special_finish` varchar(150) DEFAULT 'Soft Feel / Bio-Polish / Compacting',
  `recipe_id` bigint(20) unsigned DEFAULT NULL,
  `allocated_machine_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','planned','in_production','qc_pending','completed','cancelled') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_card_no` (`job_card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_job_work_challans`
--

DROP TABLE IF EXISTS `dyeing_job_work_challans`;

CREATE TABLE `dyeing_job_work_challans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `challan_no` varchar(64) NOT NULL,
  `challan_type` enum('outward_to_processor','inward_from_customer') NOT NULL DEFAULT 'outward_to_processor',
  `partner_id` bigint(20) unsigned NOT NULL,
  `partner_name` varchar(150) NOT NULL,
  `challan_date` date NOT NULL,
  `expected_return_date` date NOT NULL,
  `fabric_description` varchar(200) NOT NULL,
  `grey_lot_no` varchar(64) NOT NULL,
  `dispatched_rolls` int(11) NOT NULL,
  `dispatched_weight_kg` decimal(10,2) NOT NULL,
  `required_shade` varchar(100) NOT NULL,
  `process_type` varchar(100) NOT NULL DEFAULT 'Dyeing & Bio-Polish',
  `process_rate_per_kg` decimal(8,2) NOT NULL,
  `vehicle_no` varchar(32) DEFAULT NULL,
  `transporter` varchar(100) DEFAULT NULL,
  `received_rolls` int(11) NOT NULL DEFAULT 0,
  `received_weight_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `weight_loss_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','dispatched','in_process','received','qc_passed','settled') NOT NULL DEFAULT 'dispatched',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `challan_no` (`challan_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_process_sheets`
--

DROP TABLE IF EXISTS `dyeing_process_sheets`;

CREATE TABLE `dyeing_process_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `sheet_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `machine_id` bigint(20) unsigned NOT NULL,
  `total_stages` int(11) NOT NULL DEFAULT 6,
  `approved_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sheet_no` (`sheet_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_process_stages`
--

DROP TABLE IF EXISTS `dyeing_process_stages`;

CREATE TABLE `dyeing_process_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `process_sheet_id` bigint(20) unsigned NOT NULL,
  `stage_no` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `target_temperature_c` decimal(5,1) NOT NULL,
  `actual_temperature_c` decimal(5,1) DEFAULT NULL,
  `temp_ramp_rate` decimal(4,1) DEFAULT NULL,
  `target_ph` decimal(4,2) NOT NULL,
  `actual_ph` decimal(4,2) DEFAULT NULL,
  `target_duration_mins` int(11) NOT NULL,
  `actual_duration_mins` int(11) DEFAULT NULL,
  `chemical_addition` text DEFAULT NULL,
  `operator_instruction` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','deviation','hold') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_reasons`
--

DROP TABLE IF EXISTS `dyeing_reasons`;

CREATE TABLE `dyeing_reasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reason_code` varchar(32) NOT NULL,
  `reason_type` enum('wastage','reprocess','downtime','hold') NOT NULL,
  `reason_name` varchar(150) NOT NULL,
  `department` varchar(50) NOT NULL DEFAULT 'Dyeing',
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reason_code` (`reason_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_reprocess_requests`
--

DROP TABLE IF EXISTS `dyeing_reprocess_requests`;

CREATE TABLE `dyeing_reprocess_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `request_no` varchar(64) NOT NULL,
  `original_batch_id` bigint(20) unsigned NOT NULL,
  `original_batch_no` varchar(64) NOT NULL,
  `qc_inspection_id` bigint(20) unsigned DEFAULT NULL,
  `defect_category` enum('shade_variation','uneven_dyeing','patchy','off_shade','stains','poor_fastness','other') NOT NULL,
  `failed_criteria` varchar(200) NOT NULL,
  `affected_rolls` int(11) NOT NULL,
  `affected_weight_kg` decimal(10,2) NOT NULL,
  `root_cause` text NOT NULL,
  `corrective_action` text NOT NULL,
  `revised_recipe_code` varchar(64) NOT NULL,
  `additional_chem_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reprocess_attempt` int(11) NOT NULL DEFAULT 1,
  `target_machine_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending_approval','approved','in_reprocess','completed','scrapped') NOT NULL DEFAULT 'pending_approval',
  `approved_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_no` (`request_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_shade_checks`
--

DROP TABLE IF EXISTS `dyeing_shade_checks`;

CREATE TABLE `dyeing_shade_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `sample_id` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `sampling_stage` varchar(100) NOT NULL,
  `sampled_at` datetime NOT NULL,
  `sampled_by` varchar(100) NOT NULL,
  `reference_shade` varchar(100) NOT NULL,
  `light_source` varchar(64) NOT NULL DEFAULT 'D65',
  `delta_e` decimal(5,2) DEFAULT NULL,
  `l_val` decimal(6,2) DEFAULT NULL,
  `a_val` decimal(6,2) DEFAULT NULL,
  `b_val` decimal(6,2) DEFAULT NULL,
  `visual_assessment` enum('exact_match','slightly_lighter','slightly_darker','slightly_redder','slightly_greener','off_shade') NOT NULL DEFAULT 'exact_match',
  `qc_decision` enum('pass','fail','hold','add_dyes') NOT NULL DEFAULT 'pass',
  `correction_notes` text DEFAULT NULL,
  `qc_reviewer` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sample_id` (`sample_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_shades`
--

DROP TABLE IF EXISTS `dyeing_shades`;

CREATE TABLE `dyeing_shades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `shade_code` varchar(64) NOT NULL,
  `shade_name` varchar(100) NOT NULL,
  `pantone_ref` varchar(64) DEFAULT NULL,
  `hex_color` varchar(10) NOT NULL DEFAULT '#1e3a8a',
  `shade_family` enum('Navy/Blue','Black/Grey','Red/Maroon','Green/Olive','Yellow/Orange','White/Bleach','Pastel/Light') NOT NULL DEFAULT 'Navy/Blue',
  `standard_l` decimal(6,2) DEFAULT NULL,
  `standard_a` decimal(6,2) DEFAULT NULL,
  `standard_b` decimal(6,2) DEFAULT NULL,
  `delta_e_tolerance` decimal(4,2) NOT NULL DEFAULT 0.80,
  `dye_class` enum('reactive','disperse','vat','direct') NOT NULL DEFAULT 'reactive',
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `shade_code` (`shade_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_stock_transfers`
--

DROP TABLE IF EXISTS `dyeing_stock_transfers`;

CREATE TABLE `dyeing_stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `transfer_no` varchar(64) NOT NULL,
  `transfer_date` date NOT NULL,
  `source_warehouse` varchar(100) NOT NULL DEFAULT 'Dyeing Finished Floor',
  `dest_warehouse` varchar(100) NOT NULL DEFAULT 'Garment Cutting Store / Packing',
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `fabric_name` varchar(150) NOT NULL,
  `shade_name` varchar(100) NOT NULL,
  `rolls_count` int(11) NOT NULL,
  `total_weight_kg` decimal(10,2) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `receiver_name` varchar(100) DEFAULT NULL,
  `vehicle_no` varchar(32) DEFAULT NULL,
  `status` enum('draft','dispatched','received','reconciled') NOT NULL DEFAULT 'dispatched',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_no` (`transfer_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dyeing_wastage_entries`
--

DROP TABLE IF EXISTS `dyeing_wastage_entries`;

CREATE TABLE `dyeing_wastage_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `entry_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `process_stage` enum('dyeing','hydro','drying','stenter','compactor','inspection') NOT NULL,
  `input_kg` decimal(10,2) NOT NULL,
  `output_kg` decimal(10,2) NOT NULL,
  `sample_cut_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `process_waste_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `damaged_fabric_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `unaccounted_loss_kg` decimal(8,2) NOT NULL DEFAULT 0.00,
  `net_loss_kg` decimal(8,2) NOT NULL,
  `loss_percentage` decimal(5,2) NOT NULL,
  `loss_reason` varchar(255) NOT NULL,
  `disposal_method` varchar(150) DEFAULT 'Saleable Rags / Recycling',
  `reported_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_no` (`entry_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `grey_fabric_inspections`
--

DROP TABLE IF EXISTS `grey_fabric_inspections`;

CREATE TABLE `grey_fabric_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `inspection_no` varchar(64) NOT NULL,
  `inward_id` bigint(20) unsigned NOT NULL,
  `inward_no` varchar(64) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspector_name` varchar(100) NOT NULL,
  `fabric_code` varchar(64) NOT NULL,
  `grey_lot_no` varchar(64) NOT NULL,
  `measured_gsm` int(11) NOT NULL,
  `measured_width_inches` decimal(6,2) NOT NULL,
  `hole_tear_count` int(11) NOT NULL DEFAULT 0,
  `oil_stain_count` int(11) NOT NULL DEFAULT 0,
  `knitting_defect_count` int(11) NOT NULL DEFAULT 0,
  `contamination_found` enum('no','minor','severe') NOT NULL DEFAULT 'no',
  `shade_variation_obs` varchar(100) DEFAULT NULL,
  `defect_grade` enum('Grade A','Grade B','Grade C','Rejected') NOT NULL DEFAULT 'Grade A',
  `inspected_rolls` int(11) NOT NULL,
  `accepted_rolls` int(11) NOT NULL,
  `rejected_rolls` int(11) NOT NULL DEFAULT 0,
  `hold_rolls` int(11) NOT NULL DEFAULT 0,
  `accepted_weight_kg` decimal(10,2) NOT NULL,
  `rejected_weight_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_disposition` enum('accepted','hold','rejected','concession') NOT NULL DEFAULT 'accepted',
  `inspector_comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `inspection_no` (`inspection_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `grey_fabric_inwards`
--

DROP TABLE IF EXISTS `grey_fabric_inwards`;

CREATE TABLE `grey_fabric_inwards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `inward_no` varchar(64) NOT NULL,
  `inward_date` datetime NOT NULL DEFAULT current_timestamp(),
  `source_type` enum('knitting','weaving','external_supplier','transfer','customer_jobwork') NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `delivery_challan_no` varchar(64) NOT NULL,
  `po_or_order_ref` varchar(64) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `vehicle_no` varchar(32) DEFAULT NULL,
  `received_by` varchar(100) NOT NULL,
  `warehouse_location` varchar(100) NOT NULL DEFAULT 'Grey Fabric Store - Bay A',
  `fabric_code` varchar(64) NOT NULL,
  `fabric_description` varchar(200) NOT NULL,
  `construction` enum('knitted','woven','other') NOT NULL DEFAULT 'knitted',
  `composition` varchar(100) NOT NULL,
  `grey_lot_no` varchar(64) NOT NULL,
  `received_rolls` int(11) NOT NULL,
  `gross_weight_kg` decimal(10,2) NOT NULL,
  `tare_weight_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_weight_kg` decimal(10,2) NOT NULL,
  `width_inches` decimal(6,2) NOT NULL,
  `gsm` int(11) NOT NULL,
  `qc_status` enum('inspection_pending','passed','partially_passed','rejected','quarantined') NOT NULL DEFAULT 'inspection_pending',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `inward_no` (`inward_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `machine_loading_sheets`
--

DROP TABLE IF EXISTS `machine_loading_sheets`;

CREATE TABLE `machine_loading_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `loading_no` varchar(64) NOT NULL,
  `dye_batch_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(64) NOT NULL,
  `machine_id` bigint(20) unsigned NOT NULL,
  `operator_name` varchar(100) NOT NULL,
  `supervisor_name` varchar(100) NOT NULL,
  `cleaning_verified` tinyint(1) NOT NULL DEFAULT 1,
  `safety_checked` tinyint(1) NOT NULL DEFAULT 1,
  `planned_weight_kg` decimal(10,2) NOT NULL,
  `actual_loaded_kg` decimal(10,2) NOT NULL,
  `rolls_count` int(11) NOT NULL,
  `loading_start` datetime NOT NULL,
  `loading_finish` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `loading_no` (`loading_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dump completed on 2026-10-27 11:47:41
