/**
 * GuideBreadcrumb Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Breadcrumb navigation for individual SEO guide pages.
 */

import React from 'react';
import Link from 'next/link';
import { ChevronRight, Home, BookOpen } from 'lucide-react';
import { SEOGuide } from '@/lib/guides/types';

interface GuideBreadcrumbProps {
  guide: SEOGuide;
}

/**
 * Category display names
 */
const CATEGORY_LABELS: Record<string, string> = {
  'on-page': 'On-Page SEO',
  'technical': 'Technical SEO',
  'content': 'Content & Social',
};

export function GuideBreadcrumb({ guide }: GuideBreadcrumbProps) {
  return (
    <nav
      aria-label="Breadcrumb"
      className="guide-breadcrumb mb-6"
      data-testid="guide-breadcrumb"
    >
      {/* JSON-LD Schema for BreadcrumbList */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: [
              {
                '@type': 'ListItem',
                position: 1,
                name: 'Home',
                item: 'https://seoindicator.com',
              },
              {
                '@type': 'ListItem',
                position: 2,
                name: 'SEO Guides',
                item: 'https://seoindicator.com/seo-guides',
              },
              {
                '@type': 'ListItem',
                position: 3,
                name: CATEGORY_LABELS[guide.category] || guide.category,
                item: `https://seoindicator.com/seo-guides?category=${guide.category}`,
              },
              {
                '@type': 'ListItem',
                position: 4,
                name: guide.title,
                item: `https://seoindicator.com/seo-guides/${guide.slug}`,
              },
            ],
          }),
        }}
      />

      <ol className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
        {/* Home Link */}
        <li className="flex items-center">
          <Link
            href="/"
            className="flex items-center hover:text-foreground transition-colors"
            data-testid="breadcrumb-home"
          >
            <Home className="h-4 w-4" aria-hidden="true" />
            <span className="sr-only">Home</span>
          </Link>
        </li>

        <li aria-hidden="true">
          <ChevronRight className="h-4 w-4" />
        </li>

        {/* SEO Guides Link */}
        <li className="flex items-center">
          <Link
            href="/seo-guides"
            className="flex items-center hover:text-foreground transition-colors"
            data-testid="breadcrumb-guides"
          >
            <BookOpen className="h-4 w-4 mr-1" aria-hidden="true" />
            <span>SEO Guides</span>
          </Link>
        </li>

        <li aria-hidden="true">
          <ChevronRight className="h-4 w-4" />
        </li>

        {/* Category Link */}
        <li className="flex items-center">
          <Link
            href={`/seo-guides?category=${guide.category}`}
            className="hover:text-foreground transition-colors"
            data-testid="breadcrumb-category"
          >
            {CATEGORY_LABELS[guide.category] || guide.category}
          </Link>
        </li>

        <li aria-hidden="true">
          <ChevronRight className="h-4 w-4" />
        </li>

        {/* Current Guide (not linked) */}
        <li
          className="font-medium text-foreground truncate max-w-[200px] md:max-w-none"
          aria-current="page"
          data-testid="breadcrumb-current"
        >
          {guide.title}
        </li>
      </ol>
    </nav>
  );
}
