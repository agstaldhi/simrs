-- Migration: Add SatuSehat Token Settings
-- Description: Adds placeholders for SatuSehat OAuth access token and expiry to system_settings

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
('satusehat_token', '', 'string', 'security', 'SatuSehat OAuth Access Token', 0),
('satusehat_token_expires', '0', 'number', 'security', 'SatuSehat OAuth Access Token Expiry Timestamp', 0);
