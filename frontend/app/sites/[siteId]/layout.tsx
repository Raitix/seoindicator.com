import type { ReactNode } from "react";

// Static export: dynamicParams must be false (or omitted) for static export compatibility
// generateStaticParams in child pages controls which paths are pre-built
// export const dynamicParams = false; // Default is false for static export

// Generate static params for static export compatibility
// Returns placeholder siteId to satisfy static export requirements
// Actual site data is loaded client-side
export function generateStaticParams() {
  return [{ siteId: "1" }];
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
