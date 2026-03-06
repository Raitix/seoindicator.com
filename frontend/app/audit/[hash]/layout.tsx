/**
 * Public Audit Layout
 * Layout wrapper for public audit pages (no authentication required)
 * @module app/audit/[hash]/layout
 */

import type { ReactNode } from 'react';

/**
 * Layout props interface
 */
interface LayoutProps {
  children: ReactNode;
}

/**
 * Public Audit Layout Component
 * Provides a clean layout for public audit pages without auth wrapper
 * @param props - Layout props
 * @returns JSX element
 */
export default function AuditLayout({ children }: LayoutProps) {
  return (
    <div className="min-h-screen bg-slate-950">
      {children}
    </div>
  );
}
