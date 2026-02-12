const analyzeMetaDescription = require("./metaDescriptionAnalyzer");

describe("Meta Description Analyzer", () => {
  test("should correctly extract the meta description from valid HTML", () => {
    const html = `
      <html>
        <head>
          <meta name="description" content="This is a sample meta description.">
        </head>
        <body></body>
      </html>
    `;

    const result = analyzeMetaDescription(html);
    expect(result.success).toBe(true);
    expect(result.metaDescription).toBe("This is a sample meta description.");
  });

  test("should return an error for pages without a meta description", () => {
    const html = `
      <html>
        <head></head>
        <body></body>
      </html>
    `;

    const result = analyzeMetaDescription(html);
    expect(result.success).toBe(false);
    expect(result.error).toBe("Meta description not found");
  });
});