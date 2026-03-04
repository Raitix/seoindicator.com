"use client";

import * as React from "react";
import Link from "next/link";
import { cn } from "@/lib/utils";

// ============================================
// Types
// ============================================

export interface SkipLinkProps {
  /** Target element ID to skip to */
  targetId?: string;
  /** Link text */
  label?: string;
  /** Additional CSS classes */
  className?: string;
}

// ============================================
// Component
// ============================================

/**
 * SkipLink Component
 *
 * A skip-to-content link for keyboard users that allows them to bypass
 * repetitive navigation and jump directly to the main content.
 *
 * The link is visually hidden until focused, following WCAG 2.1 AA guidelines.
 *
 * @example
 * ```tsx
 * // In your layout
 * <SkipLink />
 * <header>Navigation...</header>
 * <main id="main-content">Content...</main>
 * ```
 */
export function SkipLink({
  targetId = "main-content",
  label = "Skip to main content",
  className,
}: SkipLinkProps) {
  const [isVisible, setIsVisible] = React.useState(false);

  const handleFocus = () => setIsVisible(true);
  const handleBlur = () => setIsVisible(false);

  return (
    <Link
      href={`#${targetId}`}
      className={cn(
        // Visually hidden by default (sr-only)
        "sr-only",
        // Visible when focused
        "focus:not-sr-only",
        "focus:absolute",
        "focus:top-4",
        "focus:left-4",
        "focus:z-50",
        // Styling when visible
        "focus:px-4",
        "focus:py-2",
        "focus:rounded-lg",
        "focus:bg-blue-600",
        "focus:text-white",
        "focus:font-medium",
        "focus:no-underline",
        "focus:shadow-lg",
        // Focus ring
        "focus:outline-none",
        "focus:ring-2",
        "focus:ring-blue-400",
        "focus:ring-offset-2",
        "focus:ring-offset-slate-950",
        // Animation
        "transition-all",
        "duration-200",
        // Hover state
        "hover:bg-blue-700",
        className
      )}
      onFocus={handleFocus}
      onBlur={handleBlur}
      data-testid="skip-link"
      aria-label={label}
    >
      {label}
    </Link>
  );
}

// ============================================
// SkipToMain Component (for use in main element)
// ============================================

export interface SkipToMainProps {
  /** Element ID (should match SkipLink targetId) */
  id?: string;
  /** Content to render inside main */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
}

/**
 * SkipToMain Component
 *
 * Wrapper for the main content area that provides the target ID
 * for the skip link and proper ARIA landmark.
 *
 * @example
 * ```tsx
 * <SkipToMain>
 *   <h1>Dashboard</h1>
 *   <p>Content...</p>
 * </SkipToMain>
 * ```
 */
export function SkipToMain({
  id = "main-content",
  children,
  className,
}: SkipToMainProps) {
  return (
    <main
      id={id}
      tabIndex={-1}
      className={cn("outline-none", className)}
      data-testid="main-content"
      role="main"
      aria-label="Main content"
    >
      {children}
    </main>
  );
}

export default SkipLink;
