-- =====================================================================
-- Seed Data: KARCEM Textiles & Processors Pvt Ltd (Tirupur, Tamil Nadu)
-- Realistic Real-World Production Data
-- =====================================================================

USE textile_erp;

-- 1. Company & Branches
INSERT INTO companies (id, code, name, legal_name, gstin, pan, cin, address_line1, address_line2, city, state, state_code, pincode, phone, email, website, base_currency, currency_symbol, financial_year_start, status)
VALUES (1, 'KARCEM', 'KARCEM Textiles & Processors Pvt Ltd', 'KARCEM Textiles and Processors Private Limited', '33AAACK1234F1Z5', 'AAACK1234F', 'U17111TZ2018PTC029871', 'SF No. 248/1A, SIPCOT Industrial Growth Estate', 'Perundurai Road', 'Tirupur', 'Tamil Nadu', '33', '641604', '+91 421 2234890', 'operations@karcemtextiles.com', 'https://karcemtextiles.com', 'INR', '₹', '2026-04-01', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO branches (id, company_id, code, name, branch_type, address, city, state, gstin, status) VALUES
(1, 1, 'HO-TPR', 'Head Office & Commercial', 'head_office', '45 Kumaran Road, Near Railway Station', 'Tirupur', 'Tamil Nadu', '33AAACK1234F1Z5', 'active'),
(2, 1, 'MILL-PLD', 'Spinning & Weaving Mill', 'mill', 'SF 112, Palladam-Dharapuram Highway', 'Palladam', 'Tamil Nadu', '33AAACK1234F1Z5', 'active'),
(3, 1, 'DYE-SIPCOT', 'High-Tech Dyeing & ETP Unit', 'dyeing_unit', 'Plot 12-16, SIPCOT Industrial Park', 'Perundurai', 'Tamil Nadu', '33AAACK1234F1Z5', 'active'),
(4, 1, 'GARMENT-AVN', 'Apparel & Garmenting Unit', 'garment_unit', 'Avinashi High Road, Angeripalayam', 'Tirupur', 'Tamil Nadu', '33AAACK1234F1Z5', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 2. Departments
INSERT INTO departments (id, company_id, branch_id, code, name, is_active) VALUES
(1, 1, 1, 'COMM', 'Sales & Merchandising', 1),
(2, 1, 1, 'PURCH', 'Procurement & Purchase', 1),
(3, 1, 2, 'SPIN', 'Spinning Department', 1),
(4, 1, 2, 'KNIT', 'Knitting & Weaving', 1),
(5, 1, 3, 'DYE', 'Dyeing & Processing', 1),
(6, 1, 3, 'LAB', 'Color Lab & Quality', 1),
(7, 1, 3, 'ETP', 'ETP & Zero Liquid Discharge', 1),
(8, 1, 4, 'GARMENT', 'Garmenting & Sewing', 1),
(9, 1, 3, 'STORE', 'Central Stores & Logistics', 1),
(10, 1, 1, 'FIN', 'Finance & Accounts', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. Roles & Users
INSERT INTO roles (id, company_id, name, description, is_admin) VALUES
(1, 1, 'Super Admin', 'Full enterprise control and system configurations', 1),
(2, 1, 'Production Manager', 'Release production orders, schedule machines, track WIP', 0),
(3, 1, 'Dyeing Master', 'Manage dye recipes, process parameters, loading & unloading', 0),
(4, 1, 'Quality Inspector', '4-point fabric inspection, yarn testing, lab dip trials', 0),
(5, 1, 'Stores Officer', 'GRN, stock movements, gate entry, lot assignments', 0),
(6, 1, 'Finance Manager', 'Invoicing, ledger reconciliation, receipts, payments', 0)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Password is 'password123' (bcrypt hash)
INSERT INTO users (id, company_id, branch_id, username, full_name, email, password_hash, role, role_id, department_id, designation, phone, status) VALUES
(1, 1, 1, 'admin', 'M. Senthilkumar', 'admin@karcemtextiles.com', '$2y$10$.Pi9vr28S5.88NO4u5ehyeTIAAUXESleb4S00QLSXm/V0R8iItnPK', 'admin', 1, 1, 'Managing Director', '+91 98422 12345', 'active'),
(2, 1, 3, 'dyeing_mgr', 'R. Ramakrishnan', 'dyeing@karcemtextiles.com', '$2y$10$.Pi9vr28S5.88NO4u5ehyeTIAAUXESleb4S00QLSXm/V0R8iItnPK', 'production', 3, 5, 'Dyeing Plant Head', '+91 98422 54321', 'active'),
(3, 1, 3, 'qc_lead', 'K. Murugesan', 'qc@karcemtextiles.com', '$2y$10$.Pi9vr28S5.88NO4u5ehyeTIAAUXESleb4S00QLSXm/V0R8iItnPK', 'qc', 4, 6, 'Quality Assurance Manager', '+91 98422 88776', 'active'),
(4, 1, 1, 'finance_mgr', 'S. Natarajan', 'finance@karcemtextiles.com', '$2y$10$.Pi9vr28S5.88NO4u5ehyeTIAAUXESleb4S00QLSXm/V0R8iItnPK', 'accounts', 6, 10, 'Chief Financial Officer', '+91 98422 99887', 'active'),
(5, 1, 3, 'stores_incharge', 'P. Velusamy', 'stores@karcemtextiles.com', '$2y$10$.Pi9vr28S5.88NO4u5ehyeTIAAUXESleb4S00QLSXm/V0R8iItnPK', 'stores', 5, 9, 'Senior Stores Incharge', '+91 98422 44332', 'active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- 4. UOMs
INSERT INTO uoms (id, code, name, uom_type, decimal_places, is_base, is_active) VALUES
(1, 'Kg', 'Kilogram', 'weight', 3, 1, 1),
(2, 'Gm', 'Gram', 'weight', 3, 0, 1),
(3, 'Mtr', 'Meter', 'length', 2, 0, 1),
(4, 'Roll', 'Fabric Roll', 'packaging', 0, 0, 1),
(5, 'Cone', 'Yarn Cone', 'packaging', 0, 0, 1),
(6, 'Bale', 'Cotton Bale', 'packaging', 0, 0, 1),
(7, 'Pcs', 'Pieces', 'count', 0, 1, 1),
(8, 'Ltr', 'Litre', 'volume', 2, 0, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO uom_conversions (id, from_uom_id, to_uom_id, factor) VALUES
(1, 1, 2, 1000.000000), -- 1 Kg = 1000 Gm
(2, 2, 1, 0.001000),
(3, 6, 1, 170.000000)   -- 1 Cotton Bale ~ 170 Kg
ON DUPLICATE KEY UPDATE factor=VALUES(factor);

-- 5. Tax & HSN Codes (GST Tamil Nadu)
INSERT INTO tax_codes (id, company_id, code, description, tax_type, hsn_sac, rate, cgst_rate, sgst_rate, igst_rate, effective_from, is_active) VALUES
(1, 1, 'GST5_YARN', 'Yarn & Raw Cotton (HSN 5205)', 'gst', '5205', 5.00, 2.50, 2.50, 5.00, '2024-04-01', 1),
(2, 1, 'GST5_FABRIC', 'Cotton Knitted/Woven Fabric (HSN 6006)', 'gst', '6006', 5.00, 2.50, 2.50, 5.00, '2024-04-01', 1),
(3, 1, 'GST12_GARMENT', 'Apparel & Garments > ₹1000 (HSN 6109)', 'gst', '6109', 12.00, 6.00, 6.00, 12.00, '2024-04-01', 1),
(4, 1, 'GST18_CHEM', 'Dyes, Chemicals & Auxiliaries (HSN 3204)', 'gst', '3204', 18.00, 9.00, 9.00, 18.00, '2024-04-01', 1),
(5, 1, 'GST5_JOBWORK', 'Textile Dyeing / Knitting Jobwork (SAC 9988)', 'gst', '9988', 5.00, 2.50, 2.50, 5.00, '2024-04-01', 1)
ON DUPLICATE KEY UPDATE rate=VALUES(rate);

-- 6. Warehouses & Bins
INSERT INTO warehouses (id, company_id, branch_id, code, name, warehouse_type, address, status) VALUES
(1, 1, 3, 'WH-GREY', 'Grey Fabric Inward & Storage', 'grey_fabric_store', 'Bay 1-3, SIPCOT Plant', 'active'),
(2, 1, 3, 'WH-DYES', 'Dyes & Auxiliary Chemical Store', 'dyes_chemicals', 'Controlled Temperature Bay, SIPCOT Plant', 'active'),
(3, 1, 3, 'WH-FINISH', 'Finished Dyed Fabric Warehouse', 'finished_goods', 'Bay 6-8, SIPCOT Plant', 'active'),
(4, 1, 2, 'WH-YARN', 'Yarn Storage Godown', 'yarn_store', 'Mill Godown A, Palladam', 'active'),
(5, 1, 3, 'WH-QUAR', 'QC Quarantine & Lab Holding Area', 'quarantine', 'Inspection Block, SIPCOT', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO warehouse_bins (id, warehouse_id, code, name, is_quarantine, capacity_kg, status) VALUES
(1, 1, 'GF-A01', 'Grey Rack A-01', 0, 5000.00, 'active'),
(2, 1, 'GF-A02', 'Grey Rack A-02', 0, 5000.00, 'active'),
(3, 2, 'CH-B01', 'Reactive Dyes Bin B-01', 0, 2000.00, 'active'),
(4, 2, 'CH-B02', 'Auxiliary Chemicals Bin B-02', 0, 8000.00, 'active'),
(5, 3, 'FF-C01', 'Dyed Fabric Dispatch Rack C-01', 0, 6000.00, 'active'),
(6, 5, 'QR-01', 'Quarantine Hold Area', 1, 3000.00, 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 7. Machines & Work Centres
INSERT INTO machines (id, company_id, branch_id, code, name, process_type, model_number, manufacturer, year_of_make, capacity, capacity_uom, spring_or_tubes, liquor_ratio, hourly_operating_cost, current_status, is_active) VALUES
(1, 1, 3, 'SF-01', 'Soft Flow Dyeing Vessel 500 Kg', 'soft_flow_dyeing', 'Eco-Soft 500', 'Fongs National', 2021, 500.00, 'Kg', 2, '1:5', 1850.00, 'running', 1),
(2, 1, 3, 'SF-02', 'Soft Flow Dyeing Vessel 1000 Kg', 'soft_flow_dyeing', 'Super-Flow 1000', 'Thies GmbH', 2022, 1000.00, 'Kg', 4, '1:5', 3200.00, 'available', 1),
(3, 1, 3, 'HY-01', 'High-Speed Hydro Extractor', 'hydro_extractor', 'Hydro-X 1200', 'Ferraro', 2020, 450.00, 'Kg', NULL, NULL, 650.00, 'available', 1),
(4, 1, 3, 'ST-01', 'Monforts 8-Chamber Stenter', 'stenter', 'Montex 6500', 'A. Monforts', 2021, 1200.00, 'Kg', NULL, NULL, 4200.00, 'running', 1),
(5, 1, 3, 'CP-01', 'Tubular Felt Compactor', 'compactor', 'Comptex-T', 'Santex Rimar', 2022, 800.00, 'Kg', NULL, NULL, 1400.00, 'available', 1),
(6, 1, 2, 'KN-01', 'Circular Knitting Machine 30" 24GG', 'knitting', 'Relanit 3.2 II', 'Mayer & Cie', 2021, 350.00, 'Kg', NULL, NULL, 850.00, 'running', 1),
(7, 1, 4, 'SEW-L1', 'Garment Assembly Sewing Line 1', 'sewing', 'DDL-9000C Line', 'Juki Corp', 2023, 1200.00, 'Pcs', NULL, NULL, 2800.00, 'running', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 8. Products
INSERT INTO products (id, company_id, sku, name, category, sub_category, base_uom_id, tax_code_id, hsn_sac, standard_cost, standard_price, yarn_count, fabric_composition, fabric_gsm, fabric_width_inches, fabric_construction, shade_family, reorder_level, current_stock, is_active) VALUES
(1, 1, 'YRN-30-CH', '30s Ne 100% Combed Cotton Hosiery Yarn', 'yarn', 'Cotton Yarn', 1, 1, '5205', 285.0000, 315.0000, '30s Ne Combed', '100% Cotton', NULL, NULL, NULL, 'Ecru', 2000.00, 14500.0000, 1),
(2, 1, 'GF-SJ-180', '100% Cotton Single Jersey Grey Fabric 180 GSM', 'grey_fabric', 'Single Jersey', 1, 2, '6006', 320.0000, 360.0000, '30s Ne Combed', '100% Cotton', 180, 72, 'Single Jersey', 'Grey Ecru', 3000.00, 8420.0000, 1),
(3, 1, 'DF-SJ-ROYAL', '100% Cotton Single Jersey Dyed - Royal Blue', 'dyed_fabric', 'Single Jersey', 1, 2, '6006', 420.0000, 480.0000, '30s Ne Combed', '100% Cotton', 180, 72, 'Single Jersey', 'Royal Blue', 1500.00, 5200.0000, 1),
(4, 1, 'DF-SJ-BLACK', '100% Cotton Single Jersey Dyed - Jet Black', 'dyed_fabric', 'Single Jersey', 1, 2, '6006', 415.0000, 475.0000, '30s Ne Combed', '100% Cotton', 180, 72, 'Single Jersey', 'Jet Black', 1500.00, 3800.0000, 1),
(5, 1, 'CH-REACT-BLU', 'Reactive Blue RGB 250% (Huntsman)', 'dyes_chemical', 'Dyes', 1, 4, '3204', 680.0000, 750.0000, NULL, NULL, NULL, NULL, NULL, NULL, 200.00, 850.0000, 1),
(6, 1, 'CH-SODA-ASH', 'Sodium Carbonate (Soda Ash Dense)', 'dyes_chemical', 'Auxiliaries', 1, 4, '2836', 38.0000, 45.0000, NULL, NULL, NULL, NULL, NULL, NULL, 1000.00, 4200.0000, 1),
(7, 1, 'CH-ACETIC-AC', 'Glacial Acetic Acid 99.8%', 'dyes_chemical', 'Acid', 1, 4, '2915', 65.0000, 75.0000, NULL, NULL, NULL, NULL, NULL, NULL, 500.00, 1850.0000, 1),
(8, 1, 'GMT-POLO-MENS', 'Men Classic Pique Polo T-Shirt 220 GSM', 'garment', 'Polo Shirts', 7, 3, '6109', 240.0000, 420.0000, '26s Ne Combed', '100% Cotton', 220, NULL, 'Honey Comb Pique', 'Royal Blue', 500.00, 2400.0000, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 9. Customers & Suppliers
INSERT INTO customers (id, company_id, code, name, category, gstin, pan, contact_person, phone, email, billing_address, shipping_address, city, state, state_code, pincode, currency, credit_limit, credit_days, current_balance, payment_terms, status) VALUES
(1, 1, 'CUST-001', 'Arvind Knits & Apparels Pvt Ltd', 'domestic', '33AAACA9876C1Z2', 'AAACA9876C', 'K. Rajasekaran', '+91 94433 11223', 'purchase@arvindknits.com', '12 SIDCO Industrial Area, Harvey Road', 'Unit 3, SIDCO, Tirupur', 'Tirupur', 'Tamil Nadu', '33', '641602', 'INR', 2500000.00, 45, 485000.00, '45 Days PDC', 'active'),
(2, 1, 'CUST-002', 'Viking Fashions International LLC', 'export', NULL, NULL, 'David Sterling', '+1 415 889 0012', 'sourcing@vikingfashions.com', 'Suite 400, 5th Avenue, New York, NY 10018', 'Kallapalayam ICD / Tuticorin Port', 'New York', 'Export', '99', '10018', 'USD', 10000000.00, 60, 1250000.00, 'LC at Sight 60 Days', 'active'),
(3, 1, 'CUST-003', 'Tirupur Classic Tex Processors', 'jobwork', '33AABCT4455D1ZQ', 'AABCT4455D', 'M. Soundarrajan', '+91 98422 66554', 'soundar@classictex.com', '84 Mangalam Road', '84 Mangalam Road', 'Tirupur', 'Tamil Nadu', '33', '641604', 'INR', 500000.00, 30, 85000.00, 'Net 30', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO suppliers (id, company_id, code, name, supplier_type, gstin, pan, contact_person, phone, email, address, city, state, state_code, payment_terms, msme_registered, msme_number, current_balance, status) VALUES
(1, 1, 'SUP-001', 'Vardhman Textiles Limited', 'yarn', '03AAACV0552B1ZX', 'AAACV0552B', 'R. K. Sharma', '+91 161 2228900', 'yarnsales@vardhman.com', 'Chandigarh Road', 'Ludhiana', 'Punjab', '03', 'Net 30', 0, NULL, 620000.00, 'active'),
(2, 1, 'SUP-002', 'Huntsman International (India) Pvt Ltd', 'dyes_chemicals', '27AAACH2287M1ZB', 'AAACH2287M', 'Vikram Deshmukh', '+91 22 40078000', 'dyes.south@huntsman.com', 'LBS Marg, Vikhroli West', 'Mumbai', 'Maharashtra', '27', 'Net 45', 0, NULL, 280000.00, 'active'),
(3, 1, 'SUP-003', 'Tirupur Chemical Agencies', 'dyes_chemicals', '33AAHFT1234E1Z8', 'AAHFT1234E', 'N. Karthikeyan', '+91 421 2471234', 'sales@tprchemicals.in', '34 Palladam Road, Cotton Market', 'Tirupur', 'Tamil Nadu', '33', 'Net 15', 1, 'UDYAM-TN-32-0012891', 115000.00, 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 10. Chart of Accounts (Core Double Entry Ledger)
INSERT INTO chart_of_accounts (id, company_id, account_code, account_name, account_type, parent_account_id, is_reconciliation, current_balance, is_active) VALUES
(1, 1, '1000', 'Current Assets', 'asset', NULL, 0, 0.00, 1),
(2, 1, '1010', 'HDFC Bank - Current A/c 50200012345678', 'asset', 1, 1, 4850000.00, 1),
(3, 1, '1020', 'SBI - Cash Credit Account', 'asset', 1, 1, -1200000.00, 1),
(4, 1, '1100', 'Accounts Receivable (Trade Debtors)', 'asset', 1, 1, 1820000.00, 1),
(5, 1, '1200', 'Raw Cotton & Yarn Inventory', 'asset', 1, 0, 4567500.00, 1),
(6, 1, '1210', 'Grey Fabric Inventory', 'asset', 1, 0, 2694400.00, 1),
(7, 1, '1220', 'Dyes & Chemical Inventory', 'asset', 1, 0, 878000.00, 1),
(8, 1, '1230', 'Work in Progress (WIP Dyeing & Garments)', 'asset', 1, 0, 1540000.00, 1),
(9, 1, '1240', 'Finished Goods Inventory', 'asset', 1, 0, 3504000.00, 1),
(10, 1, '2000', 'Current Liabilities', 'liability', NULL, 0, 0.00, 1),
(11, 1, '2010', 'Accounts Payable (Trade Creditors)', 'liability', 10, 1, 1015000.00, 1),
(12, 1, '2020', 'GST Output SGST Payable', 'liability', 10, 0, 142500.00, 1),
(13, 1, '2021', 'GST Output CGST Payable', 'liability', 10, 0, 142500.00, 1),
(14, 1, '2022', 'GST Output IGST Payable', 'liability', 10, 0, 285000.00, 1),
(15, 1, '3000', 'Owner Equity & Share Capital', 'equity', NULL, 0, 10000000.00, 1),
(16, 1, '4000', 'Sales Revenue - Dyed Fabric', 'revenue', NULL, 0, 28450000.00, 1),
(17, 1, '4010', 'Sales Revenue - Garments', 'revenue', NULL, 0, 18200000.00, 1),
(18, 1, '4020', 'Jobwork Processing Charges Received', 'revenue', NULL, 0, 4200000.00, 1),
(19, 1, '5000', 'Cost of Goods Sold (Raw Material Consumed)', 'expense', NULL, 0, 24500000.00, 1),
(20, 1, '5100', 'Direct Labour & Production Wages', 'expense', NULL, 0, 5400000.00, 1),
(21, 1, '5200', 'Factory Power & Fuel Charges', 'expense', NULL, 0, 3100000.00, 1),
(22, 1, '5300', 'ETP & Zero Liquid Discharge Operating Cost', 'expense', NULL, 0, 1250000.00, 1)
ON DUPLICATE KEY UPDATE account_name=VALUES(account_name);

-- 11. Document Sequences (Auto-generated Serial Numbering)
INSERT INTO document_sequences (id, company_id, document_type, prefix, next_number, padding, financial_year) VALUES
(1, 1, 'enquiry', 'ENQ-26-', 105, 5, '2026-27'),
(2, 1, 'quotation', 'QUO-26-', 112, 5, '2026-27'),
(3, 1, 'sales_order', 'SO-26-', 1045, 5, '2026-27'),
(4, 1, 'production_order', 'PRD-26-', 5021, 5, '2026-27'),
(5, 1, 'purchase_order', 'PO-26-', 3012, 5, '2026-27'),
(6, 1, 'goods_receipt', 'GRN-26-', 4018, 5, '2026-27'),
(7, 1, 'dye_batch', 'BAT-26-', 6105, 5, '2026-27'),
(8, 1, 'sales_invoice', 'INV-26-', 1024, 5, '2026-27'),
(9, 1, 'customer_receipt', 'RCT-26-', 2011, 5, '2026-27'),
(10, 1, 'gate_pass', 'GP-26-', 8015, 5, '2026-27')
ON DUPLICATE KEY UPDATE prefix=VALUES(prefix);

-- 12. Dye Recipes & Ingredients
INSERT INTO dye_recipes (id, company_id, recipe_code, shade_name, customer_id, fabric_composition, liquor_ratio, dye_class, revision_no, is_approved, approved_by, created_at) VALUES
(1, 1, 'REC-ROYAL-01', 'Royal Blue Standard', 1, '100% Cotton Single Jersey', '1:5', 'reactive', 1, 1, 2, '2026-10-15 10:00:00'),
(2, 1, 'REC-JETBLK-02', 'Jet Black Deep', 1, '100% Cotton Single Jersey', '1:6', 'reactive', 2, 1, 2, '2026-10-16 11:30:00'),
(3, 1, 'REC-OLIVE-01', 'Military Olive Drab', 2, '100% Cotton Pique', '1:6', 'reactive', 1, 1, 2, '2026-10-18 14:00:00')
ON DUPLICATE KEY UPDATE shade_name=VALUES(shade_name);

INSERT INTO dye_recipe_items (id, dye_recipe_id, chemical_product_id, item_type, dosage_percentage, dosage_grams_per_litre, process_stage) VALUES
(1, 1, 5, 'dye', 2.4500, NULL, 'dyeing'), -- 2.45% OWF Reactive Blue RGB
(2, 1, 6, 'chemical', NULL, 20.00, 'dyeing'), -- 20 g/l Soda Ash
(3, 1, 7, 'auxiliary', NULL, 1.50, 'washing'), -- 1.5 g/l Acetic Acid Neutralization
(4, 2, 5, 'dye', 4.8000, NULL, 'dyeing'),
(5, 2, 6, 'chemical', NULL, 25.00, 'dyeing')
ON DUPLICATE KEY UPDATE dosage_percentage=VALUES(dosage_percentage);

-- 13. Sample Sales Order & Production Order
INSERT INTO sales_orders (id, company_id, order_no, customer_po_no, customer_id, order_date, promised_delivery_date, order_type, currency, exchange_rate, subtotal, tax_total, grand_total, payment_terms, shipping_address, status, current_revision, credit_approved, approved_by, approved_at) VALUES
(1, 1, 'SO-26-001044', 'PO-AK-7781', 1, '2026-10-20', '2026-11-15', 'domestic', 'INR', 1.0000, 960000.00, 48000.00, 1008000.00, '45 Days Credit', 'Unit 3, SIDCO Industrial Estate, Tirupur - 641602', 'confirmed', 1, 1, 4, '2026-10-20 16:30:00')
ON DUPLICATE KEY UPDATE order_no=VALUES(order_no);

INSERT INTO sales_order_items (id, sales_order_id, product_id, shade_code, shade_name, size_breakdown, ordered_qty, produced_qty, qc_accepted_qty, dispatched_qty, uom_id, unit_price, tax_code_id, tax_amount, total_amount, delivery_date) VALUES
(1, 1, 3, 'SH-ROYAL-19', 'Royal Blue', NULL, 2000.00, 1950.00, 1920.00, 0.00, 1, 480.0000, 2, 48000.00, 1008000.00, '2026-11-15')
ON DUPLICATE KEY UPDATE ordered_qty=VALUES(ordered_qty);

INSERT INTO production_orders (id, company_id, order_no, sales_order_id, sales_order_item_id, product_id, bom_header_id, routing_id, planned_qty, actual_output_qty, rejected_qty, uom_id, start_date, due_date, stage, status, released_by, released_at) VALUES
(1, 1, 'PRD-26-005020', 1, 1, 3, NULL, NULL, 2000.00, 1950.00, 30.00, 1, '2026-10-22', '2026-11-10', 'dyeing', 'in_progress', 1, '2026-10-22 09:00:00')
ON DUPLICATE KEY UPDATE planned_qty=VALUES(planned_qty);

-- 14. Active Dye Batch (Real-time tracking)
INSERT INTO dye_batches (id, company_id, batch_no, production_order_id, dye_recipe_id, machine_id, planned_weight_kg, actual_loaded_kg, output_weight_kg, water_volume_litres, liquor_ratio, operator_name, process_start_time, process_end_time, reprocess_count, status, created_at) VALUES
(1, 1, 'BAT-26-006104', 1, 1, 1, 500.00, 498.50, 486.20, 2500.00, '1:5', 'M. Kandasamy', '2026-10-26 06:00:00', '2026-10-26 12:30:00', 0, 'completed', '2026-10-26 05:45:00')
ON DUPLICATE KEY UPDATE batch_no=VALUES(batch_no);

INSERT INTO dye_batch_parameters (id, dye_batch_id, process_step, set_temperature_c, actual_temperature_c, set_ph, actual_ph, cycle_time_minutes, recorded_at, operator_notes) VALUES
(1, 1, 'Pre-treatment / Scouring', 95.0, 94.5, 10.50, 10.40, 45, '2026-10-26 06:45:00', 'Pre-treatment liquor clear, good wettability'),
(2, 1, 'Reactive Dyeing Exhaustion', 60.0, 60.2, 7.00, 7.10, 60, '2026-10-26 08:00:00', 'Color salt added in 3 intervals smoothly'),
(3, 1, 'Fixation with Soda Ash', 60.0, 60.0, 10.80, 10.85, 45, '2026-10-26 09:15:00', 'Dosing continuous over 30 mins'),
(4, 1, 'Hot Wash & Soaping', 95.0, 93.8, 7.00, 7.00, 30, '2026-10-26 10:30:00', 'Hydrolyzed dye washed off thoroughly'),
(5, 1, 'Neutralization & Softener', 45.0, 45.0, 5.50, 5.40, 25, '2026-10-26 11:30:00', 'Hand feel verified - supple and soft')
ON DUPLICATE KEY UPDATE process_step=VALUES(process_step);

-- 15. Inventory Lots & Fabric Rolls
INSERT INTO inventory_lots (id, company_id, lot_number, product_id, supplier_lot_number, owner_type, owner_id, origin_type, origin_ref, total_qty, available_qty, reserved_qty, uom_id, unit_cost, status) VALUES
(1, 1, 'LOT-GF-202610-01', 2, 'VK-9981', 'company', NULL, 'knitting', 'KN-01', 5000.00, 4501.50, 498.50, 1, 320.0000, 'accepted'),
(2, 1, 'LOT-DF-202610-14', 3, NULL, 'company', NULL, 'dyeing', 'BAT-26-006104', 486.20, 486.20, 0.00, 1, 442.5000, 'accepted')
ON DUPLICATE KEY UPDATE lot_number=VALUES(lot_number);

INSERT INTO inventory_rolls (id, company_id, lot_id, roll_number, barcode, product_id, weight_kg, length_meters, width_inches, gsm, quality_grade, shade_group, warehouse_id, bin_id, status) VALUES
(1, 1, 2, 'RL-26-009101', 'BAR-RL-9101', 3, 24.50, 58.20, 72.00, 182, 'A', 'Band A', 3, 5, 'in_stock'),
(2, 1, 2, 'RL-26-009102', 'BAR-RL-9102', 3, 25.20, 59.80, 72.00, 180, 'A', 'Band A', 3, 5, 'in_stock'),
(3, 1, 2, 'RL-26-009103', 'BAR-RL-9103', 3, 23.80, 56.40, 71.50, 181, 'A', 'Band A', 3, 5, 'in_stock')
ON DUPLICATE KEY UPDATE roll_number=VALUES(roll_number);

-- 16. Fabric Quality Inspection (4-Point System)
INSERT INTO fabric_inspections (id, company_id, inspection_no, roll_id, batch_id, inspected_meters, width_inches, gsm, total_penalty_points, points_per_100_sq_yd, quality_grade, fastness_wash, fastness_rub_dry, fastness_rub_wet, inspector_id, inspection_date, disposition, remarks) VALUES
(1, 1, 'INSP-26-0081', 1, 1, 58.20, 72.00, 182, 6, 12.35, 'A', '4-5', '4-5', '4', 3, '2026-10-26', 'accepted', 'Excellent shade matching with approved lab dip. Minimal slubs. Passed 4-point ASTM D5430 criteria.')
ON DUPLICATE KEY UPDATE inspection_no=VALUES(inspection_no);

INSERT INTO fabric_inspection_defects (id, inspection_id, meter_position, defect_type, defect_size_inches, points_assigned) VALUES
(1, 1, 14.50, 'knot', 1.00, 1),
(2, 1, 32.20, 'stain', 2.50, 2),
(3, 1, 48.00, 'thick_thin', 5.00, 3)
ON DUPLICATE KEY UPDATE defect_type=VALUES(defect_type);

-- 17. Tamil Nadu ETP / ZLD Daily Monitoring Log
INSERT INTO etp_logs (id, company_id, log_date, shift, inlet_flow_kld, outlet_flow_kld, ro_permeate_recovery_kld, mvr_evaporator_condensate_kld, ph_value, cod_ppm, bod_ppm, tds_ppm, sludge_generated_kg, operator_name, remarks) VALUES
(1, 1, '2026-10-25', 'shift_1', 450.00, 442.00, 385.00, 57.00, 7.40, 18.50, 5.20, 1450.00, 850.00, 'G. Subramaniam', 'ZLD recovered 98.2% fresh water for recycling. Zero discharge maintained.'),
(2, 1, '2026-10-26', 'shift_1', 465.00, 458.00, 402.00, 56.00, 7.35, 16.80, 4.80, 1420.00, 820.00, 'G. Subramaniam', 'Biological aeration dissolved oxygen stable at 2.8 mg/l. RO membranes cleaned.')
ON DUPLICATE KEY UPDATE log_date=VALUES(log_date);

-- 18. Boiler Log
INSERT INTO boiler_logs (id, company_id, log_date, boiler_code, fuel_type, fuel_consumed_kg, steam_generated_kg, steam_to_fuel_ratio, operating_pressure_bar, operating_hours, certified_boiler_attendant) VALUES
(1, 1, '2026-10-26', 'BLR-01', 'briquettes', 3200.00, 14400.00, 4.50, 10.50, 12.00, 'A. Thangavel (Licence # TBR-8821)')
ON DUPLICATE KEY UPDATE log_date=VALUES(log_date);

-- 19. Employees
INSERT INTO employees (id, company_id, employee_code, full_name, department_id, designation, wage_type, base_salary, uan_number, esi_number, joining_date, bank_account_no, bank_ifsc, status) VALUES
(1, 1, 'EMP-1001', 'P. Arumugam', 5, 'Senior Dyeing Master', 'monthly', 45000.00, '100987654321', '33001234560010001', '2020-03-01', '12340100056789', 'BARB0TIRUPU', 'active'),
(2, 1, 'EMP-1002', 'C. Kalimuthu', 5, 'Dyeing Vessel Operator', 'daily', 950.00, '100987654322', '33001234560010002', '2021-06-15', '12340100056790', 'BARB0TIRUPU', 'active'),
(3, 1, 'EMP-1003', 'M. Kavitha', 8, 'Overlock Sewing Operator', 'piece_rate', 0.00, '100987654323', '33001234560010003', '2022-01-10', '12340100056791', 'BARB0TIRUPU', 'active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- 20. Sample Posted Sales Invoice (GST compliant)
INSERT INTO sales_invoices (id, company_id, invoice_no, sales_order_id, customer_id, invoice_date, due_date, irn_number, qr_code_data, eway_bill_no, subtotal, cgst_amount, sgst_amount, igst_amount, tax_total, grand_total, amount_paid, balance_amount, status, posted_at, created_by) VALUES
(1, 1, 'INV-26-001023', 1, 1, '2026-10-25', '2026-12-09', '4a8f9c2d1e0b5a6c8d7e6f4a3b2c1d0e9f8a7b6c5d4e3f2a1b0c9d8e7f6a5b4', 'E-Invoice:33AAACK1234F1Z5:INV-26-001023:₹924000:2026-10-25', '541289004412', 880000.00, 22000.00, 22000.00, 0.00, 44000.00, 924000.00, 500000.00, 424000.00, 'partially_paid', '2026-10-25 18:00:00', 4)
ON DUPLICATE KEY UPDATE invoice_no=VALUES(invoice_no);

INSERT INTO sales_invoice_items (id, sales_invoice_id, product_id, quantity, uom_id, rate, tax_code_id, hsn_sac, cgst_amount, sgst_amount, igst_amount, total_amount) VALUES
(1, 1, 3, 1833.33, 1, 480.0000, 2, '6006', 22000.00, 22000.00, 0.00, 924000.00)
ON DUPLICATE KEY UPDATE quantity=VALUES(quantity);

-- 21. Customer Receipt (Allocated)
INSERT INTO customer_receipts (id, company_id, receipt_no, customer_id, receipt_date, payment_mode, reference_number, amount, unallocated_amount, bank_account, status, created_by) VALUES
(1, 1, 'RCT-26-002010', 1, '2026-10-26', 'neft', 'HDFCN2629900142', 500000.00, 0.00, 'HDFC Bank - 50200012345678', 'posted', 4)
ON DUPLICATE KEY UPDATE receipt_no=VALUES(receipt_no);

INSERT INTO receipt_allocations (id, customer_receipt_id, sales_invoice_id, allocated_amount) VALUES
(1, 1, 1, 500000.00)
ON DUPLICATE KEY UPDATE allocated_amount=VALUES(allocated_amount);

-- 22. Balanced Journal Entry
INSERT INTO journal_entries (id, company_id, journal_no, entry_date, source_type, source_id, narration, total_debit, total_credit, status, posted_by, posted_at) VALUES
(1, 1, 'JRN-26-001001', '2026-10-26', 'customer_receipt', 1, 'NEFT receipt from Arvind Knits towards INV-26-001023 (Ref HDFCN2629900142)', 500000.00, 500000.00, 'posted', 4, '2026-10-26 11:00:00')
ON DUPLICATE KEY UPDATE journal_no=VALUES(journal_no);

INSERT INTO journal_lines (id, journal_entry_id, account_id, debit, credit, line_narration) VALUES
(1, 1, 2, 500000.00, 0.00, 'Debit: HDFC Bank Current A/c'),
(2, 1, 4, 0.00, 500000.00, 'Credit: Accounts Receivable - Arvind Knits')
ON DUPLICATE KEY UPDATE debit=VALUES(debit);
