<?php

/**
 * Pharmacy Controller
 * 
 * Handles prescription processing, medicine dispensing, inventory stock reduction, and medicine master files.
 */
class PharmacyController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display list of prescriptions
     */
    public function prescriptions()
    {
        $this->requirePermission('pharmacy.view_prescriptions');

        $status = $this->get('status', 'pending');
        $search = $this->get('search', '');

        $query = "SELECT pr.*, p.medical_record_number, p.full_name AS patient_name, 
                         u.full_name AS doctor_name, pv.visit_number
                  FROM prescriptions pr
                  JOIN patients p ON pr.patient_id = p.id
                  JOIN doctors d ON pr.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  JOIN patient_visits pv ON pr.visit_id = pv.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($status)) {
            $query .= " AND pr.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (p.full_name LIKE ? OR p.medical_record_number LIKE ? OR pr.prescription_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY pr.created_at DESC";

        $prescriptions = Database::fetchAll($query, $params);

        // Fetch prescription items for each prescription
        foreach ($prescriptions as $key => $pres) {
            $prescriptions[$key]['items'] = Database::fetchAll(
                "SELECT pi.*, m.name AS medicine_name, m.code AS medicine_code, m.unit 
                 FROM prescription_items pi 
                 JOIN medicines m ON pi.medicine_id = m.id 
                 WHERE pi.prescription_id = ?",
                [$pres['id']]
            );
        }

        $data = [
            'title' => 'Resep Farmasi - SIMRS',
            'prescriptions' => $prescriptions,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ];

        $this->view('pharmacy/views/prescriptions', $data);
    }

    /**
     * Dispense a prescription, subtract from inventory stock, and add movement history
     */
    public function dispense($id)
    {
        $this->requirePermission('pharmacy.dispense');

        if (!isPost()) {
            $this->redirect('pharmacy/prescriptions');
        }

        $this->requireCsrf();

        $prescription = Database::fetchOne("SELECT * FROM prescriptions WHERE id = ?", [$id]);
        if (!$prescription) {
            $this->setFlash('error', 'Resep tidak ditemukan.');
            $this->redirect('pharmacy/prescriptions');
        }

        if ($prescription['status'] === 'dispensed') {
            $this->setFlash('warning', 'Resep ini sudah diserahkan sebelumnya.');
            $this->redirect('pharmacy/prescriptions?status=dispensed');
        }

        $items = Database::fetchAll("SELECT * FROM prescription_items WHERE prescription_id = ?", [$id]);

        try {
            Database::beginTransaction();

            $totalPrice = 0;

            foreach ($items as $item) {
                // Find stock in main pharmacy
                $stock = Database::fetchOne(
                    "SELECT * FROM medicine_stock 
                     WHERE medicine_id = ? AND location = 'main_pharmacy' AND quantity >= ? 
                     ORDER BY expiry_date ASC LIMIT 1",
                    [$item['medicine_id'], $item['quantity']]
                );

                if (!$stock) {
                    // Try to find any stock regardless of quantity to dispense what is available or error out
                    $stock = Database::fetchOne(
                        "SELECT * FROM medicine_stock 
                         WHERE medicine_id = ? AND location = 'main_pharmacy' 
                         ORDER BY quantity DESC LIMIT 1"
                    );
                    
                    if (!$stock || $stock['quantity'] < $item['quantity']) {
                        // Get medicine name for error message
                        $med = Database::fetchOne("SELECT name FROM medicines WHERE id = ?", [$item['medicine_id']]);
                        throw new Exception("Stok obat '" . ($med['name'] ?? 'Obat') . "' tidak mencukupi di Apotek Utama.");
                    }
                }

                // Calculate cost
                $medDetails = Database::fetchOne("SELECT selling_price FROM medicines WHERE id = ?", [$item['medicine_id']]);
                $itemPrice = $medDetails['selling_price'] ?? 0;
                $itemSubtotal = $itemPrice * $item['quantity'];
                $totalPrice += $itemSubtotal;

                // Update item price
                Database::update('prescription_items', [
                    'price' => $itemPrice,
                    'total' => $itemSubtotal
                ], ['id' => $item['id']]);

                // Reduce stock
                $qtyBefore = $stock['quantity'];
                $qtyAfter = $qtyBefore - $item['quantity'];

                Database::update('medicine_stock', [
                    'quantity' => $qtyAfter,
                    'last_updated' => date('Y-m-d H:i:s'),
                    'updated_by' => Session::getUserId()
                ], ['id' => $stock['id']]);

                // Log movement
                Database::insert('medicine_stock_movements', [
                    'medicine_id' => $item['medicine_id'],
                    'movement_type' => 'out',
                    'reference_type' => 'prescription',
                    'reference_id' => $id,
                    'reference_number' => $prescription['prescription_number'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $itemPrice,
                    'total_price' => $itemSubtotal,
                    'batch_number' => $stock['batch_number'],
                    'expiry_date' => $stock['expiry_date'],
                    'location' => 'main_pharmacy',
                    'stock_before' => $qtyBefore,
                    'stock_after' => $qtyAfter,
                    'movement_date' => date('Y-m-d H:i:s'),
                    'created_by' => Session::getUserId(),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Update prescription status
            Database::update('prescriptions', [
                'status' => 'dispensed',
                'total_amount' => $totalPrice,
                'dispensed_at' => date('Y-m-d H:i:s'),
                'dispensed_by' => Session::getUserId()
            ], ['id' => $id]);

            // Add to billing invoice if visit exists
            if ($prescription['visit_id']) {
                $invoice = Database::fetchOne("SELECT id FROM invoices WHERE visit_id = ?", [$prescription['visit_id']]);
                if ($invoice && $totalPrice > 0) {
                    // Check if recipe is already invoiced
                    $existingInvItem = Database::fetchOne(
                        "SELECT id FROM invoice_items WHERE invoice_id = ? AND item_type = 'medicine' AND reference_id = ?",
                        [$invoice['id'], $id]
                    );

                    if (!$existingInvItem) {
                        Database::insert('invoice_items', [
                            'invoice_id' => $invoice['id'],
                            'item_type' => 'medicine',
                            'item_code' => $prescription['prescription_number'],
                            'item_name' => 'Resep Obat (' . $prescription['prescription_number'] . ')',
                            'quantity' => 1,
                            'unit_price' => $totalPrice,
                            'subtotal' => $totalPrice,
                            'total' => $totalPrice,
                            'reference_id' => $id,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                        // Recalculate invoice totals
                        $totals = Database::fetchOne(
                            "SELECT SUM(total) as subtotal FROM invoice_items WHERE invoice_id = ?",
                            [$invoice['id']]
                        );
                        $newSubtotal = $totals['subtotal'] ?? 0;
                        
                        $inv = Database::fetchOne("SELECT discount_percentage, tax_percentage, paid_amount FROM invoices WHERE id = ?", [$invoice['id']]);
                        $discountAmount = ($inv['discount_percentage'] / 100) * $newSubtotal;
                        $taxAmount = ($inv['tax_percentage'] / 100) * ($newSubtotal - $discountAmount);
                        $newTotal = $newSubtotal - $discountAmount + $taxAmount;
                        $outstanding = $newTotal - $inv['paid_amount'];

                        Database::update('invoices', [
                            'subtotal' => $newSubtotal,
                            'discount_amount' => $discountAmount,
                            'tax_amount' => $taxAmount,
                            'total_amount' => $newTotal,
                            'outstanding_amount' => $outstanding
                        ], ['id' => $invoice['id']]);
                    }
                }
            }

            Database::commit();

            $this->logAudit('update', 'pharmacy', 'prescriptions', $id, "Menyerahkan obat & mengurangi stok untuk resep {$prescription['prescription_number']}");
            $this->setFlash('success', 'Resep berhasil diproses dan diserahkan ke pasien.');
            $this->redirect('pharmacy/prescriptions?status=dispensed');

        } catch (Exception $e) {
            Database::rollback();
            error_log("Error dispensing prescription: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memproses resep. Error: ' . $e->getMessage());
            $this->redirect('pharmacy/prescriptions?status=pending');
        }
    }

    /**
     * Display medicine catalog
     */
    public function medicines()
    {
        $this->requirePermission('pharmacy.view_prescriptions');

        $search = $this->get('search', '');
        $category = $this->get('category', '');

        $query = "SELECT * FROM medicines WHERE is_active = 1";
        $params = [];

        if (!empty($category)) {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR generic_name LIKE ? OR code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY name ASC";

        $medicines = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Master Data Obat - SIMRS',
            'medicines' => $medicines,
            'filters' => [
                'search' => $search,
                'category' => $category
            ]
        ];

        $this->view('pharmacy/views/medicines', $data);
    }

    /**
     * Display current medicine stocks across locations
     */
    public function stock()
    {
        $this->requirePermission('pharmacy.view_prescriptions');

        $location = $this->get('location', 'main_pharmacy');
        $search = $this->get('search', '');

        $query = "SELECT ms.*, m.name AS medicine_name, m.code AS medicine_code, m.unit, m.generic_name
                  FROM medicine_stock ms
                  JOIN medicines m ON ms.medicine_id = m.id
                  WHERE ms.location = ?";
        
        $params = [$location];

        if (!empty($search)) {
            $query .= " AND (m.name LIKE ? OR m.code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY m.name ASC, ms.expiry_date ASC";

        $stocks = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Stok & Inventori Obat - SIMRS',
            'stocks' => $stocks,
            'filters' => [
                'location' => $location,
                'search' => $search
            ]
        ];

        $this->view('pharmacy/views/stock', $data);
    }
}
