'use client';

/**
 * Public Audit Page - Static Export Compatible
 * Uses query parameter for audit hash: /audit/?h=abc123
 * @module app/audit/page
 */

import * as React from 'react';
import { Suspense } from 'react';
import { Loader2 } from 'lucide-react';
import AuditPageClient from './AuditPageClient';

// ============================================================
// Loading Component
// ============================================================

function LoadingState() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="text-center">
        <Loader2 className="mx-auto h-12 w-12 animate-spin text-emerald-500" />
        <p className="mt-4 text-lg text-slate-400">Loading audit...</p>
      </div>
    </div>
  );
}

// ============================================================
// Main Component
// ============================================================

/**
 * Audit Page Component
 * Static export compatible - wraps AuditPageClient in Suspense boundary
 * URL format: /audit/?h=abc123
 */
export default function AuditPage() {
  return (
    <Suspense fallback={<LoadingState />}>
      <AuditPageClient />
    </Suspense>
  );
}
