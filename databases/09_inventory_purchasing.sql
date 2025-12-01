-- =====================================================
-- SIMRS Database Schema
-- INVENTORY & PURCHASING
-- =====================================================

-- Tabel: suppliers
-- Menyimpan data supplier/pemasok
CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    email VARCHAR(100),
    
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    
    npwp VARCHAR(30),
    tax_type ENUM('pkp', 'non_pkp') DEFAULT 'non_pkp',
    
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(150),
    
    payment_terms VARCHAR(100) COMMENT 'TOP: 30 hari, COD, dll',
    
    category ENUM('medicine', 'medical_equipment', 'office_supplies', 'food', 'general', 'other') DEFAULT 'general',
    
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: inventory_items
-- Menyimpan item inventori selain obat (alat kesehatan, ATK, dll)
CREATE TABLE inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    
    category ENUM('medical_equipment', 'consumable', 'office_supplies', 'furniture', 'electronics', 'other') NOT NULL,
    
    description TEXT,
    
    unit VARCHAR(50) DEFAULT 'pcs',
    
    purchase_price DECIMAL(12,2) DEFAULT 0,
    selling_price DECIMAL(12,2) DEFAULT 0,
    
    minimum_stock INT DEFAULT 0,
    current_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 0,
    
    location VARCHAR(100) COMMENT 'Lokasi penyimpanan',
    
    is_consumable TINYINT(1) DEFAULT 1 COMMENT 'Habis pakai atau tidak',
    is_active TINYINT(1) DEFAULT 1,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    FULLTEXT idx_fulltext_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: purchase_orders
