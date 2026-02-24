// TASK-004-02: Report Repository

const db = require('../database');
const Report = require('../models/report');

class ReportRepository {
  async create(report) {
    const query = `INSERT INTO reports (site_id, name, type, format, status, date_range_start, date_range_end, sections, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`;
    const params = [
      report.siteId, report.name, report.type, report.format, report.status,
      report.dateRangeStart, report.dateRangeEnd, JSON.stringify(report.sections),
      report.createdAt.toISOString()
    ];
    const result = await db.run(query, params);
    report.id = result.lastID;
    return report;
  }

  async findById(id) {
    const query = `SELECT * FROM reports WHERE id = ?`;
    const row = await db.get(query, [id]);
    return row ? new Report(row) : null;
  }

  async update(report) {
    const query = `UPDATE reports SET
                   site_id = ?, name = ?, type = ?, format = ?, status = ?,
                   date_range_start = ?, date_range_end = ?, sections = ?,
                   generated_at = ?, file_path = ?, file_size = ?, error = ?,
                   created_at = ? WHERE id = ?`;
    const params = [
      report.siteId, report.name, report.type, report.format, report.status,
      report.dateRangeStart, report.dateRangeEnd, JSON.stringify(report.sections),
      report.generatedAt, report.filePath, report.fileSize, report.error, report.createdAt,
      report.id
    ];
    await db.run(query, params);
    return report;
  }

  async delete(id) {
    const query = `DELETE FROM reports WHERE id = ?`;
    await db.run(query, [id]);
  }
}

module.exports = new ReportRepository();