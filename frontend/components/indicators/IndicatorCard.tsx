'use client';

import * as React from 'react';
import Link from 'next/link';
import { cva, type VariantProps } from 'class-variance-authority';
import { AlertCircle, AlertTriangle, CheckCircle, Minus, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { Skeleton } from '@/components/ui/skeleton';
import { IndicatorIcon, type LucideIconName } from './IndicatorIcon';

// ============================================================================
// Hooks
// ============================================================================

/**
 * Hook to check if the user prefers reduced motion.
 * Returns false during SSR to avoid hydration mismatches.
 */
function usePrefersReducedMotion(): boolean {
  const [prefersReducedMotion, setPrefersReducedMotion] = React.useState(false);

  React.useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    setPrefersReducedMotion(mediaQuery.matches);

    const handler = (event: MediaQueryListEvent) => {
      setPrefersReducedMotion(event.matches);
    };

    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, []);

  return prefersReducedMotion;
}

// ============================================================================
// Status Configuration
// ============================================================================

type IndicatorStatus = 'good' | 'warning' | 'critical' | 'neutral';

interface StatusConfig {
  icon: LucideIcon;
  label: string;
  badgeVariant: 'default' | 'secondary' | 'destructive' | 'outline';
  colorClass: string;
}

const statusConfig: Record<IndicatorStatus, StatusConfig> = {
  good: {
    icon: CheckCircle,
    label: 'Good',
    badgeVariant: 'default',
    colorClass: 'text-emerald-600 dark:text-emerald-400',
  },
  warning: {
    icon: AlertTriangle,
    label: 'Warning',
    badgeVariant: 'secondary',
    colorClass: 'text-amber-500 dark:text-amber-400',
  },
  critical: {
    icon: AlertCircle,
    label: 'Critical',
    badgeVariant: 'destructive',
    colorClass: 'text-destructive',
  },
  neutral: {
    icon: Minus,
    label: 'Neutral',
    badgeVariant: 'outline',
    colorClass: 'text-muted-foreground',
  },
};

// ============================================================================
// Variants
// ============================================================================

const indicatorCardVariants = cva(
  // Base styles
  'group relative flex flex-col gap-3 rounded-xl border bg-card p-4 transition-all duration-200 ease-out',
  {
    variants: {
      loading: {
        true: 'pointer-events-none cursor-not-allowed opacity-70',
        false: 'cursor-pointer hover:scale-[1.02] hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5',
      },
    },
    defaultVariants: {
      loading: false,
    },
  }
);

// ============================================================================
// Types
// ============================================================================

