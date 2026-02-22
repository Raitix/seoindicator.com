import type { ReactNode } from "react";

export const dynamicParams = false;

export function generateStaticParams(): Array<{ siteId: string }> {
  return [{ siteId: "1" }];
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  return children;
}
