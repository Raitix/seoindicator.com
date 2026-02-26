/**
 * Heading Analyzer Utility Tests
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * @jest-environment jsdom
 */

/**
 * Utility functions for heading analysis
 */

interface Heading {
  tag: string;
  text: string;
  level: number;
  keywordFound: boolean;
}

interface HeadingAnalysisResult {
  headings: Heading[];
  hasH1: boolean;
  multipleH1: boolean;
  emptyHeadings: boolean;
  hierarchyValid: boolean;
  warnings: string[];
}

/**
 * Extract all headings from HTML
 */
function extractHeadings(html: string): Heading[] {
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const headingElements = doc.querySelectorAll('h1, h2, h3, h4, h5, h6');
  
  const headings: Heading[] = [];
  
  headingElements.forEach((el) => {
    const tag = el.tagName.toLowerCase();
    const level = parseInt(tag.charAt(1));
    const text = el.textContent?.trim() || '';
    
    headings.push({
      tag,
      text,
      level,
      keywordFound: false,
    });
  });
  
  return headings;
}

/**
 * Check if keyword is found in heading text
 */
function checkKeywordInHeading(headingText: string, keyword: string): boolean {
  const normalizedHeading = headingText.toLowerCase().trim();
  const normalizedKeyword = keyword.toLowerCase().trim();
  
  // Escape special regex characters
  const escapedKeyword = normalizedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  
  // Create regex for whole word match
  const regex = new RegExp(`\\b${escapedKeyword}\\b`, 'i');
  return regex.test(normalizedHeading);
}

/**
 * Analyze headings for keyword presence
 */
function analyzeHeadingsForKeyword(headings: Heading[], keyword: string): Heading[] {
  return headings.map((heading) => ({
    ...heading,
    keywordFound: checkKeywordInHeading(heading.text, keyword),
  }));
}

/**
 * Check heading hierarchy validity
 */
function checkHierarchy(headings: Heading[]): { valid: boolean; skippedLevels: number[] } {
  if (headings.length === 0) {
    return { valid: true, skippedLevels: [] };
  }
  
  const skippedLevels: number[] = [];
  let lastLevel = 0;
  
  for (const heading of headings) {
    if (heading.level > lastLevel + 1 && lastLevel !== 0) {
      // Skipped a level
      skippedLevels.push(heading.level);
    }
    lastLevel = heading.level;
  }
  
  return {
    valid: skippedLevels.length === 0,
    skippedLevels,
  };
}

/**
 * Full heading analysis
 */
function analyzeHeadings(html: string, keyword: string): HeadingAnalysisResult {
  const headings = extractHeadings(html);
  const headingsWithKeyword = analyzeHeadingsForKeyword(headings, keyword);
  
  const h1Headings = headings.filter(h => h.tag === 'h1');
  const emptyHeadingsList = headings.filter(h => h.text === '');
  const hierarchy = checkHierarchy(headings);
  
  const warnings: string[] = [];
  
  if (h1Headings.length === 0) {
    warnings.push('Missing H1 tag. Every page should have exactly one H1.');
  }
  
  if (h1Headings.length > 1) {
    warnings.push(`Multiple H1 tags detected (${h1Headings.length}). Use only one H1 per page.`);
  }
  
  if (emptyHeadingsList.length > 0) {
    warnings.push(`Empty heading(s) found (${emptyHeadingsList.length}). All headings should have content.`);
  }
  
  if (!hierarchy.valid) {
    warnings.push(`Heading hierarchy skipped levels: ${hierarchy.skippedLevels.join(', ')}. Maintain proper heading order.`);
  }
  
  return {
    headings: headingsWithKeyword,
    hasH1: h1Headings.length === 1,
    multipleH1: h1Headings.length > 1,
    emptyHeadings: emptyHeadingsList.length > 0,
    hierarchyValid: hierarchy.valid,
    warnings,
  };
}

