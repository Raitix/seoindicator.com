/**
 * GuideCard Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Individual guide card that displays in the grid.
 */

import React from 'react';
import Link from 'next/link';
import { SEOGuide } from '@/lib/guides/types';
import { Card, CardContent } from '@/components/ui/card';

interface GuideCardProps {
  guide: SEOGuide;
}

export function GuideCard({ guide }: GuideCardProps) {
  return (
    <article
      data-testid={`guide-card-${guide.slug}`}
      className="guide-card h-full"
      role="listitem"
      aria-labelledby={`guide-title-${guide.slug}`}
    >
      <Link
        href={`/seo-guides/${guide.slug}`}
        data-testid={`guide-link-${guide.slug}`}
        className="block h-full"
      >
        <Card className="h-full hover:border-primary/50 hover:shadow-md transition-all duration-200 cursor-pointer">
          <CardContent className="p-5 flex flex-col h-full">
            {/* Icon and Tool Badge */}
            <div className="flex items-start justify-between mb-3">
              <span 
                className="guide-icon text-3xl"
                data-testid={`guide-icon-${guide.slug}`}
              >
                {guide.icon}
              </span>
              {guide.toolSlug && (
                <span
                  className="tool-badge inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                  data-testid={`tool-badge-${guide.slug}`}
                >
                  Has Tool
                </span>
              )}
            </div>

            {/* Title */}
            <h3
              id={`guide-title-${guide.slug}`}
              className="guide-title text-lg font-semibold leading-tight mb-2 line-clamp-2"
              data-testid={`guide-title-${guide.slug}`}
            >
              {guide.title}
            </h3>

            {/* Summary */}
            <p
              className="guide-summary text-sm text-muted-foreground mb-4 line-clamp-2 flex-grow"
              data-testid={`guide-summary-${guide.slug}`}
            >
              {guide.summary}
            </p>

            {/* Read Time */}
            <div className="flex items-center text-xs text-muted-foreground mt-auto">
              <svg
                className="w-4 h-4 mr-1.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span data-testid={`guide-read-time-${guide.slug}`}>
                {guide.readTime} min read
              </span>
            </div>
          </CardContent>
        </Card>
      </Link>
    </article>
  );
}
