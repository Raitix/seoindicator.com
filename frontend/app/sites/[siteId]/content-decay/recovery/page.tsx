/**
 * Content Decay Recovery Report Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Report page showing recovery statistics, timeline chart, and before/after comparisons
 */

'use client';

import * as React from 'react';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { Download, Calendar, TrendingUp, ArrowUpRight, Clock } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import {
  useContentDecayRecovery,
  useExportContentDecay,
} from '@/lib/hooks/useContentDecay';

// ============================================================
// Stat Card Component
// ============================================================

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  valueClassName?: string;
  icon?: React.ReactNode;
}

function StatCard({ title, value, subtitle, valueClassName, icon }: StatCardProps) {
  return (
    <div className="bg-white rounded-lg border p-4 shadow-sm">
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm text-muted-foreground">{title}</span>
        {icon}
      </div>
      <div className={`text-2xl font-bold ${valueClassName || ''}`}>
        {value}
      </div>
      {subtitle && (
        <div className="text-xs text-muted-foreground mt-1">{subtitle}</div>
      )}
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function RecoveryReportPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  // State
  const [dateRange, setDateRange] = React.useState<'7' | '30' | '90'>('30');

  // Data hooks
  const { recovery, isLoading, error } = useContentDecayRecovery(siteId);
  const { exportData, isExporting } = useExportContentDecay(siteId);

  // Handlers
  const handleExport = React.useCallback(async () => {
    const result = await exportData('pdf');
    if (result.success) {
      toast.success('Report exported');
    } else {
      toast.error('Failed to export report');
    }
  }, [exportData]);

  // Loading state
  if (isLoading) {
    return (
      <main className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
        <Skeleton className="h-64 mb-6" />
        <Skeleton className="h-96" />
      </main>
    );
  }

  // Error state
  if (error) {
    return (
      <main className="container mx-auto px-4 py-8">
        <div className="text-center py-12">
          <p className="text-red-600 mb-4">Failed to load recovery data</p>
          <Button onClick={() => window.location.reload()}>Retry</Button>
        </div>
      </main>
    );
  }

  // Empty state
  if (!recovery) {
    return (
      <main className="container mx-auto px-4 py-8">
        {/* Breadcrumb */}
        <nav className="mb-4 text-sm text-muted-foreground" aria-label="Breadcrumb">
          <ol className="flex items-center gap-2">
            <li><Link href="/sites" className="hover:text-foreground">Sites</Link></li>
            <li>/</li>
            <li><Link href={`/sites/${siteId}`} className="hover:text-foreground">Site {siteId}</Link></li>
            <li>/</li>
            <li><Link href={`/sites/${siteId}/content-decay`} className="hover:text-foreground">Content Decay</Link></li>
            <li>/</li>
            <li className="text-foreground font-medium">Recovery</li>
          </ol>
        </nav>

        <div className="text-center py-12" data-testid="empty-state">
          <p className="text-muted-foreground mb-2">No recovery data available yet</p>
          <p className="text-sm text-muted-foreground">
            Recovery data will appear after pages are refreshed
          </p>
        </div>
      </main>
    );
  }

  return (
    <main className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-4 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol className="flex items-center gap-2">
          <li>
            <Link href="/sites" className="hover:text-foreground">Sites</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}`} className="hover:text-foreground">Site {siteId}</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}/content-decay`} className="hover:text-foreground">
              Content Decay
            </Link>
          </li>
          <li>/</li>
          <li className="text-foreground font-medium">Recovery</li>
        </ol>
      </nav>

      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold">Recovery Report</h1>
          <p className="text-muted-foreground">
            Track content refresh outcomes and ROI
          </p>
        </div>
        <div className="flex gap-2">
          <Select value={dateRange} onValueChange={(v) => setDateRange(v as '7' | '30' | '90')}>
            <SelectTrigger className="w-40" data-testid="date-range-select">
              <Calendar className="h-4 w-4 mr-2" />
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7">Last 7 days</SelectItem>
              <SelectItem value="30">Last 30 days</SelectItem>
              <SelectItem value="90">Last 90 days</SelectItem>
            </SelectContent>
          </Select>
          <Button variant="outline" onClick={handleExport} disabled={isExporting}>
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
        </div>
      </div>

      {/* Summary Statistics */}
      <div
        data-testid="summary-stats"
        className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6"
      >
        <StatCard
          title="Total Recovered"
          value={recovery.summary.totalRecovered}
          subtitle="pages"
          valueClassName="text-green-600"
          icon={<TrendingUp className="h-4 w-4 text-green-600" />}
        />
        <StatCard
          title="Clicks Recovered"
          value={recovery.summary.totalClicksRecovered.toLocaleString()}
          subtitle="estimated"
          valueClassName="text-blue-600"
        />
        <StatCard
          title="Avg Recovery Rate"
          value={`${recovery.summary.avgRecoveryRate}%`}
          subtitle="of lost clicks"
          valueClassName="text-purple-600"
        />
        <StatCard
          title="Avg Days to Recover"
          value={recovery.summary.avgDaysToRecovery}
          subtitle="days"
          valueClassName="text-orange-600"
          icon={<Clock className="h-4 w-4 text-orange-600" />}
        />
      </div>

      {/* Navigation Tabs */}
      <div className="flex gap-4 border-b mb-6">
        <Link
          href={`/sites/${siteId}/content-decay`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Queue
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/board`}
          className="px-4 py-2 text-muted-foreground hover:text-foreground"
        >
          Board
        </Link>
        <Link
          href={`/sites/${siteId}/content-decay/recovery`}
          className="px-4 py-2 border-b-2 border-primary font-medium"
        >
          Recovery
        </Link>
      </div>

      {/* Timeline Chart */}
      <div
        data-testid="timeline-chart-section"
        className="bg-white rounded-lg border p-6 shadow-sm mb-6"
      >
        <h2 className="text-lg font-semibold mb-4">Recovery Timeline</h2>
        <div data-testid="timeline-chart" className="h-64">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={recovery.timeline}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis
                dataKey="date"
                tickFormatter={(v) => new Date(v).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
              />
              <YAxis />
              <Tooltip
                labelFormatter={(v) => new Date(v).toLocaleDateString()}
                formatter={(value: number, name: string) => [
                  name === 'recovered' ? `${value} pages` : value.toLocaleString(),
                  name === 'recovered' ? 'Recovered' : 'Clicks'
                ]}
              />
              <Legend />
              <Line
                type="monotone"
                dataKey="recovered"
                stroke="#22c55e"
                strokeWidth={2}
                name="Recovered"
                dot={{ r: 4 }}
              />
              <Line
                type="monotone"
                dataKey="clicks"
                stroke="#3b82f6"
                strokeWidth={2}
                name="Clicks"
                dot={{ r: 4 }}
              />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Before/After Comparison Table */}
      <div
        data-testid="comparison-table-section"
        className="bg-white rounded-lg border shadow-sm mb-6 overflow-hidden"
      >
        <div className="p-4 border-b">
          <h2 className="text-lg font-semibold">Before/After Comparison</h2>
        </div>
        <div data-testid="table-container" className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-muted/50">
              <tr>
                <th className="p-3 text-left font-medium">Page</th>
                <th className="p-3 text-left font-medium">Before</th>
                <th className="p-3 text-left font-medium">After</th>
                <th className="p-3 text-left font-medium">Delta</th>
              </tr>
            </thead>
            <tbody>
              {recovery.comparisons.map((item) => (
                <tr
                  key={item.id}
                  data-testid={`comparison-row-${item.id}`}
                  className="border-t hover:bg-muted/50 cursor-pointer"
                >
                  <td className="p-3">
                    <div className="font-medium truncate max-w-xs">
                      {item.pageTitle}
                    </div>
                    <div className="text-xs text-muted-foreground truncate">
                      {item.pageUrl.replace(/^https?:\/\/[^\/]+/, '')}
                    </div>
                  </td>
                  <td data-testid={`before-${item.id}`} className="p-3">
                    <div className="font-mono">{item.before.clicks}</div>
                    <div className="text-xs text-muted-foreground">
                      {item.before.impressions.toLocaleString()} impr
                    </div>
                  </td>
                  <td data-testid={`after-${item.id}`} className="p-3">
                    <div className="font-mono">{item.after.clicks}</div>
                    <div className="text-xs text-muted-foreground">
                      {item.after.impressions.toLocaleString()} impr
                    </div>
                  </td>
                  <td data-testid={`delta-${item.id}`} className="p-3">
                    <div
                      data-testid={`delta-clicks-${item.id}`}
                      className={`font-mono ${
                        item.delta.clicks > 0 ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {item.delta.clicks > 0 ? '+' : ''}{item.delta.clicks}
                    </div>
                    <div
                      data-testid={`delta-impr-${item.id}`}
                      className="text-xs text-green-600"
                    >
                      +{item.delta.impressions.toLocaleString()}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Recovery Stories */}
      <div
        data-testid="stories-section"
        className="bg-white rounded-lg border p-6 shadow-sm"
      >
        <h2 className="text-lg font-semibold mb-4">Recovery Stories</h2>
        <div
          data-testid="stories-grid"
          className="grid grid-cols-1 md:grid-cols-2 gap-4"
        >
          {recovery.stories.map((story) => (
            <div
              key={story.id}
              data-testid={`story-card-${story.id}`}
              className="border rounded-lg p-4"
            >
              <h3
                data-testid={`story-title-${story.id}`}
                className="font-medium mb-2"
              >
                {story.pageTitle}
              </h3>
              <p className="text-sm text-muted-foreground mb-3">
                {story.summary}
              </p>
              <div className="flex gap-4 text-sm">
                <div>
                  <span className="text-muted-foreground">Clicks recovered:</span>
                  <span className="font-semibold text-green-600 ml-1">
                    +{story.clicksRecovered}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Days:</span>
                  <span className="font-semibold ml-1">{story.daysToRecovery}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
