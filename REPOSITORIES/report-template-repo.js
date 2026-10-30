// TASK-004-03: Report Template Repository

const db = require('../database');
const ReportTemplate = require('../models/report-template');

class ReportTemplateRepository {
  async create(template) {
    const query = `INSERT INTO report_templates (site_id, name, is_default, sections, branding, cover_page_text, executive_summary, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)`;
    const params = [
      template.siteId, template.name, template.isDefault ? 1 : 0,
      JSON.stringify(template.sections), JSON.stringify(template.branding),
      template.coverPageText, template.executiveSummary,
      template.createdAt.toISOString()
    ];
    const result = await db.run(query, params);
    template.id = result.lastID;
    return template;
  }

  async findById(id) {
    const query = `SELECT * FROM report_templates WHERE id = ?`;
    const row = await db.get(query, [id]);
    return row ? new ReportTemplate(row) : null;
  }

  async update(template) {
    const query = `UPDATE report_templates SET
                   site_id = ?, name = ?, is_default = ?,
                   sections = ?, branding = ?, cover_page_text = ?, executive_summary = ?,
                   created_at = ?, updated_at = ? WHERE id = ?`;
    const params = [
      template.siteId, template.name, template.isDefault ? 1 : 0,
      JSON.stringify(template.sections), JSON.stringify(template.branding),
      template.coverPageText, template.executiveSummary,
      template.createdAt, template.updatedAt,
      template.id
    ];
    await db.run(query, params);
    return template;
  }

  async delete(id) {
    const query = `DELETE FROM report_templates WHERE id = ?`;
    await db.run(query, [id]);
  }
}

module.exports = new ReportTemplateRepository();