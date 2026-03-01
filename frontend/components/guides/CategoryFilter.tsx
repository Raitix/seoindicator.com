/**
 * CategoryFilter Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Category filter tabs for filtering guides by category.
 */

'use client';

import React from 'react';
import { GuideCategory } from '@/lib/guides/types';
import { cn } from '@/lib/utils';

interface CategoryCount {
  all: number;
  'on-page': number;
  technical: number;
  content: number;
}

interface CategoryFilterProps {
  activeCategory: GuideCategory | 'all';
  onCategoryChange: (category: GuideCategory | 'all') => void;
  counts: CategoryCount;
}

const CATEGORIES: { id: GuideCategory | 'all'; label: string }[] = [
  { id: 'all', label: 'All' },
  { id: 'on-page', label: 'On-Page' },
  { id: 'technical', label: 'Technical' },
  { id: 'content', label: 'Content' },
];

export function CategoryFilter({ 
  activeCategory, 
  onCategoryChange,
  counts 
}: CategoryFilterProps) {
  return (
    <div 
      role="tablist" 
      aria-label="Filter guides by category" 
      data-testid="category-filter"
      className="flex flex-wrap gap-2"
    >
      {CATEGORIES.map((cat) => (
        <button
          key={cat.id}
          role="tab"
          aria-selected={activeCategory === cat.id}
          aria-controls="guide-grid"
          data-testid={`tab-${cat.id}`}
          onClick={() => onCategoryChange(cat.id)}
          className={cn(
            'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
            activeCategory === cat.id
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground'
          )}
        >
          {cat.label}
          <span
            className={cn(
              'text-xs rounded-full px-2 py-0.5',
              activeCategory === cat.id
                ? 'bg-primary-foreground/20 text-primary-foreground'
                : 'bg-background/50'
            )}
            data-testid={`count-${cat.id}`}
          >
            {counts[cat.id]}
          </span>
        </button>
      ))}
    </div>
  );
}
