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

        $data = [
            'title' => 'Inventaris Gudang & BMHP - SIMRS',
            'items' => $items,
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

        $data = [
            'title' => 'Purchase Orders (PO) - SIMRS',
            'orders' => $orders,
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

        $data = [
            'title' => 'Daftar Supplier Vendor - SIMRS',
            'suppliers' => $suppliers,
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

        $data = [
            'title' => 'Stock Opname Logistik - SIMRS',
            'opnames' => $opnames,
            'filters' => [
                'search' => $search
            ]
        ];

        $this->view('inventory/views/stock_opname', $data);
    }
}
