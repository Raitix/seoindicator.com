import type { ReactNode } from "react";

export const dynamicParams = false;

export function generateStaticParams(): Array<{ siteId: string; keywordId: string }> {
  return [{ siteId: "1", keywordId: "1" }];
}

export default function KeywordLayout({ children }: { children: ReactNode }) {
  return children;
}
