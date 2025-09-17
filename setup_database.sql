-- Database setup script for SEOIndicator.com
-- Run this script to create the required database tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS seoindicator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE seoindicator;

-- Table for rate limiting (IP requests log)
CREATE TABLE IF NOT EXISTS ip_requests_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    last_request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_last_request_time (last_request_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing analysis jobs
CREATE TABLE IF NOT EXISTS jobs (
    job_id VARCHAR(64) PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    normalized_domain VARCHAR(255) NULL,
    final_url VARCHAR(2048) NULL,
    page_content LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled TINYINT(1) DEFAULT 0,
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing individual indicator results
CREATE TABLE IF NOT EXISTS job_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(64) NOT NULL,
    indicator VARCHAR(64) NOT NULL,
    status ENUM('pending','running','done','error') NOT NULL DEFAULT 'pending',
    score TINYINT NULL,
    details MEDIUMTEXT NULL,
    suggestion MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_indicator (indicator),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for general data logging (legacy support)
CREATE TABLE IF NOT EXISTS data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    score_has INT DEFAULT 0,
    score_max INT DEFAULT 0,
    dt_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dt_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip (ip),
    INDEX idx_domain (domain),
    INDEX idx_dt_created (dt_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;