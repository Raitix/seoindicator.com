import { Card, CardContent, CardHeader } from "@/components/ui/card";

export default function ComparisonSkeleton() {
  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Card key={index} className="border-slate-800 bg-slate-900/60">
            <CardHeader>
              <div className="h-4 w-24 animate-pulse rounded bg-slate-800" />
              <div className="mt-3 h-8 w-16 animate-pulse rounded bg-slate-800" />
            </CardHeader>
          </Card>
        ))}
      </div>
      <Card className="border-slate-800 bg-slate-900/60">
        <CardHeader>
          <div className="h-5 w-40 animate-pulse rounded bg-slate-800" />
          <div className="mt-3 h-4 w-72 animate-pulse rounded bg-slate-800" />
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {Array.from({ length: 5 }).map((_, index) => (
              <div key={index} className="h-10 w-full animate-pulse rounded bg-slate-800" />
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
