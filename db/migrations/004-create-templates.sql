-- Migration 004: Create Report Templates

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