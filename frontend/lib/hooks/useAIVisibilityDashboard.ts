/**
 * TASK-34-10: useAIVisibilityDashboard Hook
 * 
 * Data fetching hook for the AI Visibility dashboard.
 * Fetches project data, metrics, charts, and recent mentions.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

'use client';

import * as React from 'react';
import { useQuery } from '@tanstack/react-query';
import type {
  AIVisibilityDashboardData,
  UseAIVisibilityDashboardReturn,
} from '@/lib/types/ai-visibility';

// ============================================
// Query Keys
// ============================================

export const aiVisibilityKeys = {
  all: ['ai-visibility'] as const,
  projects: () => [...aiVisibilityKeys.all, 'projects'] as const,
  project: (id: string | number) => [...aiVisibilityKeys.projects(), id] as const,
  dashboard: (id: string | number) => [...aiVisibilityKeys.project(id), 'dashboard'] as const,
};

// ============================================
// API Functions
// ============================================

/**
 * Fetch dashboard data from the API
 */
async function fetchDashboardData(
  projectId: string | number
): Promise<AIVisibilityDashboardData> {
  const response = await fetch(
    `/api/v1/ai-visibility/projects/${projectId}/dashboard`
  );

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return response.json();
}

/**
 * Get mock dashboard data for development/testing
 */
export function getMockDashboardData(): AIVisibilityDashboardData {
  const now = new Date();
  const dates: string[] = [];
  
  // Generate 13 weeks of dates (91 days)
  for (let i = 12; i >= 0; i--) {
    const date = new Date(now);
    date.setDate(date.getDate() - i * 7);
    dates.push(date.toISOString().split('T')[0]);
  }

  return {
    project: {
      id: 123,
      brandName: 'SEOIndicator',
      keywords: ['seo tool', 'keyword tracking', 'backlink analysis'],
      competitors: ['Ahrefs', 'Semrush', 'Moz'],
      isActive: true,
      trackingFrequency: 'weekly',
      lastQueriedAt: new Date(Date.now() - 86400000).toISOString(),
    },
    metrics: {
      mentionRate: {
        value: 23.5,
        previousValue: 18.3,
        change: 5.2,
        trend: 'up',
      },
      shareOfVoice: {
        value: 31.2,
        previousValue: 29.1,
        change: 2.1,
        trend: 'up',
      },
      sentimentScore: {
        value: 0.45,
        previousValue: 0.38,
        change: 0.07,
        trend: 'up',
        label: 'Positive',
      },
    },
    mentionRateOverTime: dates.map((date, i) => ({
      date,
      overall: 15.2 + i * 0.7,
      chatgpt: 18.5 + i * 0.85,
      perplexity: 12.0 + i * 0.45,
    })),
    competitorComparison: [
      { name: 'SEOIndicator', value: 31.2, isBrand: true },
      { name: 'Ahrefs', value: 45.8, isBrand: false },
      { name: 'Semrush', value: 52.3, isBrand: false },
      { name: 'Moz', value: 28.5, isBrand: false },
    ],
    recentMentions: [
      {
        id: 'm1',
        brandContext:
          'For comprehensive SEO analysis, SEOIndicator offers a robust set of features including keyword tracking and competitor analysis. The platform provides detailed insights into your website performance.',
        platform: 'chatgpt',
        sentiment: 'positive',
        queriedAt: new Date(Date.now() - 3600000).toISOString(),
      },
      {
        id: 'm2',
        brandContext:
          'When comparing SEO tools, Ahrefs and Semrush are often mentioned alongside SEOIndicator for their backlink databases and keyword research capabilities.',
        platform: 'perplexity',
        sentiment: 'neutral',
        queriedAt: new Date(Date.now() - 7200000).toISOString(),
      },
      {
        id: 'm3',
        brandContext:
          'SEOIndicator is a newer player in the SEO tool space, offering competitive pricing for smaller businesses looking for affordable SEO solutions.',
        platform: 'chatgpt',
        sentiment: 'positive',
        queriedAt: new Date(Date.now() - 86400000).toISOString(),
      },
    ],
    queryCategories: [
      { name: 'comparison', value: 42 },
      { name: 'recommendation', value: 28 },
      { name: 'feature', value: 18 },
      { name: 'use_case', value: 12 },
    ],
    quickActions: {
      canRunQuery: true,
      nextQueryAt: new Date(Date.now() + 86400000).toISOString(),
      hasExportData: true,
    },
  };
}

// ============================================
// Hook
// ============================================

/**
 * Hook for fetching AI Visibility dashboard data
 * 
 * @param projectId - The project ID (string or number)
 * @param options - Optional configuration
 * @returns Dashboard data, loading state, error state, and refetch function
 */
export function useAIVisibilityDashboard(
  projectId: string | number | undefined,
  options?: {
    staleTime?: number;
    enabled?: boolean;
  }
): UseAIVisibilityDashboardReturn {
  const { staleTime = 5 * 60 * 1000, enabled = true } = options || {};

  const {
    data,
    isLoading,
    error,
    refetch,
    isStale,
  } = useQuery({
    queryKey: aiVisibilityKeys.dashboard(projectId || ''),
    queryFn: () => fetchDashboardData(projectId!),
    enabled: enabled && !!projectId,
    staleTime,
    gcTime: 10 * 60 * 1000, // 10 minutes
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
    isStale,
  };
}
