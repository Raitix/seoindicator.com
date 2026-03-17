/**
 * Audit Detail Layout
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 */

import type { ReactNode } from 'react';

// Enable dynamic params for dev mode and E2E testing
// This allows any audit ID to be accessed without pre-generation
export const dynamicParams = true;

export function generateStaticParams(): Array<{ siteId: string; auditId: string }> {
  // Return empty array for dynamic rendering
  // Actual audit pages will be client-side rendered
  return [];
}

export default function AuditLayout({ children }: { children: ReactNode }) {
  return children;
}
