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
const tool = getToolBySlug("meta-tag-analyzer");
if (!tool) throw new Error("Tool not found");

// Generate metadata with Open Graph and Twitter Cards
export const metadata = generateToolMetadata(
  tool.name,
  tool.description,
  tool.slug,
  {
    image: tool.image,
    keywords: ["meta tag analyzer", "SEO tool", "title tag checker", "meta description", "Open Graph", "Twitter Card"],
  }
);

// Generate all schemas for the page
const schemas = generateToolPageSchemas(tool);

export default function MetaTagAnalyzerPage() {
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
              <span className="text-4xl">🏷️</span>
              Meta Tag Analyzer
            </h1>
            <p className="text-slate-400 max-w-xl mx-auto">
              Enter any URL to instantly analyze its meta tags. Get warnings for missing or 
              improperly configured tags, and see how your page will appear in search results.
            </p>
          </div>

          {/* Tool Widget */}
          <ToolWidget
            tool="meta-tag-analyzer"
            title="Analyze Your Meta Tags"
            description="Enter a URL to analyze its title tag, meta description, Open Graph tags, and more."
            placeholder="https://example.com"
            showHelp={true}
          />

          {/* Info Section */}
          <div className="mt-12 space-y-8">
            {/* What We Check */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">What We Analyze</h2>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Title Tag</h3>
                    <p className="text-sm text-slate-400">
                      Length check (30-60 characters recommended), duplicate detection
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Meta Description</h3>
                    <p className="text-sm text-slate-400">
                      Length check (120-160 characters recommended), quality analysis
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Open Graph Tags</h3>
                    <p className="text-sm text-slate-400">
                      og:title, og:description, og:image, and more
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Twitter Card Tags</h3>
                    <p className="text-sm text-slate-400">
                      twitter:card, twitter:title, twitter:description
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Canonical URL</h3>
                    <p className="text-sm text-slate-400">
                      Check for proper canonical tag setup
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Robots Directives</h3>
                    <p className="text-sm text-slate-400">
                      noindex, nofollow, and other crawl directives
                    </p>
                  </div>
                </div>
              </div>
            </section>

            {/* SERP Preview */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">Google SERP Preview</h2>
              <p className="text-slate-400">
                See how your page will appear in Google search results. We generate a preview 
                based on your title tag and meta description, showing how Google may truncate 
                your content if it&apos;s too long.
              </p>
            </section>

            {/* Tips */}
            <section className="bg-emerald-500/10 rounded-lg p-6 border border-emerald-500/20">
              <h2 className="text-xl font-semibold text-white mb-4">💡 Quick Tips</h2>
              <ul className="space-y-2 text-slate-300">
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Keep your title tag between 50-60 characters for optimal display in Google</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Write compelling meta descriptions that encourage clicks (120-160 characters)</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Include your target keyword naturally in both title and description</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-500">•</span>
                  <span>Set Open Graph tags to control how your page appears when shared on social media</span>
                </li>
              </ul>
            </section>

            {/* Learn More - Related Guides */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">📚 Learn More</h2>
              <p className="text-slate-400 mb-4">
                Deepen your understanding of meta tags with our comprehensive guides:
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                <Link 
                  href="/seo-guides/meta-title/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📝</span>
                  <div>
                    <h3 className="font-medium text-white">Meta Title Guide</h3>
                    <p className="text-sm text-slate-400">Write compelling title tags</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/meta-description/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📄</span>
                  <div>
                    <h3 className="font-medium text-white">Meta Description Guide</h3>
                    <p className="text-sm text-slate-400">Master meta descriptions</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/open-graph/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">🔗</span>
                  <div>
                    <h3 className="font-medium text-white">Open Graph Guide</h3>
                    <p className="text-sm text-slate-400">Optimize social sharing</p>
                  </div>
                </Link>
                <Link 
                  href="/seo-guides/twitter-cards/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">🐦</span>
                  <div>
                    <h3 className="font-medium text-white">Twitter Cards Guide</h3>
                    <p className="text-sm text-slate-400">Optimize for Twitter</p>
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
