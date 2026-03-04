-- StromTracker Database Schema
-- Diese Datei wird von setup.php ausgeführt.
-- CREATE DATABASE und USE werden dort dynamisch mit dem konfigurierten
-- DB_NAME aus includes/config.php erzeugt – nicht aus dieser Datei.
-- Beim manuellen Ausführen: Datenbankname anpassen und USE-Zeile einfügen.

-- CREATE DATABASE IF NOT EXISTS `stromtracker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `stromtracker`;

-- Settings table (key/value store)
CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(100) PRIMARY KEY,
    `value` TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Electricity prices (supports price changes over time)
CREATE TABLE IF NOT EXISTS electricity_prices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    valid_from DATE NOT NULL,
    price_kwh  DECIMAL(6,4) NOT NULL COMMENT 'Price per kWh in EUR',
    note       VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_valid_from (valid_from)
);

-- Main readings table
CREATE TABLE IF NOT EXISTS readings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    entry_date    DATE NOT NULL,
    meter_reading DECIMAL(10,2) NOT NULL COMMENT 'Absolute meter reading in kWh',
    produced_ytd  DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Total produced solar power year to date (kWh)',
    produced_day  DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT 'Solar power produced on this day (kWh)',
    notes         TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entry_date (entry_date)
);

-- Default settings
INSERT INTO settings (`key`, `value`) VALUES
    ('app_title', 'StromTracker'),
    ('password_hash', '$2y$12$placeholder_replace_on_install'),
    ('currency', 'EUR'),
    ('unit_power', 'kWh')
ON DUPLICATE KEY UPDATE `key` = `key`;

-- Sample electricity price
INSERT INTO electricity_prices (valid_from, price_kwh, note) VALUES
    ('2024-01-01', 0.3200, 'Initialer Strompreis')
ON DUPLICATE KEY UPDATE valid_from = valid_from;
