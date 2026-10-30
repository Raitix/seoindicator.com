import type { ReactNode } from "react";

export const dynamicParams = false;

export function generateStaticParams(): Array<{ id: string }> {
  return [{ id: "preview" }];
}

export default function LocationDashboardLayout({ children }: { children: ReactNode }) {
  return children;
}