-- Menyimpan data purchase order/pesanan pembelian
CREATE TABLE purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    
    po_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    
    po_type ENUM('medicine', 'inventory', 'mixed') DEFAULT 'mixed',
    po_status ENUM('draft', 'submitted', 'approved', 'ordered', 'partially_received', 'received', 'completed', 'cancelled') DEFAULT 'draft',
    
    subtotal DECIMAL(12,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    shipping_cost DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    
    payment_terms VARCHAR(100),
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    
    notes TEXT,
    
    requested_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    
    INDEX idx_po_number (po_number),
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_po_date (po_date),
    INDEX idx_po_status (po_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: purchase_order_items
-- Detail item dalam purchase order
CREATE TABLE purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    
    item_type ENUM('medicine', 'inventory') NOT NULL,
    item_id INT UNSIGNED NOT NULL COMMENT 'medicine_id atau inventory_item_id',
    item_code VARCHAR(50),
    item_name VARCHAR(200),
    
    quantity_ordered INT NOT NULL,
    quantity_received INT DEFAULT 0,
    
    unit VARCHAR(50),
    unit_price DECIMAL(12,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) DEFAULT 0,
    
    batch_number VARCHAR(50),
    expiry_date DATE,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    
    INDEX idx_purchase_order_id (purchase_order_id),
    INDEX idx_item_type (item_type),
    INDEX idx_item_id (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: goods_receipts
-- Penerimaan barang dari supplier
CREATE TABLE goods_receipts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(20) NOT NULL UNIQUE,
    purchase_order_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    
    receipt_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    delivery_note_number VARCHAR(50) COMMENT 'Nomor surat jalan',
    invoice_number VARCHAR(50) COMMENT 'Nomor faktur supplier',
    
    total_items INT DEFAULT 0,
    
    status ENUM('draft', 'verified', 'completed') DEFAULT 'draft',
    
    notes TEXT,
    
    received_by INT UNSIGNED,
    verified_by INT UNSIGNED,
    verified_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_purchase_order_id (purchase_order_id),
    INDEX idx_receipt_date (receipt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: goods_receipt_items
-- Detail item penerimaan barang
CREATE TABLE goods_receipt_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_receipt_id INT UNSIGNED NOT NULL,
    purchase_order_item_id INT UNSIGNED NOT NULL,
    
    item_type ENUM('medicine', 'inventory') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    item_code VARCHAR(50),
    item_name VARCHAR(200),
    
    quantity_ordered INT NOT NULL,
    quantity_received INT NOT NULL,
    quantity_accepted INT NOT NULL,
    quantity_rejected INT DEFAULT 0,
    
    unit VARCHAR(50),
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    
    batch_number VARCHAR(50),
    expiry_date DATE,
    
    condition_notes TEXT COMMENT 'Catatan kondisi barang',
    rejection_reason TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items(id) ON DELETE RESTRICT,
    
    INDEX idx_goods_receipt_id (goods_receipt_id),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: inventory_stock_movements
-- Pergerakan stok inventory (selain obat)
CREATE TABLE inventory_stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT UNSIGNED NOT NULL,
    
    movement_type ENUM('in', 'out', 'adjustment', 'damaged', 'lost', 'return') NOT NULL,
    reference_type ENUM('purchase', 'usage', 'maintenance', 'transfer', 'other') NOT NULL,
    reference_id INT UNSIGNED,
    reference_number VARCHAR(50),
    
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    
    stock_before INT DEFAULT 0,
    stock_after INT DEFAULT 0,
    
    location_from VARCHAR(100),
    location_to VARCHAR(100),
    
    movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    created_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    
    INDEX idx_inventory_item_id (inventory_item_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_movement_date (movement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: stock_opnames
-- Stok opname berkala
CREATE TABLE stock_opnames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opname_number VARCHAR(20) NOT NULL UNIQUE,
    opname_date DATE NOT NULL,
    
    opname_type ENUM('medicine', 'inventory', 'all') NOT NULL,
    location VARCHAR(100),
    
    status ENUM('draft', 'in_progress', 'completed', 'approved') DEFAULT 'draft',
    
    total_items INT DEFAULT 0,
    total_discrepancy INT DEFAULT 0,
    
    notes TEXT,
    
    performed_by INT UNSIGNED,
    verified_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_opname_number (opname_number),
    INDEX idx_opname_date (opname_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: stock_opname_items
-- Detail item stok opname
CREATE TABLE stock_opname_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_opname_id INT UNSIGNED NOT NULL,
    
    item_type ENUM('medicine', 'inventory') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    item_code VARCHAR(50),
    item_name VARCHAR(200),
    
    system_stock INT NOT NULL COMMENT 'Stok menurut sistem',
    physical_stock INT NOT NULL COMMENT 'Stok fisik hasil hitung',
    discrepancy INT NOT NULL COMMENT 'Selisih (physical - system)',
    
    unit VARCHAR(50),
    unit_price DECIMAL(12,2) DEFAULT 0,
    discrepancy_value DECIMAL(12,2) DEFAULT 0 COMMENT 'Nilai selisih',
    
    batch_number VARCHAR(50),
    expiry_date DATE,
    
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stock_opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE,
    
    INDEX idx_stock_opname_id (stock_opname_id),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: Suppliers
-- =====================================================

INSERT INTO suppliers (
    code, name, contact_person, phone, email, 
    address, city, province, 
    payment_terms, category
) VALUES
('SUP-001', 'PT Kimia Farma Trading & Distribution', 'Budi Santoso', '021-5551234', 'sales@kimiafarma.co.id', 
 'Jl. Veteran No. 9', 'Jakarta Pusat', 'DKI Jakarta',
 'NET 30', 'medicine'),
 
('SUP-002', 'PT Kalbe Farma Tbk', 'Siti Rahmawati', '021-4602222', 'sales@kalbe.co.id',
 'Jl. Let. Jend. Suprapto Kav. 4', 'Jakarta Pusat', 'DKI Jakarta',
 'NET 30', 'medicine'),
 
('SUP-003', 'PT Dexa Medica', 'Ahmad Kurniawan', '021-4207910', 'sales@dexa-medica.com',
 'Jl. Bambang Utoyo No. 138', 'Tangerang', 'Banten',
 'NET 45', 'medicine'),
 
('SUP-004', 'PT Enseval Putera Megatrading', 'Dewi Lestari', '021-4602244', 'info@enseval.co.id',
 'Kawasan Industri Pulogadung', 'Jakarta Timur', 'DKI Jakarta',
 'NET 30', 'medicine'),
 
('SUP-005', 'PT Alkes Prima Medika', 'Joko Widodo', '021-8881234', 'sales@alkesprimamedika.com',
 'Jl. Gatot Subroto Kav. 32', 'Jakarta Selatan', 'DKI Jakarta',
 'NET 30', 'medical_equipment'),
 
('SUP-006', 'CV Sumber Makmur Jaya', 'Rina Wijaya', '021-7778899', 'sumbermakrmur@gmail.com',
 'Jl. Pasar Minggu Raya No. 45', 'Jakarta Selatan', 'DKI Jakarta',
 'COD', 'office_supplies');

-- =====================================================
-- Seed Data: Inventory Items (Non-Medicine)
-- =====================================================

INSERT INTO inventory_items (
    code, name, category, description, unit, 
    purchase_price, selling_price, minimum_stock, current_stock, reorder_level
) VALUES
-- Medical Equipment (Consumables)
('INV-001', 'Sarung Tangan Steril Size M', 'consumable', 'Sarung tangan latex steril ukuran M', 'box', 75000, 95000, 50, 200, 100),
('INV-002', 'Sarung Tangan Steril Size L', 'consumable', 'Sarung tangan latex steril ukuran L', 'box', 75000, 95000, 50, 180, 100),
('INV-003', 'Masker Medis 3 Ply', 'consumable', 'Masker bedah 3 lapis isi 50', 'box', 50000, 75000, 100, 500, 200),
('INV-004', 'Spuit 3cc', 'consumable', 'Syringe 3cc disposable', 'box', 45000, 65000, 100, 300, 150),
('INV-005', 'Spuit 5cc', 'consumable', 'Syringe 5cc disposable', 'box', 50000, 70000, 100, 280, 150),
('INV-006', 'Spuit 10cc', 'consumable', 'Syringe 10cc disposable', 'box', 60000, 85000, 50, 150, 100),
('INV-007', 'Infus Set Dewasa', 'consumable', 'IV Set untuk dewasa', 'box', 120000, 180000, 50, 100, 75),
('INV-008', 'Infus Set Anak', 'consumable', 'IV Set untuk anak dengan buret', 'box', 150000, 225000, 30, 60, 50),
('INV-009', 'Kateter Urin No. 14', 'consumable', 'Kateter urin steril ukuran 14', 'pcs', 8000, 12000, 50, 100, 75),
('INV-010', 'Kateter Urin No. 16', 'consumable', 'Kateter urin steril ukuran 16', 'pcs', 8000, 12000, 50, 95, 75),
('INV-011', 'NGT (Nasogastric Tube) No. 14', 'consumable', 'Selang nasogastrik ukuran 14', 'pcs', 10000, 15000, 30, 50, 40),
('INV-012', 'Kapas Perban Roll', 'consumable', 'Perban kapas roll 10cm x 10m', 'roll', 15000, 22000, 100, 200, 150),
('INV-013', 'Plester Micropore 1 inch', 'consumable', 'Plester hypoallergenic 1 inch', 'roll', 12000, 18000, 100, 150, 120),
('INV-014', 'Kasa Steril 10x10', 'consumable', 'Kasa steril 10x10cm isi 10', 'box', 25000, 35000, 80, 150, 100),
('INV-015', 'Alkohol Swab', 'consumable', 'Alcohol prep pad', 'box', 20000, 30000, 100, 250, 150),
('INV-016', 'Povidone Iodine Swab', 'consumable', 'Betadine swab stick', 'box', 35000, 50000, 50, 100, 75),

-- Medical Equipment (Durable)
('INV-101', 'Tensimeter Digital', 'medical_equipment', 'Alat ukur tekanan darah digital', 'unit', 350000, 500000, 10, 25, 15),
('INV-102', 'Thermometer Digital', 'medical_equipment', 'Termometer digital infrared', 'unit', 150000, 225000, 20, 50, 30),
('INV-103', 'Pulse Oximeter', 'medical_equipment', 'Alat ukur saturasi oksigen', 'unit', 250000, 375000, 15, 30, 20),
('INV-104', 'Stetoskop Littmann', 'medical_equipment', 'Stetoskop premium Littmann Classic III', 'unit', 1500000, 2200000, 5, 15, 10),

-- Office Supplies
('INV-201', 'Kertas HVS A4 80gr', 'office_supplies', 'Kertas HVS A4 80 gram per rim', 'rim', 40000, 55000, 50, 100, 75),
('INV-202', 'Tinta Printer Black', 'office_supplies', 'Tinta printer hitam original', 'pcs', 300000, 450000, 5, 10, 8),
('INV-203', 'Tinta Printer Color', 'office_supplies', 'Tinta printer warna original', 'pcs', 350000, 525000, 5, 8, 8),
('INV-204', 'Pulpen', 'office_supplies', 'Pulpen standard warna biru', 'box', 30000, 45000, 10, 20, 15),
('INV-205', 'Map Snelhechter', 'office_supplies', 'Map snelhechter plastik', 'box', 50000, 75000, 10, 15, 12);

-- =====================================================
-- Seed Data: Sample Purchase Order
-- =====================================================

INSERT INTO purchase_orders (
    po_number, supplier_id, po_date, expected_delivery_date,
    po_type, po_status, subtotal, tax_percentage, tax_amount, total_amount, payment_terms
) VALUES
(
    'PO-2024-0001',
    (SELECT id FROM suppliers WHERE code = 'SUP-001'),
    '2024-01-10',
    '2024-01-17',
    'medicine',
    'received',
    10000000,
    11,
    1100000,
    11100000,
    'NET 30'
),
(
    'PO-2024-0002',
    (SELECT id FROM suppliers WHERE code = 'SUP-005'),
    '2024-01-12',
    '2024-01-19',
    'inventory',
    'received',
    5000000,
    11,
    550000,
    5550000,
    'NET 30'
);

-- =====================================================
-- Seed Data: Purchase Order Items
-- =====================================================

-- PO-0001: Medicine
INSERT INTO purchase_order_items (
    purchase_order_id, item_type, item_id, item_code, item_name,
    quantity_ordered, quantity_received, unit, unit_price, subtotal
) VALUES
(
    (SELECT id FROM purchase_orders WHERE po_number = 'PO-2024-0001'),
    'medicine',
    (SELECT id FROM medicines WHERE code = 'AMX500'),
    'AMX500',
    'Amoxicillin 500mg',
    2000,
    2000,
    'tablet',
    500,
    1000000
),
(
    (SELECT id FROM purchase_orders WHERE po_number = 'PO-2024-0001'),
    'medicine',
    (SELECT id FROM medicines WHERE code = 'PARA500'),
    'PARA500',
    'Paracetamol 500mg',
    5000,
    5000,
    'tablet',
    100,
    500000
);

-- PO-0002: Inventory Items
INSERT INTO purchase_order_items (
    purchase_order_id, item_type, item_id, item_code, item_name,
    quantity_ordered, quantity_received, unit, unit_price, subtotal
) VALUES
(
    (SELECT id FROM purchase_orders WHERE po_number = 'PO-2024-0002'),
    'inventory',
    (SELECT id FROM inventory_items WHERE code = 'INV-001'),
    'INV-001',
    'Sarung Tangan Steril Size M',
    100,
    100,
    'box',
    75000,
    7500000
),
(
    (SELECT id FROM purchase_orders WHERE po_number = 'PO-2024-0002'),
    'inventory',
    (SELECT id FROM inventory_items WHERE code = 'INV-003'),
    'INV-003',
    'Masker Medis 3 Ply',
    200,
    200,
    'box',
    50000,
    10000000
);

-- =====================================================
-- View: Low Stock Items
-- =====================================================

CREATE OR REPLACE VIEW v_low_stock_inventory AS
SELECT 
    code,
    name,
    category,
    unit,
    current_stock,
    minimum_stock,
    reorder_level,
    CASE 
        WHEN current_stock = 0 THEN 'out_of_stock'
        WHEN current_stock <= minimum_stock THEN 'critical'
        WHEN current_stock <= reorder_level THEN 'low'
        ELSE 'normal'
    END AS stock_status
FROM inventory_items
WHERE is_active = 1
AND current_stock <= reorder_level
ORDER BY stock_status DESC, current_stock ASC;

-- =====================================================
-- View: Purchase Order Summary
-- =====================================================

CREATE OR REPLACE VIEW v_purchase_orders_summary AS
SELECT 
    po.id,
    po.po_number,
    po.po_date,
    po.expected_delivery_date,
    po.po_type,
    po.po_status,
    s.code AS supplier_code,
    s.name AS supplier_name,
    s.phone AS supplier_phone,
    po.total_amount,
    po.payment_status,
    COUNT(poi.id) AS total_items,
    po.created_at
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.id
LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
GROUP BY po.id
ORDER BY po.po_date DESC;

-- =====================================================
-- END OF INVENTORY & PURCHASING
-- =====================================================