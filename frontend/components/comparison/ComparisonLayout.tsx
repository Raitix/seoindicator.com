import type { ReactNode } from "react";
import { Card, CardContent, CardHeader } from "@/components/ui/card";

interface ComparisonLayoutProps {
  header: ReactNode;
  controls: ReactNode;
  toggles: ReactNode;
  table: ReactNode;
  pagination: ReactNode;
}

export default function ComparisonLayout({
  header,
  controls,
  toggles,
  table,
  pagination,
}: ComparisonLayoutProps) {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader className="gap-3">
        {header}
        {controls}
        {toggles}
      </CardHeader>
      <CardContent>
        {table}
        {pagination}
      </CardContent>
    </Card>
  );
}
