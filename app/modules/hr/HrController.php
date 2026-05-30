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
        $action = $this->get('action', 'list');

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

        $departments = Database::fetchAll("SELECT id, name FROM departments ORDER BY name ASC");
        
        $editingEmployee = null;
        if ($action === 'edit') {
            $id = $this->get('id');
            $editingEmployee = Database::fetchOne("SELECT * FROM employees WHERE id = ?", [$id]);
        }

        $data = [
            'title' => 'Direktori Kepegawaian - SIMRS',
            'employees' => $employees,
            'departments' => $departments,
            'action' => $action,
            'editingEmployee' => $editingEmployee,
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

        $action = $this->get('action', 'list');

        $query = "SELECT es.*, e.full_name AS employee_name, e.employee_number, s.name AS shift_name, 
                         s.start_time, s.end_time, d.name AS department_name
                  FROM employee_shifts es
                  JOIN employees e ON es.employee_id = e.id
                  JOIN shifts s ON es.shift_id = s.id
                  LEFT JOIN departments d ON e.department_id = d.id
                  WHERE es.shift_date >= CURDATE() - INTERVAL 1 DAY
                  ORDER BY es.shift_date ASC, s.start_time ASC";
        
        $shifts = Database::fetchAll($query);

        $employees = Database::fetchAll("SELECT id, full_name, employee_number FROM employees WHERE employment_status = 'active' ORDER BY full_name ASC");
        $shiftTypes = Database::fetchAll("SELECT id, name, code, start_time, end_time FROM shifts WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Roster Shift Kerja Pegawai - SIMRS',
            'shifts' => $shifts,
            'employees' => $employees,
            'shiftTypes' => $shiftTypes,
            'action' => $action
        ];

        $this->view('hr/views/shifts', $data);
    }

    /**
     * Display pending leaves & process leave requests (approvals)
     */
    public function leaves()
    {
        $this->requirePermission('hr.view_leaves');

        $action = $this->get('action', 'list');

        // Handle leaf approval/rejection
        if (isPost()) {
            $this->requirePermission('hr.manage_leaves');
            $this->requireCsrf();

            $leaveId = $this->post('leave_id');
            $actionPost = $this->post('action'); // approve or reject
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

            $status = ($actionPost === 'approve') ? 'approved' : 'rejected';

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

        $employees = Database::fetchAll("SELECT id, full_name, employee_number FROM employees WHERE employment_status = 'active' ORDER BY full_name ASC");

        $data = [
            'title' => 'Pengajuan Cuti Karyawan - SIMRS',
            'pendingLeaves' => $pendingLeaves,
            'historyLeaves' => $historyLeaves,
            'employees' => $employees,
            'action' => $action
        ];

        $this->view('hr/views/leaves', $data);
    }

    /**
     * Store a new employee
     */
    public function storeEmployee()
    {
        $this->requirePermission('hr.create_employee');
        $this->requireCsrf();

        $fullName = $this->post('full_name');
        $nik = $this->post('nik');
        $birthPlace = $this->post('birth_place');
        $birthDate = $this->post('birth_date');
        $gender = $this->post('gender');
        $position = $this->post('position');
        $departmentId = $this->post('department_id') ?: null;
        $joinDate = $this->post('join_date');
        $employmentType = $this->post('employment_type', 'permanent');
        $employmentStatus = $this->post('employment_status', 'active');
        $email = $this->post('email');
        $phone = $this->post('phone');

        if (empty($fullName) || empty($nik) || empty($birthDate) || empty($position) || empty($joinDate)) {
            $this->setFlash('error', 'Nama lengkap, NIK, tanggal lahir, jabatan, dan tanggal masuk wajib diisi.');
            $this->redirect('hr/employees?action=add');
        }

        try {
            $employeeNumber = generateDocumentNumber('EMP', 'employees', 'employee_number');
            
            $data = [
                'employee_number' => $employeeNumber,
                'nik' => $nik,
                'full_name' => $fullName,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'gender' => $gender,
                'position' => $position,
                'department_id' => $departmentId,
                'join_date' => $joinDate,
                'employment_type' => $employmentType,
                'employment_status' => $employmentStatus,
                'email' => $email,
                'phone' => $phone,
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $employeeId = Database::insert('employees', $data);
            $this->logAudit('create', 'hr', 'employees', $employeeId, "Menambahkan pegawai baru {$fullName} ({$employeeNumber})");
            $this->setFlash('success', 'Pegawai berhasil didaftarkan.');
        } catch (Exception $e) {
            error_log("Error store employee: " . $e->getMessage());
            $this->setFlash('error', 'Gagal mendaftarkan pegawai: ' . $e->getMessage());
        }

        $this->redirect('hr/employees');
    }

    /**
     * Update an employee's data
     */
    public function updateEmployee($id)
    {
        $this->requirePermission('hr.edit_employee');
        $this->requireCsrf();

        $employee = Database::fetchOne("SELECT * FROM employees WHERE id = ?", [$id]);
        if (!$employee) {
            $this->setFlash('error', 'Data pegawai tidak ditemukan.');
            $this->redirect('hr/employees');
        }

        $fullName = $this->post('full_name');
        $nik = $this->post('nik');
        $birthPlace = $this->post('birth_place');
        $birthDate = $this->post('birth_date');
        $gender = $this->post('gender');
        $position = $this->post('position');
        $departmentId = $this->post('department_id') ?: null;
        $joinDate = $this->post('join_date');
        $employmentType = $this->post('employment_type', 'permanent');
        $employmentStatus = $this->post('employment_status', 'active');
        $email = $this->post('email');
        $phone = $this->post('phone');

        if (empty($fullName) || empty($nik) || empty($birthDate) || empty($position) || empty($joinDate)) {
            $this->setFlash('error', 'Nama lengkap, NIK, tanggal lahir, jabatan, dan tanggal masuk wajib diisi.');
            $this->redirect("hr/employees?action=edit&id={$id}");
        }

        try {
            Database::update('employees', [
                'nik' => $nik,
                'full_name' => $fullName,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'gender' => $gender,
                'position' => $position,
                'department_id' => $departmentId,
                'join_date' => $joinDate,
                'employment_type' => $employmentType,
                'employment_status' => $employmentStatus,
                'email' => $email,
                'phone' => $phone,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $this->logAudit('update', 'hr', 'employees', $id, "Memperbarui data pegawai {$fullName} ({$employee['employee_number']})");
            $this->setFlash('success', 'Data pegawai berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error update employee: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui data pegawai: ' . $e->getMessage());
        }

        $this->redirect('hr/employees');
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee($id)
    {
        $this->requirePermission('hr.delete_employee');
        $this->requireCsrf();

        $employee = Database::fetchOne("SELECT * FROM employees WHERE id = ?", [$id]);
        if (!$employee) {
            $this->setFlash('error', 'Data pegawai tidak ditemukan.');
            $this->redirect('hr/employees');
        }

        try {
            Database::delete('employees', ['id' => $id]);
            $this->logAudit('delete', 'hr', 'employees', $id, "Menghapus pegawai {$employee['full_name']} ({$employee['employee_number']})");
            $this->setFlash('success', 'Pegawai berhasil dihapus.');
        } catch (Exception $e) {
            error_log("Error delete employee: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menghapus pegawai (kemungkinan data terikat dengan tabel lain).');
        }

        $this->redirect('hr/employees');
    }

    /**
     * Store employee shift schedule
     */
    public function storeShift()
    {
        $this->requirePermission('hr.manage_shifts');
        $this->requireCsrf();

        $employeeId = $this->post('employee_id');
        $shiftId = $this->post('shift_id');
        $shiftDate = $this->post('shift_date');
        $notes = $this->post('notes', '');

        if (empty($employeeId) || empty($shiftId) || empty($shiftDate)) {
            $this->setFlash('error', 'Pegawai, shift, dan tanggal wajib dipilih.');
            $this->redirect('hr/shifts?action=add');
        }

        try {
            $exists = Database::fetchOne(
                "SELECT id FROM employee_shifts WHERE employee_id = ? AND shift_date = ?",
                [$employeeId, $shiftDate]
            );

            if ($exists) {
                $this->setFlash('error', 'Pegawai sudah dijadwalkan pada tanggal tersebut.');
                $this->redirect('hr/shifts?action=add');
            }

            $esId = Database::insert('employee_shifts', [
                'employee_id' => $employeeId,
                'shift_id' => $shiftId,
                'shift_date' => $shiftDate,
                'status' => 'scheduled',
                'notes' => $notes,
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('create', 'hr', 'employee_shifts', $esId, "Menjadwalkan shift kerja untuk pegawai ID {$employeeId} tanggal {$shiftDate}");
            $this->setFlash('success', 'Jadwal shift berhasil dibuat.');
        } catch (Exception $e) {
            error_log("Error store shift: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menyimpan jadwal shift: ' . $e->getMessage());
        }

        $this->redirect('hr/shifts');
    }

    /**
     * Delete employee shift schedule
     */
    public function deleteShift($id)
    {
        $this->requirePermission('hr.manage_shifts');
        $this->requireCsrf();

        try {
            $shift = Database::fetchOne("SELECT * FROM employee_shifts WHERE id = ?", [$id]);
            if ($shift) {
                Database::delete('employee_shifts', ['id' => $id]);
                $this->logAudit('delete', 'hr', 'employee_shifts', $id, "Menghapus jadwal shift pegawai ID {$shift['employee_id']} tanggal {$shift['shift_date']}");
                $this->setFlash('success', 'Jadwal shift berhasil dihapus.');
            } else {
                $this->setFlash('error', 'Jadwal shift tidak ditemukan.');
            }
        } catch (Exception $e) {
            error_log("Error delete shift: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menghapus jadwal shift: ' . $e->getMessage());
        }

        $this->redirect('hr/shifts');
    }

    /**
     * Store employee leave request
     */
    public function storeLeave()
    {
        $this->requirePermission('hr.manage_leaves');
        $this->requireCsrf();

        $employeeId = $this->post('employee_id');
        $leaveType = $this->post('leave_type');
        $startDate = $this->post('start_date');
        $endDate = $this->post('end_date');
        $reason = $this->post('reason');
        $notes = $this->post('notes', '');

        if (empty($employeeId) || empty($leaveType) || empty($startDate) || empty($endDate) || empty($reason)) {
            $this->setFlash('error', 'Pegawai, tipe cuti, tanggal mulai/selesai, dan alasan wajib diisi.');
            $this->redirect('hr/leaves?action=add');
        }

        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $interval = $start->diff($end);
            $totalDays = $interval->days + 1;

            if ($totalDays <= 0) {
                $this->setFlash('error', 'Tanggal mulai harus sebelum atau sama dengan tanggal selesai.');
                $this->redirect('hr/leaves?action=add');
            }

            $leaveNumber = generateDocumentNumber('LV', 'leaves', 'leave_number');

            $leaveId = Database::insert('leaves', [
                'employee_id' => $employeeId,
                'leave_number' => $leaveNumber,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'reason' => $reason,
                'status' => 'pending',
                'notes' => $notes,
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('create', 'hr', 'leaves', $leaveId, "Mengajukan cuti baru {$leaveNumber} untuk pegawai ID {$employeeId}");
            $this->setFlash('success', 'Pengajuan cuti berhasil dikirim.');
        } catch (Exception $e) {
            error_log("Error store leave: " . $e->getMessage());
            $this->setFlash('error', 'Gagal mengajukan cuti: ' . $e->getMessage());
        }

        $this->redirect('hr/leaves');
    }
}
