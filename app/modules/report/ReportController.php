<?php

/**
 * Report Controller
 * 
 * Handles hospital daily revenue logs, monthly visitation charts, financial statements, and custom exports.
 */
class ReportController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Daily Revenue report
     */
    public function daily()
    {
        $this->requirePermission('reports.view');

        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-14 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));

        // Query daily revenue view
        $query = "SELECT * FROM v_daily_revenue 
                  WHERE payment_date BETWEEN ? AND ? 
                  ORDER BY payment_date DESC, total_revenue DESC";
        $revenue = Database::fetchAll($query, [$startDate, $endDate]);

        // Aggregate daily total
        $totals = Database::fetchOne(
            "SELECT COUNT(DISTINCT payment_date) as active_days, 
                    SUM(transaction_count) as total_tx, 
                    SUM(total_revenue) as total_rev 
             FROM v_daily_revenue 
             WHERE payment_date BETWEEN ? AND ?",
            [$startDate, $endDate]
        );

        $export = $this->get('export', '');
        if ($export === 'pdf') {
            $this->exportDailyPdf($revenue, $totals, $startDate, $endDate);
        } elseif ($export === 'excel') {
            $this->exportDailyExcel($revenue, $totals, $startDate, $endDate);
        }

        $data = [
            'title' => 'Laporan Pendapatan Harian - SIMRS',
            'revenue' => $revenue,
            'totals' => $totals,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];

        $this->view('report/views/daily', $data);
    }

    /**
     * Monthly statistics report
     */
    public function monthly()
    {
        $this->requirePermission('reports.view');

        $year = $this->get('year', date('Y'));

        // Count patient visits by month
        $visits = Database::fetchAll(
            "SELECT MONTH(visit_date) as month_num, COUNT(id) as total_visits,
                    SUM(CASE WHEN visit_type = 'outpatient' THEN 1 ELSE 0 END) as outpatient_count,
                    SUM(CASE WHEN visit_type = 'inpatient' THEN 1 ELSE 0 END) as inpatient_count,
                    SUM(CASE WHEN visit_type = 'emergency' THEN 1 ELSE 0 END) as emergency_count
             FROM patient_visits 
             WHERE YEAR(visit_date) = ?
             GROUP BY MONTH(visit_date)
             ORDER BY month_num ASC",
            [$year]
        );

        // Map months to names
        $monthlyStats = [];
        $monthsIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Initialize grid
        for ($i = 1; $i <= 12; $i++) {
            $monthlyStats[$i] = [
                'month_name' => $monthsIndo[$i],
                'total_visits' => 0,
                'outpatient' => 0,
                'inpatient' => 0,
                'emergency' => 0
            ];
        }

        foreach ($visits as $row) {
            $m = (int)$row['month_num'];
            $monthlyStats[$m]['total_visits'] = (int)$row['total_visits'];
            $monthlyStats[$m]['outpatient'] = (int)$row['outpatient_count'];
            $monthlyStats[$m]['inpatient'] = (int)$row['inpatient_count'];
            $monthlyStats[$m]['emergency'] = (int)$row['emergency_count'];
        }

        $export = $this->get('export', '');
        if ($export === 'pdf') {
            $this->exportMonthlyPdf($monthlyStats, $year);
        } elseif ($export === 'excel') {
            $this->exportMonthlyExcel($monthlyStats, $year);
        }

        $data = [
            'title' => 'Laporan Kunjungan Bulanan - SIMRS',
            'monthlyStats' => $monthlyStats,
            'year' => $year
        ];

        $this->view('report/views/monthly', $data);
    }

    /**
     * Financial summaries report
     */
    public function financial()
    {
        $this->requirePermission('reports.view');

        $month = $this->get('month', date('m'));
        $year = $this->get('year', date('Y'));

        // Income by item category in invoice_items
        $incomeDetails = Database::fetchAll(
            "SELECT ii.item_type, SUM(ii.total) as total_amount, COUNT(ii.id) as item_count 
             FROM invoice_items ii
             JOIN invoices i ON ii.invoice_id = i.id
             WHERE MONTH(i.invoice_date) = ? AND YEAR(i.invoice_date) = ? AND i.payment_status IN ('paid', 'partial')
             GROUP BY ii.item_type
             ORDER BY total_amount DESC",
            [$month, $year]
        );

        // Payments total by payment method
        $paymentMethods = Database::fetchAll(
            "SELECT payment_method, SUM(amount) as total_amount, COUNT(id) as tx_count
             FROM payments
             WHERE MONTH(payment_date) = ? AND YEAR(payment_date) = ? AND payment_status = 'approved'
             GROUP BY payment_method
             ORDER BY total_amount DESC",
            [$month, $year]
        );

        $export = $this->get('export', '');
        if ($export === 'pdf') {
            $this->exportFinancialPdf($incomeDetails, $paymentMethods, $month, $year);
        } elseif ($export === 'excel') {
            $this->exportFinancialExcel($incomeDetails, $paymentMethods, $month, $year);
        }

        $data = [
            'title' => 'Laporan Rekapitulasi Keuangan - SIMRS',
            'incomeDetails' => $incomeDetails,
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'month' => $month,
                'year' => $year
            ]
        ];

        $this->view('report/views/financial', $data);
    }

    /**
     * Custom report filtering and export simulations
     */
    public function custom()
    {
        $this->requirePermission('reports.view');

        $reportType = $this->get('report_type', 'visits');
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        $results = [];

        if ($reportType === 'visits') {
            $results = Database::fetchAll(
                "SELECT pv.visit_number, pv.visit_date, pv.visit_type, pv.payment_method, 
                        p.full_name AS patient_name, p.medical_record_number, d.full_name AS doctor_name
                 FROM patient_visits pv
                 JOIN patients p ON pv.patient_id = p.id
                 LEFT JOIN doctors doc ON pv.doctor_id = doc.id
                 LEFT JOIN users d ON doc.user_id = d.id
                 WHERE DATE(pv.visit_date) BETWEEN ? AND ?
                 ORDER BY pv.visit_date DESC",
                [$startDate, $endDate]
            );
        } elseif ($reportType === 'prescriptions') {
            $results = Database::fetchAll(
                "SELECT pr.prescription_number, pr.prescription_date, pr.status, pr.total_amount,
                        p.full_name AS patient_name, p.medical_record_number, d.full_name AS doctor_name
                 FROM prescriptions pr
                 JOIN patients p ON pr.patient_id = p.id
                 LEFT JOIN users d ON pr.doctor_id = d.id
                 WHERE DATE(pr.prescription_date) BETWEEN ? AND ?
                 ORDER BY pr.prescription_date DESC",
                [$startDate, $endDate]
            );
        } elseif ($reportType === 'lab') {
            $results = Database::fetchAll(
                "SELECT lo.order_number, lo.order_date, lo.order_status, lo.priority,
                        p.full_name AS patient_name, p.medical_record_number, d.full_name AS doctor_name
                 FROM lab_orders lo
                 JOIN patients p ON lo.patient_id = p.id
                 LEFT JOIN users d ON lo.doctor_id = d.id
                 WHERE DATE(lo.order_date) BETWEEN ? AND ?
                 ORDER BY lo.order_date DESC",
                [$startDate, $endDate]
            );
        }

        $export = $this->get('export', '');
        if ($export === 'pdf') {
            $this->exportCustomPdf($results, $reportType, $startDate, $endDate);
        } elseif ($export === 'excel') {
            $this->exportCustomExcel($results, $reportType, $startDate, $endDate);
        }

        $data = [
            'title' => 'Ekspor Kustom Laporan SIMRS',
            'results' => $results,
            'filters' => [
                'report_type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];

        $this->view('report/views/custom', $data);
    }

    /**
     * Common PDF renderer
     */
    private function exportPdf($html, $filename = 'report.pdf')
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    /**
     * Common Excel generator
     */
    private function exportExcel($headers, $rows, $title = 'Report', $filename = 'report.xlsx')
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);
        
        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }
        
        // Write rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $col = 'A';
            foreach ($row as $val) {
                $sheet->setCellValue($col . $rowNum, $val);
                $col++;
            }
            $rowNum++;
        }
        
        // Set auto column width
        $lastCol = chr(ord('A') + count($headers) - 1);
        foreach (range('A', $lastCol) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // Write to output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportDailyPdf($revenue, $totals, $startDate, $endDate)
    {
        $html = '
        <div style="font-family: Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 20px;">RUMAH SAKIT SEHAT</h1>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Jl. Kesehatan No. 123, Jakarta - Telp: (021) 555-1234</p>
                <h2 style="margin: 15px 0 0 0; font-size: 16px; color: #2196f3;">LAPORAN PENDAPATAN HARIAN</h2>
                <p style="margin: 5px 0 0 0; font-size: 12px;">Periode: ' . date('d-m-Y', strtotime($startDate)) . ' s/d ' . date('d-m-Y', strtotime($endDate)) . '</p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                <thead>
                    <tr style="background-color: #2196f3; color: white;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Tanggal</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Jumlah Transaksi</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Tunai</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Non-Tunai</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($revenue as $row) {
            $html .= '
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . date('d-m-Y', strtotime($row['payment_date'])) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['transaction_count']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rp ' . number_format($row['cash_revenue'], 0, ',', '.') . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rp ' . number_format($row['non_cash_revenue'], 0, ',', '.') . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">Rp ' . number_format($row['total_revenue'], 0, ',', '.') . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr style="background-color: #f1f3f5; font-weight: bold;">
                        <td style="padding: 10px; border: 1px solid #ddd;">TOTAL</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . number_format($totals['total_tx']) . '</td>
                        <td colspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: right;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right; color: #2196f3;">Rp ' . number_format($totals['total_rev'], 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 50px; text-align: right; font-size: 12px;">
                <p>Jakarta, ' . date('d-m-Y') . '</p>
                <p style="margin-top: 60px; font-weight: bold;">( ' . e($this->getCurrentUser()['full_name'] ?? 'Petugas Keuangan') . ' )</p>
                <p style="color: #666; font-size: 10px;">Petugas Keuangan SIMRS</p>
            </div>
        </div>';
        
        $this->exportPdf($html, 'Laporan_Pendapatan_Harian_' . date('Ymd') . '.pdf');
    }

    private function exportDailyExcel($revenue, $totals, $startDate, $endDate)
    {
        $rows = [];
        foreach ($revenue as $row) {
            $rows[] = [
                date('d-m-Y', strtotime($row['payment_date'])),
                $row['transaction_count'],
                $row['cash_revenue'],
                $row['non_cash_revenue'],
                $row['total_revenue']
            ];
        }
        $rows[] = ['TOTAL', $totals['total_tx'], '', '', $totals['total_rev']];
        
        $headers = ["Tanggal", "Jumlah Transaksi", "Tunai", "Non-Tunai", "Total Pendapatan"];
        $this->exportExcel($headers, $rows, 'Pendapatan Harian', 'Laporan_Pendapatan_Harian_' . date('Ymd') . '.xlsx');
    }

    private function exportMonthlyPdf($monthlyStats, $year)
    {
        $html = '
        <div style="font-family: Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 20px;">RUMAH SAKIT SEHAT</h1>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Jl. Kesehatan No. 123, Jakarta - Telp: (021) 555-1234</p>
                <h2 style="margin: 15px 0 0 0; font-size: 16px; color: #2196f3;">LAPORAN KUNJUNGAN BULANAN PASIEN</h2>
                <p style="margin: 5px 0 0 0; font-size: 12px;">Tahun: ' . e($year) . '</p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                <thead>
                    <tr style="background-color: #2196f3; color: white;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Bulan</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Rawat Jalan</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Rawat Inap</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">IGD</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Total Kunjungan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $totalOutpatient = 0;
        $totalInpatient = 0;
        $totalEmergency = 0;
        $totalVisits = 0;
        
        foreach ($monthlyStats as $row) {
            $totalOutpatient += $row['outpatient'];
            $totalInpatient += $row['inpatient'];
            $totalEmergency += $row['emergency'];
            $totalVisits += $row['total_visits'];
            
            $html .= '
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . e($row['month_name']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['outpatient']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['inpatient']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['emergency']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">' . number_format($row['total_visits']) . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr style="background-color: #f1f3f5; font-weight: bold;">
                        <td style="padding: 10px; border: 1px solid #ddd;">TOTAL KUNJUNGAN</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . number_format($totalOutpatient) . '</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . number_format($totalInpatient) . '</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . number_format($totalEmergency) . '</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: #2196f3;">' . number_format($totalVisits) . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 50px; text-align: right; font-size: 12px;">
                <p>Jakarta, ' . date('d-m-Y') . '</p>
                <p style="margin-top: 60px; font-weight: bold;">( ' . e($this->getCurrentUser()['full_name'] ?? 'Petugas Admisi') . ' )</p>
                <p style="color: #666; font-size: 10px;">Petugas Rekam Medis SIMRS</p>
            </div>
        </div>';
        
        $this->exportPdf($html, 'Laporan_Kunjungan_Bulanan_' . $year . '.pdf');
    }

    private function exportMonthlyExcel($monthlyStats, $year)
    {
        $rows = [];
        $totalOutpatient = 0;
        $totalInpatient = 0;
        $totalEmergency = 0;
        $totalVisits = 0;
        
        foreach ($monthlyStats as $row) {
            $totalOutpatient += $row['outpatient'];
            $totalInpatient += $row['inpatient'];
            $totalEmergency += $row['emergency'];
            $totalVisits += $row['total_visits'];
            
            $rows[] = [
                $row['month_name'],
                $row['outpatient'],
                $row['inpatient'],
                $row['emergency'],
                $row['total_visits']
            ];
        }
        $rows[] = ['TOTAL KUNJUNGAN', $totalOutpatient, $totalInpatient, $totalEmergency, $totalVisits];
        
        $headers = ["Bulan", "Rawat Jalan", "Rawat Inap", "IGD", "Total Kunjungan"];
        $this->exportExcel($headers, $rows, 'Kunjungan Bulanan', 'Laporan_Kunjungan_Bulanan_' . $year . '.xlsx');
    }

    private function exportFinancialPdf($incomeDetails, $paymentMethods, $month, $year)
    {
        $monthsIndo = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $monthName = $monthsIndo[$month] ?? $month;
        
        $html = '
        <div style="font-family: Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 20px;">RUMAH SAKIT SEHAT</h1>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Jl. Kesehatan No. 123, Jakarta - Telp: (021) 555-1234</p>
                <h2 style="margin: 15px 0 0 0; font-size: 16px; color: #2196f3;">LAPORAN REKAPITULASI KEUANGAN</h2>
                <p style="margin: 5px 0 0 0; font-size: 12px;">Periode: ' . $monthName . ' ' . $year . '</p>
            </div>
            
            <h3 style="font-size: 14px; color: #333; margin-bottom: 10px;">1. Pendapatan Berdasarkan Kategori Layanan</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12px;">
                <thead>
                    <tr style="background-color: #2196f3; color: white;">
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Kategori Layanan / Item</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Jumlah Item</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total Nominal</th>
                    </tr>
                </thead>
                <tbody>';
        
        $totalIncome = 0;
        $totalItems = 0;
        foreach ($incomeDetails as $row) {
            $totalIncome += $row['total_amount'];
            $totalItems += $row['item_count'];
            $html .= '
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . ucfirst(e($row['item_type'])) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['item_count']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rp ' . number_format($row['total_amount'], 0, ',', '.') . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr style="background-color: #f1f3f5; font-weight: bold;">
                        <td style="padding: 8px; border: 1px solid #ddd;">SUBTOTAL PENDAPATAN</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($totalItems) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #2196f3;">Rp ' . number_format($totalIncome, 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="font-size: 14px; color: #333; margin-bottom: 10px;">2. Penerimaan Kas Berdasarkan Metode Pembayaran</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                <thead>
                    <tr style="background-color: #2b8a3e; color: white;">
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Metode Pembayaran</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Jumlah Transaksi</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total Penerimaan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $totalPayments = 0;
        $totalTxCount = 0;
        foreach ($paymentMethods as $row) {
            $totalPayments += $row['total_amount'];
            $totalTxCount += $row['tx_count'];
            $html .= '
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">' . strtoupper(e($row['payment_method'])) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($row['tx_count']) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rp ' . number_format($row['total_amount'], 0, ',', '.') . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr style="background-color: #f1f3f5; font-weight: bold;">
                        <td style="padding: 8px; border: 1px solid #ddd;">SUBTOTAL PENERIMAAN</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($totalTxCount) . '</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #2b8a3e;">Rp ' . number_format($totalPayments, 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 40px; text-align: right; font-size: 12px;">
                <p>Jakarta, ' . date('d-m-Y') . '</p>
                <p style="margin-top: 60px; font-weight: bold;">( ' . e($this->getCurrentUser()['full_name'] ?? 'Petugas Keuangan') . ' )</p>
                <p style="color: #666; font-size: 10px;">Kepala Keuangan Rumah Sakit Sehat</p>
            </div>
        </div>';
        
        $this->exportPdf($html, 'Laporan_Rekap_Keuangan_' . $month . '_' . $year . '.pdf');
    }

    private function exportFinancialExcel($incomeDetails, $paymentMethods, $month, $year)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Sheet 1: Pendapatan Layanan
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Pendapatan Layanan');
        
        $sheet1->setCellValue('A1', 'Pendapatan Berdasarkan Kategori Layanan');
        $sheet1->mergeCells('A1:C1');
        $sheet1->getStyle('A1')->getFont()->setBold(true);
        
        $sheet1->setCellValue('A3', 'Kategori Layanan');
        $sheet1->setCellValue('B3', 'Jumlah Item');
        $sheet1->setCellValue('C3', 'Total Nominal');
        $sheet1->getStyle('A3:C3')->getFont()->setBold(true);
        
        $rowNum = 4;
        $totalIncome = 0;
        $totalItems = 0;
        foreach ($incomeDetails as $row) {
            $totalIncome += $row['total_amount'];
            $totalItems += $row['item_count'];
            
            $sheet1->setCellValue('A' . $rowNum, ucfirst($row['item_type']));
            $sheet1->setCellValue('B' . $rowNum, $row['item_count']);
            $sheet1->setCellValue('C' . $rowNum, $row['total_amount']);
            $rowNum++;
        }
        
        $sheet1->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet1->setCellValue('B' . $rowNum, $totalItems);
        $sheet1->setCellValue('C' . $rowNum, $totalIncome);
        $sheet1->getStyle('A' . $rowNum . ':C' . $rowNum)->getFont()->setBold(true);
        
        foreach (range('A', 'C') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Sheet 2: Penerimaan Kas
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Penerimaan Kas');
        
        $sheet2->setCellValue('A1', 'Penerimaan Kas Berdasarkan Metode Pembayaran');
        $sheet2->mergeCells('A1:C1');
        $sheet2->getStyle('A1')->getFont()->setBold(true);
        
        $sheet2->setCellValue('A3', 'Metode Pembayaran');
        $sheet2->setCellValue('B3', 'Jumlah Transaksi');
        $sheet2->setCellValue('C3', 'Total Penerimaan');
        $sheet2->getStyle('A3:C3')->getFont()->setBold(true);
        
        $rowNum = 4;
        $totalPayments = 0;
        $totalTxCount = 0;
        foreach ($paymentMethods as $row) {
            $totalPayments += $row['total_amount'];
            $totalTxCount += $row['tx_count'];
            
            $sheet2->setCellValue('A' . $rowNum, strtoupper($row['payment_method']));
            $sheet2->setCellValue('B' . $rowNum, $row['tx_count']);
            $sheet2->setCellValue('C' . $rowNum, $row['total_amount']);
            $rowNum++;
        }
        
        $sheet2->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet2->setCellValue('B' . $rowNum, $totalTxCount);
        $sheet2->setCellValue('C' . $rowNum, $totalPayments);
        $sheet2->getStyle('A' . $rowNum . ':C' . $rowNum)->getFont()->setBold(true);
        
        foreach (range('A', 'C') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Laporan_Rekap_Keuangan_' . $month . '_' . $year . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportCustomPdf($results, $reportType, $startDate, $endDate)
    {
        $reportNames = [
            'visits' => 'LAPORAN KUNJUNGAN PASIEN',
            'prescriptions' => 'LAPORAN RESEP OBAT / APOTEK',
            'lab' => 'LAPORAN PEMERIKSAAN LABORATORIUM'
        ];
        $reportTitle = $reportNames[$reportType] ?? 'LAPORAN KUSTOM SIMRS';
        
        $html = '
        <div style="font-family: Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 20px;">RUMAH SAKIT SEHAT</h1>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Jl. Kesehatan No. 123, Jakarta - Telp: (021) 555-1234</p>
                <h2 style="margin: 15px 0 0 0; font-size: 16px; color: #2196f3;">' . $reportTitle . '</h2>
                <p style="margin: 5px 0 0 0; font-size: 12px;">Periode: ' . date('d-m-Y', strtotime($startDate)) . ' s/d ' . date('d-m-Y', strtotime($endDate)) . '</p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                <thead>
                    <tr style="background-color: #2196f3; color: white;">';
        
        if ($reportType === 'visits') {
            $html .= '
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No Kunjungan</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Waktu Kunjungan</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tipe Visit</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No RM</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nama Pasien</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Dokter</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Cara Bayar</th>';
        } elseif ($reportType === 'prescriptions') {
            $html .= '
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No Resep</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tanggal Resep</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No RM</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nama Pasien</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Dokter Penulis</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Status</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total Biaya</th>';
        } elseif ($reportType === 'lab') {
            $html .= '
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No Permintaan Lab</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tanggal Order</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No RM</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nama Pasien</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Dokter Pengirim</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Prioritas</th>
                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Status</th>';
        }
        
        $html .= '
                    </tr>
                </thead>
                <tbody>';
        
        if (empty($results)) {
            $colsCount = ($reportType === 'lab' || $reportType === 'visits' || $reportType === 'prescriptions') ? 7 : 5;
            $html .= '<tr><td colspan="' . $colsCount . '" style="padding: 15px; border: 1px solid #ddd; text-align: center; color: #999;">Tidak ada data ditemukan untuk periode ini.</td></tr>';
        } else {
            foreach ($results as $row) {
                $html .= '<tr>';
                if ($reportType === 'visits') {
                    $html .= '
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['visit_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . date('d-m-Y H:i', strtotime($row['visit_date'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . ucfirst(e($row['visit_type'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['medical_record_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['patient_name']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['doctor_name'] ?: '-') . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . strtoupper(e($row['payment_method'] ?? 'UMUM')) . '</td>';
                } elseif ($reportType === 'prescriptions') {
                    $html .= '
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['prescription_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . date('d-m-Y H:i', strtotime($row['prescription_date'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['medical_record_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['patient_name']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['doctor_name'] ?: '-') . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd; text-align: center;">' . ucfirst(e($row['status'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd; text-align: right; font-weight: bold;">Rp ' . number_format($row['total_amount'], 0, ',', '.') . '</td>';
                } elseif ($reportType === 'lab') {
                    $html .= '
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['order_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . date('d-m-Y H:i', strtotime($row['order_date'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['medical_record_number']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['patient_name']) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd;">' . e($row['doctor_name'] ?: '-') . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd; text-align: center;">' . strtoupper(e($row['priority'])) . '</td>
                        <td style="padding: 6px; border: 1px solid #ddd; text-align: center;">' . ucfirst(e($row['order_status'])) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div style="margin-top: 40px; text-align: right; font-size: 12px;">
                <p>Jakarta, ' . date('d-m-Y') . '</p>
                <p style="margin-top: 60px; font-weight: bold;">( ' . e($this->getCurrentUser()['full_name'] ?? 'Petugas SIMRS') . ' )</p>
                <p style="color: #666; font-size: 10px;">Laporan Terenkripsi Sistem SIMRS</p>
            </div>
        </div>';
        
        $this->exportPdf($html, 'Laporan_' . ucfirst($reportType) . '_' . date('Ymd') . '.pdf');
    }

    private function exportCustomExcel($results, $reportType, $startDate, $endDate)
    {
        $rows = [];
        if ($reportType === 'visits') {
            $headers = ["No Kunjungan", "Waktu Kunjungan", "Tipe Visit", "No RM", "Nama Pasien", "Dokter", "Cara Bayar"];
            foreach ($results as $row) {
                $rows[] = [
                    $row['visit_number'],
                    $row['visit_date'],
                    $row['visit_type'],
                    $row['medical_record_number'],
                    $row['patient_name'],
                    $row['doctor_name'] ?: '-',
                    strtoupper($row['payment_method'] ?? 'UMUM')
                ];
            }
        } elseif ($reportType === 'prescriptions') {
            $headers = ["No Resep", "Tanggal Resep", "No RM", "Nama Pasien", "Dokter Penulis", "Status", "Total Biaya"];
            foreach ($results as $row) {
                $rows[] = [
                    $row['prescription_number'],
                    $row['prescription_date'],
                    $row['medical_record_number'],
                    $row['patient_name'],
                    $row['doctor_name'] ?: '-',
                    $row['status'],
                    $row['total_amount']
                ];
            }
        } elseif ($reportType === 'lab') {
            $headers = ["No Permintaan Lab", "Tanggal Order", "No RM", "Nama Pasien", "Dokter Pengirim", "Prioritas", "Status"];
            foreach ($results as $row) {
                $rows[] = [
                    $row['order_number'],
                    $row['order_date'],
                    $row['medical_record_number'],
                    $row['patient_name'],
                    $row['doctor_name'] ?: '-',
                    strtoupper($row['priority']),
                    $row['order_status']
                ];
            }
        } else {
            $headers = [];
        }
        
        $this->exportExcel($headers, $rows, 'Laporan Kustom', 'Laporan_' . ucfirst($reportType) . '_' . date('Ymd') . '.xlsx');
    }
}
