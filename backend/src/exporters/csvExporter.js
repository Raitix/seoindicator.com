const fs = require('fs');
const path = require('path');

class CsvExporter {
  constructor() {
    this.delimiter = ',';
  }

  async export(data, filePath) {
    const header = Object.keys(data[0]).join(this.delimiter);
    const rows = data
      .map(row =>
        Object.values(row)
          .map(value => `"${value}"`)
          .join(this.delimiter)
      )
      .join('\n');

    const csvContents = `${header}\n${rows}`;

    await fs.promises.writeFile(filePath, csvContents);

    return path.resolve(filePath);
  }
}

module.exports = new CsvExporter();