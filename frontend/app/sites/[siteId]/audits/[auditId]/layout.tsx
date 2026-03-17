/**
 * Audit Detail Layout
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 */

import type { ReactNode } from 'react';

// Static export: dynamicParams must be false (or omitted) for static export compatibility
// generateStaticParams controls which audit paths are pre-built
// export const dynamicParams = false; // Default is false for static export

// Generate static params for static export compatibility
// Returns placeholder auditId to satisfy static export requirements
// Actual audit data is loaded client-side
export function generateStaticParams() {
  return [{ auditId: "1" }];
}

export default function AuditLayout({ children }: { children: ReactNode }) {
  return children;
}
