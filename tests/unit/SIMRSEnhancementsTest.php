<?php

use PHPUnit\Framework\TestCase;

class SIMRSEnhancementsTest extends TestCase
{
    protected function setUp(): void
    {
        Database::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (Database::getConnection()->inTransaction()) {
            Database::rollback();
        }
    }

    /**
     * Test User CRUD operations and Status Toggle
     */
    public function testUserCrudAndActiveToggle()
    {
        // 1. Create User
        $userId = Database::insert('users', [
            'username' => 'test_crud_user',
            'email' => 'test_crud_user@simrs.local',
            'password' => password_hash('Password123!', PASSWORD_BCRYPT),
            'full_name' => 'Test Crud User',
            'is_active' => 1
        ]);
        $this->assertGreaterThan(0, $userId);

        // 2. Fetch User
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        $this->assertNotEmpty($user);
        $this->assertEquals('Test Crud User', $user['full_name']);
        $this->assertEquals(1, intval($user['is_active']));

        // 3. Update User
        Database::update('users', [
            'full_name' => 'Test Crud User Updated',
            'email' => 'updated_crud_user@simrs.local'
        ], ['id' => $userId]);

        $userUpdated = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        $this->assertEquals('Test Crud User Updated', $userUpdated['full_name']);
        $this->assertEquals('updated_crud_user@simrs.local', $userUpdated['email']);

        // 4. Toggle Active Status (Disable)
        Database::update('users', ['is_active' => 0], ['id' => $userId]);
        $userDisabled = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        $this->assertEquals(0, intval($userDisabled['is_active']));

        // 5. Delete User
        Database::delete('users', ['id' => $userId]);
        $userDeleted = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        $this->assertFalse($userDeleted);
    }

    /**
     * Test Role-Permission matrix toggle
     */
    public function testRolePermissionToggle()
    {
        // Fetch a role
        $role = Database::fetchOne("SELECT * FROM roles LIMIT 1");
        $permission = Database::fetchOne("SELECT * FROM permissions LIMIT 1");

        if ($role && $permission) {
            $roleId = $role['id'];
            $permId = $permission['id'];

            // Clear permission for this role if it exists to have a clean start
            Database::delete('role_permissions', ['role_id' => $roleId, 'permission_id' => $permId]);

            // Assert role does not have permission
            $exists = Database::fetchOne(
                "SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permId]
            );
            $this->assertFalse($exists);

            // Toggle ON: Insert role permission
            Database::insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permId
            ]);

            $existsAfter = Database::fetchOne(
                "SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permId]
            );
            $this->assertNotEmpty($existsAfter);

