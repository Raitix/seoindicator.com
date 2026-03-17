import type { ReactNode } from "react";

// Static export: dynamicParams must be false (or omitted) for static export compatibility
// generateStaticParams in child pages controls which paths are pre-built
// export const dynamicParams = false; // Default is false for static export

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
