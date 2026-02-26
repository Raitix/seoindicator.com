/**
 * Keyword Density Analyzer Utility
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 */

export interface KeywordAnalysisResult {
  keyword: string;
  occurrences: number;
  density: number;
  positions: number[];
  totalWords: number;
}

export type DensityStatus = 'good' | 'low' | 'over-optimized';

/**
 * Strip HTML tags from content
 */
export function stripHtmlTags(html: string): string {
  if (typeof window === 'undefined') {
    // Server-side fallback: simple regex-based stripping
    return html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  }
  const doc = new DOMParser().parseFromString(html, 'text/html');
  return doc.body?.textContent || '';
}

/**
 * Normalize text for comparison
 */
export function normalizeText(text: string): string {
  return text.toLowerCase().trim();
}

/**
 * Calculate keyword density as percentage
 */
export function calculateDensity(occurrences: number, totalWords: number): number {
  if (totalWords === 0) return 0;
  return (occurrences / totalWords) * 100;
}

/**
 * Count whole-word occurrences of keyword in text
 */
export function countKeywordOccurrences(text: string, keyword: string): number {
  if (!text || !keyword) return 0;
  
  const normalizedText = normalizeText(text);
  const normalizedKeyword = normalizeText(keyword);
  
  // Escape special regex characters
  const escapedKeyword = normalizedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  
  // Use a pattern that matches the keyword as a standalone word
  // This works for both regular keywords (SEO) and special char keywords (C++)
  // Pattern: keyword preceded by start of string or whitespace/punctuation, 
  // and followed by end of string or whitespace/punctuation
  const regex = new RegExp(`(^|\\s|[^a-z0-9])${escapedKeyword}($|\\s|[^a-z0-9])`, 'gi');
  
  const matches = normalizedText.match(regex);
  return matches ? matches.length : 0;
}

/**
 * Find positions of keyword in text
 */
export function findKeywordPositions(text: string, keyword: string): number[] {
  if (!text || !keyword) return [];
  
  const normalizedText = normalizeText(text);
  const normalizedKeyword = normalizeText(keyword);
  
  const positions: number[] = [];
  const escapedKeyword = normalizedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  
  // Use the same pattern as countKeywordOccurrences for consistency
  const regex = new RegExp(`(^|\\s|[^a-z0-9])${escapedKeyword}($|\\s|[^a-z0-9])`, 'gi');
  
  let match;
  while ((match = regex.exec(normalizedText)) !== null) {
    // Adjust position to point to the actual keyword start (accounting for leading boundary)
    positions.push(match.index + match[1].length);
  }
  
  return positions;
}

/**
 * Count total words in text
 */
export function countWords(text: string): number {
  if (!text) return 0;
  const strippedText = text.replace(/<[^>]*>/g, ' ').trim();
  if (!strippedText) return 0;
  
  const words = strippedText.split(/\s+/).filter(word => word.length > 0);
  return words.length;
}

/**
 * Analyze keyword density in HTML content
 */
export function analyzeKeywordDensity(html: string, keyword: string): KeywordAnalysisResult {
  const strippedContent = stripHtmlTags(html);
  const totalWords = countWords(strippedContent);
  const occurrences = countKeywordOccurrences(strippedContent, keyword);
  const density = calculateDensity(occurrences, totalWords);
  const positions = findKeywordPositions(strippedContent, keyword);
  
  return {
    keyword,
    occurrences,
    density,
    positions,
    totalWords,
  };
}

/**
 * Get density status based on percentage
 * - < 1% = low (yellow)
 * - 1-3% = good (green)
 * - > 3% = over-optimized (red)
 */
export function getDensityStatus(density: number): DensityStatus {
  if (density < 1) return 'low';
  if (density > 3) return 'over-optimized';
  return 'good';
}

/**
 * Get status color class
 */
export function getDensityStatusColor(status: DensityStatus): string {
  switch (status) {
    case 'good':
      return 'text-green-500';
    case 'low':
      return 'text-yellow-500';
    case 'over-optimized':
      return 'text-red-500';
    default:
      return 'text-gray-500';
  }
}

/**
 * Get status background color class
 */
export function getDensityStatusBgColor(status: DensityStatus): string {
  switch (status) {
    case 'good':
      return 'bg-green-500/10 border-green-500/30';
    case 'low':
      return 'bg-yellow-500/10 border-yellow-500/30';
    case 'over-optimized':
      return 'bg-red-500/10 border-red-500/30';
    default:
      return 'bg-gray-500/10 border-gray-500/30';
  }
}

/**
 * Get status label
 */
export function getDensityStatusLabel(status: DensityStatus): string {
  switch (status) {
    case 'good':
      return 'Good';
    case 'low':
      return 'Too low';
    case 'over-optimized':
      return 'Over-optimized';
    default:
      return 'Unknown';
  }
}
