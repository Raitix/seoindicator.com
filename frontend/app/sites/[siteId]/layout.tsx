import type { ReactNode } from "react";

// Enable dynamic params to allow E2E tests and dev mode to work with any siteId
// In production with static export, generateStaticParams controls which pages are pre-built
export const dynamicParams = true;

// Force dynamic rendering to ensure the route works in dev mode and E2E tests
export const dynamic = 'force-dynamic';

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
