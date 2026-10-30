const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');

class PdfExporter {
  async export(data, filePath) {
    return new Promise((resolve, reject) => {
      try {
        const doc = new PDFDocument();
        const outputStream = fs.createWriteStream(filePath);
        
        doc.pipe(outputStream);

        // Title Page
        doc.fontSize(20).text(`SEO Report: ${data.title}`, { align: 'center' });
        doc.moveDown(2);
        doc.fontSize(12).text(`Date: ${data.date}`, { align: 'center' });
        doc.moveDown(1);

        if (data.logoPath) {
          doc.image(data.logoPath, { fit: [250, 150], align: 'center' });
        }

        // Add sections or data
        data.sections.forEach(section => {
          doc.addPage().fontSize(16).text(section.title, { underline: true });
          doc.moveDown(1).fontSize(12).text(section.content);
        });

        doc.end();

        outputStream.on('finish', () => resolve(path.resolve(filePath)));
      } catch (error) {
        reject(error);
      }
    });
  }
}

module.exports = new PdfExporter();