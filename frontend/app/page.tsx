"use client";

import * as React from "react";
import Link from "next/link";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { IndicatorCatalog, type IndicatorData } from "@/components/indicators";
import SystemStatusBar from "@/components/SystemStatusBar";

// ============================================================================
// Indicator Data
// ============================================================================

/**
 * Sample values shown on the marketing homepage to illustrate the indicator
 * set. These are NOT live measurements - they are static placeholders.
 *
 * The real, per-site numbers are produced by the analysis pipeline and shown
 * in the authenticated dashboard, where each value comes from an actual run
 * against the user's own domain.
 *
 * See ROADMAP.md ("Live public metrics endpoint") for the planned work to
 * replace these placeholders with genuine platform-wide statistics.
 */
const homepageIndicators: IndicatorData[] = [
  {
    id: "rankings",
    icon: "TrendingUp",
    title: "Keyword Rankings",
    value: "42",
    description: "Track SERP positions",
    status: "good",
    href: "/ranking-history",
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
    href: "/audit",
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
    href: "/tools/mobile-friendly",
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
  // The catalogue owns its own category state. Passing `selectedCategory`
  // without an `onCategoryChange` made it a controlled component whose value
  // never changed - which is why the All / Rankings / Traffic / Technical
  // tabs did nothing when clicked.
  const [category, setCategory] = React.useState("All");

  return (
    <div className="min-h-screen bg-gradient-to-b from-surface-0 via-surface-1 to-surface-2 brand-gradient">
      {/* Header */}
      
      {/* Hero Section */}
      <section
        className="container mx-auto px-4 pt-14 pb-10 md:pt-20 md:pb-14 text-center animate-fade-in"
        role="region"
        aria-label="Hero"
      >
        <p className="mx-auto mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-sm font-medium text-emerald-300">
          <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true" />
          17 SEO indicators, one consolidated AI report
        </p>

        <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight text-white mb-6 max-w-4xl mx-auto text-balance">
          Find out what is holding your{" "}
          <span className="text-brand-primary">search rankings</span> back
        </h1>

        <p className="text-lg md:text-xl text-slate-400 mb-9 max-w-2xl mx-auto text-pretty">
          Enter any domain and get a scored audit of every technical, on-page and
          ranking factor that matters - plus the specific fixes to make next.
        </p>

        {/* Primary CTAs */}
        <div className="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center max-w-xl mx-auto">
          <Button
            asChild
            size="lg"
            className="h-12 bg-brand-primary text-brand-primary-foreground hover:bg-brand-primary/90 font-semibold px-8"
          >
            <Link href="/tools/meta-tag-analyzer">Analyse a website free</Link>
          </Button>
          <Button
            asChild
            size="lg"
            variant="outline"
            className="h-12 border-slate-700 bg-transparent text-slate-200 hover:border-brand-primary/60 hover:bg-slate-800/60 hover:text-white px-8"
          >
            <Link href="/how-it-works">See how it works</Link>
          </Button>
        </div>

        <p className="mt-4 text-sm text-slate-400">
          No account needed for a free analysis.
        </p>
      </section>

      {/* Indicator Catalog */}
      <section className="container mx-auto px-4 pb-16" aria-label="SEO indicators">
        <div className="mx-auto max-w-5xl">
          <div className="mb-6 text-center">
            <h2 className="text-2xl md:text-3xl font-bold text-white mb-2">
              What we check
            </h2>
            <p className="text-slate-400 max-w-2xl mx-auto text-pretty">
              Every analysis scores all 17 indicators. Pick a category to see
              what is included.
            </p>
          </div>

          <IndicatorCatalog
            indicators={homepageIndicators}
            title="SEO Indicators"
            description="The indicators we analyse for every site - shown with sample values"
            categories={indicatorCategories}
            selectedCategory={category}
            onCategoryChange={setCategory}
            className="text-left"
          />

          <p className="mt-3 text-sm text-slate-400 text-center">
            Sample values for illustration. Run an analysis on your own domain
            to see real results.
          </p>
        </div>
      </section>

      {/* Features Grid */}
      <section className="container mx-auto px-4 pb-16" aria-label="Key features">
        <h2 className="text-2xl md:text-3xl font-bold text-white text-center mb-3">
          Built for real SEO work
        </h2>
        <p className="text-slate-400 text-center max-w-2xl mx-auto mb-10 text-pretty">
          Everything below works on a free account, so you can try the whole
          product before you pay for anything.
        </p>

        <div className="grid md:grid-cols-3 gap-5 max-w-5xl mx-auto">
          <Card className="bg-surface-2 border-slate-700 hover:border-brand-primary/50 transition-colors duration-300">
            <CardHeader>
              <CardTitle className="text-white">Keyword Tracking</CardTitle>
              <CardDescription className="text-slate-400">
                Monitor your keyword positions across search engines
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-slate-400">
                Track daily ranking changes and identify optimization
                opportunities.
              </p>
              <Link
                href="/ranking-history"
                className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand-primary hover:underline"
              >
                See ranking history
                <span aria-hidden="true">&rarr;</span>
              </Link>
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
              <p className="text-slate-400">
                Compare your performance against competitors and discover gaps.
              </p>
              <Link
                href="/comparison"
                className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand-primary hover:underline"
              >
                Compare your site
                <span aria-hidden="true">&rarr;</span>
              </Link>
            </CardContent>
          </Card>

          <Card className="bg-surface-2 border-slate-700 hover:border-brand-primary/50 transition-colors duration-300">
            <CardHeader>
              <CardTitle className="text-white">Free Analysis Tools</CardTitle>
              <CardDescription className="text-slate-400">
                No sign-up required for any of them
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-slate-400">
                Meta tags, keyword density, social previews and mobile
                usability - each runs in the browser.
              </p>
              <Link
                href="/tools"
                className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand-primary hover:underline"
              >
                Browse all tools
                <span aria-hidden="true">&rarr;</span>
              </Link>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Status Bar */}
      <section className="container mx-auto px-4 pb-12">
        <Card className="bg-surface-1/50 border-slate-700">
          <CardContent>
            <SystemStatusBar />
          </CardContent>
        </Card>
      </section>

      {/* How We Compare Section */}
      <section
        className="container mx-auto px-4 pb-16"
        aria-label="How We Compare"
        data-testid="how-we-compare-section"
      >
        <h2 className="text-2xl md:text-3xl font-bold text-white text-center mb-3">
          How we compare
        </h2>
        <p className="text-slate-400 text-center max-w-2xl mx-auto mb-10 text-pretty">
          See how SEOIndicator stacks up against the tools you already pay for.
        </p>
        <div
          className="grid md:grid-cols-4 gap-5 max-w-4xl mx-auto"
          data-testid="comparison-links"
        >
          {[
            {
              href: "/compare/seoindicator-vs-ahrefs",
              label: "vs Ahrefs",
              testid: "compare-ahrefs",
            },
            {
              href: "/compare/seoindicator-vs-semrush",
              label: "vs SEMrush",
              testid: "compare-semrush",
            },
            {
              href: "/compare/seoindicator-vs-moz",
              label: "vs Moz",
              testid: "compare-moz",
            },
          ].map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="p-6 bg-surface-2 border border-slate-700 rounded-lg hover:border-brand-primary/50 transition-colors text-center hover:bg-surface-3"
              data-testid={item.testid}
            >
              <span className="text-white font-semibold">{item.label}</span>
            </Link>
          ))}
          <Link
            href="/compare"
            className="p-6 bg-brand-primary/10 border border-brand-primary/30 rounded-lg hover:bg-brand-primary/20 transition-colors text-center"
            data-testid="view-all-comparisons"
          >
            <span className="text-brand-primary font-semibold">
              View all comparisons
            </span>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-8" role="contentinfo">
        <div className="container mx-auto px-4 py-8 text-center text-slate-400">
          <p>© 2026 SEOIndicator. All rights reserved.</p>
          <p className="text-sm mt-2">
            Built with Next.js, Tailwind CSS, and Shadcn/UI
          </p>
        </div>
      </footer>
    </div>
  );
}
