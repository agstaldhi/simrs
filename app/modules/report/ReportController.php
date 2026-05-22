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
}
