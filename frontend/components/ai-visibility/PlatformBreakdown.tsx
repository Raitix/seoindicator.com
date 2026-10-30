/**
 * TASK-34-17: PlatformBreakdown Component
 * 
 * Shows comparison by platform (ChatGPT, Perplexity) with
 * radar chart and grouped bar chart visualizations.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { Globe, MessageSquare } from 'lucide-react';
import {
  ResponsiveContainer,
  RadarChart,
  PolarGrid,
  PolarAngleAxis,
  PolarRadiusAxis,
  Radar,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
  Cell,
} from 'recharts';
import { cn } from '@/lib/utils';
import type { PlatformBreakdownData } from '@/lib/hooks/useComparisonData';

// ============================================
// Types
// ============================================

interface PlatformBreakdownProps {
  brandName: string;
  platformBreakdown: PlatformBreakdownData | null;
  className?: string;
}

type PlatformKey = 'chatgpt' | 'perplexity';

// ============================================
// Constants
// ============================================

const BRAND_COLOR = '#3b82f6'; // blue-500
const COMPETITOR_COLOR = '#94a3b8'; // slate-400

const PLATFORM_LABELS: Record<PlatformKey, string> = {
  chatgpt: 'ChatGPT',
  perplexity: 'Perplexity',
};

// ============================================
// Helper Functions
// ============================================

function PlatformIcon({ platform, className }: { platform: PlatformKey; className?: string }) {
  if (platform === 'chatgpt') {
    return <MessageSquare className={className} />;
  }
  return <Globe className={className} />;
}

// ============================================
// Component
// ============================================

export function PlatformBreakdown({
  brandName,
  platformBreakdown,
  className,
}: PlatformBreakdownProps) {
  const [selectedPlatform, setSelectedPlatform] = React.useState<PlatformKey>('chatgpt');

  // Empty state
  if (!platformBreakdown) {
    return (
      <div data-testid="platform-breakdown-section" className={cn('rounded-lg bg-slate-800 p-6', className)}>
        <p className="text-center text-slate-400">No platform data available</p>
      </div>
    );
  }

  const platforms = Object.keys(platformBreakdown) as PlatformKey[];
  const currentData = platformBreakdown[selectedPlatform];

  // Prepare radar chart data
  const radarData = React.useMemo(() => {
    const brandPoint = {
      subject: brandName,
      brand: currentData.brand.rate,
      fullMark: 100,
    };
    
    const competitorPoints = currentData.competitors.map(comp => ({
      subject: comp.name,
      competitor: comp.rate,
      fullMark: 100,
    }));

    return [brandPoint, ...competitorPoints];
  }, [currentData, brandName]);

  // Prepare bar chart data
  const barData = React.useMemo(() => {
    return [
      {
        name: brandName,
        brand: currentData.brand.mentions,
        rate: currentData.brand.rate,
      },
      ...currentData.competitors.map(comp => ({
        name: comp.name,
        competitor: comp.mentions,
        rate: comp.rate,
      })),
    ];
  }, [currentData, brandName]);

  return (
    <div data-testid="platform-breakdown-section" className={cn('rounded-lg bg-slate-800', className)}>
      {/* Header with Platform Tabs */}
      <div className="flex items-center justify-between p-4 border-b border-slate-700">
        <h3 className="text-lg font-semibold text-white">Platform Breakdown</h3>
        
        {/* Platform Tabs */}
        <div role="tablist" className="flex gap-2">
          {platforms.map(platform => (
            <button
              key={platform}
              role="tab"
              aria-selected={selectedPlatform === platform}
              onClick={() => setSelectedPlatform(platform)}
              className={cn(
                'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                selectedPlatform === platform
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
              )}
            >
              <PlatformIcon platform={platform} className="h-4 w-4" />
              {PLATFORM_LABELS[platform]}
            </button>
          ))}
        </div>
      </div>

      {/* Charts */}
      <div className="p-4">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Radar Chart */}
          <div>
            <h4 className="text-sm font-medium text-slate-400 mb-4">Mention Rate by Brand</h4>
            <ResponsiveContainer width="100%" height={250}>
              <RadarChart
                data={radarData}
                aria-label={`${selectedPlatform} mention rate radar chart`}
              >
                <PolarGrid data-testid="polar-grid" />
                <PolarAngleAxis
                  data-testid="polar-angle-axis"
                  dataKey="subject"
                  tick={{ fill: '#94a3b8', fontSize: 12 }}
                />
                <PolarRadiusAxis
                  data-testid="polar-radius-axis"
                  tick={{ fill: '#94a3b8', fontSize: 10 }}
                />
                <Radar
                  data-testid="radar"
                  name={brandName}
                  dataKey="brand"
                  stroke={BRAND_COLOR}
                  fill={BRAND_COLOR}
                  fillOpacity={0.3}
                />
                <Radar
                  data-testid="radar"
                  name="Competitors"
                  dataKey="competitor"
                  stroke={COMPETITOR_COLOR}
                  fill={COMPETITOR_COLOR}
                  fillOpacity={0.3}
                />
                <Tooltip data-testid="tooltip" />
                <Legend data-testid="legend" />
              </RadarChart>
            </ResponsiveContainer>
          </div>

          {/* Bar Chart */}
          <div>
            <h4 className="text-sm font-medium text-slate-400 mb-4">Mention Count by Brand</h4>
            <ResponsiveContainer width="100%" height={250}>
              <BarChart
                data={barData}
                aria-label={`${selectedPlatform} mention count bar chart`}
              >
                <XAxis
                  data-testid="x-axis"
                  dataKey="name"
                  tick={{ fill: '#94a3b8', fontSize: 12 }}
                />
                <YAxis
                  data-testid="y-axis"
                  type="number"
                  tick={{ fill: '#94a3b8', fontSize: 10 }}
                />
                <Tooltip data-testid="tooltip" />
                <Legend data-testid="legend" />
                <Bar
                  data-testid="bar"
                  dataKey="brand"
                  name={brandName}
                  fill={BRAND_COLOR}
                >
                  {barData.map((entry, index) => (
                    <Cell key={`cell-${index}`} data-testid="cell" />
                  ))}
                </Bar>
                <Bar
                  data-testid="bar"
                  dataKey="competitor"
                  name="Competitors"
                  fill={COMPETITOR_COLOR}
                >
                  {barData.map((entry, index) => (
                    <Cell key={`cell-${index}`} data-testid="cell" />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Platform Indicator */}
        <div className="mt-4 flex items-center justify-center gap-2 text-slate-400 text-sm">
          <PlatformIcon platform={selectedPlatform} className="h-4 w-4" />
          <span>Data from {PLATFORM_LABELS[selectedPlatform]}</span>
        </div>
      </div>
    </div>
  );
}

export default PlatformBreakdown;
