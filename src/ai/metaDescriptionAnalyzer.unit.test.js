const analyzeMetaDescription = require('./metaDescriptionAnalyzer');

describe('Meta Description AI Analyzer (edge cases + i18n + relevancy)', () => {
  test('extracts description with any attribute order and decodes HTML entities', () => {
    const html = `
      <html lang="en">
        <head>
          <meta content='Hello &amp; World' name="description">
        </head>
      </html>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.success).toBe(true);
    expect(result.metaDescription).toBe('Hello & World');
    expect(result.analysis.source).toBe('meta[name=description]');
  });

  test('prioritizes the first meta description when multiple are present', () => {
    const html = `
      <head>
        <meta name="description" content="First description.">
        <meta name="description" content="Second description.">
      </head>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.metaDescription).toBe('First description.');
  });

  test('returns a clear error when meta description is missing', () => {
    const html = `<html><head><title>Missing</title></head><body></body></html>`;
    const result = analyzeMetaDescription(html);

    expect(result.success).toBe(false);
    expect(result.error).toBe('Meta description not found');
    expect(result.analysis.issues).toContain('missing');
  });

  test('evaluates diverse content lengths for Latin scripts', () => {
    const html = `
      <head>
        <meta name="description" content="${'a'.repeat(140)}">
        <title>Sample Title</title>
      </head>
      <body>Sample content about SEO meta description best practices.</body>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.analysis.length.value).toBe(140);
    expect(result.analysis.length.status).toBe('optimal');
  });

  test('applies CJK length guidance for internationalized content', () => {
    const japanese = 'これは検索結果に表示される説明文の例です。'.repeat(4); // ~64 chars
    const html = `
      <html lang="ja">
        <head>
          <meta name="description" content="${japanese}">
          <title>メタディスクリプションのガイド</title>
        </head>
        <body>メタディスクリプションはクリック率に影響します。</body>
      </html>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.analysis.language.isCJK).toBe(true);
    expect(result.analysis.length.status).toBe('optimal');
  });

  test('flags overly long descriptions', () => {
    const html = `
      <head>
        <meta name="description" content="${'b'.repeat(220)}">
        <title>Length Test</title>
      </head>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.analysis.length.status).toBe('too_long');
    expect(result.analysis.issues).toContain('too_long');
  });

  test('scores relevancy based on overlap with title/body context', () => {
    const html = `
      <head>
        <title>Best Running Shoes for Marathon Training</title>
        <meta name="description" content="Discover the best running shoes and marathon training tips to stay injury-free.">
      </head>
      <body>
        Our guide covers marathon training plans, running shoes, and injury prevention.
      </body>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.analysis.relevancy.score).toBeGreaterThan(0.3);
    expect(result.analysis.relevancy.status).toBe('good');
  });

  test('flags descriptions that duplicate the title', () => {
    const html = `
      <head>
        <title>Ultimate SEO Guide</title>
        <meta name="description" content="Ultimate SEO Guide">
      </head>
    `;

    const result = analyzeMetaDescription(html);

    expect(result.analysis.issues).toContain('duplicate_title');
  });
});
