import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

export default function Home() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800">
      {/* Header */}
      <header className="border-b border-slate-700">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="h-8 w-8 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-bold">
              SI
            </div>
            <span className="text-xl font-semibold text-white">SEOIndicator</span>
          </div>
          <nav className="flex items-center gap-4">
            <Button variant="ghost" className="text-slate-300 hover:text-white">
              Dashboard
            </Button>
            <Button variant="ghost" className="text-slate-300 hover:text-white">
              Indicators
            </Button>
            <Button variant="outline" className="border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white">
              Sign In
            </Button>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-20 text-center">
        <h1 className="text-5xl font-bold text-white mb-6">
          Track Your SEO Performance
        </h1>
        <p className="text-xl text-slate-400 mb-8 max-w-2xl mx-auto">
          Monitor keyword rankings, analyze competitors, and get actionable insights
          to improve your search engine visibility.
        </p>
        <div className="flex gap-4 justify-center">
          <Input 
            type="email" 
            placeholder="Enter your email" 
            className="max-w-xs bg-slate-800 border-slate-600 text-white placeholder:text-slate-500"
          />
          <Button className="bg-emerald-500 hover:bg-emerald-600 text-white px-8">
            Get Started
          </Button>
        </div>
      </section>

      {/* Features Grid */}
      <section className="container mx-auto px-4 py-16">
        <h2 className="text-3xl font-bold text-white text-center mb-12">
          Key Features
        </h2>
        <div className="grid md:grid-cols-3 gap-6">
          <Card className="bg-slate-800 border-slate-700">
            <CardHeader>
              <CardTitle className="text-white">Keyword Tracking</CardTitle>
              <CardDescription className="text-slate-400">
                Monitor your keyword positions across search engines
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-slate-500">
                Track daily ranking changes and identify optimization opportunities.
              </p>
            </CardContent>
          </Card>

          <Card className="bg-slate-800 border-slate-700">
            <CardHeader>
              <CardTitle className="text-white">Competitor Analysis</CardTitle>
              <CardDescription className="text-slate-400">
                Understand your competition&apos;s strategies
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-slate-500">
                Compare your performance against competitors and discover gaps.
              </p>
            </CardContent>
          </Card>

          <Card className="bg-slate-800 border-slate-700">
            <CardHeader>
              <CardTitle className="text-white">Custom Indicators</CardTitle>
              <CardDescription className="text-slate-400">
                Create your own SEO metrics and KPIs
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-slate-500">
                Build personalized indicators tailored to your business goals.
              </p>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Status Bar */}
      <section className="container mx-auto px-4 py-8">
        <Card className="bg-slate-800/50 border-slate-700">
          <CardContent className="flex items-center justify-between py-4">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 bg-emerald-500 rounded-full animate-pulse"></div>
              <span className="text-slate-300">System Status: Operational</span>
            </div>
            <span className="text-slate-500 text-sm">
              Backend API Ready • Database Connected
            </span>
          </CardContent>
        </Card>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-20">
        <div className="container mx-auto px-4 py-8 text-center text-slate-500">
          <p>© 2026 SEOIndicator. All rights reserved.</p>
          <p className="text-sm mt-2">
            Built with Next.js, Tailwind CSS, and Shadcn/UI
          </p>
        </div>
      </footer>
    </div>
  );
}