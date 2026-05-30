<?php

/**
 * Appointment & Queue Controller
 * 
 * Handles patient appointments, daily queues, and doctor schedules
 */

class AppointmentController extends Controller
{
    /**
     * Constructor - Require authentication for all actions
     */
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display list of appointments
     */
    public function index()
    {
        $this->requirePermission('appointments.view');

        // Filter parameters
        $date = $this->get('date', date('Y-m-d'));
        $polyclinicId = $this->get('polyclinic_id', '');
        $status = $this->get('status', '');

        $query = "SELECT a.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                         u.full_name AS doctor_name, poly.name AS polyclinic_name
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  JOIN polyclinics poly ON a.polyclinic_id = poly.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($date)) {
            $query .= " AND a.appointment_date = ?";
            $params[] = $date;
        }

        if (!empty($polyclinicId)) {
            $query .= " AND a.polyclinic_id = ?";
            $params[] = $polyclinicId;
        }

        if (!empty($status)) {
            $query .= " AND a.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

        $appointments = Database::fetchAll($query, $params);
        $polyclinics = Database::fetchAll("SELECT id, name FROM polyclinics WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Daftar Perjanjian (Appointment) - SIMRS',
            'appointments' => $appointments,
            'polyclinics' => $polyclinics,
            'filters' => [
                'date' => $date,
                'polyclinic_id' => $polyclinicId,
                'status' => $status
            ]
        ];

        $this->view('appointment/views/index', $data);
    }

    /**
     * Show create appointment form
     */
    public function create()
    {
        $this->requirePermission('appointments.create');

        $patients = Database::fetchAll("SELECT id, medical_record_number, full_name, birth_date FROM patients WHERE is_active = 1 ORDER BY full_name ASC");
        $doctors = Database::fetchAll(
            "SELECT d.id, u.full_name AS doctor_name, d.specialization 
             FROM doctors d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.is_active = 1 ORDER BY u.full_name ASC"
        );
        $polyclinics = Database::fetchAll("SELECT id, name FROM polyclinics WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Buat Perjanjian Baru - SIMRS',
            'patients' => $patients,
            'doctors' => $doctors,
            'polyclinics' => $polyclinics
        ];

        $this->view('appointment/views/create', $data);
    }

