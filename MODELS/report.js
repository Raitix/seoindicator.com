// TASK-004-02: Report Model

class Report {
  constructor({
    id, siteId, name, type, format = 'pdf', status = 'pending',
    dateRangeStart, dateRangeEnd, compareRangeStart = null, compareRangeEnd = null,
    sections, generatedAt = null, filePath = null, fileSize = null, error = null,
    createdAt = new Date() }) {
    this.id = id;
    this.siteId = siteId;
    this.name = name;
    this.type = type;
    this.format = format;
    this.status = status;
    this.dateRangeStart = dateRangeStart;
    this.dateRangeEnd = dateRangeEnd;
    this.compareRangeStart = compareRangeStart;
    this.compareRangeEnd = compareRangeEnd;
    this.sections = sections;
    this.generatedAt = generatedAt;
    this.filePath = filePath;
    this.fileSize = fileSize;
    this.error = error;
    this.createdAt = createdAt;
  }
}

module.exports = Report;