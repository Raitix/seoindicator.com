/**
 * Audits List Page
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Page component that displays the list of audits for a site
 */

import * as React from 'react';
import { AuditList } from '@/components/audit/AuditList';

// ============================================================
// Route Configuration
// ============================================================

// Force dynamic rendering to ensure the route works in dev mode and E2E tests
export const dynamic = 'force-dynamic';

// ============================================================
// Page Component
// ============================================================

export default async function AuditsPage({ params }: { params: Promise<{ siteId: string }> }) {
  const { siteId } = await params;
  
  return (
    <main className="container mx-auto px-4 py-8 max-w-4xl">
      <AuditList siteId={siteId} />
    </main>
  );
}
