/**
 * Keyword Density Analyzer Utility Tests
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * @jest-environment jsdom
 */

import {
  stripHtmlTags,
  normalizeText,
  calculateDensity,
  countKeywordOccurrences,
  findKeywordPositions,
  countWords,
  analyzeKeywordDensity,
  getDensityStatus,
  type KeywordAnalysisResult,
} from '@/lib/utils/keywordDensityAnalyzer';

describe('Keyword Density Analyzer', () => {
  // UT-041: Calculate single keyword density
  it('should calculate correct density for single keyword', () => {
    const result = calculateDensity(5, 100);
    expect(result).toBe(5);
  });

  // UT-042: Calculate zero occurrences
  it('should return 0% density for zero occurrences', () => {
    const result = calculateDensity(0, 100);
    expect(result).toBe(0);
  });

  // UT-043: Case-insensitive keyword match
  it('should match keywords case-insensitively', () => {
    const text = 'SEO is important. seo is essential. Seo is everywhere.';
    const count = countKeywordOccurrences(text, 'SEO');
    expect(count).toBe(3);
  });

  // UT-044: Partial word match excluded
  it('should not count partial word matches', () => {
    const text = 'testing tested tester test';
    const count = countKeywordOccurrences(text, 'test');
    // Only "test" should match, not "testing", "tested", "tester"
    expect(count).toBe(1);
  });

  // UT-045: Multiple keyword forms
  it('should count multiple occurrences of same keyword', () => {
    const text = 'SEO is great. Learn SEO today. Best SEO practices.';
    const count = countKeywordOccurrences(text, 'SEO');
    expect(count).toBe(3);
  });

  // UT-046: Strip HTML tags before counting
  it('should strip HTML tags before counting', () => {
    const html = '<p>SEO</p> <div>content</div> <span>SEO</span>';
    const stripped = stripHtmlTags(html);
    expect(stripped).not.toContain('<p>');
    expect(stripped).not.toContain('</p>');
    expect(stripped).toContain('SEO');
  });

  // UT-047: Handle special characters in keyword
  it('should handle keywords with special characters', () => {
    const text = 'Learn C++ programming. C++ is powerful. Best C++ tutorials.';
    const count = countKeywordOccurrences(text, 'C++');
    expect(count).toBe(3);
  });

  // UT-048: Handle multi-word keywords
  it('should count multi-word keyword occurrences', () => {
    const text = 'Search engine optimization is key. Search engine marketing is related. Search engine optimization helps.';
    const count = countKeywordOccurrences(text, 'search engine optimization');
    expect(count).toBe(2);
  });

  // UT-049: Extract keyword positions
  it('should extract keyword positions in text', () => {
    const text = 'SEO is important. SEO helps ranking. Learn SEO today.';
    const positions = findKeywordPositions(text, 'SEO');
    expect(positions).toHaveLength(3);
    expect(positions[0]).toBeGreaterThanOrEqual(0);
  });

  // UT-050: Calculate density for multiple keywords
  it('should analyze multiple keywords', () => {
    const html = '<p>SEO is important. Marketing helps SEO. SEO best practices.</p>';
    
    const seoResult = analyzeKeywordDensity(html, 'SEO');
    expect(seoResult.occurrences).toBe(3);
    expect(seoResult.totalWords).toBe(9);
    
    const marketingResult = analyzeKeywordDensity(html, 'Marketing');
    expect(marketingResult.occurrences).toBe(1);
  });

  // Additional: Empty text handling
  it('should handle empty text gracefully', () => {
    const count = countKeywordOccurrences('', 'SEO');
    expect(count).toBe(0);
    
    const words = countWords('');
    expect(words).toBe(0);
  });

  // Additional: Whitespace handling
  it('should handle extra whitespace', () => {
    const text = '  SEO   is   important  ';
    const words = countWords(text);
    expect(words).toBe(3);
  });

  // Additional: Density status - good
  it('should return good status for optimal density', () => {
    expect(getDensityStatus(1.5)).toBe('good');
    expect(getDensityStatus(2.0)).toBe('good');
    expect(getDensityStatus(2.9)).toBe('good');
  });

  // Additional: Density status - low
  it('should return low status for density below 1%', () => {
    expect(getDensityStatus(0)).toBe('low');
    expect(getDensityStatus(0.5)).toBe('low');
    expect(getDensityStatus(0.99)).toBe('low');
  });

  // Additional: Density status - over-optimized
  it('should return over-optimized status for density above 3%', () => {
    expect(getDensityStatus(3.1)).toBe('over-optimized');
    expect(getDensityStatus(5.0)).toBe('over-optimized');
    expect(getDensityStatus(10.0)).toBe('over-optimized');
  });

  // Additional: Normalize text
  it('should normalize text correctly', () => {
    expect(normalizeText('  SEO  ')).toBe('seo');
    expect(normalizeText('SEO')).toBe('seo');
    expect(normalizeText('Seo')).toBe('seo');
  });

  // Additional: Word count with HTML
  it('should count words after stripping HTML', () => {
    const html = '<p>One two three</p><div>four five</div>';
    const stripped = stripHtmlTags(html);
    const words = countWords(stripped);
    expect(words).toBe(5);
  });

  // Additional: Position accuracy
  it('should return accurate positions', () => {
    const text = 'test one test two test';
    const positions = findKeywordPositions(text, 'test');
    
    // Check that positions point to the actual keyword
    expect(text.substring(positions[0], positions[0] + 4).toLowerCase()).toBe('test');
    expect(text.substring(positions[1], positions[1] + 4).toLowerCase()).toBe('test');
    expect(text.substring(positions[2], positions[2] + 4).toLowerCase()).toBe('test');
  });

  // Additional: Full analysis result
  it('should return complete analysis result', () => {
    const html = '<html><body><p>SEO is important for SEO ranking. Learn SEO today.</p></body></html>';
    const result = analyzeKeywordDensity(html, 'SEO');
    
    expect(result).toHaveProperty('keyword', 'SEO');
    expect(result).toHaveProperty('occurrences');
    expect(result).toHaveProperty('density');
    expect(result).toHaveProperty('positions');
    expect(result).toHaveProperty('totalWords');
    expect(result.occurrences).toBe(3);
    expect(result.totalWords).toBe(10);
    expect(Math.abs(result.density - 30)).toBeLessThan(0.1); // ~30%
  });

  // Edge case: Keyword at start of text
  it('should find keyword at start of text', () => {
    const text = 'SEO is important';
    const count = countKeywordOccurrences(text, 'SEO');
    expect(count).toBe(1);
  });

  // Edge case: Keyword at end of text
  it('should find keyword at end of text', () => {
    const text = 'Learn about SEO';
    const count = countKeywordOccurrences(text, 'SEO');
    expect(count).toBe(1);
  });

  // Edge case: Keyword with punctuation
  it('should find keyword followed by punctuation', () => {
    const text = 'SEO! SEO, SEO. SEO?';
    const count = countKeywordOccurrences(text, 'SEO');
    expect(count).toBe(4);
  });

  // Edge case: Nested HTML
  it('should handle nested HTML elements', () => {
    const html = '<div><p><span>SEO</span> content</p></div>';
    const stripped = stripHtmlTags(html);
    expect(stripped).toContain('SEO');
    expect(stripped).not.toContain('<');
  });
});
