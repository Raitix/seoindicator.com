'use client';

/**
 * Guide Index Client Component
 * Task: TASK-20-2 - Create Guide Index Page Component
 * 
 * Client component for interactive guide filtering and search.
 */

import React, { useState, useMemo, useCallback } from 'react';
import { 
  getAllGuides, 
  getGuidesByCategory 
} from '@/lib/guides/registry';
import { GuideCategory, SEOGuide } from '@/lib/guides/types';
import { GuideCard } from '@/components/guides/GuideCard';
import { CategoryFilter } from '@/components/guides/CategoryFilter';
import { GuideSearch } from '@/components/guides/GuideSearch';

export function GuidesIndexClient() {
  // Get all guides
  const allGuides = useMemo(() => getAllGuides(), []);
  
  // State for category filter and search
  const [activeCategory, setActiveCategory] = useState<GuideCategory | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Calculate counts for each category
  const categoryCounts = useMemo(() => ({
    all: allGuides.length,
    'on-page': getGuidesByCategory('on-page').length,
    technical: getGuidesByCategory('technical').length,
    content: getGuidesByCategory('content').length,
  }), [allGuides]);

  // Filter guides based on category and search
  const filteredGuides = useMemo(() => {
    let guides = activeCategory === 'all' 
      ? allGuides 
      : getGuidesByCategory(activeCategory);

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase().trim();
      guides = guides.filter(guide => 
        guide.title.toLowerCase().includes(query) ||
        guide.summary.toLowerCase().includes(query)
      );
    }

    return guides;
  }, [allGuides, activeCategory, searchQuery]);

  // Handle category change
  const handleCategoryChange = useCallback((category: GuideCategory | 'all') => {
    setActiveCategory(category);
  }, []);

  // Handle search change
  const handleSearchChange = useCallback((value: string) => {
    setSearchQuery(value);
  }, []);

  return (
    <div className="container mx-auto px-4 py-8 max-w-7xl">
      {/* Page Header */}
      <header className="mb-8">
        <h1 className="text-4xl font-bold tracking-tight mb-2">
          SEO Guides
        </h1>
        <p 
          className="text-muted-foreground text-lg"
          data-testid="page-description"
        >
          Master SEO with our comprehensive guides
        </p>
      </header>

      {/* Filters Section */}
      <section className="mb-8 space-y-4">
        {/* Category Filter */}
        <CategoryFilter
          activeCategory={activeCategory}
          onCategoryChange={handleCategoryChange}
          counts={categoryCounts}
        />

        {/* Search */}
        <div className="max-w-md">
          <GuideSearch
            value={searchQuery}
            onChange={handleSearchChange}
            placeholder="Search guides..."
          />
        </div>
      </section>

      {/* Guide Grid */}
      {filteredGuides.length > 0 ? (
        <div
          id="guide-grid"
          role="list"
          data-testid="guide-grid"
          className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
          {filteredGuides.map((guide) => (
            <GuideCard key={guide.slug} guide={guide} />
          ))}
        </div>
      ) : (
        <div 
          className="text-center py-12"
          data-testid="empty-state"
        >
          <div className="text-4xl mb-4">🔍</div>
          <h2 className="text-xl font-semibold mb-2">No guides found</h2>
          <p className="text-muted-foreground">
            {searchQuery 
              ? `No guides match "${searchQuery}". Try a different search term.`
              : 'No guides available in this category.'}
          </p>
        </div>
      )}
    </div>
  );
}
