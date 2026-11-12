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
const tool = getToolBySlug("mobile-friendly");
if (!tool) throw new Error("Tool not found");

// Generate metadata with Open Graph and Twitter Cards
export const metadata = generateToolMetadata(
  tool.name,
  tool.description,
  tool.slug,
  {
    image: tool.image,
    keywords: [
      "mobile friendly test",
      "mobile friendly checker",
      "responsive design test",
      "mobile usability",
      "viewport check",
      "SEO tool",
    ],
  }
);

// Generate all schemas for the page
const schemas = generateToolPageSchemas(tool);

export default function MobileFriendlyPage() {
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
              <Button variant="ghost" className="bg-transparent text-slate-300 hover:text-white">
                ← All Tools
              </Button>
            </Link>
            <Link href="/dashboard">
              <Button variant="outline" className="border-emerald-500 bg-transparent text-emerald-400">
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
              <span className="text-4xl">📱</span>
              Mobile Friendly Test
            </h1>
            <p className="text-slate-400 max-w-xl mx-auto">
              Enter any URL to check how well your site works on mobile. See the viewport
              setup, tap target sizes, text readability, and any horizontal overflow that
              would send visitors scrolling sideways.
            </p>
          </div>

          {/* Tool Widget */}
          <ToolWidget
            tool="mobile-friendly"
            title="Test Your Site on Mobile"
            description="Enter a URL to check its viewport configuration, responsive layout, and mobile usability."
            placeholder="https://example.com"
            showHelp={true}
          />

          {/* Info Section */}
          <div className="mt-12 space-y-8">
            {/* What We Check */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">What We Check</h2>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Viewport Configuration</h3>
                    <p className="text-sm text-slate-400">
                      A responsive viewport meta tag is present and set correctly
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Responsive Images</h3>
                    <p className="text-sm text-slate-400">
                      Images scale to the viewport instead of forcing horizontal scroll
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Tap Targets</h3>
                    <p className="text-sm text-slate-400">
                      Links and buttons are large enough to tap accurately on a phone
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Text Readability</h3>
                    <p className="text-sm text-slate-400">
                      Font sizes are legible on a small screen without zooming
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Overflow Detection</h3>
                    <p className="text-sm text-slate-400">
                      Fixed-width elements that would make the page scroll sideways
                    </p>
                  </div>
                </div>
              </div>
            </section>

            {/* Why It Matters */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">Why It Matters</h2>
              <p className="text-slate-400">
                Google indexes the mobile version of your site, and most traffic to
                most sites now arrives on a phone. A page that is hard to use on
                mobile loses visitors before they ever reach your content, and it
                ranks less well as a result. This check runs the same rules Google
                applies when it decides whether a site is usable on mobile devices.
              </p>
            </section>

            {/* Tips */}
            <section className="bg-emerald-500/10 rounded-lg p-6 border border-emerald-500/20">
              <h2 className="text-xl font-semibold text-white mb-4">💡 Quick Tips</h2>
              <ul className="space-y-2 text-slate-300">
                <li className="flex items-start gap-2">
                  <span className="text-emerald-400">•</span>
                  <span>
                    Always include{' '}
                    <code className="text-emerald-300">
                      &lt;meta name=&quot;viewport&quot; content=&quot;width=device-width, initial-scale=1&quot;&gt;
                    </code>
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-400">•</span>
                  <span>
                    Use relative units (%, rem, vw) rather than fixed pixel widths so
                    the layout adapts between devices
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-400">•</span>
                  <span>
                    Keep interactive elements at least 44×44 pixels so they are easy
                    to hit with a thumb
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-emerald-400">•</span>
                  <span>
                    Set images to <code className="text-emerald-300">max-width: 100%</code> so
                    they never exceed the screen width
                  </span>
                </li>
              </ul>
            </section>

            {/* Learn More */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">📚 Learn More</h2>
              <p className="text-slate-400 mb-4">
                Deepen your understanding of mobile SEO with our guides:
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                <Link
                  href="/seo-guides/mobile-friendly/"
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📱</span>
                  <div>
                    <h3 className="font-medium text-white">Mobile Friendly Guide</h3>
                    <p className="text-sm text-slate-400">Pass every mobile check</p>
                  </div>
                </Link>
                <Link
                  href="/seo-guides/image-optimization/"
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">📐</span>
                  <div>
                    <h3 className="font-medium text-white">Image Optimization Guide</h3>
                    <p className="text-sm text-slate-400">Serve images that fit any screen</p>
                  </div>
                </Link>
              </div>
            </section>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-12">
        <div className="container mx-auto px-4 py-8 text-center text-slate-400">
          <p>© 2026 SEOIndicator. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}
