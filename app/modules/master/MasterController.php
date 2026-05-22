<?php

/**
 * Master Data Controller
 * 
 * Handles hospital information, departments, doctors, and polyclinics
 */

class MasterController extends Controller
{
    /**
     * Constructor - Require admin access for all master data operations
     */
    public function __construct()
    {
        $this->requireAuth();
        if (!Auth::hasRole('admin')) {
            $this->setFlash('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman Master Data.');
            $this->redirect('dashboard');
        }
    }

    /**
     * View and Edit Hospital Info
     */
    public function hospital()
    {
        $hospital = Database::fetchOne("SELECT * FROM hospital_info LIMIT 1");
        
        $data = [
            'title' => 'Informasi Rumah Sakit - SIMRS',
            'hospital' => $hospital ?: []
        ];
        
        $this->view('master/views/hospital', $data);
    }

    /**
     * Update Hospital Info
     */
    public function updateHospital()
    {
        if (!isPost()) {
            $this->redirect('master/hospital');
        }
        
        $this->requireCsrf();
        
        $postData = [
            'name' => $this->post('name'),
            'slogan' => $this->post('slogan'),
            'address' => $this->post('address'),
            'city' => $this->post('city'),
            'province' => $this->post('province'),
            'postal_code' => $this->post('postal_code'),
            'phone' => $this->post('phone'),
            'email' => $this->post('email'),
            'website' => $this->post('website'),
            'director_name' => $this->post('director_name'),
            'license_number' => $this->post('license_number'),
            'accreditation' => $this->post('accreditation')
        ];
        
        try {
            $existing = Database::fetchOne("SELECT id FROM hospital_info LIMIT 1");
            if ($existing) {
                Database::update('hospital_info', $postData, ['id' => $existing['id']]);
            } else {
                Database::insert('hospital_info', $postData);
            }
            
            $this->logAudit('update', 'master', 'hospital_info', $existing['id'] ?? null, 'Memperbarui informasi rumah sakit');
            $this->setFlash('success', 'Informasi rumah sakit berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error updateHospital: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui informasi rumah sakit.');
        }
        
        $this->redirect('master/hospital');
    }

    /**
     * View Departments
     */
    public function department()
    {
        $departments = Database::fetchAll("SELECT * FROM departments ORDER BY code ASC");
        
        $data = [
            'title' => 'Daftar Departemen - SIMRS',
            'departments' => $departments
        ];
        
        $this->view('master/views/department', $data);
    }

    /**
     * Store Department
     */
    public function storeDepartment()
    {
        if (!isPost()) {
            $this->redirect('master/department');
        }
        
        $this->requireCsrf();
        
        $insertData = [
            'code' => strtoupper($this->post('code')),
            'name' => $this->post('name'),
            'description' => $this->post('description'),
            'head_name' => $this->post('head_name'),
            'phone' => $this->post('phone'),
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            $id = Database::insert('departments', $insertData);
            $this->logAudit('create', 'master', 'departments', $id, 'Menambah departemen baru: ' . $insertData['name']);
            $this->setFlash('success', 'Departemen baru berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error storeDepartment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan departemen. Kode mungkin sudah terdaftar.');
        }
        
        $this->redirect('master/department');
    }

    /**
     * Update Department
     */
    public function updateDepartment($id)
    {
        if (!isPost()) {
            $this->redirect('master/department');
        }
        
        $this->requireCsrf();
        
        $updateData = [
            'name' => $this->post('name'),
            'description' => $this->post('description'),
            'head_name' => $this->post('head_name'),
            'phone' => $this->post('phone'),
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            Database::update('departments', $updateData, ['id' => $id]);
            $this->logAudit('update', 'master', 'departments', $id, 'Mengubah departemen: ' . $updateData['name']);
            $this->setFlash('success', 'Data departemen berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error updateDepartment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui data departemen.');
        }
        
        $this->redirect('master/department');
    }

