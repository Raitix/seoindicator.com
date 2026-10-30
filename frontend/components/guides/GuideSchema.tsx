/**
 * GuideSchema Component
 * Task: TASK-20-27 - Add Schema Markup to All Guides
 *
 * Generates HowTo schema.org structured data for SEO guide pages.
 * Outputs JSON-LD format for rich results in Google Search.
 */

import React from 'react';
import { SEOGuide } from '@/lib/guides/types';

// ============================================================================
// Types
// ============================================================================

interface GuideSchemaProps {
  guide: SEOGuide | null;
}

interface HowToStep {
  '@type': 'HowToStep';
  name: string;
  text: string;
}

interface HowToSchema {
  '@context': 'https://schema.org';
  '@type': 'HowTo';
  name: string;
  description: string;
  step: HowToStep[];
  estimatedCost: {
    '@type': 'MonetaryAmount';
    currency: 'USD';
    value: '0';
  };
  totalTime: string;
}

// ============================================================================
// Schema Generation Function
// ============================================================================

/**
 * Maximum length for HowToStep text before truncation
 */
const MAX_STEP_TEXT_LENGTH = 200;

/**
 * Truncates text to a maximum length, adding ellipsis if truncated
 */
function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) {
    return text;
  }
  return text.slice(0, maxLength - 3) + '...';
}

/**
 * Formats read time as ISO 8601 duration (PT{n}M)
 */
function formatTotalTime(minutes: number): string {
  return `PT${minutes}M`;
}

/**
 * Generates HowTo schema.org structured data from an SEOGuide
 *
 * @param guide - The SEO guide to generate schema for
 * @returns HowTo schema object or null if guide is invalid
 */
export function generateHowToSchema(guide: SEOGuide): HowToSchema {
  // Generate HowToStep array from guide sections
  const steps: HowToStep[] = guide.sections.map((section) => ({
    '@type': 'HowToStep' as const,
    name: section.title,
    text: truncateText(section.content, MAX_STEP_TEXT_LENGTH),
  }));

  // Build the complete HowTo schema
  const schema: HowToSchema = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: guide.title,
    description: guide.summary,
    step: steps,
    estimatedCost: {
      '@type': 'MonetaryAmount',
      currency: 'USD',
      value: '0',
    },
    totalTime: formatTotalTime(guide.readTime),
  };

  return schema;
}

// ============================================================================
// Component
// ============================================================================

/**
 * GuideSchema Component
 *
 * Renders a JSON-LD script tag containing HowTo schema.org structured data.
 * Returns null if the guide prop is null, undefined, or an empty object.
 *
 * @example
 * ```tsx
 * <Head>
 *   <GuideSchema guide={guide} />
 * </Head>
 * ```
 */
export function GuideSchema({ guide }: GuideSchemaProps): React.ReactElement | null {
  // Return null for null, undefined, or empty objects
  if (!guide || Object.keys(guide).length === 0) {
    return null;
  }

  // Generate the schema
  const schema = generateHowToSchema(guide);

  // Render JSON-LD script tag
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{
        __html: JSON.stringify(schema),
      }}
    />
  );
}

export default GuideSchema;
