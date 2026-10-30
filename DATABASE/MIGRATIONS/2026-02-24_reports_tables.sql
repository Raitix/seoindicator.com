-- SQL Migration: Reports, Templates, Scheduled Reports, and Branding Tables

-- Reports Table
CREATE TABLE reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,              
    format TEXT NOT NULL DEFAULT 'pdf',
    status TEXT NOT NULL DEFAULT 'pending',
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    compare_range_start DATE,
    compare_range_end DATE,
    sections TEXT,
    generated_at DATETIME,
    file_path TEXT,
    file_size INTEGER,
    error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_reports_site ON reports(site_id);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_reports_generated ON reports(generated_at);

-- Report Templates Table
CREATE TABLE report_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER,
    name TEXT NOT NULL,
    is_default INTEGER DEFAULT 0,
    sections TEXT NOT NULL,
    branding TEXT,
    cover_page_text TEXT,
    executive_summary TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_site ON report_templates(site_id);
CREATE INDEX idx_templates_default ON report_templates(is_default);

-- Scheduled Reports Table
CREATE TABLE scheduled_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    template_id INTEGER,
    name TEXT NOT NULL,
    frequency TEXT NOT NULL,         
    format TEXT NOT NULL DEFAULT 'pdf',
    recipients TEXT NOT NULL,
    enabled INTEGER DEFAULT 1,
    last_run DATETIME,
    next_run DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES report_templates(id)
);

CREATE INDEX idx_scheduled_site ON scheduled_reports(site_id);
CREATE INDEX idx_scheduled_next_run ON scheduled_reports(next_run);
CREATE INDEX idx_scheduled_enabled ON scheduled_reports(enabled);

-- Report Branding Table
CREATE TABLE report_branding (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL UNIQUE,
    logo_url TEXT,
    logo_file TEXT,                  
    primary_color TEXT DEFAULT '#2563eb',
    secondary_color TEXT DEFAULT '#64748b',
    font_family TEXT DEFAULT 'Inter',
    company_name TEXT,
    company_website TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_branding_site ON report_branding(site_id);