    /**
     * Store new appointment
     */
    public function store()
    {
        $this->requirePermission('appointments.create');

        if (!isPost()) {
            $this->redirect('appointment');
        }

        $this->requireCsrf();

        $patientId = $this->post('patient_id');
        $doctorId = $this->post('doctor_id');
        $polyclinicId = $this->post('polyclinic_id');
        $appointmentDate = $this->post('appointment_date');
        $appointmentTime = $this->post('appointment_time');
        $chiefComplaint = $this->post('chief_complaint');
        $notes = $this->post('notes');
        $appointmentType = $this->post('appointment_type', 'new');
        $bookingMethod = $this->post('booking_method', 'walk_in');

        // Generate appointment number: APT-YYYYMMDD-XXXX
        $todayStr = date('Ymd', strtotime($appointmentDate));
        $prefix = "APT-{$todayStr}-";
        $latest = Database::fetchOne("SELECT appointment_number FROM appointments WHERE appointment_number LIKE ? ORDER BY id DESC LIMIT 1", [$prefix . '%']);
        if ($latest) {
            $num = (int) substr($latest['appointment_number'], -4);
            $num++;
        } else {
            $num = 1;
        }
        $appointmentNumber = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

        $insertData = [
            'appointment_number' => $appointmentNumber,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'polyclinic_id' => $polyclinicId,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'appointment_type' => $appointmentType,
            'booking_method' => $bookingMethod,
            'status' => 'scheduled',
            'chief_complaint' => $chiefComplaint,
            'notes' => $notes,
            'created_by' => Session::getUserId()
        ];

        try {
            $id = Database::insert('appointments', $insertData);
            $this->logAudit('create', 'appointment', 'appointments', $id, 'Membuat janji temu baru: ' . $appointmentNumber);
            $this->setFlash('success', 'Perjanjian baru berhasil dijadwalkan dengan nomor: ' . $appointmentNumber);
        } catch (Exception $e) {
            error_log("Error store appointment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal membuat perjanjian baru. Silakan periksa kembali data input.');
        }

        $this->redirect('appointment');
    }

    /**
     * Update appointment status
     */
    public function updateStatus($id)
    {
        $this->requirePermission('appointments.edit');

        if (!isPost()) {
            $this->redirect('appointment');
        }

        $this->requireCsrf();

        $status = $this->post('status');
        $cancellationReason = $this->post('cancellation_reason', null);

        $appointment = Database::fetchOne("SELECT * FROM appointments WHERE id = ?", [$id]);
        if (!$appointment) {
            $this->setFlash('error', 'Janji temu tidak ditemukan.');
            $this->redirect('appointment');
        }

        $updateData = ['status' => $status];

        if ($status === 'cancelled') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
            $updateData['cancelled_by'] = Session::getUserId();
            $updateData['cancellation_reason'] = $cancellationReason;
        } elseif ($status === 'confirmed') {
            $updateData['is_confirmed'] = 1;
            $updateData['confirmed_at'] = date('Y-m-d H:i:s');
            $updateData['confirmed_by'] = Session::getUserId();
        }

        try {
            Database::update('appointments', $updateData, ['id' => $id]);
            $this->logAudit('update', 'appointment', 'appointments', $id, "Mengubah status appointment menjadi: {$status}");

            // Jika status dirubah ke 'arrived' (pasien datang), masukkan ke antrean harian secara otomatis
            if ($status === 'arrived') {
                $today = date('Y-m-d');
                $polyclinicId = $appointment['polyclinic_id'];
                
                // Cek apakah sudah ada antrean hari ini untuk pasien dan poli yang sama
                $existingQueue = Database::fetchOne(
                    "SELECT id FROM queues WHERE queue_date = ? AND patient_id = ? AND polyclinic_id = ? AND status != 'cancelled'",
                    [$today, $appointment['patient_id'], $polyclinicId]
                );

                if (!$existingQueue) {
                    // Cari nomor antrean berikutnya untuk poli hari ini
                    $nextNumRow = Database::fetchOne(
                        "SELECT COALESCE(MAX(queue_number), 0) + 1 AS next_num FROM queues WHERE queue_date = ? AND polyclinic_id = ?",
                        [$today, $polyclinicId]
                    );
                    $queueNum = $nextNumRow['next_num'];

                    // Tambahkan ke queues
                    $queueId = Database::insert('queues', [
                        'queue_date' => $today,
                        'queue_number' => $queueNum,
                        'patient_id' => $appointment['patient_id'],
                        'polyclinic_id' => $polyclinicId,
                        'doctor_id' => $appointment['doctor_id'],
                        'appointment_id' => $id,
                        'queue_type' => 'appointment',
                        'priority' => 'normal',
                        'status' => 'waiting',
                        'registration_time' => date('Y-m-d H:i:s')
                    ]);

                    $this->logAudit('create', 'queue', 'queues', $queueId, "Otomatis membuat antrean nomor {$queueNum} untuk pasien");
                    $this->setFlash('success', "Status diperbarui. Pasien berhasil masuk antrean nomor {$queueNum}.");
                } else {
                    $this->setFlash('success', 'Status diperbarui. Pasien sudah terdaftar di antrean hari ini.');
                }
            } else {
                $this->setFlash('success', 'Status perjanjian berhasil diperbarui.');
            }

        } catch (Exception $e) {
            error_log("Error updateStatus appointment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui status perjanjian.');
        }

        $this->redirect('appointment');
    }

    /**
     * Daily Queues Dashboard
     */
    public function queue()
    {
        $this->requirePermission('queues.view');

        $polyclinicId = $this->get('polyclinic_id', '');
        $today = date('Y-m-d');

        $query = "SELECT q.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                         u.full_name AS doctor_name, poly.name AS polyclinic_name, poly.code AS polyclinic_code
                  FROM queues q
                  JOIN patients p ON q.patient_id = p.id
                  LEFT JOIN doctors d ON q.doctor_id = d.id
                  LEFT JOIN users u ON d.user_id = u.id
                  JOIN polyclinics poly ON q.polyclinic_id = poly.id
                  WHERE q.queue_date = ?
                  AND q.status != 'cancelled'";
        
        $params = [$today];

        if (!empty($polyclinicId)) {
            $query .= " AND q.polyclinic_id = ?";
            $params[] = $polyclinicId;
        }

        $query .= " ORDER BY q.status = 'called' DESC, q.status = 'in_service' DESC, q.status = 'waiting' DESC, q.priority DESC, q.queue_number ASC";

        $queues = Database::fetchAll($query, $params);
        $polyclinics = Database::fetchAll("SELECT id, name FROM polyclinics WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Dashboard Antrean Hari Ini - SIMRS',
            'queues' => $queues,
            'polyclinics' => $polyclinics,
            'polyclinic_id' => $polyclinicId
        ];

        $this->view('appointment/views/queue', $data);
    }

    /**
     * Call and progress Queue status
     */
    public function callQueue($id)
    {
        $this->requirePermission('queues.call');

        if (!isPost()) {
            $this->redirect('queue');
        }

        $this->requireCsrf();

        $action = $this->post('action'); // 'call', 'serve', 'complete', 'no_show'
        $queue = Database::fetchOne("SELECT * FROM queues WHERE id = ?", [$id]);

        if (!$queue) {
            $this->setFlash('error', 'Antrean tidak ditemukan.');
            $this->redirect('queue');
        }

        $updateData = [];
        $now = date('Y-m-d H:i:s');

        if ($action === 'call') {
            $updateData['status'] = 'called';
            $updateData['called_time'] = $now;
            $updateData['called_by'] = Session::getUserId();
            $message = "Antrean nomor " . $queue['queue_number'] . " dipanggil.";
        } elseif ($action === 'serve') {
            $updateData['status'] = 'in_service';
            $updateData['service_start_time'] = $now;
            $message = "Pasien antrean nomor " . $queue['queue_number'] . " mulai dilayani.";
        } elseif ($action === 'complete') {
            $updateData['status'] = 'completed';
            $updateData['service_end_time'] = $now;
            $message = "Pasien antrean nomor " . $queue['queue_number'] . " selesai dilayani.";

            // Jika antrean dihubungkan dengan appointment, update status appointment ke completed
            if ($queue['appointment_id']) {
                Database::update('appointments', ['status' => 'completed'], ['id' => $queue['appointment_id']]);
            }
        } elseif ($action === 'no_show') {
            $updateData['status'] = 'no_show';
            $message = "Pasien antrean nomor " . $queue['queue_number'] . " ditandai tidak hadir (no show).";

            if ($queue['appointment_id']) {
                Database::update('appointments', ['status' => 'no_show'], ['id' => $queue['appointment_id']]);
            }
        }

        try {
            Database::update('queues', $updateData, ['id' => $id]);
            $this->logAudit('update', 'queue', 'queues', $id, "Mengubah status antrean menjadi: " . ($updateData['status'] ?? $action));
            $this->setFlash('success', $message);
        } catch (Exception $e) {
            error_log("Error callQueue: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memproses antrean.');
        }

        $this->redirect('queue');
    }

    /**
     * Doctor Schedules
     */
    public function schedule()
    {
        $this->requirePermission('appointments.view');

        $query = "SELECT ds.*, u.full_name AS doctor_name, d.specialization, poly.name AS polyclinic_name, r.name AS room_name
                  FROM doctor_schedules ds
                  JOIN doctors d ON ds.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  JOIN polyclinics poly ON ds.polyclinic_id = poly.id
                  LEFT JOIN rooms r ON ds.room_id = r.id
                  ORDER BY FIELD(ds.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), ds.start_time ASC";
        
        $schedules = Database::fetchAll($query);
        $doctors = Database::fetchAll(
            "SELECT d.id, u.full_name 
             FROM doctors d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.is_active = 1 
             ORDER BY u.full_name ASC"
        );
        $polyclinics = Database::fetchAll("SELECT id, name FROM polyclinics WHERE is_active = 1 ORDER BY name ASC");
        $rooms = Database::fetchAll("SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY name ASC");

        $data = [
            'title' => 'Jadwal Dokter Spesialis - SIMRS',
            'schedules' => $schedules,
            'doctors' => $doctors,
            'polyclinics' => $polyclinics,
            'rooms' => $rooms
        ];

        $this->view('appointment/views/schedule', $data);
    }

    /**
     * Store new Doctor Schedule
     */
    public function storeSchedule()
    {
        $this->requirePermission('appointments.create');

        if (!isPost()) {
            $this->redirect('schedule');
        }

        $this->requireCsrf();

        $insertData = [
            'doctor_id' => $this->post('doctor_id'),
            'polyclinic_id' => $this->post('polyclinic_id'),
            'room_id' => $this->post('room_id') ?: null,
            'day_of_week' => $this->post('day_of_week'),
            'start_time' => $this->post('start_time'),
            'end_time' => $this->post('end_time'),
            'quota' => $this->post('quota', 20),
            'booking_available' => $this->post('booking_available', 1),
            'effective_from' => $this->post('effective_from') ?: date('Y-m-d'),
            'effective_to' => $this->post('effective_to') ?: null,
            'notes' => $this->post('notes'),
            'is_active' => 1,
            'created_by' => Session::getUserId()
        ];

        try {
            $id = Database::insert('doctor_schedules', $insertData);
            $this->logAudit('create', 'appointment', 'doctor_schedules', $id, 'Menambahkan jadwal praktek dokter baru');
            $this->setFlash('success', 'Jadwal dokter spesialis baru berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error storeSchedule: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan jadwal dokter baru.');
        }

        $this->redirect('schedule');
    }

    /**
     * Waiting room queue TV display board
     */
    public function display()
    {
        $this->requirePermission('queues.view');
        
        // Fetch active polyclinics
        $polyclinics = Database::fetchAll("SELECT * FROM polyclinics WHERE is_active = 1 ORDER BY name ASC");
        
        $data = [
            'title' => 'Layar Antrean Utama - SIMRS',
            'polyclinics' => $polyclinics
        ];
        
        $this->view('appointment/views/display', $data, false);
    }

    /**
     * Active called queue feed in JSON
     */
    public function activeCalls()
    {
        $this->requirePermission('queues.view');
        $today = date('Y-m-d');
        
        // Query to get the latest called queue for each polyclinic today
        $query = "SELECT q.id, q.queue_number, q.status, q.called_time, poly.id AS polyclinic_id, poly.name AS polyclinic_name, poly.code AS polyclinic_code, u.full_name AS doctor_name
                  FROM queues q
                  JOIN polyclinics poly ON q.polyclinic_id = poly.id
                  LEFT JOIN doctors d ON q.doctor_id = d.id
                  LEFT JOIN users u ON d.user_id = u.id
                  WHERE q.queue_date = ? AND q.status = 'called'
                  AND q.id IN (
                      SELECT MAX(id) FROM queues WHERE queue_date = ? AND status = 'called' GROUP BY polyclinic_id
                  )
                  ORDER BY q.called_time DESC";
        
        $calls = Database::fetchAll($query, [$today, $today]);
        
        // Also get the single latest called queue across the entire hospital to trigger the audio announcement!
        $latestCall = Database::fetchOne(
            "SELECT q.id, q.queue_number, poly.name AS polyclinic_name, poly.code AS polyclinic_code, u.full_name AS doctor_name
             FROM queues q
             JOIN polyclinics poly ON q.polyclinic_id = poly.id
             LEFT JOIN doctors d ON q.doctor_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             WHERE q.queue_date = ? AND q.status = 'called'
             ORDER BY q.called_time DESC LIMIT 1",
            [$today]
        );
        
        $this->json([
            'calls' => $calls,
            'latestCall' => $latestCall
        ]);
    }
}
