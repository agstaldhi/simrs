<?php

/**
 * Patient Model
 * 
 * Handles database operations for patients
 */

class PatientModel extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'id';

    /**
     * Search patients by multiple criteria
     * 
     * @param string $query Search query
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function search($query, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;

        // Search by MRN, NIK (hash lookup), name, or phone
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND (
                    medical_record_number LIKE ? 
                    OR (nik_hash = ?)
                    OR full_name LIKE ?
                    OR phone LIKE ?
                    OR mobile LIKE ?
                )
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";

        $searchParam = "%{$query}%";
        $nikHashParam = Crypt::hash($query);
        $params = [$searchParam, $nikHashParam, $searchParam, $searchParam, $searchParam, $perPage, $offset];

        $data = Database::fetchAll($sql, $params);
        $data = array_map([$this, 'decryptPatient'], $data);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} 
                     WHERE is_active = 1 
                     AND (
                          medical_record_number LIKE ? 
                          OR (nik_hash = ?)
                          OR full_name LIKE ?
                          OR phone LIKE ?
                          OR mobile LIKE ?
                      )";

        $countParams = [$searchParam, $nikHashParam, $searchParam, $searchParam, $searchParam];
        $total = Database::fetchOne($countSql, $countParams)['count'];

        $totalPages = ceil($total / $perPage);

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Get patient by medical record number
     * 
     * @param string $mrn Medical record number
     * @return array|false
     */
    public function getByMRN($mrn)
    {
        return $this->findOne(['medical_record_number' => $mrn]);
    }

    /**
     * Get patient by NIK
     * 
     * @param string $nik NIK
     * @return array|false
     */
    public function getByNIK($nik)
    {
        return $this->findOne(['nik' => $nik]);
    }

    /**
     * Get patient statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        return [
            'total' => $this->count(['is_active' => 1]),
            'today' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE DATE(registration_date) = CURDATE()"
            )['count'],
            'this_month' => Database::fetchOne(
                "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE MONTH(registration_date) = MONTH(CURDATE()) 
                 AND YEAR(registration_date) = YEAR(CURDATE())"
            )['count'],
            'male' => $this->count(['is_active' => 1, 'gender' => 'male']),
            'female' => $this->count(['is_active' => 1, 'gender' => 'female'])
        ];
    }

    /**
     * Get patient with visits
     * 
     * @param int $id Patient ID
     * @return array|false
     */
    public function getWithVisits($id)
    {
        $patient = $this->find($id);

        if (!$patient) {
            return false;
        }

        $patient['visits'] = Database::fetchAll(
            "SELECT pv.*, 
                    d.employee_number, 
                    u.full_name as doctor_name,
                    p.name as polyclinic_name,
                    r.name as room_name
             FROM patient_visits pv
             LEFT JOIN doctors d ON pv.doctor_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             LEFT JOIN polyclinics p ON pv.polyclinic_id = p.id
             LEFT JOIN rooms r ON pv.room_id = r.id
             WHERE pv.patient_id = ?
             ORDER BY pv.visit_date DESC",
            [$id]
        );

        $patient['allergies'] = Database::fetchAll(
            "SELECT * FROM allergies WHERE patient_id = ? AND is_active = 1",
            [$id]
        );

        return $patient;
    }

    /**
     * Get active patients count
     * 
     * @return int
     */
    public function getActiveCount()
    {
        return $this->count(['is_active' => 1]);
    }

    /**
     * Get patients by insurance type
     * 
     * @param string $insuranceType Insurance type
     * @return array
     */
    public function getByInsuranceType($insuranceType)
    {
        return $this->findAll(['insurance_type' => $insuranceType, 'is_active' => 1]);
    }

    /**
     * Get birthday patients this month
     * 
     * @return array
     */
    public function getBirthdayThisMonth()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE MONTH(birth_date) = MONTH(CURDATE())
                AND is_active = 1
                ORDER BY DAY(birth_date) ASC";

        $data = Database::fetchAll($sql);
        return array_map([$this, 'decryptPatient'], $data);
    }

    // ==========================================
    // OVERRIDES & CRYPTO HELPERS FOR UU PDP
    // ==========================================

    public function find($id)
    {
        $patient = parent::find($id);
        return $patient ? $this->decryptPatient($patient) : false;
    }

    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = null)
    {
        $patients = parent::findAll($conditions, $orderBy, $limit, $offset);
        return array_map([$this, 'decryptPatient'], $patients);
    }

    public function findOne($conditions)
    {
        $conditions = $this->hashConditions($conditions);
        $patient = parent::findOne($conditions);
        return $patient ? $this->decryptPatient($patient) : false;
    }

    public function create($data)
    {
        $data = $this->encryptPatient($data);
        return parent::create($data);
    }

    public function update($id, $data)
    {
        $data = $this->encryptPatient($data);
        return parent::update($id, $data);
    }

    /**
     * Encrypt sensitive fields and generate search hashes
     */
    private function encryptPatient($data)
    {
        if (isset($data['nik'])) {
            $data['nik_hash'] = Crypt::hash($data['nik']);
            $data['nik'] = Crypt::encrypt($data['nik']);
        }
        if (isset($data['insurance_number'])) {
            $data['insurance_number_hash'] = Crypt::hash($data['insurance_number']);
            $data['insurance_number'] = Crypt::encrypt($data['insurance_number']);
        }
        return $data;
    }

    /**
     * Decrypt sensitive patient fields
     */
    public function decryptPatient($data)
    {
        if (empty($data)) return $data;
        
        if (isset($data['nik'])) {
            $data['nik'] = Crypt::decrypt($data['nik']);
        }
        if (isset($data['insurance_number'])) {
            $data['insurance_number'] = Crypt::decrypt($data['insurance_number']);
        }
        return $data;
    }

    /**
     * Map search conditions (for NIK / BPJS number) to search hashes
     */
    private function hashConditions($conditions)
    {
        if (isset($conditions['nik'])) {
            $conditions['nik_hash'] = Crypt::hash($conditions['nik']);
            unset($conditions['nik']);
        }
        if (isset($conditions['insurance_number'])) {
            $conditions['insurance_number_hash'] = Crypt::hash($conditions['insurance_number']);
            unset($conditions['insurance_number']);
        }
        return $conditions;
    }
}
