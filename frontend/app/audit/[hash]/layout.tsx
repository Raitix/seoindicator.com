/**
 * Public Audit Layout
 * Layout wrapper for public audit pages (no authentication required)
 * @module app/audit/[hash]/layout
 */

import { ReactNode } from 'react';

/**
 * Props for the audit layout component
 */
interface AuditLayoutProps {
  children: ReactNode;
}

/**
 * Audit Layout Component
 * Provides a clean layout for public audit pages
 * No authentication wrapper - pages are publicly accessible
 * @param props - Layout props with children
 * @returns JSX element
 */
export default function AuditLayout({ children }: AuditLayoutProps) {
  return (
    <div className="min-h-screen bg-gray-50">
      {children}
    </div>
  );
}
