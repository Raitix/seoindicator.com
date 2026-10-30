/**
 * TypeScript Types for SEO Guides
 * Task: TASK-20-1 - Create Guide Data Structure and TypeScript Types
 * 
 * This module defines the core data structures for the SEO Guides Library.
 */

// ============================================================================
// Category and Priority Types
// ============================================================================

/**
 * Valid guide categories
 */
export type GuideCategory = 'on-page' | 'technical' | 'content';

/**
 * Valid checklist item priorities
 */
export type ChecklistPriority = 'critical' | 'important' | 'recommended';

/**
 * Constant array of valid categories
 */
export const GUIDE_CATEGORIES: readonly GuideCategory[] = [
  'on-page',
  'technical',
  'content',
] as const;

/**
 * Constant array of valid priorities
 */
export const CHECKLIST_PRIORITIES: readonly ChecklistPriority[] = [
  'critical',
  'important',
  'recommended',
] as const;

// ============================================================================
// Core Interfaces
// ============================================================================

/**
 * Code example for guide sections
 */
export interface CodeExample {
  /** Programming language for syntax highlighting */
  language: string;
  /** The code snippet */
  code: string;
  /** Optional description of what the code demonstrates */
  description?: string;
}

/**
 * Section within an SEO guide
 */
export interface GuideSection {
  /** Section heading */
  title: string;
  /** Markdown content for the section */
  content: string;
  /** Optional code examples for the section */
  codeExamples?: CodeExample[];
}

/**
 * Checklist item for actionable SEO recommendations
 */
export interface ChecklistItem {
  /** The checklist item text */
  text: string;
  /** Priority level indicating importance */
  priority: ChecklistPriority;
}

/**
 * Complete SEO Guide definition
 */
export interface SEOGuide {
  /** URL-friendly slug (kebab-case) */
  slug: string;
  /** Display title */
  title: string;
  /** Brief summary for preview cards */
  summary: string;
  /** Guide category for filtering */
  category: GuideCategory;
  /** Optional link to interactive tool */
  toolSlug?: string;
  /** Emoji or icon component name */
  icon: string;
  /** Estimated read time in minutes */
  readTime: number;
  /** Content sections (populated in Phase 2) */
  sections: GuideSection[];
  /** Actionable checklist items */
  checklist: ChecklistItem[];
  /** Slugs of related guides */
  relatedGuides: string[];
}

// ============================================================================
// Type Guards
// ============================================================================

/**
 * Check if a value is a valid GuideCategory
 */
export function isValidCategory(value: unknown): value is GuideCategory {
  return (
    typeof value === 'string' &&
    value.length > 0 &&
    GUIDE_CATEGORIES.includes(value as GuideCategory)
  );
}

/**
 * Check if a value is a valid ChecklistPriority
 */
export function isValidPriority(value: unknown): value is ChecklistPriority {
  return (
    typeof value === 'string' &&
    value.length > 0 &&
    CHECKLIST_PRIORITIES.includes(value as ChecklistPriority)
  );
}

/**
 * Check if a value is a valid CodeExample
 */
export function isCodeExample(value: unknown): value is CodeExample {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const example = value as Record<string, unknown>;

  return (
    typeof example.language === 'string' &&
    typeof example.code === 'string'
  );
}

/**
 * Check if a value is a valid GuideSection
 */
export function isGuideSection(value: unknown): value is GuideSection {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const section = value as Record<string, unknown>;

  // Check required fields
  if (typeof section.title !== 'string' || typeof section.content !== 'string') {
    return false;
  }

  // Check optional codeExamples
  if (section.codeExamples !== undefined) {
    if (!Array.isArray(section.codeExamples)) {
      return false;
    }
    if (!section.codeExamples.every(isCodeExample)) {
      return false;
    }
  }

  return true;
}

/**
 * Check if a value is a valid ChecklistItem
 */
export function isChecklistItem(value: unknown): value is ChecklistItem {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const item = value as Record<string, unknown>;

  return (
    typeof item.text === 'string' &&
    isValidPriority(item.priority)
  );
}

/**
 * Check if a value is a valid SEOGuide
 */
export function isSEOGuide(value: unknown): value is SEOGuide {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const guide = value as Record<string, unknown>;

  // Check required string fields
  if (
    typeof guide.slug !== 'string' ||
    typeof guide.title !== 'string' ||
    typeof guide.summary !== 'string' ||
    typeof guide.icon !== 'string'
  ) {
    return false;
  }

  // Check category
  if (!isValidCategory(guide.category)) {
    return false;
  }

  // Check readTime (must be non-negative number)
  if (typeof guide.readTime !== 'number' || guide.readTime < 0) {
    return false;
  }

  // Check optional toolSlug
  if (guide.toolSlug !== undefined && typeof guide.toolSlug !== 'string') {
    return false;
  }

  // Check sections array
  if (!Array.isArray(guide.sections)) {
    return false;
  }
  if (!guide.sections.every(isGuideSection)) {
    return false;
  }

  // Check checklist array
  if (!Array.isArray(guide.checklist)) {
    return false;
  }
  if (!guide.checklist.every(isChecklistItem)) {
    return false;
  }

  // Check relatedGuides array
  if (!Array.isArray(guide.relatedGuides)) {
    return false;
  }
  if (!guide.relatedGuides.every((slug) => typeof slug === 'string')) {
    return false;
  }

  return true;
}