            // Toggle OFF: Delete role permission
            Database::delete('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permId
            ]);

            $existsEnd = Database::fetchOne(
                "SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permId]
            );
            $this->assertFalse($existsEnd);
        }
    }

    /**
     * Test ICD-10 Search and Autocomplete lookup logic
     */
    public function testIcd10SearchAndAutocomplete()
    {
        // 1. Insert mock ICD-10 record
        $code = 'A01.09';
        $nameEn = 'Typhoid fever, unspecified';
        $nameId = 'Demam tifoid, tidak spesifik';

        // Delete if exists first
        Database::delete('icds', ['code' => $code]);

        Database::insert('icds', [
            'code' => $code,
            'name_en' => $nameEn,
            'name_id' => $nameId
        ]);

        // 2. Perform query simulating ICD-10 autocomplete search
        $q = 'tifoid';
        $query = "SELECT code, name_en, name_id 
                  FROM icds 
                  WHERE code LIKE ? OR name_id LIKE ? OR name_en LIKE ? 
                  LIMIT 15";
        $params = ["%$q%", "%$q%", "%$q%"];
        
        $results = Database::fetchAll($query, $params);
        $this->assertNotEmpty($results);

        $matched = false;
        foreach ($results as $row) {
            if ($row['code'] === $code) {
                $matched = true;
                $this->assertEquals($nameId, $row['name_id']);
                $this->assertEquals($nameEn, $row['name_en']);
            }
        }
        $this->assertTrue($matched);
    }

    /**
     * Test Electronic Medical Record locking and verification compliance (Permenkes 24/2022)
     */
    public function testElectronicMedicalRecordLocking()
    {
        // 1. Insert dummy patient
        $patientId = Database::insert('patients', [
            'medical_record_number' => 'RM-999-999',
            'full_name' => 'EMR Test Patient',
            'gender' => 'L',
            'birth_date' => '1990-01-01',
            'is_active' => 1
        ]);

        // 2. Insert dummy visit
        // Need polyclinic and doctor first
        $poly = Database::fetchOne("SELECT id FROM polyclinics LIMIT 1");
        $doctor = Database::fetchOne("SELECT id FROM doctors LIMIT 1");

        if ($poly && $doctor) {
            $visitId = Database::insert('patient_visits', [
                'patient_id' => $patientId,
                'polyclinic_id' => $poly['id'],
                'doctor_id' => $doctor['id'],
                'visit_date' => date('Y-m-d H:i:s'),
                'visit_number' => 'V-999999',
                'visit_status' => 'registered'
            ]);

            // 3. Insert Medical Record as completed (not verified/locked yet)
            $mrId = Database::insert('medical_records', [
                'patient_id' => $patientId,
                'visit_id' => $visitId,
                'doctor_id' => $doctor['id'],
                'subjective' => 'Keluhan pusing',
                'objective' => 'TD 120/80',
                'assessment' => 'Cephalgia',
                'plan' => 'Paracetamol 3x500mg',
                'record_status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Assert record is not verified
            $mrBefore = Database::fetchOne("SELECT * FROM medical_records WHERE id = ?", [$mrId]);
            $this->assertEquals('completed', $mrBefore['record_status']);
            $this->assertNull($mrBefore['verified_by']);
            $this->assertNull($mrBefore['verified_at']);

            // 4. Verify and Lock Medical Record
            $userId = 1; // dummy doctor user id
            Database::update('medical_records', [
                'record_status' => 'verified',
                'verified_by' => $userId,
                'verified_at' => date('Y-m-d H:i:s')
            ], ['id' => $mrId]);

            // Fetch after verification
            $mrAfter = Database::fetchOne("SELECT * FROM medical_records WHERE id = ?", [$mrId]);
            $this->assertEquals('verified', $mrAfter['record_status']);
            $this->assertEquals($userId, intval($mrAfter['verified_by']));
            $this->assertNotNull($mrAfter['verified_at']);
        }
    }

    /**
     * Test Billing Invoice and Receipt data query
     */
    public function testBillingInvoiceAndReceiptQuery()
    {
        // Fetch any invoice and verify its structure
        $invoice = Database::fetchOne("SELECT * FROM invoices LIMIT 1");
        if ($invoice) {
            $this->assertArrayHasKey('invoice_number', $invoice);
            $this->assertArrayHasKey('total_amount', $invoice);
            $this->assertArrayHasKey('payment_status', $invoice);
        }

        // Fetch any payment receipt and verify its structure
        $payment = Database::fetchOne("SELECT * FROM payments LIMIT 1");
        if ($payment) {
            $this->assertArrayHasKey('payment_number', $payment);
            $this->assertArrayHasKey('amount', $payment);
            $this->assertArrayHasKey('payment_method', $payment);
        }
    }

    public function testActiveCalledQueueDisplayQuery()
    {
        $today = date('Y-m-d');
        
        // Mock a called queue for testing the display query
        $poly = Database::fetchOne("SELECT id FROM polyclinics LIMIT 1");
        $patient = Database::fetchOne("SELECT id FROM patients LIMIT 1");
        
        if ($poly && $patient) {
            $queueId = Database::insert('queues', [
                'polyclinic_id' => $poly['id'],
                'patient_id' => $patient['id'],
                'queue_number' => 999,
                'queue_date' => $today,
                'status' => 'called',
                'called_time' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Run display feed query
            $query = "SELECT q.id, q.queue_number, q.status, q.called_time, poly.name AS polyclinic_name
                      FROM queues q
                      JOIN polyclinics poly ON q.polyclinic_id = poly.id
                      WHERE q.queue_date = ? AND q.status = 'called'
                      ORDER BY q.called_time DESC";
            
            $calls = Database::fetchAll($query, [$today]);
            $this->assertNotEmpty($calls);

            $found = false;
            foreach ($calls as $call) {
                if (intval($call['id']) === intval($queueId)) {
                    $found = true;
                    $this->assertEquals(999, intval($call['queue_number']));
                }
            }
            $this->assertTrue($found);
        }
    }
}
