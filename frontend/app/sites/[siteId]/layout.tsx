import type { ReactNode } from "react";

export function generateStaticParams() {
  return [];
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