export interface IndicatorCardProps
  extends Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, 'href'>,
    VariantProps<typeof indicatorCardVariants> {
  /** Unique identifier for the card */
  id: string;
  /** Lucide icon name or custom React node */
  icon: LucideIconName | React.ReactNode;
  /** Indicator title */
  title: string;
  /** Current value */
  value: string | number;
  /** Description text */
  description: string;
  /** Status indicator */
  status: IndicatorStatus;
  /** Link destination */
  href: string;
  /** Optional tooltip override */
  tooltip?: string;
  /** Loading state */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================================
// Sub-Components
// ============================================================================

interface StatusBadgeProps {
  status: IndicatorStatus;
  className?: string;
}

function StatusBadge({ status, className }: StatusBadgeProps) {
  const config = statusConfig[status];
  const StatusIcon = config.icon;

  return (
    <Badge
      variant={config.badgeVariant}
      role="status"
      className={cn('gap-1', className)}
      data-status={status}
      aria-label={`Status: ${config.label}`}
    >
      <StatusIcon className="size-3" aria-hidden="true" />
      {config.label}
    </Badge>
  );
}

interface CardSkeletonProps {
  className?: string;
}

function CardSkeleton({ className }: CardSkeletonProps) {
  return (
    <div className={cn('flex flex-col gap-3', className)}>
      <div className="flex items-start justify-between gap-2">
        <Skeleton className="size-10 rounded-lg" />
        <Skeleton className="h-5 w-16 rounded-full" />
      </div>
      <div className="flex flex-col gap-1.5">
        <Skeleton className="h-4 w-24" />
        <Skeleton className="h-8 w-16" />
      </div>
      <Skeleton className="h-4 w-full" />
    </div>
  );
}

// ============================================================================
// Component
// ============================================================================

/**
 * IndicatorCard - A card component for displaying SEO indicators
 *
 * Displays an indicator with icon, title, value, status, and description.
 * Supports hover animations, loading states, and responsive design.
 *
 * @example
 * ```tsx
 * // Basic usage
 * <IndicatorCard
 *   id="keyword-rankings"
 *   icon="TrendingUp"
 *   title="Keyword Rankings"
 *   value="142"
 *   description="Keywords ranking in top 100"
 *   status="good"
 *   href="/dashboard"
 * />
 *
 * // With loading state
 * <IndicatorCard
 *   {...props}
 *   isLoading={true}
 * />
 *
 * // With custom tooltip
 * <IndicatorCard
 *   {...props}
 *   tooltip="Click to view details"
 * />
 * ```
 */
export const IndicatorCard = React.forwardRef<HTMLAnchorElement, IndicatorCardProps>(
  (
    {
      id,
      icon,
      title,
      value,
      description,
      status,
      href,
      tooltip,
      isLoading = false,
      className,
      ...props
    },
    ref
  ) => {
    // Handle invalid status by defaulting to neutral
    const safeStatus: IndicatorStatus = statusConfig[status] ? status : 'neutral';
    const config = statusConfig[safeStatus];
    const StatusIcon = config.icon;

    // Tooltip content - use provided tooltip or fall back to description
    const tooltipContent = tooltip ?? description;

    // Safe href handling
    const safeHref = href || '#';

    // Check for reduced motion preference
    const reducedMotion = usePrefersReducedMotion();

    // Loading state
    if (isLoading) {
      return (
        <div
          data-testid={`indicator-card-${id}`}
          data-slot="indicator-card"
          data-status={safeStatus}
          data-loading="true"
          className={cn(indicatorCardVariants({ loading: true }), className)}
          aria-busy="true"
          aria-label={`${title} loading`}
        >
          <CardSkeleton />
        </div>
      );
    }

    // Render card with tooltip wrapper
    const cardContent = (
      <Link
        ref={ref}
        href={safeHref}
        data-testid={`indicator-card-${id}`}
        data-slot="indicator-card"
        data-status={safeStatus}
        data-loading="false"
        className={cn(
          indicatorCardVariants({ loading: false }),
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background',
          reducedMotion && 'transition-none',
          className
        )}
        aria-label={`${title}: ${value}. ${description}. Status: ${config.label}`}
        {...props}
      >
        {/* Header: Icon + Status Badge */}
        <div className="flex items-start justify-between gap-2">
          {/* Icon */}
          <div className={cn(
            'flex size-10 items-center justify-center rounded-lg bg-primary/10',
            config.colorClass
          )}>
            {typeof icon === 'string' ? (
              <IndicatorIcon icon={icon as LucideIconName} size="md" />
            ) : (
              <span className="size-6">{icon}</span>
            )}
          </div>

          {/* Status Badge */}
          <StatusBadge status={safeStatus} />
        </div>

        {/* Content: Title + Value */}
        <div className="flex flex-col gap-0.5">
          <span className="text-sm font-medium text-muted-foreground truncate" title={title}>
            {title || <span className="opacity-50">No title</span>}
          </span>
          <span className="text-2xl font-bold tracking-tight truncate" title={String(value)}>
            {value !== undefined && value !== null && value !== '' 
              ? value 
              : <span className="opacity-50">—</span>}
          </span>
        </div>

        {/* Footer: Description */}
        <p className="text-sm text-muted-foreground line-clamp-2" title={description}>
          {description || <span className="opacity-50">No description</span>}
        </p>

        {/* Hover glow effect */}
        <div 
          className="pointer-events-none absolute inset-0 rounded-xl opacity-0 transition-opacity duration-200 group-hover:opacity-100"
          style={{
            background: 'radial-gradient(circle at center, rgba(var(--primary) / 0.05) 0%, transparent 70%)',
          }}
          aria-hidden="true"
        />
      </Link>
    );

    // Wrap with Tooltip if we have content
    if (tooltipContent) {
      return (
        <Tooltip>
          <TooltipTrigger asChild>
            {cardContent}
          </TooltipTrigger>
          <TooltipContent side="top" sideOffset={4}>
            {tooltipContent}
          </TooltipContent>
        </Tooltip>
      );
    }

    return cardContent;
  }
);

IndicatorCard.displayName = 'IndicatorCard';

// ============================================================================
// Exports
// ============================================================================

export default IndicatorCard;
export { statusConfig, type IndicatorStatus };
