-- TASK-004-01: Reports and Templates Schema Migration

-- Table for storing reports
CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL,               -- e.g., 'weekly', 'monthly', etc.
    format TEXT NOT NULL DEFAULT 'pdf',
    status TEXT NOT NULL DEFAULT 'pending',
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    compare_range_start DATE,
    compare_range_end DATE,
    sections TEXT,                    -- JSON array of section names
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

-- Table for storing report templates
CREATE TABLE IF NOT EXISTS report_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER,
    name TEXT NOT NULL,
    is_default INTEGER DEFAULT 0,
    sections TEXT NOT NULL,           -- JSON array
    branding TEXT,                    -- JSON: {logo_url, primary_color, font}
    cover_page_text TEXT,
    executive_summary TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_site ON report_templates(site_id);
CREATE INDEX idx_templates_default ON report_templates(is_default);