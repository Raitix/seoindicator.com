/**
 * TASK-34-17: useComparisonData Hook
 * 
 * Data fetching hook for the Competitor Comparison page.
 * Fetches ranking, head-to-head, advantages, and platform breakdown data.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { useQuery } from '@tanstack/react-query';
import { aiVisibilityKeys } from './useAIVisibilityDashboard';

// ============================================
// Types
// ============================================

export interface Competitor {
  id: string;
  name: string;
  keywords?: string[];
}

export interface RankingEntry {
  position: number;
  name: string;
  rate: number;
  trend: 'up' | 'down' | 'stable';
  isBrand?: boolean;
}

export interface RankingsData {
  rankings: RankingEntry[];
  brand_position: number;
}

export interface HeadToHeadData {
  brand: {
    mentions: number;
    rate: number;
    sentiment: number;
  };
  competitor: {
    mentions: number;
    rate: number;
    sentiment: number;
  };
  co_mentions: number;
  brand_advantage: string[];
  competitor_advantage: string[];
}

export interface AdvantageCategory {
  category: string;
  query_count: number;
  examples: string[];
}

export interface AdvantagesData {
  brand_wins: AdvantageCategory[];
  competitor_wins: AdvantageCategory[];
}

export interface PlatformData {
  brand: {
    mentions: number;
    rate: number;
  };
  competitors: Array<{
    name: string;
    mentions: number;
    rate: number;
  }>;
}

export interface PlatformBreakdownData {
  chatgpt: PlatformData;
  perplexity: PlatformData;
}

export interface CompetitorDetails {
  recent_mentions: Array<{
    context: string;
    platform: string;
    sentiment: string;
  }>;
  sentiment_breakdown: {
    positive: number;
    neutral: number;
    negative: number;
  };
  query_categories: string[];
  platforms: string[];
}

export interface ComparisonData {
  projectId: string;
  projectName: string;
  brandName: string;
  competitors: Competitor[];
  rankings: RankingsData;
  headToHead: HeadToHeadData | null;
  advantages: AdvantagesData;
  platformBreakdown: PlatformBreakdownData | null;
  competitorDetails: Record<string, CompetitorDetails>;
}

export interface UseComparisonDataReturn {
  data: ComparisonData | null;
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

// ============================================
// Mock Data
// ============================================

export function getMockComparisonData(): ComparisonData {
  return {
    projectId: '1',
    projectName: 'SEOIndicator',
    brandName: 'SEOIndicator',
    competitors: [
      { id: 'comp-1', name: 'Ahrefs', keywords: ['ahrefs', 'ahrefs.com'] },
      { id: 'comp-2', name: 'SEMrush', keywords: ['semrush', 'semrush.com'] },
      { id: 'comp-3', name: 'Moz', keywords: ['moz', 'moz.com'] },
    ],
    rankings: {
      rankings: [
        { position: 1, name: 'Ahrefs', rate: 38.5, trend: 'up' },
        { position: 2, name: 'SEMrush', rate: 30.8, trend: 'stable' },
        { position: 3, name: 'SEOIndicator', rate: 23.5, trend: 'up', isBrand: true },
        { position: 4, name: 'Moz', rate: 15.2, trend: 'down' },
      ],
      brand_position: 3,
    },
    headToHead: {
      brand: { mentions: 15, rate: 23.5, sentiment: 0.45 },
      competitor: { mentions: 25, rate: 38.5, sentiment: 0.30 },
      co_mentions: 5,
      brand_advantage: ['comparison', 'use_case'],
      competitor_advantage: ['recommendation'],
    },
    advantages: {
      brand_wins: [
        { category: 'comparison', query_count: 12, examples: ['How does SEOIndicator compare to Ahrefs?', 'SEOIndicator vs SEMrush'] },
        { category: 'use_case', query_count: 8, examples: ['Best SEO tool for beginners', 'Affordable SEO tools'] },
      ],
      competitor_wins: [
        { category: 'recommendation', query_count: 15, examples: ['What SEO tool do you recommend?', 'Best SEO tool overall'] },
        { category: 'feature', query_count: 5, examples: ['Best backlink analysis tool', 'Keyword research tools'] },
      ],
    },
    platformBreakdown: {
      chatgpt: {
        brand: { mentions: 8, rate: 25.5 },
        competitors: [
          { name: 'Ahrefs', mentions: 12, rate: 38.2 },
          { name: 'SEMrush', mentions: 9, rate: 28.6 },
        ],
      },
      perplexity: {
        brand: { mentions: 7, rate: 21.3 },
        competitors: [
          { name: 'Ahrefs', mentions: 13, rate: 39.1 },
          { name: 'SEMrush', mentions: 11, rate: 33.0 },
        ],
      },
    },
    competitorDetails: {
      'comp-1': {
        recent_mentions: [
          { context: 'Ahrefs is great for backlink analysis...', platform: 'chatgpt', sentiment: 'positive' },
          { context: 'I recommend Ahrefs for keyword research...', platform: 'perplexity', sentiment: 'positive' },
          { context: 'Ahrefs is expensive compared to alternatives...', platform: 'chatgpt', sentiment: 'negative' },
        ],
        sentiment_breakdown: { positive: 15, neutral: 8, negative: 2 },
        query_categories: ['recommendation', 'feature', 'comparison'],
        platforms: ['chatgpt', 'perplexity'],
      },
    },
  };
}

// ============================================
// API Functions
// ============================================

async function fetchComparisonData(
  projectId: string | number
): Promise<ComparisonData> {
  const response = await fetch(
    `/api/v1/ai-visibility/projects/${projectId}/comparison`
  );

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  const json = await response.json();
  return json.data || json;
}

// ============================================
// Hook
// ============================================

export function useComparisonData(
  projectId: string | number | undefined,
  options?: {
    staleTime?: number;
    enabled?: boolean;
  }
): UseComparisonDataReturn {
  const { staleTime = 5 * 60 * 1000, enabled = true } = options || {};

  const {
    data,
    isLoading,
    error,
    refetch,
  } = useQuery({
    queryKey: [...aiVisibilityKeys.project(projectId || ''), 'comparison'],
    queryFn: () => fetchComparisonData(projectId!),
    enabled: enabled && !!projectId,
    staleTime,
    gcTime: 10 * 60 * 1000,
    retry: 2,
    refetchOnWindowFocus: false,
  });

  const refetchAsync = React.useCallback(async () => {
    await refetch();
  }, [refetch]);

  return {
    data: data ?? null,
    isLoading,
    error: error ?? null,
    refetch: refetchAsync,
  };
}
