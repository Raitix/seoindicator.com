/**
 * GuideHero Component
 * Task: TASK-20-3 - Create Guide Page Template Component
 * 
 * Hero section for individual SEO guide pages.
 */

import React from 'react';
import { Clock, Wrench } from 'lucide-react';
import { SEOGuide } from '@/lib/guides/types';

interface GuideHeroProps {
  guide: SEOGuide;
}

/**
 * Category badge colors
 */
const CATEGORY_COLORS: Record<string, string> = {
  'on-page': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  'technical': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
  'content': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
};

/**
 * Category display names
 */
const CATEGORY_LABELS: Record<string, string> = {
  'on-page': 'On-Page SEO',
  'technical': 'Technical SEO',
  'content': 'Content & Social',
};

/**
 * Format read time for display
 */
function formatReadTime(minutes: number): string {
  if (minutes <= 0) {
    return 'Quick read';
  }
  if (minutes >= 60) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours} hr ${mins} min read` : `${hours} hr read`;
  }
  return `${minutes} min read`;
}

export function GuideHero({ guide }: GuideHeroProps) {
  return (
    <header
      className="guide-hero mb-8"
      data-testid="guide-hero"
    >
      <div className="flex flex-col gap-4">
        {/* Top Row: Icon and Badges */}
        <div className="flex flex-wrap items-center gap-3">
          {/* Icon */}
          <span
            className="guide-icon text-5xl"
            data-testid="guide-hero-icon"
          >
            {guide.icon}
          </span>

          {/* Category Badge */}
          <span
            className={`category-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${CATEGORY_COLORS[guide.category] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'}`}
            data-testid="guide-hero-category"
          >
            {CATEGORY_LABELS[guide.category] || guide.category}
          </span>

          {/* Tool Badge */}
          {guide.toolSlug && (
            <span
              className="tool-badge inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
              data-testid="guide-hero-tool"
            >
              <Wrench className="h-3 w-3 mr-1" aria-hidden="true" />
              Has Interactive Tool
            </span>
          )}

          {/* Read Time */}
          <span
            className="read-time inline-flex items-center text-sm text-muted-foreground"
            data-testid="guide-hero-read-time"
          >
            <Clock className="h-4 w-4 mr-1.5" aria-hidden="true" />
            {formatReadTime(guide.readTime)}
          </span>
        </div>

        {/* Title */}
        <h1
          className="guide-title text-3xl md:text-4xl font-bold tracking-tight"
          data-testid="guide-hero-title"
        >
          {guide.title}
        </h1>

        {/* Summary */}
        <p
          className="guide-summary text-lg text-muted-foreground max-w-3xl"
          data-testid="guide-hero-summary"
        >
          {guide.summary}
        </p>
      </div>
    </header>
  );
}
