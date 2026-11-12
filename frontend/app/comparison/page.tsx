
import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";
import GuestPreview from "@/components/auth/GuestPreview";

export default function ComparisonPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      
      <main className="container mx-auto px-4 py-10">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">Comparison</h1>
          <p className="text-sm text-slate-300">
            Put your site side by side with the competitors you track.
          </p>
        </div>

        <div className="mb-6">
          <GuestPreview feature="your side-by-side comparison data" />
        </div>

        <ComparisonDashboard />
      </main>
    </div>
  );
}
