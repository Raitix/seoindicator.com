import type { ReactNode } from "react";

export const dynamicParams = false;

export function generateStaticParams(): Array<{ siteId: string; locationId: string }> {
  return [{ siteId: "preview", locationId: "preview" }];
}

export default function SiteLocationDashboardLayout({ children }: { children: ReactNode }) {
  return children;
}
