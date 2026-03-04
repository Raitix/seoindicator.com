'use client';

import * as React from 'react';
import {
  Activity,
  AlertCircle,
  CheckCircle,
  ChevronDown,
  HelpCircle,
  Info,
  Search,
  Star,
  TrendingUp,
  Warning,
  XCircle,
  Zap,
  LucideIcon,
} from 'lucide-react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

// ============================================================================
// Icon Registry
// ============================================================================

const iconRegistry: Record<string, LucideIcon> = {
  Activity,
  AlertCircle,
  CheckCircle,
  ChevronDown,
  HelpCircle,
  Info,
  Search,
  Star,
  TrendingUp,
  Warning,
  XCircle,
  Zap,
};

export type LucideIconName = keyof typeof iconRegistry;

// ============================================================================
// Variants
// ============================================================================

const indicatorIconVariants = cva(
  // Base styles
  'inline-flex items-center justify-center rounded-sm [&_svg]:size-full [&_svg]:stroke-linecap-round [&_svg]:stroke-linejoin-round',
  {
    variants: {
      size: {
        sm: 'size-4 [&_svg]:stroke-[1.5]',
        md: 'size-6 [&_svg]:stroke-2',
        lg: 'size-8 [&_svg]:stroke-[2.5]',
      },
      color: {
        default: 'text-current',
        primary: 'text-primary dark:text-primary',
        success: 'text-emerald-600 dark:text-emerald-400',
        warning: 'text-amber-500 dark:text-amber-400',
        error: 'text-destructive dark:text-destructive',
      },
    },
    defaultVariants: {
      size: 'md',
      color: 'default',
    },
  }
);

// ============================================================================
// Types
// ============================================================================

export interface IndicatorIconProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof indicatorIconVariants> {
  /** Lucide icon name from the registry */
  icon?: LucideIconName;
  /** Custom SVG element to render instead of Lucide icon */
  customIcon?: React.ReactNode;
  /** Accessible label for screen readers */
  'aria-label'?: string;
}

// ============================================================================
// Component
// ============================================================================

/**
 * IndicatorIcon - A reusable icon wrapper for SEO indicators
 *
 * Renders Lucide icons or custom SVGs with consistent styling,
 * size variants, and color variants.
 *
 * @example
 * ```tsx
 * // Basic Lucide icon
 * <IndicatorIcon icon="Activity" />
 *
 * // With size and color
 * <IndicatorIcon icon="CheckCircle" size="lg" color="success" />
 *
 * // Custom SVG
 * <IndicatorIcon customIcon={<MyCustomSvg />} size="sm" />
 *
 * // With accessibility label
 * <IndicatorIcon icon="Search" aria-label="Search indicator" />
 * ```
 */
export const IndicatorIcon = React.forwardRef<HTMLSpanElement, IndicatorIconProps>(
  (
    {
      icon,
      customIcon,
      size = 'md',
      color = 'default',
      className,
      'aria-label': ariaLabel,
      ...props
    },
    ref
  ) => {
    // Return null if no icon provided
    if (!icon && !customIcon) {
      return null;
    }

    // Determine which icon to render
    const IconComponent = icon ? iconRegistry[icon] : null;

    // If icon name is provided but not found in registry, return null
    if (icon && !IconComponent) {
      return null;
    }

    // Accessibility attributes
    const accessibilityProps = ariaLabel
      ? {
          role: 'img' as const,
          'aria-label': ariaLabel,
        }
      : {
          'aria-hidden': 'true' as const,
        };

    return (
      <span
        ref={ref}
        data-slot="indicator-icon"
        data-size={size}
        data-color={color}
        className={cn(indicatorIconVariants({ size, color }), className)}
        {...accessibilityProps}
        {...props}
      >
        {customIcon ? (
          customIcon
        ) : IconComponent ? (
          <IconComponent focusable="false" />
        ) : null}
      </span>
    );
  }
);

IndicatorIcon.displayName = 'IndicatorIcon';

// ============================================================================
// Exports
// ============================================================================

export default IndicatorIcon;
