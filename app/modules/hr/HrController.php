<?php

/**
 * HR Controller
 * 
 * Handles hospital staff employee directory, attendance rosters, shifts rosters, and leave approvals.
 */
class HrController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display employees list
     */
    public function employees()
    {
        $this->requirePermission('hr.view_employees');

        $search = $this->get('search', '');
        $status = $this->get('status', 'active');

        $query = "SELECT * FROM v_employees_list WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $query .= " AND employment_status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $query .= " AND (full_name LIKE ? OR employee_number LIKE ? OR position LIKE ? OR department_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY full_name ASC";
        $employees = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Direktori Kepegawaian - SIMRS',
            'employees' => $employees,
            'filters' => [
                'search' => $search,
                'status' => $status
            ]
        ];

        $this->view('hr/views/employees', $data);
    }

    /**
     * Display attendance records & allow daily self check-in/out
     */
    public function attendance()
    {
        $this->requirePermission('hr.view_attendance');

        // Check if there is an attendance action (check-in/check-out)
        if (isPost()) {
            $this->requireCsrf();
            $action = $this->post('action');
            $userId = Session::getUserId();

            // Find employee record for the logged-in user
            $employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ?", [$userId]);

            if (!$employee) {
                $this->setFlash('error', 'Data pegawai Anda tidak ditemukan. Akun Anda tidak ditautkan ke tabel pegawai.');
                $this->redirect('hr/attendance');
            }

            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            if ($action === 'check_in') {
                // Check if already checked in today
                $exists = Database::fetchOne(
                    "SELECT * FROM attendances WHERE employee_id = ? AND attendance_date = ?",
                    [$employee['id'], $today]
                );

                if ($exists) {
                    $this->setFlash('warning', 'Anda sudah melakukan check-in hari ini.');
                } else {
                    $attendanceId = Database::insert('attendances', [
                        'employee_id' => $employee['id'],
                        'attendance_date' => $today,
                        'check_in_time' => $now,
                        'check_in_location' => 'Kantor Rumah Sakit',
                        'status' => (date('H:i') > '08:00') ? 'late' : 'present',
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $this->logAudit('check_in', 'hr', 'attendances', $attendanceId, "Pegawai {$employee['full_name']} melakukan Check-In");
                    $this->setFlash('success', 'Check-in berhasil dilakukan. Selamat bertugas!');
                }
            } elseif ($action === 'check_out') {
                $todayRecord = Database::fetchOne(
                    "SELECT * FROM attendances WHERE employee_id = ? AND attendance_date = ?",
                    [$employee['id'], $today]
                );

                if (!$todayRecord) {
                    $this->setFlash('error', 'Anda belum melakukan check-in hari ini.');
                } elseif ($todayRecord['check_out_time'] !== null) {
                    $this->setFlash('warning', 'Anda sudah melakukan check-out hari ini.');
                } else {
                    $inTime = new DateTime($todayRecord['check_in_time']);
                    $outTime = new DateTime($now);
                    $interval = $inTime->diff($outTime);
                    $hours = round($interval->h + ($interval->i / 60), 2);

                    Database::update('attendances', [
                        'check_out_time' => $now,
                        'check_out_location' => 'Kantor Rumah Sakit',
                        'working_hours' => $hours,
                        'updated_at' => $now
                    ], ['id' => $todayRecord['id']]);

                    $this->logAudit('check_out', 'hr', 'attendances', $todayRecord['id'], "Pegawai {$employee['full_name']} melakukan Check-Out. Durasi kerja: {$hours} jam.");
                    $this->setFlash('success', "Check-out berhasil. Jam kerja hari ini: {$hours} jam. Terima kasih atas kerja kerasnya!");
                }
            }

            $this->redirect('hr/attendance');
        }

        // Get list of attendance today
        $search = $this->get('search', '');
        $query = "SELECT * FROM v_attendance_today WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (full_name LIKE ? OR employee_number LIKE ? OR department_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY check_in_time DESC";
        $attendanceToday = Database::fetchAll($query, $params);

        // Get logged-in user's attendance status for today
        $myAttendance = null;
        $employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ?", [Session::getUserId()]);
        if ($employee) {
            $myAttendance = Database::fetchOne(
                "SELECT * FROM attendances WHERE employee_id = ? AND attendance_date = ?",
                [$employee['id'], date('Y-m-d')]
            );
        }

        $data = [
            'title' => 'Absensi & Presensi Pegawai - SIMRS',
            'attendanceToday' => $attendanceToday,
            'myAttendance' => $myAttendance,
            'isEmployeeLinked' => ($employee !== false),
            'filters' => [
                'search' => $search
            ]
        ];

        $this->view('hr/views/attendance', $data);
    }

    /**
     * Display shift schedules
     */
    public function shifts()
    {
        $this->requirePermission('hr.view_shifts');

        $query = "SELECT es.*, e.full_name AS employee_name, e.employee_number, s.name AS shift_name, 
                         s.start_time, s.end_time, d.name AS department_name
                  FROM employee_shifts es
                  JOIN employees e ON es.employee_id = e.id
                  JOIN shifts s ON es.shift_id = s.id
                  LEFT JOIN departments d ON e.department_id = d.id
                  WHERE es.shift_date >= CURDATE() - INTERVAL 1 DAY
                  ORDER BY es.shift_date ASC, s.start_time ASC";
        
        $shifts = Database::fetchAll($query);

        $data = [
            'title' => 'Roster Shift Kerja Pegawai - SIMRS',
            'shifts' => $shifts
        ];

        $this->view('hr/views/shifts', $data);
    }

    /**
     * Display pending leaves & process leave requests (approvals)
     */
    public function leaves()
    {
        $this->requirePermission('hr.view_leaves');

        // Handle leaf approval/rejection
        if (isPost()) {
            $this->requirePermission('hr.manage_leaves');
            $this->requireCsrf();

            $leaveId = $this->post('leave_id');
            $action = $this->post('action'); // approve or reject
            $reason = $this->post('rejection_reason', '');

            $leave = Database::fetchOne("SELECT * FROM leaves WHERE id = ?", [$leaveId]);
            if (!$leave) {
                $this->setFlash('error', 'Data pengajuan cuti tidak ditemukan.');
                $this->redirect('hr/leaves');
            }

            if ($leave['status'] !== 'pending') {
                $this->setFlash('warning', 'Pengajuan cuti ini sudah diproses sebelumnya.');
                $this->redirect('hr/leaves');
            }

            $status = ($action === 'approve') ? 'approved' : 'rejected';

            try {
                Database::update('leaves', [
                    'status' => $status,
                    'approved_by' => Session::getUserId(),
                    'approved_at' => date('Y-m-d H:i:s'),
                    'rejection_reason' => ($status === 'rejected') ? $reason : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $leaveId]);

                $this->logAudit('update', 'hr', 'leaves', $leaveId, "Memproses pengajuan cuti {$leave['leave_number']}: " . strtoupper($status));
                $this->setFlash('success', 'Status cuti berhasil diperbarui menjadi ' . strtoupper($status));
            } catch (Exception $e) {
                error_log("Error process leave: " . $e->getMessage());
                $this->setFlash('error', 'Gagal memproses cuti: ' . $e->getMessage());
            }

            $this->redirect('hr/leaves');
        }

        // Get list of pending leaves
        $pendingLeaves = Database::fetchAll("SELECT * FROM v_pending_leaves ORDER BY requested_at ASC");

        // Get history of processed leaves
        $historyLeaves = Database::fetchAll(
            "SELECT l.*, e.full_name, e.employee_number, u.full_name AS approved_by_name 
             FROM leaves l
             JOIN employees e ON l.employee_id = e.id
             LEFT JOIN users u ON l.approved_by = u.id
             WHERE l.status != 'pending' 
             ORDER BY l.updated_at DESC LIMIT 50"
        );

        $data = [
            'title' => 'Pengajuan Cuti Karyawan - SIMRS',
            'pendingLeaves' => $pendingLeaves,
            'historyLeaves' => $historyLeaves
        ];

        $this->view('hr/views/leaves', $data);
    }
}
