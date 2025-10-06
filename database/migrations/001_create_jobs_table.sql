-- Migration: 001_create_jobs_table.sql
-- Description: Create jobs table for background job management
-- Date: 2025-01-17

CREATE TABLE jobs (
    job_id VARCHAR(64) PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    normalized_domain VARCHAR(255) NULL,
    final_url VARCHAR(2048) NULL,
    page_content MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled TINYINT(1) DEFAULT 0,
    INDEX idx_session_id (session_id),
    INDEX idx_domain (domain),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
