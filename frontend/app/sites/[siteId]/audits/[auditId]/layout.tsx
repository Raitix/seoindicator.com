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

// Force dynamic rendering to ensure the route works in dev mode and E2E tests
// Without this, the page might be statically generated with no paths
export const dynamic = 'force-dynamic';

export default function AuditLayout({ children }: { children: ReactNode }) {
  return children;
}
