/**
 * ButtonLoading Component - TASK-28-11
 *
 * Loading state wrapper for buttons using CircularProgress.
 * Shows spinner while loading and disables the button.
 *
 * @see TESTS/TEST-28-11.md
 */

"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { CircularProgress } from "@/components/ui/CircularProgress";

/**
 * ButtonLoading Props
 */
export interface ButtonLoadingProps {
  /** Whether the button is in loading state */
  isLoading: boolean;
  /** Button text when loading */
  loadingText?: string;
  /** Button text when not loading */
  children: React.ReactNode;
  /** Progress value for determinate mode */
  progressValue?: number;
  /** Size of the circular progress */
  size?: "sm" | "md" | "lg";
  /** Color of the circular progress */
  color?: "primary" | "secondary" | "white";
  /** Additional CSS classes */
  className?: string;
  /** Button disabled state (independent of loading) */
  disabled?: boolean;
  /** Click handler */
  onClick?: () => void;
  /** Button type */
  type?: "button" | "submit" | "reset";
  /** Button variant */
  variant?: "default" | "outline" | "ghost" | "link" | "destructive";
}

/**
 * ButtonLoading Component
 *
 * A button that shows a circular progress indicator during loading.
 *
 * @example
 * ```tsx
 * <ButtonLoading
 *   isLoading={isSubmitting}
 *   loadingText="Analyzing..."
 *   onClick={handleAnalyze}
 * >
 *   Analyze
 * </ButtonLoading>
 * ```
 */
export function ButtonLoading({
  isLoading,
  loadingText,
  children,
  progressValue,
  size = "sm",
  color = "white",
  className,
  disabled,
  onClick,
  type = "button",
  variant = "default",
}: ButtonLoadingProps) {
  const isDisabled = disabled || isLoading;

  // Variant styles
  const variantStyles: Record<string, string> = {
    default: "bg-brand-primary hover:bg-brand-primary/90 text-white",
    outline: "border border-slate-700 text-slate-200 hover:bg-slate-800",
    ghost: "text-slate-200 hover:bg-slate-800",
    link: "text-brand-primary hover:underline",
    destructive: "bg-error hover:bg-error/90 text-white",
  };

  return (
    <button
      data-testid={isLoading ? "loading-button" : "button"}
      type={type}
      disabled={isDisabled}
      onClick={onClick}
      className={cn(
        "inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium transition-colors",
        "focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-slate-950",
        "disabled:opacity-50 disabled:cursor-not-allowed",
        variantStyles[variant],
        className
      )}
    >
      {isLoading && (
        <CircularProgress value={progressValue} size={size} color={color} />
      )}
      <span>{isLoading && loadingText ? loadingText : children}</span>
    </button>
  );
}

/**
 * LoadingButton - Higher-order component for adding loading state to any button
 */
export function withLoadingState<P extends object>(
  ButtonComponent: React.ComponentType<P>
): React.FC<P & { isLoading?: boolean; loadingText?: string }> {
  return ({ isLoading, loadingText, children, ...props }: any) => {
    if (isLoading) {
      return (
        <ButtonComponent {...props} disabled>
          <CircularProgress size="sm" color="white" />
          {loadingText || children}
        </ButtonComponent>
      );
    }
    return <ButtonComponent {...props}>{children}</ButtonComponent>;
  };
}
