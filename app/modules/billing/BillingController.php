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
        $this->requirePermission('billing.process_payment');

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
        $this->requirePermission('billing.process_payment');

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

    /**
     * Generate PDF Receipt for a Payment
     */
    public function receiptPdf($id)
    {
        $this->requirePermission('billing.process_payment');

        $id = (int)$id;
        $payment = Database::fetchOne(
            "SELECT py.*, i.invoice_number, i.invoice_date, p.full_name AS patient_name, p.medical_record_number, u.full_name AS cashier_name
             FROM payments py
             JOIN invoices i ON py.invoice_id = i.id
             JOIN patients p ON py.patient_id = p.id
             LEFT JOIN users u ON py.received_by = u.id
             WHERE py.id = ?",
            [$id]
        );

        if (!$payment) {
            $this->setFlash('error', 'Pembayaran tidak ditemukan.');
            $this->redirect('billing/invoices');
        }

        $html = '
        <div style="font-family: monospace; color: #333; width: 100%; max-width: 400px; margin: 0 auto; padding: 10px; font-size: 12px; line-height: 1.4;">
            <div style="text-align: center; border-bottom: 1px dashed #333; padding-bottom: 10px; margin-bottom: 10px;">
                <h2 style="margin: 0; font-size: 16px; font-weight: bold;">RUMAH SAKIT SEHAT</h2>
                <p style="margin: 2px 0; font-size: 10px;">Jl. Kesehatan No. 123, Jakarta</p>
                <p style="margin: 2px 0; font-size: 10px;">Telp: (021) 555-1234</p>
            </div>
            
            <div style="text-align: center; margin-bottom: 15px;">
                <strong style="font-size: 13px;">KUITANSI PEMBAYARAN</strong><br>
                <span>No: ' . e($payment['payment_number']) . '</span>
            </div>
            
            <table style="width: 100%; font-size: 11px; margin-bottom: 15px;">
                <tr>
                    <td style="width: 35%; padding: 2px 0;">Tanggal</td>
                    <td style="width: 5%;">:</td>
                    <td>' . date('d-m-Y H:i', strtotime($payment['payment_date'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;">No. Rekam Medis</td>
                    <td>:</td>
                    <td><strong>' . e($payment['medical_record_number']) . '</strong></td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;">Nama Pasien</td>
                    <td>:</td>
                    <td>' . e($payment['patient_name']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;">No. Tagihan</td>
                    <td>:</td>
                    <td>' . e($payment['invoice_number']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;">Metode Bayar</td>
                    <td>:</td>
                    <td>' . strtoupper(e($payment['payment_method'])) . '</td>
                </tr>
            </table>
            
            <div style="border-top: 1px dashed #333; border-bottom: 1px dashed #333; padding: 10px 0; margin-bottom: 15px; text-align: center;">
                <span style="font-size: 11px;">NOMINAL PEMBAYARAN</span><br>
                <strong style="font-size: 20px; color: #111;">Rp ' . number_format($payment['amount'], 0, ',', '.') . '</strong>
            </div>
            
            <table style="width: 100%; font-size: 10px;">
                <tr>
                    <td style="width: 50%; text-align: center; padding-top: 40px;">
                        <p>Pasien</p>
                        <p style="margin-top: 40px;">(........................)</p>
                    </td>
                    <td style="width: 50%; text-align: center; padding-top: 40px;">
                        <p>Kasir</p>
                        <p style="margin-top: 40px; font-weight: bold;">( ' . e($payment['cashier_name'] ?: 'Petugas Kasir') . ' )</p>
                    </td>
                </tr>
            </table>
            
            <div style="text-align: center; margin-top: 20px; font-size: 9px; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
                Terima kasih atas kunjungan Anda.<br>Semoga lekas sembuh.
            </div>
        </div>';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 150],
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Kuitansi_' . e($payment['payment_number']) . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    /**
     * Generate PDF Invoice detailing services, meds, totals
     */
    public function invoicePdf($id)
    {
        $this->requirePermission('billing.view_invoices');

        $id = (int)$id;
        $invoice = Database::fetchOne(
            "SELECT i.*, p.full_name AS patient_name, p.medical_record_number, p.gender, p.birth_date, p.insurance_type,
                     pv.visit_number, pv.visit_date, d.specialization, ud.full_name AS doctor_name
             FROM invoices i
             JOIN patients p ON i.patient_id = p.id
             LEFT JOIN patient_visits pv ON i.visit_id = pv.id
             LEFT JOIN doctors d ON pv.doctor_id = d.id
             LEFT JOIN users ud ON d.user_id = ud.id
             WHERE i.id = ?",
            [$id]
        );

        if (!$invoice) {
            $this->setFlash('error', 'Tagihan tidak ditemukan.');
            $this->redirect('billing/invoices');
        }

        $items = Database::fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC",
            [$id]
        );

        $birthDate = new DateTime($invoice['birth_date']);
        $today = new DateTime($invoice['invoice_date']);
        $age = $birthDate->diff($today)->y;

        $statusColor = $invoice['payment_status'] === 'paid' ? '#2b8a3e' : ($invoice['payment_status'] === 'partial' ? '#ff9100' : '#dc3545');
        $statusText = strtoupper($invoice['payment_status']);

        $html = '
        <div style="font-family: Arial, sans-serif; color: #333; font-size: 12px; line-height: 1.4;">
            <table style="width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <tr>
                    <td style="width: 60%;">
                        <h1 style="margin: 0; font-size: 20px; color: #0b132b;">RUMAH SAKIT SEHAT</h1>
                        <p style="margin: 3px 0; font-size: 11px; color: #666;">Jl. Kesehatan No. 123, Jakarta</p>
                        <p style="margin: 3px 0; font-size: 11px; color: #666;">Telp: (021) 555-1234 | Email: info@rssehat.co.id</p>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <h2 style="margin: 0; font-size: 18px; color: #2196f3;">INVOICE TAGIHAN</h2>
                        <span style="font-size: 11px; color: #555;">No: ' . e($invoice['invoice_number']) . '</span><br>
                        <span style="font-size: 11px; color: #555;">Tanggal: ' . date('d-m-Y H:i', strtotime($invoice['invoice_date'])) . '</span>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; margin-bottom: 20px; background-color: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #e9ecef;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <table style="width: 100%;">
                            <tr><td style="width: 35%; color: #666; padding: 2px 0;">No. Rekam Medis</td><td>:</td><td>' . e($invoice['medical_record_number']) . '</td></tr>
                            <tr><td style="color: #666; padding: 2px 0;">Nama Pasien</td><td>:</td><td>' . e($invoice['patient_name']) . '</td></tr>
                            <tr><td style="color: #666; padding: 2px 0;">Umur / Gender</td><td>:</td><td>' . $age . ' Tahun / ' . ($invoice['gender'] === 'male' ? 'Laki-laki' : 'Perempuan') . '</td></tr>
                        </table>
                    </td>
                    <td style="vertical-align: top;">
                        <table style="width: 100%;">
                            <tr><td style="width: 35%; color: #666; padding: 2px 0;">No. Kunjungan</td><td>:</td><td>' . e($invoice['visit_number'] ?: '-') . '</td></tr>
                            <tr><td style="color: #666; padding: 2px 0;">Dokter / Poli</td><td>:</td><td>' . e($invoice['doctor_name'] ? 'Dr. ' . $invoice['doctor_name'] : '-') . '</td></tr>
                            <tr><td style="color: #666; padding: 2px 0;">Metode Penjamin</td><td>:</td><td><strong style="color: #2196f3;">' . strtoupper(e($invoice['insurance_type'] ?? 'UMUM')) . '</strong></td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                <thead>
                    <tr style="background-color: #2196f3; color: white;">
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 5%;">No</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 20%;">Jenis Item</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Keterangan / Rincian</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: right; width: 15%;">Harga Satuan</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 10%;">Jumlah</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: right; width: 15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

        $no = 1;
        foreach ($items as $item) {
            $typeLabel = str_replace('_', ' ', $item['item_type']);
            $typeLabel = ucwords($typeLabel);
            if ($item['item_type'] === 'medicine') $typeLabel = 'Apotek / Obat';
            if ($item['item_type'] === 'consultation') $typeLabel = 'Konsultasi';
            if ($item['item_type'] === 'registration') $typeLabel = 'Pendaftaran';
            if ($item['item_type'] === 'laboratory') $typeLabel = 'Laboratorium';
            if ($item['item_type'] === 'treatment') $typeLabel = 'Tindakan';

            $html .= '
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . $no++ . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . e($typeLabel) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . e($item['item_description']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">' . number_format($item['price'], 0, ',', '.') . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($item['quantity']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">' . number_format($item['total'], 0, ',', '.') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div style="border: 2px solid ' . $statusColor . '; color: ' . $statusColor . '; font-weight: 800; font-size: 16px; padding: 10px; width: 180px; text-align: center; border-radius: 4px; text-transform: uppercase; margin-top: 15px;">
                            STATUS: ' . $statusText . '
                        </div>
                    </td>
                    <td style="vertical-align: top;">
                        <table style="width: 100%; font-size: 12px;">
                            <tr>
                                <td style="text-align: right; padding: 5px 10px; color: #666;">Total Tagihan</td>
                                <td style="text-align: right; padding: 5px 10px; width: 130px; font-weight: bold; border-bottom: 1px solid #ddd;">Rp ' . number_format($invoice['total_amount'], 0, ',', '.') . '</td>
                            </tr>
                            <tr>
                                <td style="text-align: right; padding: 5px 10px; color: #2b8a3e;">Jumlah Terbayar</td>
                                <td style="text-align: right; padding: 5px 10px; font-weight: bold; color: #2b8a3e; border-bottom: 1px solid #ddd;">Rp ' . number_format($invoice['paid_amount'], 0, ',', '.') . '</td>
                            </tr>
                            <tr style="background-color: #fdf2f2;">
                                <td style="text-align: right; padding: 7px 10px; font-weight: bold; color: #dc3545;">Sisa Pembayaran</td>
                                <td style="text-align: right; padding: 7px 10px; font-weight: bold; color: #dc3545; border-bottom: 2px double #dc3545;">Rp ' . number_format($invoice['outstanding_amount'], 0, ',', '.') . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; margin-top: 50px; font-size: 11px;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Keluarga/Penanggung Jawab Pasien</p>
                        <p style="margin-top: 60px;">(...........................................)</p>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <p>Petugas Kasir Pembayaran</p>
                        <p style="margin-top: 60px; font-weight: bold;">( ' . e($this->getCurrentUser()['full_name'] ?? 'Kasir SIMRS') . ' )</p>
                        <p style="color: #666; font-size: 9px;">Staf Admisi & Billing</p>
                    </td>
                </tr>
            </table>
        </div>';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Invoice_' . e($invoice['invoice_number']) . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
