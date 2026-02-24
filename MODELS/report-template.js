// TASK-004-03: ReportTemplate Model

class ReportTemplate {
  constructor({
    id, siteId, name, isDefault = false, sections, branding = {},
    coverPageText = null, executiveSummary = null, createdAt = new Date(), updatedAt = new Date() }) {
    this.id = id;
    this.siteId = siteId;
    this.name = name;
    this.isDefault = isDefault;
    this.sections = sections;
    this.branding = branding;
    this.coverPageText = coverPageText;
    this.executiveSummary = executiveSummary;
    this.createdAt = createdAt;
    this.updatedAt = updatedAt;
  }
}

module.exports = ReportTemplate;