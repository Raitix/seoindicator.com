import Link from "next/link";
import { SocialMediaPreview } from "@/components/tools/SocialMediaPreview";
import { Button } from "@/components/ui/button";
import {
  generateToolMetadata,
  generateToolPageSchemas,
  getToolBySlug,
  schemaToJsonLd,
} from "@/lib/seo";

// Get tool configuration
const tool = getToolBySlug("social-preview");
if (!tool) throw new Error("Tool not found");

// Generate metadata with Open Graph and Twitter Cards
export const metadata = generateToolMetadata(
  tool.name,
  tool.description,
  tool.slug,
  {
    image: tool.image,
    keywords: ["social media preview", "open graph checker", "twitter card validator", "facebook preview", "linkedin preview", "SEO tool"],
  }
);

// Generate all schemas for the page
const schemas = generateToolPageSchemas(tool);

export default function SocialMediaPreviewPage() {
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
              <span className="text-4xl">📱</span>
              Social Media Preview
            </h1>
            <p className="text-slate-400 max-w-xl mx-auto">
              See exactly how your page will appear when shared on Facebook, Twitter, and LinkedIn.
              Analyze Open Graph and Twitter Card tags, detect missing tags, and get recommendations.
            </p>
          </div>

          {/* Tool Component */}
          <SocialMediaPreview />

          {/* Info Section */}
          <div className="mt-12 space-y-8">
            {/* What We Check */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">What We Analyze</h2>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Open Graph Tags</h3>
                    <p className="text-sm text-slate-400">
                      og:title, og:description, og:image, og:url, og:type, and more
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Twitter Card Tags</h3>
                    <p className="text-sm text-slate-400">
                      twitter:card, twitter:site, twitter:title, twitter:description
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Missing Tag Detection</h3>
                    <p className="text-sm text-slate-400">
                      Identify required tags that are missing from your page
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-emerald-500 mt-1">✓</span>
                  <div>
                    <h3 className="font-medium text-white">Image Validation</h3>
                    <p className="text-sm text-slate-400">
                      Check image URLs, dimensions, and HTTPS usage
                    </p>
                  </div>
                </div>
              </div>
            </section>

            {/* Platform Previews */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">Platform Previews</h2>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <span className="text-2xl">📘</span>
                  <div>
                    <h3 className="font-medium text-white">Facebook Preview</h3>
                    <p className="text-sm text-slate-400">
                      See how your link will appear in Facebook posts, Messenger, and WhatsApp.
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-2xl">🐦</span>
                  <div>
                    <h3 className="font-medium text-white">Twitter Preview</h3>
                    <p className="text-sm text-slate-400">
                      Preview your Twitter card (summary or summary_large_image).
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <span className="text-2xl">💼</span>
                  <div>
                    <h3 className="font-medium text-white">LinkedIn Preview</h3>
                    <p className="text-sm text-slate-400">
                      See how your content appears when shared on LinkedIn.
                    </p>
                  </div>
                </div>
              </div>
            </section>

            {/* Image Recommendations */}
            <section className="bg-emerald-500/10 rounded-lg p-6 border border-emerald-500/20">
              <h2 className="text-xl font-semibold text-white mb-4">💡 Image Size Recommendations</h2>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-700">
                      <th className="text-left py-2 text-slate-400">Platform</th>
                      <th className="text-left py-2 text-slate-400">Recommended Size</th>
                      <th className="text-left py-2 text-slate-400">Aspect Ratio</th>
                    </tr>
                  </thead>
                  <tbody className="text-slate-300">
                    <tr className="border-b border-slate-700/50">
                      <td className="py-2">Facebook</td>
                      <td className="py-2">1200 × 630 px</td>
                      <td className="py-2">1.91:1</td>
                    </tr>
                    <tr className="border-b border-slate-700/50">
                      <td className="py-2">Twitter (large)</td>
                      <td className="py-2">1200 × 600 px</td>
                      <td className="py-2">2:1</td>
                    </tr>
                    <tr className="border-b border-slate-700/50">
                      <td className="py-2">Twitter (summary)</td>
                      <td className="py-2">240 × 240 px</td>
                      <td className="py-2">1:1</td>
                    </tr>
                    <tr>
                      <td className="py-2">LinkedIn</td>
                      <td className="py-2">1200 × 627 px</td>
                      <td className="py-2">1.91:1</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            {/* Tips */}
            <section className="bg-blue-500/10 rounded-lg p-6 border border-blue-500/20">
              <h2 className="text-xl font-semibold text-white mb-4">💡 Quick Tips</h2>
              <ul className="space-y-2 text-slate-300">
                <li className="flex items-start gap-2">
                  <span className="text-blue-400">•</span>
                  <span>Always use HTTPS for image URLs to avoid security warnings</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-blue-400">•</span>
                  <span>Include og:image:width and og:image:height for faster rendering</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-blue-400">•</span>
                  <span>Twitter falls back to Open Graph tags when Twitter Card tags are missing</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-blue-400">•</span>
                  <span>Use Facebook Sharing Debugger to clear cached previews after changes</span>
                </li>
              </ul>
            </section>

            {/* Learn More */}
            <section className="bg-slate-800/50 rounded-lg p-6 border border-slate-700">
              <h2 className="text-xl font-semibold text-white mb-4">📚 Learn More</h2>
              <p className="text-slate-400 mb-4">
                Master social media optimization with our comprehensive guides:
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                <Link 
                  href="/seo-guides/open-graph/" 
                  className="flex items-center gap-3 p-3 rounded-lg bg-slate-700/50 hover:bg-slate-700 transition-colors"
                >
                  <span className="text-2xl">🔗</span>
                  <div>
                    <h3 className="font-medium text-white">Open Graph Guide</h3>
                    <p className="text-sm text-slate-400">Master OG tags</p>
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
