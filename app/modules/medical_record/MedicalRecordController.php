<?php

/**
 * Medical Record Controller
 * 
 * Handles patient clinical records, vital signs, diagnoses (ICD-10), and SOAP assessments.
 */
class MedicalRecordController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Display list of medical records
     */
    public function index()
    {
        $this->requirePermission('medical_records.view');

        $search = $this->get('search', '');
        $date = $this->get('date', '');

        $query = "SELECT mr.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                         u.full_name AS doctor_name, pv.visit_number
                  FROM medical_records mr
                  JOIN patients p ON mr.patient_id = p.id
                  JOIN doctors d ON mr.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN patient_visits pv ON mr.visit_id = pv.id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $query .= " AND (p.full_name LIKE ? OR p.medical_record_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($date)) {
            $query .= " AND DATE(mr.created_at) = ?";
            $params[] = $date;
        }

        $query .= " ORDER BY mr.created_at DESC";

        $records = Database::fetchAll($query, $params);

        $data = [
            'title' => 'Rekam Medis Pasien - SIMRS',
            'records' => $records,
            'filters' => [
                'search' => $search,
                'date' => $date
            ]
        ];

        $this->view('medical_record/views/index', $data);
    }

    /**
     * View detail of a single medical record
     */
    public function detail($id)
    {
        $this->requirePermission('medical_records.view');

        $record = Database::fetchOne(
            "SELECT mr.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date, 
                     p.blood_type, p.insurance_type, u.full_name AS doctor_name, d.specialization,
                     pv.visit_number, pv.visit_date, uv.full_name AS verifier_name
             FROM medical_records mr
             JOIN patients p ON mr.patient_id = p.id
             JOIN doctors d ON mr.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             LEFT JOIN users uv ON mr.verified_by = uv.id
             LEFT JOIN patient_visits pv ON mr.visit_id = pv.id
             WHERE mr.id = ?",
            [$id]
        );

        if (!$record) {
            $this->setFlash('error', 'Rekam medis tidak ditemukan.');
            $this->redirect('medical-record');
        }

        // Get vital signs
        $vitalSigns = Database::fetchOne(
            "SELECT * FROM vital_signs WHERE medical_record_id = ? ORDER BY id DESC LIMIT 1",
            [$id]
        );

        // Get diagnoses
        $diagnoses = Database::fetchAll(
            "SELECT * FROM diagnoses WHERE medical_record_id = ? ORDER BY diagnosis_type ASC",
            [$id]
        );

        // Get allergies
        $allergies = Database::fetchAll(
            "SELECT * FROM allergies WHERE patient_id = ? AND is_active = 1",
            [$record['patient_id']]
        );

        // Get prescriptions for this visit
        $prescriptions = [];
        if ($record['visit_id']) {
            $prescriptions = Database::fetchAll(
                "SELECT p.*, u.full_name AS pharmacist_name 
                 FROM prescriptions p 
                 LEFT JOIN users u ON p.dispensed_by = u.id
                 WHERE p.visit_id = ?",
                [$record['visit_id']]
            );

            foreach ($prescriptions as $key => $prescription) {
                $prescriptions[$key]['items'] = Database::fetchAll(
                    "SELECT pi.*, m.name AS medicine_name, m.code AS medicine_code 
                     FROM prescription_items pi 
                     JOIN medicines m ON pi.medicine_id = m.id 
                     WHERE pi.prescription_id = ?",
                    [$prescription['id']]
                );
            }
        }

        // Get lab orders for this visit
        $labOrders = [];
        if ($record['visit_id']) {
            $labOrders = Database::fetchAll(
                "SELECT lo.*, u.full_name AS verified_by_name 
                 FROM lab_orders lo 
                 LEFT JOIN users u ON lo.verified_by = u.id
                 WHERE lo.visit_id = ?",
                [$record['visit_id']]
            );

            foreach ($labOrders as $key => $order) {
                $labOrders[$key]['items'] = Database::fetchAll(
                    "SELECT loi.*, lt.name AS test_name, lt.category, lr.result_value, lr.result_unit, lr.reference_range, lr.result_flag, lr.result_interpretation
                     FROM lab_order_items loi 
                     JOIN lab_templates lt ON loi.lab_template_id = lt.id 
                     LEFT JOIN lab_results lr ON lr.lab_order_item_id = loi.id
                     WHERE loi.lab_order_id = ?",
                    [$order['id']]
                );
            }
        }

        $data = [
            'title' => 'Detail Rekam Medis - SIMRS',
            'record' => $record,
            'vitalSigns' => $vitalSigns,
            'diagnoses' => $diagnoses,
            'allergies' => $allergies,
            'prescriptions' => $prescriptions,
            'labOrders' => $labOrders
        ];

        $this->view('medical_record/views/detail', $data);
    }

    /**
     * Show form to write a new medical record
     */
    public function create()
    {
        $this->requirePermission('medical_records.create');

        $visitId = $this->get('visit_id', '');

        // Fetch active/recent patient visits that don't have medical records yet
        $visitsQuery = "SELECT pv.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date,
                              u.full_name AS doctor_name, poly.name AS polyclinic_name
                       FROM patient_visits pv
                       JOIN patients p ON pv.patient_id = p.id
                       LEFT JOIN doctors d ON pv.doctor_id = d.id
                       LEFT JOIN users u ON d.user_id = u.id
                       JOIN polyclinics poly ON pv.polyclinic_id = poly.id
                       LEFT JOIN medical_records mr ON pv.id = mr.visit_id
                       WHERE mr.id IS NULL AND pv.visit_status IN ('registered', 'waiting', 'ongoing')
                       ORDER BY pv.visit_date DESC";
        
        $visits = Database::fetchAll($visitsQuery);
        $selectedVisit = null;
        $allergies = [];

        if (!empty($visitId)) {
            $selectedVisit = Database::fetchOne(
                "SELECT pv.*, p.medical_record_number, p.full_name AS patient_name, p.gender, p.birth_date, p.blood_type,
                        u.full_name AS doctor_name, poly.name AS polyclinic_name
                 FROM patient_visits pv
                 JOIN patients p ON pv.patient_id = p.id
                 LEFT JOIN doctors d ON pv.doctor_id = d.id
                 LEFT JOIN users u ON d.user_id = u.id
                 JOIN polyclinics poly ON pv.polyclinic_id = poly.id
                 WHERE pv.id = ?",
                [$visitId]
            );

            if ($selectedVisit) {
                $allergies = Database::fetchAll("SELECT * FROM allergies WHERE patient_id = ? AND is_active = 1", [$selectedVisit['patient_id']]);
            }
        }

        $icd10 = Database::fetchAll("SELECT DISTINCT icd10_code, diagnosis_name FROM diagnoses LIMIT 50"); // mock standard diagnoses

        $data = [
            'title' => 'Input Pemeriksaan Rekam Medis (SOAP) - SIMRS',
            'visits' => $visits,
            'selectedVisit' => $selectedVisit,
            'allergies' => $allergies,
            'icd10' => $icd10
        ];

        $this->view('medical_record/views/create', $data);
    }

    /**
     * Store new medical record and vital signs
     */
    public function store()
    {
        $this->requirePermission('medical_records.create');

        if (!isPost()) {
            $this->redirect('medical-record');
        }

        $this->requireCsrf();

        $visitId = $this->post('visit_id');
        $subjective = $this->post('subjective');
        $objective = $this->post('objective');
        $assessment = $this->post('assessment');
        $plan = $this->post('plan');
        
        $generalCondition = $this->post('general_condition');
        $consciousnessLevel = $this->post('consciousness_level');
        $physicalExamNotes = $this->post('physical_exam_notes');
        $doctorInstructions = $this->post('doctor_instructions');
        $followUpPlan = $this->post('follow_up_plan');
        $followUpDate = $this->post('follow_up_date') ?: null;

        // Vital Signs
        $systolic = $this->post('systolic');
        $diastolic = $this->post('diastolic');
        $heartRate = $this->post('heart_rate');
        $respRate = $this->post('respiratory_rate');
        $temperature = $this->post('temperature');
        $oxygenSat = $this->post('oxygen_saturation');
        $weight = $this->post('weight');
        $height = $this->post('height');
        $painScale = $this->post('pain_scale');
        $vitalNotes = $this->post('vital_notes');

        // Diagnoses
        $icdCode = $this->post('icd10_code');
        $diagName = $this->post('diagnosis_name');

        $visit = Database::fetchOne("SELECT * FROM patient_visits WHERE id = ?", [$visitId]);
        if (!$visit) {
            $this->setFlash('error', 'Kunjungan pasien tidak valid.');
            $this->redirect('medical-record/create');
        }

        try {
            Database::beginTransaction();

            // 1. Insert Medical Record
            $mrId = Database::insert('medical_records', [
                'patient_id' => $visit['patient_id'],
                'visit_id' => $visitId,
                'doctor_id' => $visit['doctor_id'],
                'subjective' => $subjective,
                'objective' => $objective,
                'assessment' => $assessment,
                'plan' => $plan,
                'general_condition' => $generalCondition,
                'consciousness_level' => $consciousnessLevel,
                'physical_exam_notes' => $physicalExamNotes,
                'doctor_instructions' => $doctorInstructions,
                'follow_up_plan' => $followUpPlan,
                'follow_up_date' => $followUpDate,
                'record_status' => 'completed',
                'created_by' => Session::getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Insert Vital Signs
            $bmi = null;
            if (!empty($weight) && !empty($height)) {
                $heightMeters = $height / 100;
                $bmi = round($weight / ($heightMeters * $heightMeters), 2);
            }

            Database::insert('vital_signs', [
                'patient_id' => $visit['patient_id'],
                'visit_id' => $visitId,
                'medical_record_id' => $mrId,
                'measured_by' => Session::getUserId(),
                'blood_pressure_systolic' => $systolic ?: null,
                'blood_pressure_diastolic' => $diastolic ?: null,
                'heart_rate' => $heartRate ?: null,
                'respiratory_rate' => $respRate ?: null,
                'temperature' => $temperature ?: null,
                'oxygen_saturation' => $oxygenSat ?: null,
                'weight' => $weight ?: null,
                'height' => $height ?: null,
                'bmi' => $bmi,
                'pain_scale' => $painScale !== '' ? $painScale : null,
                'notes' => $vitalNotes,
                'measured_at' => date('Y-m-d H:i:s')
            ]);

            // 3. Insert Primary Diagnosis
            if (!empty($diagName)) {
                Database::insert('diagnoses', [
                    'medical_record_id' => $mrId,
                    'icd10_code' => $icdCode ?: 'R50.9',
                    'diagnosis_name' => $diagName,
                    'diagnosis_type' => 'primary',
                    'notes' => 'Diinput pasca pemeriksaan umum',
                    'created_by' => Session::getUserId()
                ]);
            }

            // 4. Update Visit status to completed
            Database::update('patient_visits', ['visit_status' => 'completed', 'discharge_date' => date('Y-m-d H:i:s')], ['id' => $visitId]);

            Database::commit();

            $this->logAudit('create', 'medical_record', 'medical_records', $mrId, "Mengisi rekam medis dan asesmen SOAP untuk visit {$visit['visit_number']}");
            $this->setFlash('success', 'Rekam medis SOAP berhasil disimpan.');
            $this->redirect('medical-record/detail/' . $mrId);

        } catch (Exception $e) {
            Database::rollback();
            error_log("Error store medical record: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menyimpan rekam medis. Error: ' . $e->getMessage());
            $this->redirect('medical-record/create?visit_id=' . $visitId);
        }
    }

    /**
     * Search ICD-10 codes for autocompletion
     */
    public function icd10Autocomplete()
    {
        $this->requirePermission('medical_records.view');
        $q = trim($this->get('q', ''));
        
        if (strlen($q) < 2) {
            $this->json([]);
        }
        
        $query = "SELECT code, name_en, name_id 
                  FROM icds 
                  WHERE code LIKE ? OR name_id LIKE ? OR name_en LIKE ? 
                  LIMIT 15";
        $params = ["%$q%", "%$q%", "%$q%"];
        
        $results = Database::fetchAll($query, $params);
        $this->json($results);
    }

    /**
     * Verify and lock a medical record (Permenkes 24/2022 compliance)
     */
    public function verify($id)
    {
        $this->requirePermission('medical_records.edit');
        $this->requireCsrf();

        $id = (int)$id;
        $record = Database::fetchOne("SELECT id, record_status, patient_id FROM medical_records WHERE id = ?", [$id]);
        if (!$record) {
            $this->setFlash('error', 'Rekam medis tidak ditemukan.');
            $this->redirect('medical-record');
        }

        if ($record['record_status'] === 'verified') {
            $this->setFlash('warning', 'Rekam medis sudah terverifikasi sebelumnya.');
            $this->redirect('medical-record/detail/' . $id);
        }

        // Get current user id
        $user = $this->getCurrentUser();
        $userId = $user['id'] ?? null;

        try {
            Database::update('medical_records', [
                'record_status' => 'verified',
                'verified_by' => $userId,
                'verified_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $this->logAudit('verify', 'medical_record', 'medical_records', $id, "Memverifikasi dan mengunci rekam medis ID: {$id}");
            $this->setFlash('success', 'Rekam medis berhasil diverifikasi dan dikunci secara sah.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Gagal memverifikasi rekam medis: ' . $e->getMessage());
        }

        $this->redirect('medical-record/detail/' . $id);
    }
}
