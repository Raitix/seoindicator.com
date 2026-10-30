/**
 * Audit Settings Page (Server Component Wrapper)
 *
 * Task: TASK-33-22 - Add Visibility Toggle to Audit Settings
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Settings page for individual audits, including visibility toggle
 * for publishing/unpublishing audits publicly.
 *
 * Route: /dashboard/audits/[id]/settings
 *
 * @module app/dashboard/audits/[id]/settings/page
 */

import { AuditSettingsClient } from './AuditSettingsClient';

// ============================================================
// Static Params for Static Export
// ============================================================

// Disable dynamic params for static export compatibility
// The placeholder ID is generated statically, actual routing is client-side
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns a placeholder ID to satisfy Next.js static export requirement.
 * The actual ID lookup is done client-side via AuditSettingsClient.
 */
export function generateStaticParams() {
  // Return a placeholder to satisfy Next.js static export
  // The client-side component handles actual ID routing
  return [{ id: '_' }];
}

// ============================================================
// Page Component
// ============================================================

/**
 * AuditSettingsPage - Server Component Wrapper
 *
 * This is a server component that provides static params for build.
 * The actual client-side logic is in AuditSettingsClient.
 */
export default function AuditSettingsPage() {
  return <AuditSettingsClient />;
}
