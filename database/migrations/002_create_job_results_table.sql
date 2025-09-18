-- Migration: 002_create_job_results_table.sql
-- Description: Create job_results table for individual indicator results
-- Date: 2025-01-17

CREATE TABLE job_results (
    job_id VARCHAR(64) NOT NULL,
    indicator VARCHAR(64) NOT NULL,
    status ENUM('pending','running','done','error') NOT NULL DEFAULT 'pending',
    score TINYINT NULL,
    details MEDIUMTEXT NULL,
    suggestion MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(job_id, indicator),
    INDEX(job_id),
    INDEX(status),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
