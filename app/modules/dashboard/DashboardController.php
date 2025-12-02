<?php

/**
 * Dashboard Controller
 * 
 * Handles main dashboard and statistics
 */

class DashboardController extends Controller
{

    /**
     * Show dashboard
     */
    public function index()
    {
        $this->requireAuth();

        $user = $this->getCurrentUser();

        // Get statistics based on user role
        $stats = $this->getStatistics($user);

        $data = [
            'title' => 'Dashboard - SIMRS',
            'user' => $user,
            'stats' => $stats
        ];

        $this->view('dashboard/views/index', $data);
    }

    /**
     * Get statistics based on user role
     * 
     * @param array $user Current user data
     * @return array
     */
    protected function getStatistics($user)
    {
        $stats = [];

        // Common statistics for all roles
        $stats['today'] = date('d F Y');
        $stats['current_time'] = date('H:i');

        // Get today's visits
        $stats['today_visits'] = Database::fetchOne(
            "SELECT COUNT(*) as count FROM patient_visits WHERE DATE(visit_date) = CURDATE()"
        )['count'] ?? 0;

        // Get today's queue
        $stats['today_queue'] = Database::fetchOne(
            "SELECT COUNT(*) as count FROM queues WHERE queue_date = CURDATE() AND status = 'waiting'"
        )['count'] ?? 0;

        // Role-specific statistics
        if (in_array('admin', $user['roles'])) {
            $stats = array_merge($stats, $this->getAdminStats());
        } elseif (in_array('doctor', $user['roles'])) {
            $stats = array_merge($stats, $this->getDoctorStats($user['id']));
        } elseif (in_array('nurse', $user['roles'])) {
            $stats = array_merge($stats, $this->getNurseStats());
        } elseif (in_array('receptionist', $user['roles'])) {
            $stats = array_merge($stats, $this->getReceptionistStats());
        } elseif (in_array('lab_staff', $user['roles'])) {
            $stats = array_merge($stats, $this->getLabStats());
        } elseif (in_array('pharmacist', $user['roles'])) {
            $stats = array_merge($stats, $this->getPharmacyStats());
        } elseif (in_array('cashier', $user['roles'])) {
            $stats = array_merge($stats, $this->getCashierStats());
        }

        return $stats;
    }

    /**
     * Get admin statistics
     * 
     * @return array
     */
    protected function getAdminStats()
    {
        return [
            'total_patients' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patients WHERE is_active = 1"
            )['count'] ?? 0,

            'active_employees' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM employees WHERE employment_status = 'active'"
            )['count'] ?? 0,

            'unpaid_invoices' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM invoices WHERE payment_status = 'unpaid'"
            )['count'] ?? 0,

            'outstanding_amount' => Database::fetchOne(
                "SELECT SUM(outstanding_amount) as total FROM invoices WHERE payment_status = 'unpaid'"
            )['total'] ?? 0,

            'today_revenue' => Database::fetchOne(
                "SELECT SUM(amount) as total FROM payments 
                 WHERE DATE(payment_date) = CURDATE() AND payment_status = 'approved'"
            )['total'] ?? 0,

            'pending_appointments' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM appointments 
                 WHERE appointment_date = CURDATE() AND status IN ('scheduled', 'confirmed')"
            )['count'] ?? 0
        ];
    }

    /**
     * Get doctor statistics
     * 
     * @param int $userId User ID
     * @return array
     */
    protected function getDoctorStats($userId)
    {
        // Get doctor ID
        $doctor = Database::fetchOne("SELECT id FROM doctors WHERE user_id = ?", [$userId]);

        if (!$doctor) {
            return [];
        }

        return [
            'my_appointments_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM appointments 
                 WHERE doctor_id = ? AND appointment_date = CURDATE() 
                 AND status IN ('scheduled', 'confirmed')",
                [$doctor['id']]
            )['count'] ?? 0,

            'my_queue_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM queues 
                 WHERE doctor_id = ? AND queue_date = CURDATE() AND status = 'waiting'",
                [$doctor['id']]
            )['count'] ?? 0,

            'pending_medical_records' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM medical_records 
                 WHERE doctor_id = ? AND record_status = 'draft'",
                [$doctor['id']]
            )['count'] ?? 0,

            'my_patients_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patient_visits 
                 WHERE doctor_id = ? AND DATE(visit_date) = CURDATE()",
                [$doctor['id']]
            )['count'] ?? 0
        ];
    }

    /**
     * Get nurse statistics
     * 
     * @return array
     */
    protected function getNurseStats()
    {
        return [
            'inpatients' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patient_visits 
                 WHERE visit_type = 'inpatient' AND visit_status IN ('ongoing', 'waiting')"
            )['count'] ?? 0,

            'emergency_patients' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patient_visits 
                 WHERE visit_type = 'emergency' AND DATE(visit_date) = CURDATE()"
            )['count'] ?? 0
        ];
    }

    /**
     * Get receptionist statistics
     * 
     * @return array
     */
    protected function getReceptionistStats()
    {
        return [
            'today_registrations' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patient_visits WHERE DATE(visit_date) = CURDATE()"
            )['count'] ?? 0,

            'new_patients_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM patients WHERE DATE(registration_date) = CURDATE()"
            )['count'] ?? 0
        ];
    }

    /**
     * Get lab statistics
     * 
     * @return array
     */
    protected function getLabStats()
    {
        return [
            'pending_lab_orders' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM lab_orders WHERE order_status = 'pending'"
            )['count'] ?? 0,

            'in_progress_orders' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM lab_orders WHERE order_status = 'in_progress'"
            )['count'] ?? 0,

            'completed_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM lab_orders 
                 WHERE order_status = 'completed' AND DATE(completed_at) = CURDATE()"
            )['count'] ?? 0
        ];
    }

    /**
     * Get pharmacy statistics
     * 
     * @return array
     */
    protected function getPharmacyStats()
    {
        return [
            'pending_prescriptions' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'"
            )['count'] ?? 0,

            'low_stock_medicines' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM v_medicine_stock_current 
                 WHERE stock_status IN ('low_stock', 'out_of_stock')"
            )['count'] ?? 0,

            'dispensed_today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM prescriptions 
                 WHERE status = 'dispensed' AND DATE(dispensed_at) = CURDATE()"
            )['count'] ?? 0
        ];
    }

    /**
     * Get cashier statistics
     * 
     * @return array
     */
    protected function getCashierStats()
    {
        return [
            'unpaid_invoices' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM invoices WHERE payment_status = 'unpaid'"
            )['count'] ?? 0,

            'today_payments' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM payments 
                 WHERE DATE(payment_date) = CURDATE() AND payment_status = 'approved'"
            )['count'] ?? 0,

            'today_revenue' => Database::fetchOne(
                "SELECT SUM(amount) as total FROM payments 
                 WHERE DATE(payment_date) = CURDATE() AND payment_status = 'approved'"
            )['total'] ?? 0
        ];
    }

    /**
     * Get recent activities
     */
    public function recentActivities()
    {
        $this->requireAuth();
        $this->requirePermission('audit.view');

        $activities = Database::fetchAll(
            "SELECT * FROM v_recent_activities LIMIT 50"
        );

        $this->json([
            'success' => true,
            'data' => $activities
        ]);
    }
}
