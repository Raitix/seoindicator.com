"use client";

import Link from "next/link";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { IndicatorCatalog, type IndicatorData } from "@/components/indicators";

// ============================================================================
// Indicator Data
// ============================================================================

const homepageIndicators: IndicatorData[] = [
  {
    id: "rankings",
    icon: "TrendingUp",
    title: "Keyword Rankings",
    value: "42",
    description: "Track SERP positions",
    status: "good",
    href: "/dashboard",
    category: "Rankings",
  },
  {
    id: "visibility",
    icon: "Eye",
    title: "Visibility Score",
    value: "78%",
    description: "Overall SEO visibility",
    status: "warning",
    href: "/dashboard",
    category: "Traffic",
  },
  {
    id: "technical",
    icon: "Wrench",
    title: "Technical Health",
    value: "95",
    description: "Site audit score",
    status: "good",
    href: "/tools",
    category: "Technical",
  },
  {
    id: "backlinks",
    icon: "Link2",
    title: "Backlink Profile",
    value: "1.2K",
    description: "Link analysis",
    status: "good",
    href: "/dashboard",
    category: "Traffic",
  },
  {
    id: "content",
    icon: "FileText",
    title: "Content Opportunities",
    value: "15",
    description: "Keyword gaps",
    status: "neutral",
    href: "/dashboard",
    category: "Rankings",
  },
  {
    id: "serp",
    icon: "Star",
    title: "SERP Features",
    value: "8",
    description: "Featured snippets, etc.",
    status: "good",
    href: "/dashboard",
    category: "Rankings",
  },
  {
    id: "speed",
    icon: "Zap",
    title: "Page Speed",
    value: "89",
    description: "Performance metrics",
    status: "warning",
    href: "/tools",
    category: "Technical",
  },
  {
    id: "competitors",
    icon: "Users",
    title: "Competitor Analysis",
    value: "3",
    description: "Compare with competitors",
    status: "neutral",
    href: "/comparison",
    category: "Traffic",
  },
];

// ============================================================================
// Categories for Filtering
// ============================================================================

const indicatorCategories = ["All", "Rankings", "Traffic", "Technical"];

// ============================================================================
// Homepage Component
// ============================================================================

export default function Home() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-surface-0 via-surface-1 to-surface-2 brand-gradient">
      {/* Header */}
      <header className="border-b border-slate-700" role="banner">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="h-8 w-8 bg-brand-primary rounded-lg flex items-center justify-center text-white font-bold">
              SI
            </div>
            <span className="text-xl font-semibold text-white">SEOIndicator</span>
          </div>
          <nav className="flex items-center gap-4" role="navigation" aria-label="Main navigation">
            <Button asChild variant="ghost" className="text-slate-300 hover:text-white">
              <Link href="/dashboard">Dashboard</Link>
            </Button>
            <Button variant="ghost" className="text-slate-300 hover:text-white">
              Indicators
            </Button>
            <Button asChild variant="ghost" className="text-slate-300 hover:text-white">
              <Link href="/competitors">Competitors</Link>
            </Button>
            <Button asChild variant="ghost" className="text-slate-300 hover:text-white">
              <Link href="/comparison">Comparison</Link>
            </Button>
            <Button variant="outline" className="border-brand-primary text-brand-primary hover:bg-brand-primary hover:text-white">
              Sign In
            </Button>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section 
        className="container mx-auto px-4 py-12 md:py-16 lg:py-20 text-center animate-fade-in"
        role="region"
        aria-label="Hero"
      >
        <h1 className="text-4xl md:text-5xl font-bold text-white mb-6 transition-all duration-500">
          Track Your SEO Performance
        </h1>
        <p className="text-lg md:text-xl text-slate-400 mb-8 max-w-2xl mx-auto">
          Monitor keyword rankings, analyze competitors, and get actionable insights
          to improve your search engine visibility.
        </p>
        
        {/* Indicator Catalog */}
        <div className="mb-8 transition-all duration-500">
          <IndicatorCatalog
            indicators={homepageIndicators}
            title="SEO Indicators"
            description="Real-time metrics for your website's SEO performance"
            categories={indicatorCategories}
            selectedCategory="All"
            className="text-left"
          />
        </div>
        
        {/* CTA */}
        <div className="flex gap-4 justify-center flex-wrap">
          <Input 
            type="email" 
            placeholder="Enter your email" 
            className="max-w-xs bg-surface-2 border-slate-600 text-white placeholder:text-slate-500"
          />
          <Button asChild className="bg-brand-primary hover:bg-brand-primary/90 text-white px-8">
            <Link href="/dashboard">Get Started</Link>
          </Button>
        </div>
      </section>

      {/* Features Grid */}
      <section className="container mx-auto px-4 py-16">
        <h2 className="text-3xl font-bold text-white text-center mb-12">
          Key Features
        </h2>
        <div className="grid md:grid-cols-3 gap-6">
          <Card className="bg-surface-2 border-slate-700 hover:border-brand-primary/50 transition-colors duration-300">
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

          <Card className="bg-surface-2 border-slate-700 hover:border-brand-primary/50 transition-colors duration-300">
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

          <Card className="bg-surface-2 border-slate-700 hover:border-brand-primary/50 transition-colors duration-300">
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
        <Card className="bg-surface-1/50 border-slate-700">
          <CardContent className="flex items-center justify-between py-4">
            <div className="flex items-center gap-3">
              <div className="h-3 w-3 bg-success rounded-full animate-pulse" aria-hidden="true"></div>
              <span className="text-slate-300">System Status: Operational</span>
            </div>
            <span className="text-slate-500 text-sm">
              Backend API Ready • Database Connected
            </span>
          </CardContent>
        </Card>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-20" role="contentinfo">
        <div className="container mx-auto px-4 py-8 text-center text-slate-500">
          <p>© 2026 SEOIndicator. All rights reserved.</p>
          <p className="text-sm mt-2">
            Built with Next.js, Tailwind CSS, and Shadcn/UI
          </p>
        </div>
      </footer>
    </main>
  );
}
