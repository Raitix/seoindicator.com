import Link from "next/link";

import ComparisonDashboard from "@/components/comparison/ComparisonDashboard";
import GuestPreview from "@/components/auth/GuestPreview";
import { Button } from "@/components/ui/button";

export default function ComparisonPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <header className="border-b border-slate-800">
        <div className="container mx-auto flex items-center justify-between px-4 py-4">
          <Link href="/" className="text-lg font-semibold text-white hover:text-emerald-400 transition-colors">
            SEOIndicator
          </Link>
          <nav className="flex items-center gap-3">
            <Button asChild variant="ghost" className="bg-transparent text-slate-300 hover:text-white">
              <Link href="/dashboard">Dashboard</Link>
            </Button>
            <Button asChild variant="ghost" className="bg-transparent text-slate-300 hover:text-white">
              <Link href="/seo-guides">Guides</Link>
            </Button>
            <Button asChild variant="ghost" className="bg-transparent text-slate-300 hover:text-white">
              <Link href="/competitors">Competitors</Link>
            </Button>
            <Button asChild variant="outline" className="border-emerald-500 bg-transparent text-emerald-400">
              <Link href="/comparison">Comparison</Link>
            </Button>
          </nav>
        </div>
      </header>

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
