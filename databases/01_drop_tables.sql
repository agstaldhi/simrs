-- =====================================================
-- SIMRS Database Schema
-- Part 1: DROP TABLES (jika sudah ada)
-- =====================================================
-- CATATAN: Jalankan ini HANYA jika ingin reset database
-- Ini akan menghapus SEMUA data!
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Audit & Logs
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS login_attempts;

-- HR & Kepegawaian
DROP TABLE IF EXISTS attendances;
DROP TABLE IF EXISTS shifts;
DROP TABLE IF EXISTS employees;

-- Billing & Payment
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;

-- Inventory & Purchasing
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS inventory_items;

-- Pharmacy
DROP TABLE IF EXISTS medicine_stock;
DROP TABLE IF EXISTS prescription_items;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS medicines;

-- Laboratory
DROP TABLE IF EXISTS lab_results;
DROP TABLE IF EXISTS lab_orders;
DROP TABLE IF EXISTS lab_templates;

-- Scheduling & Appointments
DROP TABLE IF EXISTS queues;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctor_schedules;

-- Medical Records
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS allergies;
DROP TABLE IF EXISTS vital_signs;
DROP TABLE IF EXISTS diagnoses;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS patient_visits;

-- Patients
DROP TABLE IF EXISTS patients;

-- Master Data
DROP TABLE IF EXISTS polyclinics;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS hospital_info;

-- Authentication & Authorization
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- END OF DROP TABLES
-- =====================================================