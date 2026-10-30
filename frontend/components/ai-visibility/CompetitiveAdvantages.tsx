/**
 * TASK-34-17: CompetitiveAdvantages Component
 * 
 * Shows query categories where brand outperforms and where competitors
 * outperform, with expandable details and filtering.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { ThumbsUp, AlertTriangle, ChevronDown, ChevronUp, Filter } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdvantageCategory, AdvantagesData } from '@/lib/hooks/useComparisonData';

// ============================================
// Types
// ============================================

interface CompetitiveAdvantagesProps {
  brandName: string;
  competitorName: string;
  advantages: AdvantagesData;
  className?: string;
}

// ============================================
// Component
// ============================================

export function CompetitiveAdvantages({
  brandName,
  competitorName,
  advantages,
  className,
}: CompetitiveAdvantagesProps) {
  const [selectedCategory, setSelectedCategory] = React.useState<string | null>(null);
  const [expandedItems, setExpandedItems] = React.useState<Set<string>>(new Set());

  // Get all unique categories
  const allCategories = React.useMemo(() => {
    const brandCats = advantages.brand_wins.map(a => a.category);
    const compCats = advantages.competitor_wins.map(a => a.category);
    return [...new Set([...brandCats, ...compCats])];
  }, [advantages]);

  // Filter advantages by selected category
  const filteredBrandWins = React.useMemo(() => {
    if (!selectedCategory) return advantages.brand_wins;
    return advantages.brand_wins.filter(a => a.category === selectedCategory);
  }, [advantages.brand_wins, selectedCategory]);

  const filteredCompetitorWins = React.useMemo(() => {
    if (!selectedCategory) return advantages.competitor_wins;
    return advantages.competitor_wins.filter(a => a.category === selectedCategory);
  }, [advantages.competitor_wins, selectedCategory]);

  // Toggle expanded item
  const toggleExpanded = (key: string) => {
    setExpandedItems(prev => {
      const next = new Set(prev);
      if (next.has(key)) {
        next.delete(key);
      } else {
        next.add(key);
      }
      return next;
    });
  };

  // Empty state
  if (advantages.brand_wins.length === 0 && advantages.competitor_wins.length === 0) {
    return (
      <div data-testid="advantages-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <p className="text-center text-slate-400">No advantage data available</p>
      </div>
    );
  }

  return (
    <div data-testid="advantages-section" className={cn('rounded-lg bg-slate-800', className)}>
      {/* Header with Category Filter */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <h3 className="text-lg font-semibold text-white">Competitive Advantages</h3>
        
        {/* Category Filter */}
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-slate-400" />
          <div className="flex gap-1">
            <button
              onClick={() => setSelectedCategory(null)}
              className={cn(
                'rounded-lg px-3 py-1 text-sm font-medium transition-colors',
                !selectedCategory
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
              )}
            >
              All
            </button>
            {allCategories.map(category => (
              <button
                key={category}
                onClick={() => setSelectedCategory(category)}
                className={cn(
                  'rounded-lg px-3 py-1 text-sm font-medium capitalize transition-colors',
                  selectedCategory === category
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                )}
              >
                {formatCategory(category)}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="p-4 space-y-6">
        {/* Brand Wins Section */}
        <div data-testid="brand-wins">
          <div className="flex items-center gap-2 mb-3">
            <ThumbsUp data-testid="thumbs-up-icon" className="h-5 w-5 text-green-400" />
            <h4 className="text-sm font-semibold text-green-400">{brandName} Wins</h4>
          </div>

          {filteredBrandWins.length === 0 ? (
            <p className="text-sm text-slate-500 pl-7">No advantages found</p>
          ) : (
            <div className="space-y-2">
              {filteredBrandWins.map(item => (
                <AdvantageItem
                  key={`brand-${item.category}`}
                  item={item}
                  isExpanded={expandedItems.has(`brand-${item.category}`)}
                  onToggle={() => toggleExpanded(`brand-${item.category}`)}
                  variant="win"
                />
              ))}
            </div>
          )}
        </div>

        {/* Competitor Wins Section */}
        <div data-testid="competitor-wins">
          <div className="flex items-center gap-2 mb-3">
            <AlertTriangle data-testid="alert-icon" className="h-5 w-5 text-red-400" />
            <h4 className="text-sm font-semibold text-red-400">{competitorName} Wins</h4>
          </div>

          {filteredCompetitorWins.length === 0 ? (
            <p className="text-sm text-slate-500 pl-7">No competitive threats found</p>
          ) : (
            <div className="space-y-2">
              {filteredCompetitorWins.map(item => (
                <AdvantageItem
                  key={`comp-${item.category}`}
                  item={item}
                  isExpanded={expandedItems.has(`comp-${item.category}`)}
                  onToggle={() => toggleExpanded(`comp-${item.category}`)}
                  variant="loss"
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ============================================
// Advantage Item Sub-Component
// ============================================

interface AdvantageItemProps {
  item: AdvantageCategory;
  isExpanded: boolean;
  onToggle: () => void;
  variant: 'win' | 'loss';
}

function AdvantageItem({ item, isExpanded, onToggle, variant }: AdvantageItemProps) {
  return (
    <div>
      <button
        onClick={onToggle}
        aria-expanded={isExpanded}
        className={cn(
          'w-full flex items-center justify-between p-3 rounded-lg transition-colors',
          variant === 'win' ? 'bg-green-500/10 hover:bg-green-500/20' : 'bg-red-500/10 hover:bg-red-500/20'
        )}
      >
        <div className="flex items-center gap-3">
          <span className={cn(
            'text-sm font-medium capitalize',
            variant === 'win' ? 'text-green-300' : 'text-red-300'
          )}>
            {formatCategory(item.category)}
          </span>
          <span className={cn(
            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
            variant === 'win' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
          )}>
            {item.query_count}
          </span>
        </div>
        
        {isExpanded ? (
          <ChevronUp data-testid="chevron-up-icon" className="h-4 w-4 text-slate-400" />
        ) : (
          <ChevronDown data-testid="chevron-down-icon" className="h-4 w-4 text-slate-400" />
        )}
      </button>

      {/* Expanded Details */}
      {isExpanded && (
        <div
          data-testid={`${item.category}-details`}
          className="mt-2 ml-3 pl-4 border-l-2 border-slate-600 space-y-2"
        >
          {item.examples.map((example, idx) => (
            <p key={idx} className="text-sm text-slate-400 italic">
              "{example}"
            </p>
          ))}
        </div>
      )}
    </div>
  );
}

// ============================================
// Helpers
// ============================================

function formatCategory(category: string): string {
  return category
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

export default CompetitiveAdvantages;
