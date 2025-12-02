<?php

/**
 * Patient Controller
 * 
 * Handles patient management (CRUD operations)
 */

class PatientController extends Controller
{
    protected $patientModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->patientModel = $this->model('Patient');
    }

    /**
     * List all patients
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('patients.view');

        // Get search query
        $search = $this->get('search', '');
        $page = $this->get('page', 1);
        $perPage = 20;

        // Get patients with pagination
        if (!empty($search)) {
            $patients = $this->patientModel->search($search, $page, $perPage);
        } else {
            $patients = $this->patientModel->paginate($page, $perPage, ['is_active' => 1], 'created_at DESC');
        }

        $data = [
            'title' => 'Daftar Pasien - SIMRS',
            'patients' => $patients,
            'search' => $search
        ];

        $this->view('patient/views/index', $data);
    }

    /**
     * Show create patient form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('patients.create');

        $data = [
            'title' => 'Daftar Pasien Baru - SIMRS'
        ];

        $this->view('patient/views/create', $data);
    }

    /**
     * Store new patient
     */
    public function store()
    {
        $this->requireAuth();
        $this->requirePermission('patients.create');

        if (!isPost()) {
            $this->redirect('patient/create');
        }

        $this->requireCsrf();

        // Get input
        $input = [
            'nik' => $this->post('nik'),
            'title' => $this->post('title'),
            'full_name' => $this->post('full_name'),
            'birth_place' => $this->post('birth_place'),
            'birth_date' => $this->post('birth_date'),
            'gender' => $this->post('gender'),
            'blood_type' => $this->post('blood_type', 'unknown'),
            'religion' => $this->post('religion', 'islam'),
            'education' => $this->post('education', 'sma'),
            'marital_status' => $this->post('marital_status', 'belum_kawin'),
            'occupation' => $this->post('occupation'),
            'address' => $this->post('address'),
            'rt' => $this->post('rt'),
            'rw' => $this->post('rw'),
            'kelurahan' => $this->post('kelurahan'),
            'kecamatan' => $this->post('kecamatan'),
            'city' => $this->post('city'),
            'province' => $this->post('province'),
            'postal_code' => $this->post('postal_code'),
            'phone' => $this->post('phone'),
            'mobile' => $this->post('mobile'),
            'email' => $this->post('email'),
            'emergency_contact_name' => $this->post('emergency_contact_name'),
            'emergency_contact_relation' => $this->post('emergency_contact_relation'),
            'emergency_contact_phone' => $this->post('emergency_contact_phone'),
            'insurance_type' => $this->post('insurance_type', 'umum'),
            'insurance_number' => $this->post('insurance_number')
        ];

        // Validation
        $errors = $this->validate($input, [
            'full_name' => 'required|min:3',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female',
            'mobile' => 'required|phone',
            'nik' => 'unique:patients,nik'
        ]);

        if (!empty($errors)) {
            flashOld($input);
            $this->setFlash('error', 'Data tidak valid: ' . implode(', ', $errors));
            $this->redirect('patient/create');
        }

        // Generate medical record number
        $input['medical_record_number'] = generateMRN();
        $input['registration_date'] = date('Y-m-d H:i:s');
        $input['created_by'] = Auth::id();

        // Save to database
        try {
            $patientId = $this->patientModel->create($input);

            // Log audit
            $this->logAudit(
                'create',
                'patient',
                'patients',
                $patientId,
                'Daftar pasien baru: ' . $input['full_name']
            );

            clearOld();
            $this->setFlash('success', 'Pasien berhasil didaftarkan dengan No. RM: ' . $input['medical_record_number']);
            $this->redirect('patient/detail/' . $patientId);
        } catch (Exception $e) {
            error_log("Patient create error: " . $e->getMessage());
            flashOld($input);
            $this->setFlash('error', 'Gagal menyimpan data pasien');
            $this->redirect('patient/create');
        }
    }

    /**
     * Show patient detail
     * 
     * @param int $id Patient ID
     */
    public function detail($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.view_detail');

        $patient = $this->patientModel->find($id);

        if (!$patient) {
            $this->setFlash('error', 'Pasien tidak ditemukan');
            $this->redirect('patient');
        }

        // Get patient visits
        $visits = Database::fetchAll(
            "SELECT pv.*, d.employee_number, u.full_name as doctor_name, p.name as polyclinic_name
             FROM patient_visits pv
             LEFT JOIN doctors d ON pv.doctor_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             LEFT JOIN polyclinics p ON pv.polyclinic_id = p.id
             WHERE pv.patient_id = ?
             ORDER BY pv.visit_date DESC
             LIMIT 10",
            [$id]
        );

        // Get allergies
        $allergies = Database::fetchAll(
            "SELECT * FROM allergies WHERE patient_id = ? AND is_active = 1",
            [$id]
        );

        $data = [
            'title' => 'Detail Pasien - SIMRS',
            'patient' => $patient,
            'visits' => $visits,
            'allergies' => $allergies,
            'age' => calculateAge($patient['birth_date'])
        ];

        $this->view('patient/views/detail', $data);
    }

    /**
     * Show edit patient form
     * 
     * @param int $id Patient ID
     */
    public function edit($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.edit');

        $patient = $this->patientModel->find($id);

        if (!$patient) {
            $this->setFlash('error', 'Pasien tidak ditemukan');
            $this->redirect('patient');
        }

        $data = [
            'title' => 'Edit Data Pasien - SIMRS',
            'patient' => $patient
        ];

        $this->view('patient/views/edit', $data);
    }

    /**
     * Update patient
     * 
     * @param int $id Patient ID
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.edit');

        if (!isPost()) {
            $this->redirect('patient/edit/' . $id);
        }

        $this->requireCsrf();

        $patient = $this->patientModel->find($id);

        if (!$patient) {
            $this->setFlash('error', 'Pasien tidak ditemukan');
            $this->redirect('patient');
        }

        // Get input (same as store)
        $input = [
            'nik' => $this->post('nik'),
            'title' => $this->post('title'),
            'full_name' => $this->post('full_name'),
            'birth_place' => $this->post('birth_place'),
            'birth_date' => $this->post('birth_date'),
            'gender' => $this->post('gender'),
            'blood_type' => $this->post('blood_type'),
            'religion' => $this->post('religion'),
            'education' => $this->post('education'),
            'marital_status' => $this->post('marital_status'),
            'occupation' => $this->post('occupation'),
            'address' => $this->post('address'),
            'rt' => $this->post('rt'),
            'rw' => $this->post('rw'),
            'kelurahan' => $this->post('kelurahan'),
            'kecamatan' => $this->post('kecamatan'),
            'city' => $this->post('city'),
            'province' => $this->post('province'),
            'postal_code' => $this->post('postal_code'),
            'phone' => $this->post('phone'),
            'mobile' => $this->post('mobile'),
            'email' => $this->post('email'),
            'emergency_contact_name' => $this->post('emergency_contact_name'),
            'emergency_contact_relation' => $this->post('emergency_contact_relation'),
            'emergency_contact_phone' => $this->post('emergency_contact_phone'),
            'insurance_type' => $this->post('insurance_type'),
            'insurance_number' => $this->post('insurance_number')
        ];

        // Validation (exclude current record for NIK uniqueness)
        $errors = $this->validate($input, [
            'full_name' => 'required|min:3',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female',
            'mobile' => 'required|phone'
        ]);

        // Check NIK uniqueness manually
        if (!empty($input['nik']) && $input['nik'] !== $patient['nik']) {
            $exists = $this->patientModel->exists(['nik' => $input['nik']]);
            if ($exists) {
                $errors['nik'] = 'NIK sudah digunakan';
            }
        }

        if (!empty($errors)) {
            flashOld($input);
            $this->setFlash('error', 'Data tidak valid: ' . implode(', ', $errors));
            $this->redirect('patient/edit/' . $id);
        }

        $input['updated_by'] = Auth::id();

        // Update database
        try {
            $this->patientModel->update($id, $input);

            // Log audit
            $this->logAudit(
                'update',
                'patient',
                'patients',
                $id,
                'Update data pasien: ' . $input['full_name']
            );

            clearOld();
            $this->setFlash('success', 'Data pasien berhasil diupdate');
            $this->redirect('patient/detail/' . $id);
        } catch (Exception $e) {
            error_log("Patient update error: " . $e->getMessage());
            flashOld($input);
            $this->setFlash('error', 'Gagal mengupdate data pasien');
            $this->redirect('patient/edit/' . $id);
        }
    }

    /**
     * Delete patient (soft delete)
     * 
     * @param int $id Patient ID
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->requirePermission('patients.delete');

        if (!isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $this->requireCsrf();

        $patient = $this->patientModel->find($id);

        if (!$patient) {
            $this->json(['success' => false, 'message' => 'Pasien tidak ditemukan'], 404);
        }

        try {
            // Soft delete
            $this->patientModel->softDelete($id);

            // Log audit
            $this->logAudit(
                'delete',
                'patient',
                'patients',
                $id,
                'Hapus pasien: ' . $patient['full_name']
            );

            $this->json(['success' => true, 'message' => 'Pasien berhasil dihapus']);
        } catch (Exception $e) {
            error_log("Patient delete error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Gagal menghapus pasien'], 500);
        }
    }

    /**
     * Search patients (AJAX)
     */
    public function search()
    {
        $this->requireAuth();
        $this->requirePermission('patients.view');

        $query = $this->get('q', '');

        if (empty($query)) {
            $this->json(['success' => false, 'data' => []]);
        }

        $results = $this->patientModel->search($query, 1, 10);

        $this->json([
            'success' => true,
            'data' => $results['data']
        ]);
    }
}
