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

        // Search by MRN, NIK, name, or phone
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND (
                    medical_record_number LIKE ? 
                    OR nik LIKE ?
                    OR full_name LIKE ?
                    OR phone LIKE ?
                    OR mobile LIKE ?
                )
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";

        $searchParam = "%{$query}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $perPage, $offset];

        $data = Database::fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} 
                     WHERE is_active = 1 
                     AND (
                         medical_record_number LIKE ? 
                         OR nik LIKE ?
                         OR full_name LIKE ?
                         OR phone LIKE ?
                         OR mobile LIKE ?
                     )";

        $countParams = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
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

        return Database::fetchAll($sql);
    }
}
