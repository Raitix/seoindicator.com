import DashboardSummary from "@/components/dashboard/DashboardSummary";

export default function DashboardPage() {
  return (
    <div className="min-h-screen bg-slate-950">
      <div className="container mx-auto px-4 py-10">
        <DashboardSummary siteId="1" />
      </div>
    </div>
  );
}
