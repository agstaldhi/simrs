-- Migration: Create icds table
-- Description: Creates the master catalog table for ICD-10 codes

CREATE TABLE IF NOT EXISTS `icds` (
  `code` VARCHAR(20) NOT NULL PRIMARY KEY,
  `name_en` TEXT NOT NULL,
  `name_id` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
