-- Migration 004: Create Scheduled Reports

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