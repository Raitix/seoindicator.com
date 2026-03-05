/**
 * ComparisonSchema Component
 * Generates JSON-LD structured data for SEO (BreadcrumbList, FAQPage, HowTo)
 *
 * @see TASK-32-10
 * @see SPEC-32 (Competitor Comparison Landing Pages)
 */

import React from 'react';
import type { ComparisonData, FAQ, ComparisonSchemaProps } from '@/lib/compare/types';

/**
 * Generates BreadcrumbList schema for comparison pages.
 * Structure: Home → Compare → {Comparison Title}
 */
function generateBreadcrumbSchema(comparison: ComparisonData) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "https://seoindicator.com/"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "Compare",
        "item": "https://seoindicator.com/compare/"
      },
      {
        "@type": "ListItem",
        "position": 3,
        "name": comparison.title,
        "item": `https://seoindicator.com/compare/${comparison.slug}/`
      }
    ]
  };
}

/**
 * Generates FAQPage schema from FAQ array.
 * Each FAQ becomes a Question with an acceptedAnswer.
 */
function generateFAQSchema(faqs: FAQ[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": faqs.map((faq) => ({
      "@type": "Question",
      "name": faq.question,
      "acceptedAnswer": {
        "@type": "Answer",
        "text": faq.answer
      }
    }))
  };
}

/**
 * Generates HowTo schema for choosing between tools.
 * Provides step-by-step guidance for comparison pages.
 */
function generateHowToSchema(comparison: ComparisonData) {
  return {
    "@context": "https://schema.org",
    "@type": "HowTo",
    "name": `How to Choose Between ${comparison.title}`,
    "step": [
      {
        "@type": "HowToStep",
        "name": "Compare Features",
        "text": "Review the feature comparison to see which tool offers what you need."
      },
      {
        "@type": "HowToStep",
        "name": "Compare Pricing",
        "text": "Evaluate pricing tiers to find the best fit for your budget."
      },
      {
        "@type": "HowToStep",
        "name": "Consider Your Use Case",
        "text": "Match your specific needs (solo, team, agency) to the recommended use cases."
      },
      {
        "@type": "HowToStep",
        "name": "Try Free Tier",
        "text": "Start with the free tier to test the tool before committing."
      }
    ]
  };
}

/**
 * ComparisonSchema Component
 *
 * Renders three JSON-LD script tags for structured data:
 * 1. BreadcrumbList - Navigation breadcrumb for SERP
 * 2. FAQPage - FAQ rich results for search
 * 3. HowTo - Step-by-step guide for choosing tools
 *
 * Uses JSON.stringify for automatic XSS prevention (escapes <, >, etc.)
 *
 * @param props.comparison - Complete comparison data
 * @param props.faqs - Array of FAQs for FAQPage schema
 */
export function ComparisonSchema({ comparison, faqs }: ComparisonSchemaProps) {
  const breadcrumbSchema = generateBreadcrumbSchema(comparison);
  const faqSchema = generateFAQSchema(faqs);
  const howToSchema = generateHowToSchema(comparison);

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(howToSchema) }}
      />
    </>
  );
}

export default ComparisonSchema;
