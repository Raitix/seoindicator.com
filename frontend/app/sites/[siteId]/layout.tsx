import type { ReactNode } from "react";

// Enable dynamic params to allow E2E tests and dev mode to work with any siteId
// In production with static export, generateStaticParams controls which pages are pre-built
export const dynamicParams = true;

export function generateStaticParams(): Array<{ siteId: string }> {
  // Return siteId "1" for static export, but allow other IDs in dev mode
  return [{ siteId: "1" }];
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
