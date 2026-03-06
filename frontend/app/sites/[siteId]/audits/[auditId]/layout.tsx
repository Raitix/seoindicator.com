/**
 * Audit Detail Layout
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 */

import type { ReactNode } from 'react';

export const dynamicParams = false;

export function generateStaticParams(): Array<{ siteId: string; auditId: string }> {
  // Return placeholder params for static export
  // Actual audit pages will be dynamically rendered
  return [{ siteId: '1', auditId: '1' }];
}

export default function AuditLayout({ children }: { children: ReactNode }) {
  return children;
}
