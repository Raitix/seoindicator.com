import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

interface SummaryCardProps {
  title: string;
  value: number | string;
  valueClassName?: string;
}

export default function SummaryCard({ title, value, valueClassName }: SummaryCardProps) {
  return (
    <Card className="border-slate-800 bg-slate-900/60">
      <CardHeader>
        <CardDescription>{title}</CardDescription>
        <CardTitle className={`text-3xl text-white ${valueClassName ?? ""}`.trim()}>{value}</CardTitle>
      </CardHeader>
    </Card>
  );
}
