/**
 * Heading Analyzer Utility
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 */

export interface Heading {
  tag: string;
  text: string;
  level: number;
  keywordFound: boolean;
}

export interface HeadingAnalysisResult {
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
export function extractHeadings(html: string): Heading[] {
  if (typeof window === 'undefined') {
    // Server-side fallback: simple regex-based extraction
    const headingRegex = /<h([1-6])[^>]*>(.*?)<\/h\1>/gi;
    const headings: Heading[] = [];
    let match;
    
    while ((match = headingRegex.exec(html)) !== null) {
      const level = parseInt(match[1]);
      const text = match[2].replace(/<[^>]*>/g, '').trim();
      headings.push({
        tag: `h${level}`,
        text,
        level,
        keywordFound: false,
      });
    }
    
    return headings;
  }
  
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
 * Check if keyword is found in heading text (whole word match)
 */
export function checkKeywordInHeading(headingText: string, keyword: string): boolean {
  if (!headingText || !keyword) return false;
  
  const normalizedHeading = headingText.toLowerCase().trim();
  const normalizedKeyword = keyword.toLowerCase().trim();
  
  // Escape special regex characters
  const escapedKeyword = normalizedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  
  // Use a pattern that matches the keyword as a standalone word
  // This works for both regular keywords (SEO) and special char keywords (C++)
  const regex = new RegExp(`(^|\\s|[^a-z0-9])${escapedKeyword}($|\\s|[^a-z0-9])`, 'i');
  return regex.test(normalizedHeading);
}

/**
 * Analyze headings for keyword presence
 */
export function analyzeHeadingsForKeyword(headings: Heading[], keyword: string): Heading[] {
  return headings.map((heading) => ({
    ...heading,
    keywordFound: checkKeywordInHeading(heading.text, keyword),
  }));
}

/**
 * Check heading hierarchy validity
 */
export function checkHierarchy(headings: Heading[]): { valid: boolean; skippedLevels: number[] } {
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
export function analyzeHeadings(html: string, keyword: string): HeadingAnalysisResult {
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

/**
 * Get headings with keyword found
 */
export function getHeadingsWithKeyword(headings: Heading[]): Heading[] {
  return headings.filter(h => h.keywordFound);
}

/**
 * Get headings without keyword
 */
export function getHeadingsWithoutKeyword(headings: Heading[]): Heading[] {
  return headings.filter(h => !h.keywordFound);
}

/**
 * Generate heading recommendations
 */
export function generateHeadingRecommendations(headings: Heading[], keyword: string): string[] {
  const recommendations: string[] = [];
  const headingsWithoutKeyword = getHeadingsWithoutKeyword(headings);
  
  if (headingsWithoutKeyword.length > 0) {
    const h1Missing = headingsWithoutKeyword.find(h => h.tag === 'h1');
    if (h1Missing) {
      recommendations.push(`Consider adding "${keyword}" to your main H1 heading.`);
    }
    
    const h2Missing = headingsWithoutKeyword.filter(h => h.tag === 'h2');
    if (h2Missing.length > 0) {
      recommendations.push(`Consider adding "${keyword}" to section headings: ${h2Missing.map(h => `"${h.text}"`).join(', ')}.`);
    }
  }
  
  return recommendations;
}
