/**
 * CostEstimate Component
 *
 * Displays cost estimation for model usage with balance comparison.
 *
 * @see TASK-26-22
 */

'use client';

import * as React from 'react';
import { DollarSign, AlertTriangle, Zap, ExternalLink, Loader2, Brain } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useModelPricing } from '@/lib/api/models';
import { formatCost } from '@/lib/api/models';

// ==================== Types ====================

export interface CostEstimateProps {
  modelId: number | null | undefined;
  analysisType?: 'quick' | 'standard' | 'deep';
  userBalance?: number;
  onAddCredits?: () => void;
  className?: string;
}

// ==================== Analysis Type Config ====================

const analysisTypeConfig = {
  quick: {
    label: 'Quick Analysis',
    icon: Zap,
    description: '~500 tokens',
  },
  standard: {
    label: 'Standard Analysis',
    icon: DollarSign,
    description: '~2,000 tokens',
  },
  deep: {
    label: 'Deep Analysis',
    icon: Brain,
    description: '~5,000 tokens',
  },
};

// ==================== Component ====================

export function CostEstimate({
  modelId,
  analysisType = 'standard',
  userBalance,
  onAddCredits,
  className,
}: CostEstimateProps) {
  const { pricing, isLoading, error } = useModelPricing(modelId, analysisType);

  // Get estimate for current analysis type
  const getEstimate = () => {
    if (!pricing) return null;

    const typeMap = {
      quick: pricing.estimates.quick_analysis,
      standard: pricing.estimates.standard_analysis,
      deep: pricing.estimates.deep_analysis,
    };

    return typeMap[analysisType];
  };

  const estimate = getEstimate();
  const isFree = estimate?.cost === 0 || pricing?.free_tier.available;
  const hasInsufficientBalance =
    userBalance !== undefined &&
    estimate !== null &&
    !isFree &&
    userBalance < estimate.cost;

  const neededAmount = hasInsufficientBalance && estimate
    ? estimate.cost - userBalance
    : 0;

  const config = analysisTypeConfig[analysisType];
  const Icon = config.icon;

  // Loading state
  if (isLoading) {
    return (
      <div
        data-testid="cost-estimate"
        className={cn('flex flex-col gap-2 p-4 rounded-lg bg-muted/50', className)}
      >
        <Skeleton className="h-4 w-24" />
        <Skeleton className="h-6 w-20" />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div
        data-testid="cost-estimate"
        className={cn(
          'flex flex-col gap-2 p-4 rounded-lg bg-destructive/10 text-destructive',
          className
        )}
      >
        <span className="text-sm">Failed to load pricing</span>
      </div>
    );
  }

  // No model selected
  if (!modelId || !estimate) {
    return null;
  }

  return (
    <div
      data-testid="cost-estimate"
      className={cn('flex flex-col gap-3 p-4 rounded-lg bg-muted/50', className)}
    >
      {/* Analysis Type Header */}
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Icon className="h-4 w-4" />
        <span>{config.label}</span>
        <span className="text-xs">({config.description})</span>
      </div>

      {/* Cost Display */}
      <div className="flex items-baseline gap-2">
        <span
          data-testid="cost-value"
          className={cn(
            'text-2xl font-bold',
            isFree ? 'text-green-500' : 'text-foreground'
          )}
        >
          {isFree ? 'Free' : formatCost(estimate.cost)}
        </span>
        {!isFree && (
          <span
            data-testid="token-count"
            className="text-sm text-muted-foreground"
          >
            {estimate.tokens.toLocaleString()} tokens
          </span>
        )}
      </div>

      {/* Free tier indicator */}
      {isFree && (
        <div
          data-testid="free-label"
          className="flex items-center gap-2 text-sm text-green-500"
        >
          <Zap className="h-4 w-4" />
          <span>Free tier model</span>
        </div>
      )}

      {/* Balance comparison */}
      {userBalance !== undefined && !isFree && (
        <div className="flex items-center gap-2 text-sm">
          <span className="text-muted-foreground">Your balance:</span>
          <span
            className={cn(
              'font-medium',
              hasInsufficientBalance ? 'text-destructive' : 'text-foreground'
            )}
          >
            {formatCost(userBalance)}
          </span>
          {!hasInsufficientBalance && (
            <span className="text-muted-foreground">
              (remaining: {formatCost(userBalance - estimate.cost)})
            </span>
          )}
        </div>
      )}

      {/* Insufficient balance warning */}
      {hasInsufficientBalance && (
        <div
          data-testid="insufficient-warning"
          className="flex flex-col gap-2 p-3 rounded-lg bg-destructive/10 text-destructive"
          role="alert"
        >
          <div className="flex items-center gap-2">
            <AlertTriangle className="h-4 w-4" data-testid="alert-icon" />
            <span className="font-medium">Insufficient balance</span>
          </div>
          <p className="text-sm">
            You need {formatCost(neededAmount)} more to run this analysis.
          </p>
          {onAddCredits && (
            <Button
              data-testid="add-credits-btn"
              variant="destructive"
              size="sm"
              onClick={onAddCredits}
              className="mt-1 w-fit"
            >
              Add Credits
              <ExternalLink className="ml-2 h-4 w-4" />
            </Button>
          )}
        </div>
      )}
    </div>
  );
}

export default CostEstimate;