describe('Heading Analyzer', () => {
  // UT-051: Extract H1 from HTML
  it('should extract H1 from HTML', () => {
    const html = '<html><body><h1>Main Title</h1></body></html>';
    const headings = extractHeadings(html);
    
    expect(headings).toHaveLength(1);
    expect(headings[0].tag).toBe('h1');
    expect(headings[0].text).toBe('Main Title');
    expect(headings[0].level).toBe(1);
  });

  // UT-052: Extract all headings
  it('should extract all headings from HTML', () => {
    const html = `
      <html>
        <body>
          <h1>Title</h1>
          <h2>Subtitle</h2>
          <h3>Section</h3>
          <h4>Subsection</h4>
          <h5>Detail</h5>
          <h6>Fine Print</h6>
        </body>
      </html>
    `;
    const headings = extractHeadings(html);
    
    expect(headings).toHaveLength(6);
    expect(headings.map(h => h.tag)).toEqual(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
  });

  // UT-053: Check keyword in heading
  it('should detect keyword in heading', () => {
    const result = checkKeywordInHeading('SEO Best Practices Guide', 'SEO');
    expect(result).toBe(true);
  });

  // UT-054: Case-insensitive heading match
  it('should match keyword case-insensitively in heading', () => {
    expect(checkKeywordInHeading('SEO Guide', 'seo')).toBe(true);
    expect(checkKeywordInHeading('seo guide', 'SEO')).toBe(true);
    expect(checkKeywordInHeading('SeO GuIdE', 'SEO')).toBe(true);
  });

  // UT-055: No keyword in heading
  it('should return false when keyword not in heading', () => {
    const result = checkKeywordInHeading('Welcome to Our Site', 'SEO');
    expect(result).toBe(false);
  });

  // UT-056: Multiple heading levels
  it('should extract headings in document order', () => {
    const html = `
      <h1>Main</h1>
      <h2>First</h2>
      <h2>Second</h2>
      <h3>Detail</h3>
    `;
    const headings = extractHeadings(html);
    
    expect(headings[0].tag).toBe('h1');
    expect(headings[1].tag).toBe('h2');
    expect(headings[2].tag).toBe('h2');
    expect(headings[3].tag).toBe('h3');
  });

  // UT-057: Missing H1
  it('should detect missing H1', () => {
    const html = '<html><body><h2>No H1 here</h2></body></html>';
    const result = analyzeHeadings(html, 'test');
    
    expect(result.hasH1).toBe(false);
    expect(result.warnings).toContainEqual(expect.stringContaining('Missing H1'));
  });

  // UT-058: Multiple H1 tags
  it('should detect multiple H1 tags', () => {
    const html = `
      <html>
        <body>
          <h1>First H1</h1>
          <h1>Second H1</h1>
        </body>
      </html>
    `;
    const result = analyzeHeadings(html, 'test');
    
    expect(result.multipleH1).toBe(true);
    expect(result.warnings).toContainEqual(expect.stringContaining('Multiple H1'));
  });

  // UT-059: Empty heading
  it('should detect empty headings', () => {
    const html = '<html><body><h1></h1><h2>Content</h2></body></html>';
    const result = analyzeHeadings(html, 'test');
    
    expect(result.emptyHeadings).toBe(true);
    expect(result.warnings).toContainEqual(expect.stringContaining('Empty heading'));
  });

  // UT-060: Heading hierarchy check
  it('should detect skipped heading levels', () => {
    const html = `
      <html>
        <body>
          <h1>Main</h1>
          <h3>Skipped H2</h3>
        </body>
      </html>
    `;
    const result = analyzeHeadings(html, 'test');
    
    expect(result.hierarchyValid).toBe(false);
    expect(result.warnings).toContainEqual(expect.stringContaining('hierarchy'));
  });

  // Additional: Valid hierarchy
  it('should pass for valid heading hierarchy', () => {
    const html = `
      <html>
        <body>
          <h1>Main</h1>
          <h2>Section</h2>
          <h3>Subsection</h3>
          <h2>Another Section</h2>
        </body>
      </html>
    `;
    const result = analyzeHeadings(html, 'test');
    
    expect(result.hierarchyValid).toBe(true);
    expect(result.hasH1).toBe(true);
    expect(result.multipleH1).toBe(false);
    expect(result.emptyHeadings).toBe(false);
  });

  // Additional: Keyword found in multiple headings
  it('should mark keyword found in multiple headings', () => {
    const html = `
      <html>
        <body>
          <h1>SEO Guide</h1>
          <h2>SEO Basics</h2>
          <h2>Advanced SEO</h2>
        </body>
      </html>
    `;
    const result = analyzeHeadings(html, 'SEO');
    
    expect(result.headings[0].keywordFound).toBe(true);
    expect(result.headings[1].keywordFound).toBe(true);
    expect(result.headings[2].keywordFound).toBe(true);
  });

  // Additional: Keyword found in some headings
  it('should correctly identify which headings contain keyword', () => {
    const html = `
      <html>
        <body>
          <h1>SEO Guide</h1>
          <h2>Introduction</h2>
          <h2>SEO Strategies</h2>
        </body>
      </html>
    `;
    const result = analyzeHeadings(html, 'SEO');
    
    expect(result.headings[0].keywordFound).toBe(true);
    expect(result.headings[1].keywordFound).toBe(false);
    expect(result.headings[2].keywordFound).toBe(true);
  });

  // Additional: Empty HTML
  it('should handle HTML with no headings', () => {
    const html = '<html><body><p>Just a paragraph</p></body></html>';
    const result = analyzeHeadings(html, 'test');
    
    expect(result.headings).toHaveLength(0);
    expect(result.warnings).toContainEqual(expect.stringContaining('Missing H1'));
  });

  // Additional: Nested headings
  it('should extract headings from nested elements', () => {
    const html = `
      <html>
        <body>
          <div>
            <article>
              <h1>Article Title</h1>
              <section>
                <h2>Section Title</h2>
              </section>
            </article>
          </div>
        </body>
      </html>
    `;
    const headings = extractHeadings(html);
    
    expect(headings).toHaveLength(2);
    expect(headings[0].text).toBe('Article Title');
    expect(headings[1].text).toBe('Section Title');
  });

  // Additional: Heading with HTML inside
  it('should extract text from headings with nested HTML', () => {
    const html = '<h1><span>SEO</span> Best <strong>Practices</strong></h1>';
    const headings = extractHeadings(html);
    
    expect(headings).toHaveLength(1);
    expect(headings[0].text).toBe('SEO Best Practices');
  });

  // Additional: Partial keyword match should not count
  it('should not match partial keywords in headings', () => {
    const result = checkKeywordInHeading('Seasonal changes', 'season');
    expect(result).toBe(false);
  });

  // Additional: Multi-word keyword in heading
  it('should match multi-word keywords in headings', () => {
    const result = checkKeywordInHeading('Search Engine Optimization Guide', 'search engine optimization');
    expect(result).toBe(true);
  });

  // Additional: Heading with special characters
  it('should handle headings with special characters', () => {
    const html = '<h1>SEO & Marketing: A Complete Guide!</h1>';
    const headings = extractHeadings(html);
    
    expect(headings[0].text).toBe('SEO & Marketing: A Complete Guide!');
  });

  // Additional: Keyword with special characters
  it('should match keywords with special characters', () => {
    const result = checkKeywordInHeading('Learn C++ Today', 'C++');
    expect(result).toBe(true);
  });

  // Additional: Whitespace in heading
  it('should trim whitespace from heading text', () => {
    const html = '<h1>   Spaced Heading   </h1>';
    const headings = extractHeadings(html);
    
    expect(headings[0].text).toBe('Spaced Heading');
  });
});
