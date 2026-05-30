<?php

/**
 * Inpatient Controller
 * Handles Rawat Inap (inpatient admission), Bed Management, and Nursing Notes
 */
class InpatientController extends Controller
{
    private $inpatientModel;

    /**
     * Constructor - Instantiate InpatientModel
     */
    public function __construct()
    {
        $this->inpatientModel = $this->model('Inpatient');
    }

    /**
     * Inpatient Dashboard
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('patients.view'); // Reuse patients.view for simplicity

        $admissions = $this->inpatientModel->getActiveAdmissions();
        $stats = $this->inpatientModel->getInpatientStats();

        $data = [
            'title' => 'Rawat Inap - SIMRS',
            'admissions' => $admissions,
            'stats' => $stats
        ];

        $this->view('inpatient/views/index', $data);
    }

    /**
     * Bed occupancy and availability status
     */
    public function beds()
    {
        $this->requireAuth();
        $this->requirePermission('patients.view');
        
        $beds = $this->inpatientModel->getBedStatus();
        $stats = $this->inpatientModel->getInpatientStats();

        $data = [
            'title' => 'Status Tempat Tidur - SIMRS',
            'beds' => $beds,
            'stats' => $stats
        ];

        $this->view('inpatient/views/beds', $data);
    }

    /**
     * Show create inpatient admission form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('patients.create');

        // Load active patients (not already in rawat inap)
        $patientModel = $this->model('Patient');
        $patients = $patientModel->findAll(['is_active' => 1], 'full_name ASC');

        // Filter out patients who are currently hospitalized
        $activeVisits = $this->inpatientModel->getActiveAdmissions();
        $hospitalizedPatientIds = array_column($activeVisits, 'patient_id');
        $availablePatients = [];
        foreach ($patients as $p) {
            if (!in_array($p['id'], $hospitalizedPatientIds)) {
                $availablePatients[] = $p;
            }
        }

        // Load available rooms (inpatient, capacity > 0, available beds > 0)
        $rooms = Database::fetchAll(
            "SELECT * FROM rooms 
             WHERE type IN ('inpatient', 'icu', 'emergency') 
             AND available_beds > 0 
             AND is_active = 1
             ORDER BY building ASC, floor ASC, name ASC"
        );

        // Load active doctors
        $doctors = Database::fetchAll(
            "SELECT d.id, u.full_name as doctor_name, d.specialization 
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE d.is_active = 1
             ORDER BY u.full_name ASC"
        );

        $data = [
            'title' => 'Admisi Pasien Rawat Inap Baru - SIMRS',
            'patients' => $availablePatients,
            'rooms' => $rooms,
            'doctors' => $doctors
        ];

        $this->view('inpatient/views/create', $data);
    }

    /**
     * Store inpatient admission
     */
    public function store()
    {
        $this->requireAuth();
        $this->requirePermission('patients.create');

        if (!isPost()) {
            $this->redirect('inpatient/create');
        }

        $this->requireCsrf();

        $input = [
            'patient_id' => $this->post('patient_id'),
            'room_id' => $this->post('room_id'),
            'doctor_id' => $this->post('doctor_id'),
            'chief_complaint' => $this->post('chief_complaint'),
            'notes' => $this->post('notes')
        ];

        // Validation
        $errors = $this->validate($input, [
            'patient_id' => 'required',
            'room_id' => 'required',
            'doctor_id' => 'required',
            'chief_complaint' => 'required'
        ]);

        if (!empty($errors)) {
            flashOld($input);
            $this->setFlash('error', 'Data tidak valid: ' . implode(', ', $errors));
            $this->redirect('inpatient/create');
        }

        $input['created_by'] = Auth::id();

        try {
            $visitId = $this->inpatientModel->admitPatient($input);

            // Log Audit
            $this->logAudit(
                'admit',
                'inpatient',
                'patient_visits',
                $visitId,
                'Pasien didaftarkan rawat inap, Visit ID: ' . $visitId
            );

            clearOld();
            $this->setFlash('success', 'Pasien berhasil didaftarkan masuk rawat inap');
            $this->redirect('inpatient/detail/' . $visitId);
        } catch (Exception $e) {
            error_log("Inpatient store error: " . $e->getMessage());
            flashOld($input);
            $this->setFlash('error', 'Gagal menyimpan data rawat inap: ' . $e->getMessage());
            $this->redirect('inpatient/create');
        }
    }

