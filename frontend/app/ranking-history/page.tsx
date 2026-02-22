import RankingHistoryChart, { RankingHistoryPoint } from "@/components/charts/RankingHistoryChart";

const sampleData: RankingHistoryPoint[] = [
  { date: "2026-01-25", position: 18 },
  { date: "2026-01-28", position: 21 },
  { date: "2026-02-01", position: 14 },
  { date: "2026-02-04", position: 9 },
  { date: "2026-02-08", position: 12 },
  { date: "2026-02-11", position: 7 },
  { date: "2026-02-14", position: 5 },
  { date: "2026-02-17", position: 8 },
  { date: "2026-02-20", position: 4 },
  { date: "2026-02-22", position: 11 },
  { date: "2026-02-23", position: 3 },
  { date: "2026-02-24", position: 2 },
  { date: "2026-02-25", position: null },
  { date: "2026-02-26", position: 16 },
  { date: "2026-02-27", position: 10 },
  { date: "2026-02-28", position: 6 },
  { date: "2026-03-01", position: 4 },
];

export default function RankingHistoryPage() {
  return (
    <div className="min-h-screen bg-slate-950">
      <div className="container mx-auto px-4 py-10">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">Ranking History</h1>
          <p className="text-sm text-slate-300">
            Demo view for validating the Ranking History chart component in production.
          </p>
        </div>
        <RankingHistoryChart keywordName="best seo tools" data={sampleData} initialRange="30d" />
      </div>
    </div>
  );
}
