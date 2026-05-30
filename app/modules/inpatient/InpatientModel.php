<?php

/**
 * Inpatient Model
 * Handles Rawat Inap (inpatient admission), Bed Management, and Nursing Notes
 */
class InpatientModel extends Model
{
    protected $table = 'patient_visits';
    protected $primaryKey = 'id';

    /**
     * Get active inpatient admissions
     * 
     * @return array
     */
    public function getActiveAdmissions()
    {
        $sql = "SELECT pv.*, 
                       p.medical_record_number, p.full_name as patient_name, p.nik, p.gender, p.birth_date,
                       r.name as room_name, r.code as room_code, r.floor, r.building,
                       u.full_name as doctor_name
                FROM {$this->table} pv
                JOIN patients p ON pv.patient_id = p.id
                LEFT JOIN rooms r ON pv.room_id = r.id
                LEFT JOIN doctors d ON pv.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE pv.visit_type = 'inpatient' 
                AND pv.visit_status IN ('registered', 'waiting', 'ongoing')
                ORDER BY pv.visit_date DESC";

        $admissions = Database::fetchAll($sql);
        
        // Decrypt NIK for compliance display
        foreach ($admissions as &$row) {
            $row['nik'] = Crypt::decrypt($row['nik']);
            $row['age'] = calculateAge($row['birth_date']);
        }
        
        return $admissions;
    }

    /**
     * Get admission detail with patient and room info
     * 
     * @param int $id Visit ID
     * @return array|false
     */
    public function getAdmissionDetail($id)
    {
        $sql = "SELECT pv.*, 
                       p.medical_record_number, p.full_name as patient_name, p.nik, p.gender, p.birth_date, p.blood_type, p.phone,
                       r.name as room_name, r.code as room_code, r.floor, r.building, r.type as room_type,
                       u.full_name as doctor_name, d.specialization
                FROM {$this->table} pv
                JOIN patients p ON pv.patient_id = p.id
                LEFT JOIN rooms r ON pv.room_id = r.id
                LEFT JOIN doctors d ON pv.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE pv.id = ? LIMIT 1";

        $admission = Database::fetchOne($sql, [$id]);
        
        if ($admission) {
            $admission['nik'] = Crypt::decrypt($admission['nik']);
            $admission['age'] = calculateAge($admission['birth_date']);
        }
        
        return $admission;
    }

    /**
     * Get all rooms with inpatient type for Bed Management
     * 
     * @return array
     */
    public function getBedStatus()
    {
        $sql = "SELECT r.*, d.name as department_name,
                (r.capacity - r.available_beds) as occupied_beds
                FROM rooms r
                LEFT JOIN departments d ON r.department_id = d.id
                WHERE r.type IN ('inpatient', 'icu', 'emergency') 
                AND r.is_active = 1
                ORDER BY r.building ASC, r.floor ASC, r.code ASC";
                
        return Database::fetchAll($sql);
    }

    /**
     * Get statistics for rawat inap (BOR - Bed Occupancy Rate)
     * 
     * @return array
     */
    public function getInpatientStats()
    {
        $sql = "SELECT 
                SUM(capacity) as total_beds,
                SUM(available_beds) as available_beds,
                SUM(capacity - available_beds) as occupied_beds
                FROM rooms 
                WHERE type IN ('inpatient', 'icu', 'emergency') 
                AND is_active = 1";
                
        $stats = Database::fetchOne($sql);
        
        $total = intval($stats['total_beds'] ?? 0);
        $occupied = intval($stats['occupied_beds'] ?? 0);
        
        $bor = ($total > 0) ? round(($occupied / $total) * 100, 1) : 0;
        
        return [
            'total_beds' => $total,
            'available_beds' => intval($stats['available_beds'] ?? 0),
            'occupied_beds' => $occupied,
            'bor' => $bor
        ];
    }

    /**
     * Admit a patient to rawat inap
     * 
     * @param array $data Inpatient admission data
     * @return int Visit ID
     */
    public function admitPatient($data)
    {
        $this->beginTransaction();
        try {
            // Check if room has available beds
            $roomId = $data['room_id'];
            $room = Database::fetchOne("SELECT capacity, available_beds FROM rooms WHERE id = ? FOR UPDATE", [$roomId]);
            
            if (!$room || $room['available_beds'] <= 0) {
                throw new Exception("Ruangan penuh atau tidak ditemukan");
            }
            
            // Insert visit record
            $data['visit_type'] = 'inpatient';
            $data['visit_status'] = 'ongoing';
            $data['admission_date'] = date('Y-m-d H:i:s');
            $data['visit_number'] = generateDocumentNumber('REG', 'patient_visits', 'visit_number');
            
            $visitId = parent::create($data);
            
            // Decrement available beds
            $newAvailable = $room['available_beds'] - 1;
            Database::update('rooms', ['available_beds' => $newAvailable], ['id' => $roomId]);
            
            $this->commit();
            return $visitId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Discharge a patient (check out from rawat inap)
     * 
     * @param int $id Visit ID
     * @param string $notes Discharge notes
     * @return bool
     */
    public function dischargePatient($id, $notes)
    {
        $this->beginTransaction();
        try {
            $visit = Database::fetchOne("SELECT id, room_id, visit_date, visit_status FROM {$this->table} WHERE id = ? FOR UPDATE", [$id]);
            
            if (!$visit || $visit['visit_status'] === 'completed') {
                throw new Exception("Kunjungan tidak ditemukan atau sudah selesai");
            }
            
            $roomId = $visit['room_id'];
            $dischargeDate = date('Y-m-d H:i:s');
            
            // Calculate length of stay (minimum 1 day)
            $admissionTime = strtotime($visit['visit_date']);
            $dischargeTime = strtotime($dischargeDate);
            $secondsDiff = $dischargeTime - $admissionTime;
            $days = ceil($secondsDiff / (3600 * 24));
            $lengthOfStay = max(1, intval($days));

            // Update visit status
            $updateData = [
                'visit_status' => 'completed',
                'discharge_date' => $dischargeDate,
                'length_of_stay' => $lengthOfStay,
                'notes' => $notes
            ];
            
            Database::update($this->table, $updateData, ['id' => $id]);
            
            // Increment available beds in room
            if ($roomId) {
                $room = Database::fetchOne("SELECT available_beds, capacity FROM rooms WHERE id = ? FOR UPDATE", [$roomId]);
                if ($room) {
                    $newAvailable = min($room['capacity'], $room['available_beds'] + 1);
                    Database::update('rooms', ['available_beds' => $newAvailable], ['id' => $roomId]);
                }
            }
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get nursing notes for an admission
     * 
     * @param int $visitId
     * @return array
     */
    public function getNursingNotes($visitId)
    {
        $sql = "SELECT nn.*, u.full_name as nurse_name
                FROM nursing_notes nn
                JOIN users u ON nn.nurse_id = u.id
                WHERE nn.visit_id = ?
                ORDER BY nn.note_date DESC";
                
        return Database::fetchAll($sql, [$visitId]);
    }

    /**
     * Save a new nursing note
     * 
     * @param array $data
     * @return int Last insert ID
     */
    public function saveNursingNote($data)
    {
        return Database::insert('nursing_notes', $data);
    }
}
