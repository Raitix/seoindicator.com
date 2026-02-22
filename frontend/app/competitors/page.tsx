import Link from "next/link";

import CompetitorList from "@/components/competitors/CompetitorList";
import { Button } from "@/components/ui/button";

export default function CompetitorsPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <header className="border-b border-slate-800">
        <div className="container mx-auto flex items-center justify-between px-4 py-4">
          <div className="text-lg font-semibold text-white">SEOIndicator</div>
          <nav className="flex items-center gap-3">
            <Button variant="ghost" className="text-slate-300 hover:text-white">
              Dashboard
            </Button>
            <Button variant="ghost" className="text-slate-300 hover:text-white">
              Indicators
            </Button>
            <Button asChild variant="ghost" className="text-slate-300 hover:text-white">
              <Link href="/comparison">Comparison</Link>
            </Button>
            <Button asChild variant="outline" className="border-emerald-500 text-emerald-300">
              <Link href="/competitors">Competitors</Link>
            </Button>
          </nav>
        </div>
      </header>

      <main className="container mx-auto px-4 py-10">
        <CompetitorList />
      </main>
    </div>
  );
}
