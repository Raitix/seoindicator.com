
import CompetitorList from "@/components/competitors/CompetitorList";
import GuestPreview from "@/components/auth/GuestPreview";

export default function CompetitorsPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      
      <main className="container mx-auto px-4 py-10">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">Competitors</h1>
          <p className="text-sm text-slate-300">
            Track the sites you compete with and see where their rankings differ
            from yours.
          </p>
        </div>

        <div className="mb-6">
          <GuestPreview feature="the competitors you track and their keyword positions" />
        </div>

        <CompetitorList />
      </main>
    </div>
  );
}
