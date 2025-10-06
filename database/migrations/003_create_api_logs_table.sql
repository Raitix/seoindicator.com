-- Migration: 003_create_api_logs_table.sql
-- Description: Create api_logs table for API request/response logging
-- Date: 2025-10-06

CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(64),
    provider VARCHAR(50) NOT NULL,
    model VARCHAR(100),
    request_data MEDIUMTEXT,
    response_data MEDIUMTEXT,
    http_code INT,
    success BOOLEAN,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_id (job_id),
    INDEX idx_provider (provider),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