    /**
     * Inpatient Admission Detail / SOAP & Nursing Notes History
     * 
     * @param int $id Visit ID
     */
    public function detail($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.view_detail');

        $admission = $this->inpatientModel->getAdmissionDetail($id);

        if (!$admission) {
            $this->setFlash('error', 'Data rawat inap tidak ditemukan');
            $this->redirect('inpatient');
        }

        // Get medical records (SOAP Dokter)
        $medicalRecords = Database::fetchAll(
            "SELECT mr.*, u.full_name as doctor_name
             FROM medical_records mr
             JOIN users u ON mr.created_by = u.id
             WHERE mr.visit_id = ?
             ORDER BY mr.created_at DESC",
            [$id]
        );

        // Get vital signs
        $vitalSigns = Database::fetchAll(
            "SELECT vs.*, u.full_name as nurse_name
             FROM vital_signs vs
             LEFT JOIN users u ON vs.measured_by = u.id
             WHERE vs.visit_id = ?
             ORDER BY vs.measured_at DESC",
            [$id]
        );

        // Get nursing notes
        $nursingNotes = $this->inpatientModel->getNursingNotes($id);

        $data = [
            'title' => 'Detail Rawat Inap - SIMRS',
            'admission' => $admission,
            'medicalRecords' => $medicalRecords,
            'vitalSigns' => $vitalSigns,
            'nursingNotes' => $nursingNotes
        ];

        $this->view('inpatient/views/detail', $data);
    }

    /**
     * Store new nursing SOAP note
     */
    public function storeNursingNote()
    {
        $this->requireAuth();

        if (!isPost()) {
            $this->redirect('inpatient');
        }

        $this->requireCsrf();

        $visitId = $this->post('visit_id');
        $patientId = $this->post('patient_id');

        $input = [
            'visit_id' => $visitId,
            'patient_id' => $patientId,
            'nurse_id' => Auth::id(),
            'subjective' => $this->post('subjective'),
            'objective' => $this->post('objective'),
            'assessment' => $this->post('assessment'),
            'plan' => $this->post('plan'),
            'intervention' => $this->post('intervention'),
            'evaluation' => $this->post('evaluation'),
            'note_date' => date('Y-m-d H:i:s')
        ];

        try {
            $this->inpatientModel->saveNursingNote($input);

            // Log Audit
            $this->logAudit(
                'create_nursing_note',
                'inpatient',
                'nursing_notes',
                $visitId,
                'Catatan asuhan keperawatan baru ditambahkan untuk Visit ID: ' . $visitId
            );

            // Dynamically insert a Vital Sign record if vital sign numbers are filled
            $bp_sys = $this->post('bp_systolic');
            $bp_dia = $this->post('bp_diastolic');
            $temp = $this->post('temperature');
            $pulse = $this->post('pulse');

            if (!empty($bp_sys) || !empty($temp) || !empty($pulse)) {
                Database::insert('vital_signs', [
                    'patient_id' => $patientId,
                    'visit_id' => $visitId,
                    'measured_at' => date('Y-m-d H:i:s'),
                    'measured_by' => Auth::id(),
                    'blood_pressure_systolic' => $bp_sys ? intval($bp_sys) : null,
                    'blood_pressure_diastolic' => $bp_dia ? intval($bp_dia) : null,
                    'temperature' => $temp ? floatval($temp) : null,
                    'heart_rate' => $pulse ? intval($pulse) : null
                ]);
            }

            $this->setFlash('success', 'Catatan keperawatan berhasil disimpan');
        } catch (Exception $e) {
            error_log("Save nursing note error: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menyimpan catatan keperawatan');
        }

        $this->redirect('inpatient/detail/' . $visitId);
    }

    /**
     * Discharge patient from rawat inap (Checkout)
     * 
     * @param int $id Visit ID
     */
    public function discharge($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.edit');

        if (!isPost()) {
            $this->redirect('inpatient/detail/' . $id);
        }

        $this->requireCsrf();

        $notes = $this->post('discharge_notes', 'Pulang atas instruksi dokter');

        try {
            $this->inpatientModel->dischargePatient($id, $notes);

            // Log Audit
            $this->logAudit(
                'discharge',
                'inpatient',
                'patient_visits',
                $id,
                'Pasien rawat inap dipulangkan (Discharged), Visit ID: ' . $id
            );

            $this->setFlash('success', 'Pasien berhasil dipulangkan dan tempat tidur dikosongkan.');
        } catch (Exception $e) {
            error_log("Inpatient discharge error: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memulangkan pasien: ' . $e->getMessage());
        }

        $this->redirect('inpatient');
    }
}
