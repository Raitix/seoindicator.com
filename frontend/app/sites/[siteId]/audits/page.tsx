/**
 * Audits List Page
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Page component that displays the list of audits for a site
 */

'use client';

import * as React from 'react';
import { useParams } from 'next/navigation';
import { AuditList } from '@/components/audit/AuditList';

// ============================================================
// Page Component
// ============================================================

export default function AuditsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <main className="container mx-auto px-4 py-8 max-w-4xl">
      <AuditList siteId={siteId} />
    </main>
  );
}
