<?php

/**
 * Laboratory Controller
 * 
 * Handles lab order queue, sample collection, test processing, results entry, and pathologist validation.
 */
class LaboratoryController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display lab orders queue
     */
    public function orders()
    {
        $this->requirePermission('lab.view_orders');

        $status = $this->get('status', 'pending');
        $search = $this->get('search', '');

        $query = "SELECT lo.*, p.medical_record_number, p.full_name AS patient_name, 
                         u.full_name AS doctor_name, pv.visit_number
                  FROM lab_orders lo
                  JOIN patients p ON lo.patient_id = p.id
                  JOIN doctors d ON lo.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  JOIN patient_visits pv ON lo.visit_id = pv.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($status)) {
            $query .= " AND lo.order_status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (p.full_name LIKE ? OR p.medical_record_number LIKE ? OR lo.order_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY lo.created_at DESC";

        $orders = Database::fetchAll($query, $params);

        // Fetch items for each order
        foreach ($orders as $key => $order) {
            $orders[$key]['items'] = Database::fetchAll(
                "SELECT loi.*, lt.name AS test_name, lt.category 
                 FROM lab_order_items loi 
                 JOIN lab_templates lt ON loi.lab_template_id = lt.id 
                 WHERE loi.lab_order_id = ?",
                [$order['id']]
            );
        }

        $data = [
            'title' => 'Antrian Laboratorium - SIMRS',
            'orders' => $orders,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ];

        $this->view('laboratory/views/orders', $data);
    }

    /**
     * Display interface for inputting lab results
     */
    public function results()
    {
        $this->requirePermission('lab.input_results');

        $orderId = $this->get('order_id');

        if (empty($orderId)) {
            $this->setFlash('error', 'Silakan pilih order laboratorium terlebih dahulu.');
            $this->redirect('laboratory/orders');
        }

        $order = Database::fetchOne(
            "SELECT lo.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                    u.full_name AS doctor_name, pv.visit_number
             FROM lab_orders lo
             JOIN patients p ON lo.patient_id = p.id
             JOIN doctors d ON lo.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             JOIN patient_visits pv ON lo.visit_id = pv.id
             WHERE lo.id = ?",
            [$orderId]
        );

        if (!$order) {
            $this->setFlash('error', 'Order laboratorium tidak ditemukan.');
            $this->redirect('laboratory/orders');
        }

        // Get order items and their template configurations
        $items = Database::fetchAll(
            "SELECT loi.*, lt.name AS test_name, lt.category, lt.unit AS template_unit, lt.reference_range AS template_range,
                    lr.id AS result_id, lr.result_value, lr.result_unit, lr.reference_range, lr.result_flag, lr.result_interpretation
             FROM lab_order_items loi
             JOIN lab_templates lt ON loi.lab_template_id = lt.id
             LEFT JOIN lab_results lr ON lr.lab_order_item_id = loi.id
             WHERE loi.lab_order_id = ?",
            [$orderId]
        );

        $data = [
            'title' => 'Input Hasil Laboratorium - SIMRS',
            'order' => $order,
            'items' => $items
        ];

        $this->view('laboratory/views/results', $data);
    }

    /**
     * Process result inputs (performed by lab staff, verified by pathologist)
     */
    public function inputResults($id)
    {
        $this->requirePermission('lab.input_results');

        if (!isPost()) {
            $this->redirect('laboratory/orders');
        }

        $this->requireCsrf();

        $action = $this->post('action', 'save'); // 'save' (draft) or 'verify' (validation by pathologist)
        $results = $this->post('results', []); // array of loi_id => [value, flag, interpretation]

        $order = Database::fetchOne("SELECT * FROM lab_orders WHERE id = ?", [$id]);
        if (!$order) {
            $this->setFlash('error', 'Order laboratorium tidak ditemukan.');
            $this->redirect('laboratory/orders');
        }

        try {
            Database::beginTransaction();

            $allCompleted = true;

            foreach ($results as $loiId => $res) {
                $value = $res['value'] ?? '';
                $flag = $res['flag'] ?? 'normal';
                $interpretation = $res['interpretation'] ?? '';

                if (empty($value)) {
                    $allCompleted = false;
                    continue;
                }

                // Get template info to retrieve base units & reference range
                $item = Database::fetchOne(
                    "SELECT loi.*, lt.unit, lt.reference_range 
                     FROM lab_order_items loi 
                     JOIN lab_templates lt ON loi.lab_template_id = lt.id 
                     WHERE loi.id = ?",
                    [$loiId]
                );

                if (!$item) continue;

                // Check if result record already exists
                $existingResult = Database::fetchOne("SELECT id FROM lab_results WHERE lab_order_item_id = ?", [$loiId]);

                if ($existingResult) {
                    Database::update('lab_results', [
                        'result_value' => $value,
                        'result_unit' => $item['unit'],
                        'reference_range' => $item['reference_range'],
                        'result_flag' => $flag,
                        'result_interpretation' => $interpretation,
                        'performed_by' => Session::getUserId(),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], ['id' => $existingResult['id']]);
                } else {
                    Database::insert('lab_results', [
                        'lab_order_item_id' => $loiId,
                        'lab_order_id' => $id,
                        'patient_id' => $order['patient_id'],
                        'result_value' => $value,
                        'result_unit' => $item['unit'],
                        'reference_range' => $item['reference_range'],
                        'result_flag' => $flag,
                        'result_interpretation' => $interpretation,
                        'result_date' => date('Y-m-d H:i:s'),
                        'performed_by' => Session::getUserId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }

                // Update item status
                Database::update('lab_order_items', ['status' => 'completed'], ['id' => $loiId]);
            }

            // Update main lab order status
            $newOrderStatus = 'in_progress';
            if ($action === 'verify') {
                $newOrderStatus = 'completed';
                Database::update('lab_orders', [
                    'order_status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'completed_by' => Session::getUserId(),
                    'verified_by' => Session::getUserId(),
                    'verified_at' => date('Y-m-d H:i:s')
                ], ['id' => $id]);

                // Create billing invoice item if visit exists
                if ($order['visit_id']) {
                    // Check if invoice exists for this visit
                    $invoice = Database::fetchOne("SELECT id FROM invoices WHERE visit_id = ?", [$order['visit_id']]);
                    if ($invoice) {
                        // Calculate total price of lab order items
                        $totalPrice = Database::fetchOne("SELECT SUM(price) as total FROM lab_order_items WHERE lab_order_id = ?", [$id])['total'] ?? 0;
                        
                        // Check if lab item is already in invoice
                        $existingInvoiceItem = Database::fetchOne(
                            "SELECT id FROM invoice_items WHERE invoice_id = ? AND item_type = 'laboratory' AND reference_id = ?",
                            [$invoice['id'], $id]
                        );

                        if (!$existingInvoiceItem && $totalPrice > 0) {
                            Database::insert('invoice_items', [
                                'invoice_id' => $invoice['id'],
                                'item_type' => 'laboratory',
                                'item_code' => $order['order_number'],
                                'item_name' => 'Pemeriksaan Laboratorium (' . $order['order_number'] . ')',
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
                            
                            // Check tax/discount
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
            } else {
                // Just update to in_progress or sample_collected
                Database::update('lab_orders', ['order_status' => 'in_progress'], ['id' => $id]);
            }

            Database::commit();

            $logMsg = $action === 'verify' ? "Memvalidasi hasil laboratorium untuk order {$order['order_number']}" : "Menyimpan draft hasil laboratorium untuk order {$order['order_number']}";
            $this->logAudit('update', 'laboratory', 'lab_orders', $id, $logMsg);

            $this->setFlash('success', $action === 'verify' ? 'Hasil laboratorium berhasil divalidasi & diselesaikan.' : 'Draft hasil laboratorium berhasil disimpan.');
            $this->redirect('laboratory/orders?status=' . ($action === 'verify' ? 'completed' : 'in_progress'));

        } catch (Exception $e) {
            Database::rollback();
            error_log("Error saving lab results: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menyimpan hasil laboratorium. Error: ' . $e->getMessage());
            $this->redirect('laboratory/results?order_id=' . $id);
        }
    }

    /**
     * Display Reference Templates for lab examinations
     */
    public function templates()
    {
        $this->requirePermission('lab.view_orders');

        $category = $this->get('category', '');
        $search = $this->get('search', '');

        $query = "SELECT * FROM lab_templates WHERE is_active = 1";
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

        $query .= " ORDER BY category ASC, display_order ASC";

        $templates = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Katalog Parameter Laboratorium - SIMRS',
            'templates' => $templates,
            'filters' => [
                'category' => $category,
                'search' => $search
            ]
        ];

        $this->view('laboratory/views/templates', $data);
    }
}
