<?php

/**
 * Billing Controller
 * 
 * Handles patient billing invoices, payment processing, transaction records, and outstanding accounts.
 */
class BillingController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display patient invoices list
     */
    public function invoices()
    {
        $this->requirePermission('billing.view_invoices');

        $status = $this->get('status', '');
        $search = $this->get('search', '');

        $query = "SELECT * FROM v_invoices_summary WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $query .= " AND payment_status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (patient_name LIKE ? OR medical_record_number LIKE ? OR invoice_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY invoice_date DESC";

        $invoices = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Tagihan Pasien - SIMRS',
            'invoices' => $invoices,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ];

        $this->view('billing/views/invoices', $data);
    }

    /**
     * Display interface for paying an invoice
     */
    public function payments()
    {
        $this->requirePermission('billing.receive_payments');

        $invoiceId = $this->get('invoice_id');

        if (empty($invoiceId)) {
            $this->setFlash('error', 'Silakan pilih tagihan terlebih dahulu.');
            $this->redirect('billing/invoices');
        }

        $invoice = Database::fetchOne(
            "SELECT i.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                    pv.visit_number, pv.visit_date
             FROM invoices i
             JOIN patients p ON i.patient_id = p.id
             LEFT JOIN patient_visits pv ON i.visit_id = pv.id
             WHERE i.id = ?",
            [$invoiceId]
        );

        if (!$invoice) {
            $this->setFlash('error', 'Invoice tidak ditemukan.');
            $this->redirect('billing/invoices');
        }

        // Get invoice items
        $items = Database::fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC",
            [$invoiceId]
        );

        // Get past payments
        $payments = Database::fetchAll(
            "SELECT py.*, u.full_name AS cashier_name 
             FROM payments py
             LEFT JOIN users u ON py.received_by = u.id
             WHERE py.invoice_id = ? ORDER BY py.payment_date DESC",
            [$invoiceId]
        );

        $data = [
            'title' => 'Transaksi Pembayaran Kasir - SIMRS',
            'invoice' => $invoice,
            'items' => $items,
            'payments' => $payments
        ];

        $this->view('billing/views/payments', $data);
    }

    /**
     * Save payment transaction
     */
    public function storePayment()
    {
        $this->requirePermission('billing.receive_payments');

        if (!isPost()) {
            $this->redirect('billing/invoices');
        }

        $this->requireCsrf();

        $invoiceId = $this->post('invoice_id');
        $method = $this->post('payment_method');
        $amount = floatval($this->post('amount', 0));
        $notes = $this->post('notes', '');
        
        $bankName = $this->post('bank_name', null);
        $cardNumber = $this->post('card_number', null);
        $reference = $this->post('transaction_reference', null);

        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
        if (!$invoice) {
            $this->setFlash('error', 'Tagihan tidak ditemukan.');
            $this->redirect('billing/invoices');
        }

        if ($amount <= 0) {
            $this->setFlash('error', 'Jumlah pembayaran harus lebih besar dari Rp 0.');
            $this->redirect('billing/payments?invoice_id=' . $invoiceId);
        }

        if ($amount > $invoice['outstanding_amount']) {
            $this->setFlash('error', 'Jumlah pembayaran melebihi sisa tagihan (' . formatRupiah($invoice['outstanding_amount']) . ').');
            $this->redirect('billing/payments?invoice_id=' . $invoiceId);
        }

        try {
            Database::beginTransaction();

            // 1. Insert Payment record
            $paymentNum = generateDocumentNumber('PAY', 'payments', 'payment_number');
            $paymentId = Database::insert('payments', [
                'payment_number' => $paymentNum,
                'invoice_id' => $invoiceId,
                'patient_id' => $invoice['patient_id'],
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_method' => $method,
                'amount' => $amount,
                'bank_name' => $bankName,
                'card_number' => $cardNumber,
                'transaction_reference' => $reference,
                'payment_status' => 'approved',
                'notes' => $notes,
                'received_by' => Session::getUserId(),
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Update Invoice totals
            $newPaid = $invoice['paid_amount'] + $amount;
            $newOutstanding = $invoice['total_amount'] - $newPaid;
            
            $status = 'partial';
            if (round($newOutstanding, 2) <= 0) {
                $status = 'paid';
                $newOutstanding = 0;
            }

            Database::update('invoices', [
                'paid_amount' => $newPaid,
                'outstanding_amount' => $newOutstanding,
                'payment_status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $invoiceId]);

            Database::commit();

            $this->logAudit('create', 'billing', 'payments', $paymentId, "Memproses pembayaran {$paymentNum} sebesar " . formatRupiah($amount) . " untuk invoice {$invoice['invoice_number']}");
            
            $this->setFlash('success', 'Pembayaran berhasil disimpan. Status tagihan: ' . strtoupper($status));
            $this->redirect('billing/payments?invoice_id=' . $invoiceId);

        } catch (Exception $e) {
            Database::rollback();
            error_log("Error store payment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memproses pembayaran. Error: ' . $e->getMessage());
            $this->redirect('billing/payments?invoice_id=' . $invoiceId);
        }
    }

    /**
     * Display outstanding (unpaid/partial) invoices
     */
    public function outstanding()
    {
        $this->requirePermission('billing.view_invoices');

        $search = $this->get('search', '');

        $query = "SELECT * FROM v_outstanding_invoices WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (patient_name LIKE ? OR medical_record_number LIKE ? OR invoice_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY days_overdue DESC";

        $outstanding = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Piutang & Tunggakan Pasien - SIMRS',
            'outstanding' => $outstanding,
            'filters' => [
                'search' => $search
            ]
        ];

        $this->view('billing/views/outstanding', $data);
    }
}
