const ReportTemplate = require('../models/reportTemplate');

class ReportTemplateRepo {
  async createTemplate(data) {
    return await ReportTemplate.create(data);
  }

  async getTemplate(id) {
    return await ReportTemplate.findByPk(id);
  }

  async getTemplatesBySite(siteId) {
    return await ReportTemplate.findAll({ where: { site_id: siteId } });
  }

  async updateTemplate(id, updates) {
    const template = await this.getTemplate(id);
    if (!template) {
      throw new Error('Template not found');
    }
    return await template.update(updates);
  }

  async deleteTemplate(id) {
    return await ReportTemplate.destroy({ where: { id } });
  }
}

module.exports = new ReportTemplateRepo();