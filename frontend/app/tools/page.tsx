import Link from "next/link";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const tools = [
  {
    id: "meta-tag-analyzer",
    name: "Meta Tag Analyzer",
    description: "Analyze your page's meta tags including title, description, Open Graph, and Twitter Card tags. Get instant warnings and optimization suggestions.",
    icon: "🏷️",
    features: [
      "Title tag analysis",
      "Meta description check",
      "SERP preview",
      "Open Graph tags",
      "Twitter Card tags",
    ],
    path: "/tools/meta-tag-analyzer",
    status: "available",
  },
  {
    id: "keyword-density",
    name: "Keyword Density Checker",
    description: "Check keyword density and placement in your content. Get recommendations for optimal keyword usage.",
    icon: "📊",
    features: [
      "Density calculation",
      "Heading analysis",
      "Placement check",
      "Over-optimization warnings",
      "Export results",
    ],
    path: "/tools/keyword-density",
    status: "available",
  },
  {
    id: "mobile-friendly",
    name: "Mobile-Friendly Test",
    description: "Test how mobile-friendly your page is. Check viewport settings, tap targets, and text readability.",
    icon: "📱",
    features: [
      "Viewport analysis",
      "Mobile preview",
      "Tap target check",
      "Text size warnings",
    ],
    path: "/tools/mobile-friendly",
    status: "coming-soon",
  },
  {
    id: "social-preview",
    name: "Social Media Preview",
    description: "Preview how your page appears on Facebook, Twitter, and other social platforms.",
    icon: "🔗",
    features: [
      "Facebook preview",
      "Twitter preview",
      "Open Graph analysis",
      "Missing tag warnings",
    ],
    path: "/tools/social-preview",
    status: "coming-soon",
  },
];

export default function ToolsPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-900 to-slate-800">
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
            <Link href="/dashboard">
              <Button variant="ghost" className="text-slate-300 hover:text-white">
                Dashboard
              </Button>
            </Link>
            <Link href="/projects">
              <Button variant="ghost" className="text-slate-300 hover:text-white">
                Projects
              </Button>
            </Link>
            <Link href="/tools">
              <Button variant="outline" className="border-emerald-500 text-emerald-500">
                Tools
              </Button>
            </Link>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-16 text-center">
        <h1 className="text-4xl font-bold text-white mb-4">
          Free SEO Tools
        </h1>
        <p className="text-xl text-slate-400 mb-8 max-w-2xl mx-auto">
          Analyze your website&apos;s SEO instantly with our free interactive tools.
          No signup required for basic analysis.
        </p>
      </section>

      {/* Tools Grid */}
      <section className="container mx-auto px-4 pb-16">
        <div className="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          {tools.map((tool) => (
            <Card 
              key={tool.id}
              className={`bg-slate-800 border-slate-700 transition-all duration-200 ${
                tool.status === 'available' 
                  ? 'hover:border-emerald-500/50 hover:shadow-lg hover:shadow-emerald-500/10' 
                  : 'opacity-75'
              }`}
            >
              <CardHeader>
                <div className="flex items-center gap-3">
                  <span className="text-3xl">{tool.icon}</span>
                  <div>
                    <CardTitle className="text-white flex items-center gap-2">
                      {tool.name}
                      {tool.status === 'coming-soon' && (
                        <span className="text-xs px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400">
                          Coming Soon
                        </span>
                      )}
                    </CardTitle>
                  </div>
                </div>
                <CardDescription className="text-slate-400 mt-2">
                  {tool.description}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 mb-4">
                  {tool.features.map((feature, index) => (
                    <li key={index} className="flex items-center gap-2 text-sm text-slate-300">
                      <span className="text-emerald-500">✓</span>
                      {feature}
                    </li>
                  ))}
                </ul>
                {tool.status === 'available' ? (
                  <Link href={tool.path}>
                    <Button className="w-full bg-emerald-500 hover:bg-emerald-600 text-white">
                      Use Tool
                    </Button>
                  </Link>
                ) : (
                  <Button disabled className="w-full bg-slate-700 text-slate-400">
                    Coming Soon
                  </Button>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      {/* Info Section */}
      <section className="container mx-auto px-4 py-12 border-t border-slate-700">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-2xl font-bold text-white mb-4">
            Why Use Our Free SEO Tools?
          </h2>
          <div className="grid md:grid-cols-3 gap-6 mt-8">
            <div className="p-4">
              <div className="text-3xl mb-2">⚡</div>
              <h3 className="text-lg font-semibold text-white mb-2">Instant Results</h3>
              <p className="text-slate-400 text-sm">
                Get analysis in seconds, no waiting for reports or processes.
              </p>
            </div>
            <div className="p-4">
              <div className="text-3xl mb-2">🔒</div>
              <h3 className="text-lg font-semibold text-white mb-2">No Data Storage</h3>
              <p className="text-slate-400 text-sm">
                Your URLs are analyzed in real-time and not stored on our servers.
              </p>
            </div>
            <div className="p-4">
              <div className="text-3xl mb-2">🎯</div>
              <h3 className="text-lg font-semibold text-white mb-2">Actionable Insights</h3>
              <p className="text-slate-400 text-sm">
                Get specific recommendations you can implement right away.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Rate Limit Notice */}
      <section className="container mx-auto px-4 py-8">
        <Card className="bg-slate-800/50 border-slate-700 max-w-2xl mx-auto">
          <CardContent className="py-4 text-center text-slate-400 text-sm">
            <p>
              Free tools have a usage limit of 3 analyses per day per IP address.
              <Link href="/signup" className="text-emerald-400 hover:text-emerald-300 ml-1">
                Create a free account
              </Link>
              {" "}for unlimited access and additional features.
            </p>
          </CardContent>
        </Card>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-700 mt-8">
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
