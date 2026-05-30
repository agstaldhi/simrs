<?php

use PHPUnit\Framework\TestCase;

class PatientFlowTest extends TestCase
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
     * Test the complete patient lifecycle journey:
     * Patient Registration -> Visit -> Medical Record -> Billing Invoice -> Payment.
     */
    public function testPatientLifecycleFlow()
    {
        // 1. Patient Registration
        $rmNumber = 'RM-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        $patientId = Database::insert('patients', [
            'medical_record_number' => $rmNumber,
            'full_name' => 'Integration Lifecycle Patient',
            'gender' => 'P',
            'birth_date' => '1995-05-15',
            'phone' => '081234567890',
            'address' => 'Jl. Integration Test No. 42',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertGreaterThan(0, $patientId);
        
        // Assert patient exists in DB
        $patient = Database::fetchOne("SELECT * FROM patients WHERE id = ?", [$patientId]);
        $this->assertEquals('Integration Lifecycle Patient', $patient['full_name']);
        $this->assertEquals($rmNumber, $patient['medical_record_number']);

        // 2. Patient Visit / Admission
        $poly = Database::fetchOne("SELECT id FROM polyclinics LIMIT 1");
        $doctor = Database::fetchOne("SELECT id FROM doctors LIMIT 1");
        
        $this->assertNotEmpty($poly, "Should have polyclinic seed data");
        $this->assertNotEmpty($doctor, "Should have doctor seed data");
        
        $visitNumber = 'V-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $visitId = Database::insert('patient_visits', [
            'patient_id' => $patientId,
            'polyclinic_id' => $poly['id'],
            'doctor_id' => $doctor['id'],
            'visit_date' => date('Y-m-d H:i:s'),
            'visit_number' => $visitNumber,
            'visit_status' => 'registered',
            'payment_method' => 'cash',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertGreaterThan(0, $visitId);
        
        // Assert visit exists
        $visit = Database::fetchOne("SELECT * FROM patient_visits WHERE id = ?", [$visitId]);
        $this->assertEquals('registered', $visit['visit_status']);
        $this->assertEquals($visitNumber, $visit['visit_number']);

        // 3. Electronic Medical Record Creation & Lock (Permenkes 24/2022 compliance)
        $mrId = Database::insert('medical_records', [
            'patient_id' => $patientId,
            'visit_id' => $visitId,
            'doctor_id' => $doctor['id'],
            'subjective' => 'Pasien mengeluh demam tinggi selama 3 hari.',
            'objective' => 'Suhu 38.9 C, TD 120/80, Nadi 88 bpm.',
            'assessment' => 'Febris Suspect Typhoid Fever',
            'plan' => 'Cek darah lengkap, paracetamol 500mg prn.',
            'record_status' => 'completed',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertGreaterThan(0, $mrId);
        
        // Verify MR subjective/objective
        $mr = Database::fetchOne("SELECT * FROM medical_records WHERE id = ?", [$mrId]);
        $this->assertEquals('completed', $mr['record_status']);
        $this->assertNull($mr['verified_by']);
        
        // Lock/Verify EMR
        Database::update('medical_records', [
            'record_status' => 'verified',
            'verified_by' => 1, // Assume user ID 1 (Doctor/Admin)
            'verified_at' => date('Y-m-d H:i:s')
        ], ['id' => $mrId]);
        
        // Assert locked status
        $mrLocked = Database::fetchOne("SELECT * FROM medical_records WHERE id = ?", [$mrId]);
        $this->assertEquals('verified', $mrLocked['record_status']);
        $this->assertNotNull($mrLocked['verified_by']);
        $this->assertNotNull($mrLocked['verified_at']);

        // 4. Billing Invoice & Items Generation
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $invoiceId = Database::insert('invoices', [
            'invoice_number' => $invoiceNumber,
            'patient_id' => $patientId,
            'visit_id' => $visitId,
            'invoice_date' => date('Y-m-d'),
            'subtotal' => 150000.00,
            'discount_percentage' => 0.00,
            'discount_amount' => 0.00,
            'tax_percentage' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => 150000.00,
            'paid_amount' => 0.00,
            'outstanding_amount' => 150000.00,
            'payment_status' => 'unpaid',
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertGreaterThan(0, $invoiceId);
        
        // Insert invoice items
        $item1Id = Database::insert('invoice_items', [
            'invoice_id' => $invoiceId,
            'item_type' => 'service',
            'item_code' => 'SRV-001',
            'item_name' => 'Jasa Konsultasi Dokter Spesialis',
            'quantity' => 1,
            'unit_price' => 100000.00,
            'subtotal' => 100000.00,
            'total' => 100000.00,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $this->assertGreaterThan(0, $item1Id);
        
        $item2Id = Database::insert('invoice_items', [
            'invoice_id' => $invoiceId,
            'item_type' => 'medicine',
            'item_code' => 'MED-002',
            'item_name' => 'Paracetamol 500mg Tab',
            'quantity' => 1,
            'unit_price' => 50000.00,
            'subtotal' => 50000.00,
            'total' => 50000.00,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $this->assertGreaterThan(0, $item2Id);
        
        // Assert invoice status is unpaid
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
        $this->assertEquals('unpaid', $invoice['payment_status']);
        $this->assertEquals(150000.00, floatval($invoice['total_amount']));
        $this->assertEquals(150000.00, floatval($invoice['outstanding_amount']));

        // 5. Cash Payment and Receipt Generation
        $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $paymentId = Database::insert('payments', [
            'payment_number' => $paymentNumber,
            'invoice_id' => $invoiceId,
            'patient_id' => $patientId,
            'payment_date' => date('Y-m-d H:i:s'),
            'payment_method' => 'cash',
            'amount' => 150000.00,
            'payment_status' => 'approved',
            'notes' => 'Pembayaran lunas kunjungan terintegrasi.',
            'received_by' => 1,
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertGreaterThan(0, $paymentId);
        
        // Update invoice values to Paid
        Database::update('invoices', [
            'paid_amount' => 150000.00,
            'outstanding_amount' => 0.00,
            'payment_status' => 'paid',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $invoiceId]);
        
        // Update visit status to completed
        Database::update('patient_visits', [
            'visit_status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $visitId]);

        // 6. Assertions for the Completed Cycle
        $finalInvoice = Database::fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
        $this->assertEquals('paid', $finalInvoice['payment_status']);
        $this->assertEquals(0.00, floatval($finalInvoice['outstanding_amount']));
        $this->assertEquals(150000.00, floatval($finalInvoice['paid_amount']));
        
        $finalVisit = Database::fetchOne("SELECT * FROM patient_visits WHERE id = ?", [$visitId]);
        $this->assertEquals('completed', $finalVisit['visit_status']);
        
        $paymentRecord = Database::fetchOne("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        $this->assertEquals('approved', $paymentRecord['payment_status']);
        $this->assertEquals(150000.00, floatval($paymentRecord['amount']));
    }
}
