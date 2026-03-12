import Link from "next/link";
import { ToolWidget } from "@/components/tools/ToolWidget";
import { Button } from "@/components/ui/button";
import {
  generateToolMetadata,
  generateToolPageSchemas,
  getToolBySlug,
  schemaToJsonLd,
} from "@/lib/seo";

// Get tool configuration
const tool = getToolBySlug("keyword-density");
if (!tool) throw new Error("Tool not found");

// Generate metadata with Open Graph and Twitter Cards
export const metadata = generateToolMetadata(
  tool.name,
  tool.description,
  tool.slug,
  {
    image: tool.image,
    keywords: ["keyword density", "SEO tool", "keyword analysis", "keyword stuffing", "content optimization"],
  }
);

// Generate all schemas for the page
const schemas = generateToolPageSchemas(tool);

export default function KeywordDensityPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800">
      {/* JSON-LD Schemas */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: schemaToJsonLd(schemas.softwareApplication) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: schemaToJsonLd(schemas.breadcrumbs) }}
      />
      {schemas.faq && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: schemaToJsonLd(schemas.faq) }}
        />
      )}

      {/* Header */}
      <header className="border-b border-slate-700">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-2">
            <div className="h-8 w-8 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-bold">
              SI
            </div>
            <span className="text-xl font-semibold text-white">SEOIndicator</span>
          </Link>
          <nav className="flex items-center gap-4">
            <Link href="/tools">
              <Button variant="ghost" className="text-slate-300 hover:text-white">
                ← All Tools
              </Button>
            </Link>
            <Link href="/dashboard">
              <Button variant="outline" className="border-emerald-500 text-emerald-500">
                Dashboard
              </Button>
            </Link>
          </nav>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        <div className="max-w-3xl mx-auto">
          {/* Page Header */}
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-white mb-4 flex items-center justify-center gap-3">
              <span className="text-4xl">📊</span>
              Keyword Density Checker
            </h1>
            <p className="text-slate-400 max-w-xl mx-auto">
              Enter a URL and target keyword to analyze keyword density and placement. 
              Get warnings for over-optimization and recommendations for improvement.
            </p>
          </div>

          {/* Tool Widget */}
          <ToolWidget
            tool="keyword-density"
            title="Check Your Keyword Density"
            description="Enter a URL and your target keyword to analyze its density and placement."
            placeholder="https://example.com"
            showHelp={true}
          />

          {/* Info Section */}
          <div className="mt-12 space-y-8">
            {/* What is Keyword Density */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">What is Keyword Density?</h2>
              <p className="text-slate-400 mb-4">
                Keyword density is the percentage of times a keyword appears on a webpage compared 
                to the total number of words. It&apos;s an important SEO factor, but over-optimization 
                can lead to penalties.
              </p>
              <div className="grid md:grid-cols-3 gap-4 mt-4">
                <div className="bg-yellow-500/10 rounded-lg p-4 border border-yellow-500/20">
                  <h3 className="font-medium text-yellow-400 mb-1">&lt; 1%</h3>
                  <p className="text-sm text-slate-400">Too low - consider adding more mentions</p>
                </div>
                <div className="bg-green-500/10 rounded-lg p-4 border border-green-500/20">
                  <h3 className="font-medium text-green-400 mb-1">1-3%</h3>
                  <p className="text-sm text-slate-400">Optimal range for most content</p>
                </div>
                <div className="bg-red-500/10 rounded-lg p-4 border border-red-500/20">
                  <h3 className="font-medium text-red-400 mb-1">&gt; 3%</h3>
                  <p className="text-sm text-slate-400">Risk of keyword stuffing penalty</p>
                </div>
              </div>
            </section>

            {/* What We Analyze */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">What We Analyze</h2>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Keyword Frequency</h3>
                    <p className="text-sm text-slate-400">
                      Count of keyword occurrences in visible content
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Density Percentage</h3>
                    <p className="text-sm text-slate-400">
                      Ratio of keyword to total word count
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Heading Placement</h3>
                    <p className="text-sm text-slate-400">
                      Check if keyword appears in H1, H2, etc.
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Title & Description</h3>
                    <p className="text-sm text-slate-400">
                      Presence in meta title and description
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">First Paragraph</h3>
                    <p className="text-sm text-slate-400">
                      Check if keyword appears early in content
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Over-Optimization</h3>
                    <p className="text-sm text-slate-400">
                      Warning if density is too high
                    </p>
                  </div>
                </div>
              </div>
            </section>

            {/* Tips */}
            <section className="bg-emerald-500/10 rounded-lg p-6 border border-emerald-500/20">
              <h2 className="text-xl font-semibold text-white mb-4">💡 Best Practices</h2>
              <ul className="space-y-2 text-slate-300">
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Focus on natural language - write for humans first, search engines second</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Include the keyword in your title, H1, and first paragraph when natural</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Use variations and related terms instead of repeating the same keyword</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Avoid keyword stuffing - it can result in search engine penalties</span>
                </li>
              </ul>
            </section>

            {/* Learn More - Related Guides */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">📚 Learn More</h2>
              <p className="text-slate-400 mb-4">
                Master keyword optimization with our comprehensive guides:
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                <Link 
                  href="/seo-guides/content-optimization/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📝</span>
                  <div>
                    <h3 className="font-medium text-white">Content Optimization Guide</h3>
                    <p className="text-sm text-slate-400">Write SEO-friendly content</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/keyword-research/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">🔍</span>
                  <div>
                    <h3 className="font-medium text-white">Keyword Research Guide</h3>
                    <p className="text-sm text-slate-400">Find the right keywords</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/on-page-seo/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📄</span>
                  <div>
                    <h3 className="font-medium text-white">On-Page SEO Guide</h3>
                    <p className="text-sm text-slate-400">Optimize your pages</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 transition-colors"
                >
                  <span className="text-2xl">📚</span>
                  <div>
                    <h3 className="font-medium text-emerald-400">All SEO Guides</h3>
                    <p className="text-sm text-slate-400">Browse all guides</p>
                  </div>
                </Link>
              </div>
            </section>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-12">
        <div className="container mx-auto px-4 py-8 text-center text-slate-500">
          <p>© 2026 SEOIndicator. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}
