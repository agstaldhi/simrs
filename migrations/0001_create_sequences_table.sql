-- Migration: Create sequences table
-- Description: Creates the sequences table for thread-safe sequential ID generation

CREATE TABLE IF NOT EXISTS sequences (
    sequence_key VARCHAR(100) NOT NULL PRIMARY KEY,
    current_value INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
