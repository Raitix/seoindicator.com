const Report = require('../models/report');

class ReportRepo {
  async createReport(data) {
    return await Report.create(data);
  }

  async getReport(id) {
    return await Report.findByPk(id);
  }

  async getReportsBySite(siteId) {
    return await Report.findAll({ where: { site_id: siteId } });
  }

  async updateReport(id, updates) {
    const report = await this.getReport(id);
    if (!report) {
      throw new Error('Report not found');
    }
    return await report.update(updates);
  }

  async deleteReport(id) {
    return await Report.destroy({ where: { id } });
  }
}

module.exports = new ReportRepo();