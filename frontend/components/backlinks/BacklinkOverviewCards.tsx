'use client';

import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
  ArrowUpRight,
  ArrowDownRight,
  ExternalLink,
  TrendingUp,
  Building2,
  Link2,
  Shield,
  Activity,
} from 'lucide-react';
import type { BacklinkSummary } from './types';

// ==================== Types ====================

interface BacklinkOverviewCardsProps {
  summary: BacklinkSummary | null;
  loading?: boolean;
  className?: string;
}

interface MetricCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: {
    value: number;
    isPositive: boolean;
  };
  progress?: number;
  loading?: boolean;
}

// ==================== Helper Functions ====================

/**
 * Format number with K/M suffix
 */
function formatNumber(value: number | null | undefined): string {
  if (value === null || value === undefined) return '0';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K`;
  return value.toLocaleString();
}

/**
 * Format percentage
 */
function formatPercent(value: number | null | undefined): string {
  if (value === null || value === undefined) return '0%';
  return `${value.toFixed(1)}%`;
}

/**
 * Get profile score color
 */
function getScoreColor(score: number): string {
  if (score >= 80) return 'text-green-500';
  if (score >= 60) return 'text-yellow-500';
  if (score >= 40) return 'text-orange-500';
  return 'text-red-500';
}

/**
 * Get profile score progress color
 */
function getScoreProgressColor(score: number): string {
  if (score >= 80) return 'bg-green-500';
  if (score >= 60) return 'bg-yellow-500';
  if (score >= 40) return 'bg-orange-500';
  return 'bg-red-500';
}

// ==================== Skeleton Component ====================

function CardSkeleton() {
  return (
    <Card className="animate-pulse">
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <div className="h-4 w-24 bg-muted rounded" />
          <div className="h-8 w-8 bg-muted rounded" />
        </div>
      </CardHeader>
      <CardContent>
        <div className="h-8 w-20 bg-muted rounded mb-1" />
        <div className="h-3 w-32 bg-muted rounded" />
      </CardContent>
    </Card>
  );
}

// ==================== Metric Card Component ====================

const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  subtitle,
  icon,
  trend,
  progress,
  loading,
}) => {
  if (loading) {
    return <CardSkeleton />;
  }

  return (
    <Card>
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-medium text-muted-foreground">
            {title}
          </CardTitle>
          <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
            {icon}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="flex items-baseline gap-2">
          <div className="text-2xl font-bold">{value}</div>
          {trend && (
            <div
              className={`flex items-center text-sm ${
                trend.isPositive ? 'text-green-500' : 'text-red-500'
              }`}
            >
              {trend.isPositive ? (
                <ArrowUpRight className="h-4 w-4" />
              ) : (
                <ArrowDownRight className="h-4 w-4" />
              )}
              <span>{Math.abs(trend.value).toFixed(1)}%</span>
            </div>
          )}
        </div>
        {subtitle && (
          <p className="text-xs text-muted-foreground mt-1">{subtitle}</p>
        )}
        {progress !== undefined && (
          <div className="mt-3">
            <Progress value={progress} className="h-2" />
          </div>
        )}
      </CardContent>
    </Card>
  );
};

// ==================== Profile Score Card Component ====================

interface ProfileScoreCardProps {
  score: number;
  loading?: boolean;
}

const ProfileScoreCard: React.FC<ProfileScoreCardProps> = ({ score, loading }) => {
  if (loading) {
    return <CardSkeleton />;
  }

  return (
    <Card>
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-medium text-muted-foreground">
            Profile Score
          </CardTitle>
          <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
            <Shield className="h-4 w-4" />
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-bold ${getScoreColor(score)}`}>
          {score.toFixed(0)}
          <span className="text-sm font-normal text-muted-foreground">/100</span>
        </div>
        <div className="mt-3">
          <Progress value={score} className="h-2" />
        </div>
        <p className="text-xs text-muted-foreground mt-2">
          {score >= 80
            ? 'Excellent backlink profile'
            : score >= 60
            ? 'Good backlink profile'
            : score >= 40
            ? 'Fair backlink profile'
            : 'Needs improvement'}
        </p>
      </CardContent>
    </Card>
  );
};

// ==================== Empty State Component ====================

function EmptyState() {
  return (
    <Card className="col-span-full">
      <CardContent className="py-8 text-center">
        <Link2 className="h-12 w-12 mx-auto text-muted-foreground opacity-50 mb-4" />
        <p className="text-muted-foreground">No backlink data available yet</p>
        <p className="text-sm text-muted-foreground mt-1">
          Run a backlink analysis to see your metrics
        </p>
      </CardContent>
    </Card>
  );
}

// ==================== Main Component ====================

const BacklinkOverviewCards: React.FC<BacklinkOverviewCardsProps> = ({
  summary,
  loading = false,
  className,
}) => {
  // Empty state
  if (!loading && !summary) {
    return (
      <div className={className} data-testid="overview-cards-empty">
        <EmptyState />
      </div>
    );
  }

  return (
    <div
      className={`grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 ${className || ''}`}
      data-testid="overview-cards"
    >
      {/* Total Backlinks */}
      <MetricCard
        title="Total Backlinks"
        value={formatNumber(summary?.totalBacklinks)}
        subtitle="Total inbound links"
        icon={<Link2 className="h-4 w-4" />}
        trend={
          summary?.growthRate !== undefined
            ? {
                value: summary.growthRate,
                isPositive: summary.growthRate >= 0,
              }
            : undefined
        }
        loading={loading}
      />

      {/* Referring Domains */}
      <MetricCard
        title="Referring Domains"
        value={formatNumber(summary?.referringDomains)}
        subtitle="Unique domains linking"
        icon={<Building2 className="h-4 w-4" />}
        loading={loading}
      />

      {/* Dofollow Ratio */}
      <MetricCard
        title="Dofollow Ratio"
        value={formatPercent(summary?.dofollowRatio)}
        subtitle="SEO-passing links"
        icon={<ExternalLink className="h-4 w-4" />}
        progress={summary?.dofollowRatio}
        loading={loading}
      />

      {/* Average Domain Trust */}
      <MetricCard
        title="Avg. Domain Trust"
        value={summary?.avgDomainTrust?.toFixed(1) ?? '0'}
        subtitle="Average trust score"
        icon={<Shield className="h-4 w-4" />}
        progress={summary?.avgDomainTrust}
        loading={loading}
      />

      {/* Growth Rate */}
      <MetricCard
        title="Growth Rate"
        value={formatPercent(summary?.growthRate)}
        subtitle="30-day change"
        icon={<TrendingUp className="h-4 w-4" />}
        trend={
          summary?.growthRate !== undefined
            ? {
                value: summary.growthRate,
                isPositive: summary.growthRate >= 0,
              }
            : undefined
        }
        loading={loading}
      />

      {/* Profile Score */}
      <ProfileScoreCard
        score={summary?.profileScore ?? 0}
        loading={loading}
      />
    </div>
  );
};

export default BacklinkOverviewCards;

// Named export for flexibility
export { BacklinkOverviewCards };