    /**
     * Delete Department
     */
    public function deleteDepartment($id)
    {
        if (!isPost()) {
            $this->redirect('master/department');
        }
        
        $this->requireCsrf();
        
        try {
            // Soft delete or hard delete depending on requirement. We will toggle is_active to 0
            Database::update('departments', ['is_active' => 0], ['id' => $id]);
            $this->logAudit('delete', 'master', 'departments', $id, 'Menonaktifkan departemen (ID: ' . $id . ')');
            $this->setFlash('success', 'Departemen berhasil dinonaktifkan.');
        } catch (Exception $e) {
            error_log("Error deleteDepartment: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menonaktifkan departemen.');
        }
        
        $this->redirect('master/department');
    }

    /**
     * View Doctors
     */
    public function doctor()
    {
        $doctors = Database::fetchAll(
            "SELECT d.*, u.full_name, u.email, u.phone, u.username 
             FROM doctors d 
             LEFT JOIN users u ON d.user_id = u.id 
             ORDER BY u.full_name ASC"
        );
        
        // Fetch users who have doctor role but not registered in doctors table yet
        $availableUsers = Database::fetchAll(
            "SELECT u.id, u.full_name 
             FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.name = 'doctor' 
             AND u.id NOT IN (SELECT user_id FROM doctors WHERE user_id IS NOT NULL)"
        );
        
        $data = [
            'title' => 'Daftar Dokter - SIMRS',
            'doctors' => $doctors,
            'availableUsers' => $availableUsers
        ];
        
        $this->view('master/views/doctor', $data);
    }

    /**
     * Store Doctor
     */
    public function storeDoctor()
    {
        if (!isPost()) {
            $this->redirect('master/doctor');
        }
        
        $this->requireCsrf();
        
        $insertData = [
            'user_id' => $this->post('user_id') ? intval($this->post('user_id')) : null,
            'employee_number' => $this->post('employee_number'),
            'specialization' => $this->post('specialization'),
            'license_number' => $this->post('license_number'),
            'sip_number' => $this->post('sip_number'),
            'education' => $this->post('education'),
            'experience_years' => intval($this->post('experience_years')),
            'consultation_fee' => floatval($this->post('consultation_fee')),
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            $id = Database::insert('doctors', $insertData);
            $this->logAudit('create', 'master', 'doctors', $id, 'Menambah profil dokter baru: ' . $insertData['employee_number']);
            $this->setFlash('success', 'Profil dokter baru berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error storeDoctor: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan dokter. NIK/SIP mungkin sudah terdaftar.');
        }
        
        $this->redirect('master/doctor');
    }

    /**
     * Update Doctor
     */
    public function updateDoctor($id)
    {
        if (!isPost()) {
            $this->redirect('master/doctor');
        }
        
        $this->requireCsrf();
        
        $updateData = [
            'specialization' => $this->post('specialization'),
            'license_number' => $this->post('license_number'),
            'sip_number' => $this->post('sip_number'),
            'education' => $this->post('education'),
            'experience_years' => intval($this->post('experience_years')),
            'consultation_fee' => floatval($this->post('consultation_fee')),
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            Database::update('doctors', $updateData, ['id' => $id]);
            $this->logAudit('update', 'master', 'doctors', $id, 'Mengubah data dokter (ID: ' . $id . ')');
            $this->setFlash('success', 'Data dokter berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error updateDoctor: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui data dokter.');
        }
        
        $this->redirect('master/doctor');
    }

    /**
     * Delete Doctor
     */
    public function deleteDoctor($id)
    {
        if (!isPost()) {
            $this->redirect('master/doctor');
        }
        
        $this->requireCsrf();
        
        try {
            Database::update('doctors', ['is_active' => 0], ['id' => $id]);
            $this->logAudit('delete', 'master', 'doctors', $id, 'Menonaktifkan dokter (ID: ' . $id . ')');
            $this->setFlash('success', 'Dokter berhasil dinonaktifkan.');
        } catch (Exception $e) {
            error_log("Error deleteDoctor: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menonaktifkan dokter.');
        }
        
        $this->redirect('master/doctor');
    }

    /**
     * View Polyclinics
     */
    public function polyclinic()
    {
        $polyclinics = Database::fetchAll(
            "SELECT p.*, r.name as room_name 
             FROM polyclinics p 
             LEFT JOIN rooms r ON p.room_id = r.id 
             ORDER BY p.code ASC"
        );
        
        $rooms = Database::fetchAll("SELECT id, name, code FROM rooms WHERE type = 'outpatient' AND is_active = 1");
        
        $data = [
            'title' => 'Daftar Poliklinik - SIMRS',
            'polyclinics' => $polyclinics,
            'rooms' => $rooms
        ];
        
        $this->view('master/views/polyclinic', $data);
    }

    /**
     * Store Polyclinic
     */
    public function storePolyclinic()
    {
        if (!isPost()) {
            $this->redirect('master/polyclinic');
        }
        
        $this->requireCsrf();
        
        $insertData = [
            'code' => strtoupper($this->post('code')),
            'name' => $this->post('name'),
            'description' => $this->post('description'),
            'room_id' => $this->post('room_id') ? intval($this->post('room_id')) : null,
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            $id = Database::insert('polyclinics', $insertData);
            $this->logAudit('create', 'master', 'polyclinics', $id, 'Menambah poliklinik baru: ' . $insertData['name']);
            $this->setFlash('success', 'Poliklinik baru berhasil ditambahkan.');
        } catch (Exception $e) {
            error_log("Error storePolyclinic: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menambahkan poliklinik. Kode mungkin sudah terdaftar.');
        }
        
        $this->redirect('master/polyclinic');
    }

    /**
     * Update Polyclinic
     */
    public function updatePolyclinic($id)
    {
        if (!isPost()) {
            $this->redirect('master/polyclinic');
        }
        
        $this->requireCsrf();
        
        $updateData = [
            'name' => $this->post('name'),
            'description' => $this->post('description'),
            'room_id' => $this->post('room_id') ? intval($this->post('room_id')) : null,
            'is_active' => $this->post('is_active') === '0' ? 0 : 1
        ];
        
        try {
            Database::update('polyclinics', $updateData, ['id' => $id]);
            $this->logAudit('update', 'master', 'polyclinics', $id, 'Mengubah poliklinik: ' . $updateData['name']);
            $this->setFlash('success', 'Data poliklinik berhasil diperbarui.');
        } catch (Exception $e) {
            error_log("Error updatePolyclinic: " . $e->getMessage());
            $this->setFlash('error', 'Gagal memperbarui data poliklinik.');
        }
        
        $this->redirect('master/polyclinic');
    }

    /**
     * Delete Polyclinic
     */
    public function deletePolyclinic($id)
    {
        if (!isPost()) {
            $this->redirect('master/polyclinic');
        }
        
        $this->requireCsrf();
        
        try {
            Database::update('polyclinics', ['is_active' => 0], ['id' => $id]);
            $this->logAudit('delete', 'master', 'polyclinics', $id, 'Menonaktifkan poliklinik (ID: ' . $id . ')');
            $this->setFlash('success', 'Poliklinik berhasil dinonaktifkan.');
        } catch (Exception $e) {
            error_log("Error deletePolyclinic: " . $e->getMessage());
            $this->setFlash('error', 'Gagal menonaktifkan poliklinik.');
        }
        
        $this->redirect('master/polyclinic');
    }
}
