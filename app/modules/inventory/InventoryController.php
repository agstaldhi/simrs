<?php

/**
 * Inventory Controller
 * 
 * Manages inventory items, purchase orders, suppliers, and stock opnames.
 */
class InventoryController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display inventory items
     */
    public function items()
    {
        $this->requirePermission('inventory.view_items');

        $search = $this->get('search', '');
        $category = $this->get('category', '');
        $action = $this->get('action', 'list');

        $query = "SELECT * FROM inventory_items WHERE 1=1";
        $params = [];

        if (!empty($category)) {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY name ASC";
        $items = Database::fetchAll($query, $params);

        $editingItem = null;
        if ($action === 'edit') {
            $id = $this->get('id');
            $editingItem = Database::fetchOne("SELECT * FROM inventory_items WHERE id = ?", [$id]);
        }

        $data = [
            'title' => 'Inventaris Gudang & BMHP - SIMRS',
            'items' => $items,
            'action' => $action,
            'editingItem' => $editingItem,
            'filters' => [
                'search' => $search,
                'category' => $category
            ]
        ];

        $this->view('inventory/views/items', $data);
    }

    /**
     * Display purchase orders list
     */
    public function purchaseOrders()
    {
        $this->requirePermission('inventory.view_purchase_orders');

        $search = $this->get('search', '');
        $status = $this->get('status', '');
        $action = $this->get('action', 'list');

        $query = "SELECT * FROM v_purchase_orders_summary WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $query .= " AND po_status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (po_number LIKE ? OR supplier_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY po_date DESC";
        $orders = Database::fetchAll($query, $params);

        $suppliers = Database::fetchAll("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Purchase Orders (PO) - SIMRS',
            'orders' => $orders,
            'suppliers' => $suppliers,
            'action' => $action,
            'filters' => [
                'search' => $search,
                'status' => $status
            ]
        ];

        $this->view('inventory/views/purchase_orders', $data);
    }

    /**
     * Display and manage suppliers
     */
    public function suppliers()
    {
        $this->requirePermission('inventory.view_suppliers');

        // Handle addition of new supplier
        if (isPost()) {
            $this->requirePermission('inventory.manage_suppliers');
            $this->requireCsrf();

            $name = $this->post('name');
            $contact = $this->post('contact_person');
            $phone = $this->post('phone');
            $email = $this->post('email');
            $category = $this->post('category', 'general');
            $address = $this->post('address');

            if (empty($name)) {
                $this->setFlash('error', 'Nama supplier wajib diisi.');
                $this->redirect('inventory/suppliers');
            }

            try {
                $code = generateDocumentNumber('SPL', 'suppliers', 'code');
                $supplierId = Database::insert('suppliers', [
                    'code' => $code,
                    'name' => $name,
                    'contact_person' => $contact,
                    'phone' => $phone,
                    'email' => $email,
                    'category' => $category,
                    'address' => $address,
                    'is_active' => 1,
                    'created_by' => Session::getUserId(),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $this->logAudit('create', 'inventory', 'suppliers', $supplierId, "Menambahkan supplier baru {$name} ({$code})");
                $this->setFlash('success', 'Supplier berhasil ditambahkan.');
            } catch (Exception $e) {
                error_log("Error store supplier: " . $e->getMessage());
                $this->setFlash('error', 'Gagal menambahkan supplier: ' . $e->getMessage());
            }

            $this->redirect('inventory/suppliers');
        }

        $search = $this->get('search', '');
        $action = $this->get('action', 'list');

        $query = "SELECT * FROM suppliers WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR code LIKE ? OR contact_person LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY name ASC";
        $suppliers = Database::fetchAll($query, $params);

        $editingSupplier = null;
        if ($action === 'edit') {
            $id = $this->get('id');
            $editingSupplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = ?", [$id]);
        }

        $data = [
            'title' => 'Daftar Supplier Vendor - SIMRS',
            'suppliers' => $suppliers,
            'action' => $action,
            'editingSupplier' => $editingSupplier,
            'filters' => [
                'search' => $search
            ]
        ];

        $this->view('inventory/views/suppliers', $data);
    }

    /**
     * Display stock opnames
     */
    public function stockOpname()
    {
        $this->requirePermission('inventory.view_stock_opname');

        $search = $this->get('search', '');
        $action = $this->get('action', 'list');

        $query = "SELECT s.*, u.full_name AS performed_by_name 
                  FROM stock_opnames s
                  LEFT JOIN users u ON s.performed_by = u.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (s.opname_number LIKE ? OR s.location LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY s.opname_date DESC";
        $opnames = Database::fetchAll($query, $params);

        $items = Database::fetchAll("SELECT id, name, code, current_stock, unit FROM inventory_items WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Stock Opname Logistik - SIMRS',
            'opnames' => $opnames,
            'items' => $items,
            'action' => $action,
            'filters' => [
                'search' => $search
            ]
        ];

        $this->view('inventory/views/stock_opname', $data);
    }

    /**
     * Store a new inventory item
     */
    public function storeItem()
    {
        $this->requirePermission('inventory.create');
        $this->requireCsrf();

        $name = $this->post('name');
        $category = $this->post('category');
        $unit = $this->post('unit', 'pcs');
        $purchasePrice = (float)$this->post('purchase_price', 0);
        $sellingPrice = (float)$this->post('selling_price', 0);
        $minimumStock = (int)$this->post('minimum_stock', 0);
        $currentStock = (int)$this->post('current_stock', 0);
        $reorderLevel = (int)$this->post('reorder_level', 0);
        $location = $this->post('location');
        $description = $this->post('description');

        if (empty($name) || empty($category)) {
            $this->setFlash('error', 'Nama barang dan kategori wajib diisi.');
            $this->redirect('inventory/items?action=add');
        }

        try {
            $code = generateDocumentNumber('INV', 'inventory_items', 'code');
            $itemId = Database::insert('inventory_items', [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'minimum_stock' => $minimumStock,
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
                'location' => $location,
                'description' => $description,
                'is_active' => 1,
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('create', 'inventory', 'inventory_items', $itemId, "Menambahkan barang inventaris baru {$name} ({$code})");
            $this->setFlash('success', 'Barang berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error store item: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }

        $this->redirect('inventory/items');
    }

    /**
     * Update an inventory item
     */
    public function updateItem($id)
    {
        $this->requirePermission('inventory.edit');
        $this->requireCsrf();

        $item = Database::fetchOne("SELECT * FROM inventory_items WHERE id = ?", [$id]);
        if (!$item) {
            $this->setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('inventory/items');
        }

        $name = $this->post('name');
        $category = $this->post('category');
        $unit = $this->post('unit', 'pcs');
        $purchasePrice = (float)$this->post('purchase_price', 0);
        $sellingPrice = (float)$this->post('selling_price', 0);
        $minimumStock = (int)$this->post('minimum_stock', 0);
        $currentStock = (int)$this->post('current_stock', 0);
        $reorderLevel = (int)$this->post('reorder_level', 0);
        $location = $this->post('location');
        $description = $this->post('description');

        if (empty($name) || empty($category)) {
            $this->setFlash('error', 'Nama barang dan kategori wajib diisi.');
            $this->redirect("inventory/items?action=edit&id={$id}");
        }

        try {
            Database::update('inventory_items', [
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'minimum_stock' => $minimumStock,
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
                'location' => $location,
                'description' => $description,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $this->logAudit('update', 'inventory', 'inventory_items', $id, "Memperbarui barang inventaris {$name} ({$item['code']})");
            $this->setFlash('success', 'Barang berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error update item: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui barang: ' . $e->getMessage());
        }

        $this->redirect('inventory/items');
    }

    /**
     * Delete an inventory item
     */
    public function deleteItem($id)
    {
        $this->requirePermission('inventory.delete');
        $this->requireCsrf();

        $item = Database::fetchOne("SELECT * FROM inventory_items WHERE id = ?", [$id]);
        if (!$item) {
            $this->setFlash('error', 'Barang tidak ditemukan.');
            $this->redirect('inventory/items');
        }

        try {
            Database::delete('inventory_items', ['id' => $id]);
            $this->logAudit('delete', 'inventory', 'inventory_items', $id, "Menghapus barang inventaris {$item['name']} ({$item['code']})");
            $this->setFlash('success', 'Barang berhasil dihapus.');
        } catch (Exception $e) {
            error_log("Error delete item: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menghapus barang (kemungkinan data terikat dengan tabel lain).');
        }

        $this->redirect('inventory/items');
    }

    /**
     * Store a new purchase order
     */
    public function storePurchaseOrder()
    {
        $this->requirePermission('inventory.create');
        $this->requireCsrf();

        $supplierId = $this->post('supplier_id');
        $poDate = $this->post('po_date');
        $expectedDeliveryDate = $this->post('expected_delivery_date') ?: null;
        $poType = $this->post('po_type', 'mixed');
        $totalAmount = (float)$this->post('total_amount', 0);
        $notes = $this->post('notes', '');

        if (empty($supplierId) || empty($poDate)) {
            $this->setFlash('error', 'Supplier dan tanggal PO wajib diisi.');
            $this->redirect('inventory/purchase-orders?action=add');
        }

        try {
            $poNumber = generateDocumentNumber('PO', 'purchase_orders', 'po_number');
            $poId = Database::insert('purchase_orders', [
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'po_date' => $poDate,
                'expected_delivery_date' => $expectedDeliveryDate,
                'po_type' => $poType,
                'po_status' => 'draft',
                'total_amount' => $totalAmount,
                'notes' => $notes,
                'requested_by' => Session::getUserId(),
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('create', 'inventory', 'purchase_orders', $poId, "Membuat Purchase Order baru {$poNumber}");
            $this->setFlash('success', 'Purchase Order berhasil dibuat.');
        } catch (Exception $e) {
            error_log("Error store PO: " . $e->getMessage());
            $this->setFlash('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }

        $this->redirect('inventory/purchase-orders');
    }

    /**
     * Delete a purchase order
     */
    public function deletePurchaseOrder($id)
    {
        $this->requirePermission('inventory.delete');
        $this->requireCsrf();

        try {
            $po = Database::fetchOne("SELECT * FROM purchase_orders WHERE id = ?", [$id]);
            if ($po) {
                Database::delete('purchase_orders', ['id' => $id]);
                $this->logAudit('delete', 'inventory', 'purchase_orders', $id, "Menghapus Purchase Order {$po['po_number']}");
                $this->setFlash('success', 'Purchase Order berhasil dihapus.');
            } else {
                $this->setFlash('error', 'Purchase Order tidak ditemukan.');
            }
        } catch (Exception $e) {
            error_log("Error delete PO: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menghapus Purchase Order.');
        }

        $this->redirect('inventory/purchase-orders');
    }

    /**
     * Store a new supplier (alternative route)
     */
    public function storeSupplier()
    {
        $this->requirePermission('inventory.manage_suppliers');
        $this->requireCsrf();

        $name = $this->post('name');
        $contact = $this->post('contact_person');
        $phone = $this->post('phone');
        $email = $this->post('email');
        $category = $this->post('category', 'general');
        $address = $this->post('address');

        if (empty($name)) {
            $this->setFlash('error', 'Nama supplier wajib diisi.');
            $this->redirect('inventory/suppliers');
        }

        try {
            $code = generateDocumentNumber('SPL', 'suppliers', 'code');
            $supplierId = Database::insert('suppliers', [
                'code' => $code,
                'name' => $name,
                'contact_person' => $contact,
                'phone' => $phone,
                'email' => $email,
                'category' => $category,
                'address' => $address,
                'is_active' => 1,
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('create', 'inventory', 'suppliers', $supplierId, "Menambahkan supplier baru {$name} ({$code})");
            $this->setFlash('success', 'Supplier berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error store supplier: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan supplier: ' . $e->getMessage());
        }

        $this->redirect('inventory/suppliers');
    }

    /**
     * Update a supplier
     */
    public function updateSupplier($id)
    {
        $this->requirePermission('inventory.manage_suppliers');
        $this->requireCsrf();

        $supplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = ?", [$id]);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier tidak ditemukan.');
            $this->redirect('inventory/suppliers');
        }

        $name = $this->post('name');
        $contact = $this->post('contact_person');
        $phone = $this->post('phone');
        $email = $this->post('email');
        $category = $this->post('category', 'general');
        $address = $this->post('address');

        if (empty($name)) {
            $this->setFlash('error', 'Nama supplier wajib diisi.');
            $this->redirect("inventory/suppliers?action=edit&id={$id}");
        }

        try {
            Database::update('suppliers', [
                'name' => $name,
                'contact_person' => $contact,
                'phone' => $phone,
                'email' => $email,
                'category' => $category,
                'address' => $address,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $this->logAudit('update', 'inventory', 'suppliers', $id, "Memperbarui data supplier {$name} ({$supplier['code']})");
            $this->setFlash('success', 'Supplier berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error update supplier: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui supplier: ' . $e->getMessage());
        }

        $this->redirect('inventory/suppliers');
    }

    /**
     * Delete a supplier
     */
    public function deleteSupplier($id)
    {
        $this->requirePermission('inventory.manage_suppliers');
        $this->requireCsrf();

        $supplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = ?", [$id]);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier tidak ditemukan.');
            $this->redirect('inventory/suppliers');
        }

        try {
            Database::delete('suppliers', ['id' => $id]);
            $this->logAudit('delete', 'inventory', 'suppliers', $id, "Menghapus supplier {$supplier['name']} ({$supplier['code']})");
            $this->setFlash('success', 'Supplier berhasil dihapus.');
        } catch (Exception $e) {
            error_log("Error delete supplier: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menghapus supplier (kemungkinan data terikat dengan tabel lain).');
        }

        $this->redirect('inventory/suppliers');
    }

    /**
     * Store a stock opname adjustment
     */
    public function storeStockOpname()
    {
        $this->requirePermission('inventory.stock_opname');
        $this->requireCsrf();

        $opnameDate = $this->post('opname_date');
        $opnameType = $this->post('opname_type', 'inventory');
        $location = $this->post('location');
        $notes = $this->post('notes', '');
        
        $itemId = $this->post('item_id');
        $physicalStock = $this->post('physical_stock');

        if (empty($opnameDate) || empty($location)) {
            $this->setFlash('error', 'Tanggal dan lokasi gudang wajib diisi.');
            $this->redirect('inventory/stock-opname?action=add');
        }

        try {
            $opnameNumber = generateDocumentNumber('SO', 'stock_opnames', 'opname_number');
            
            $opnameId = Database::insert('stock_opnames', [
                'opname_number' => $opnameNumber,
                'opname_date' => $opnameDate,
                'opname_type' => $opnameType,
                'location' => $location,
                'status' => 'approved',
                'notes' => $notes,
                'performed_by' => Session::getUserId(),
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!empty($itemId) && $physicalStock !== '') {
                $item = Database::fetchOne("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);
                if ($item) {
                    $systemStock = (int)$item['current_stock'];
                    $physical = (int)$physicalStock;
                    $discrepancy = $physical - $systemStock;

                    Database::insert('stock_opname_items', [
                        'stock_opname_id' => $opnameId,
                        'item_type' => 'inventory',
                        'item_id' => $itemId,
                        'item_code' => $item['code'],
                        'item_name' => $item['name'],
                        'system_stock' => $systemStock,
                        'physical_stock' => $physical,
                        'discrepancy' => $discrepancy,
                        'unit' => $item['unit'],
                        'unit_price' => $item['purchase_price'],
                        'discrepancy_value' => $discrepancy * $item['purchase_price'],
                        'notes' => 'Penyesuaian stok opname manual'
                    ]);

                    Database::update('inventory_items', [
                        'current_stock' => $physical
                    ], ['id' => $itemId]);

                    Database::insert('inventory_stock_movements', [
                        'inventory_item_id' => $itemId,
                        'movement_type' => 'adjustment',
                        'reference_type' => 'other',
                        'reference_id' => $opnameId,
                        'reference_number' => $opnameNumber,
                        'quantity' => $discrepancy,
                        'unit_price' => $item['purchase_price'],
                        'total_price' => $discrepancy * $item['purchase_price'],
                        'stock_before' => $systemStock,
                        'stock_after' => $physical,
                        'location_to' => $location,
                        'notes' => 'Stock opname adjustment',
                        'created_by' => Session::getUserId()
                    ]);

                    Database::update('stock_opnames', [
                        'total_items' => 1,
                        'total_discrepancy' => $discrepancy
                    ], ['id' => $opnameId]);
                }
            }

            $this->logAudit('create', 'inventory', 'stock_opnames', $opnameId, "Melakukan stock opname baru {$opnameNumber}");
            $this->setFlash('success', 'Stock opname berhasil disimpan dan stok disesuaikan.');
        } catch (Exception $e) {
            error_log("Error store stock opname: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menyimpan stock opname: ' . $e->getMessage());
        }

        $this->redirect('inventory/stock-opname');
    }
}
