/**
 * AboutContent Component
 * Task: TASK-30-2 - About Page
 * 
 * Displays mission statement, product overview, key features,
 * team/company information, and call-to-action to sign up.
 */

import React from 'react';
import Link from 'next/link';

// ============================================================================
// Types
// ============================================================================

interface AboutContentProps {
  className?: string;
}

// ============================================================================
// Feature Data
// ============================================================================

const features = [
  {
    icon: '🔍',
    title: '17 SEO Indicators',
    description: 'Comprehensive analysis across technical SEO, content quality, performance, and user experience indicators.',
  },
  {
    icon: '🤖',
    title: 'AI-Powered Suggestions',
    description: 'Get actionable recommendations powered by AI to improve your SEO score and search visibility.',
  },
  {
    icon: '📊',
    title: 'Competitor Analysis',
    description: 'Compare your website against competitors to identify opportunities and competitive gaps.',
  },
  {
    icon: '📈',
    title: 'Rank Tracking',
    description: 'Monitor your keyword rankings over time with automated daily tracking and alerts.',
  },
  {
    icon: '⚡',
    title: 'Instant Analysis',
    description: 'Get complete SEO audits in seconds with our fast and efficient scanning engine.',
  },
];

const stats = [
  { value: '17', label: 'SEO Indicators' },
  { value: '100K+', label: 'Sites Analyzed' },
  { value: '99.9%', label: 'Uptime' },
  { value: '24/7', label: 'Support' },
];

// ============================================================================
// Component
// ============================================================================

/**
 * AboutContent Component
 * Renders About page content with mission, features, and CTA
 */
export default function AboutContent({ className = '' }: AboutContentProps) {
  return (
    <div className={className}>
      {/* Hero Section */}
      <section className="relative overflow-hidden">
        {/* Background Gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-slate-900/50" />
        
        <div className="relative mx-auto max-w-5xl px-4 py-16 md:py-24">
          {/* Mission Statement */}
          <div className="text-center">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
              Making SEO{' '}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
                Accessible to Everyone
              </span>
            </h1>
            <p className="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
              SEOIndicator is your all-in-one AI-powered SEO analysis platform. We help website owners, 
              marketers, and SEO professionals understand and improve their search engine visibility 
              with actionable insights.
            </p>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="border-y border-slate-800 bg-slate-900/30">
        <div className="mx-auto max-w-5xl px-4 py-12">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {stats.map((stat, index) => (
              <div key={index} className="text-center">
                <div className="text-3xl md:text-4xl font-bold text-emerald-400 mb-1">
                  {stat.value}
                </div>
                <div className="text-sm text-slate-400">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Product Overview Section */}
      <section className="mx-auto max-w-5xl px-4 py-16">
        <div className="grid md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 className="text-3xl font-bold text-white mb-6">
              What We Do
            </h2>
            <p className="text-slate-300 leading-relaxed mb-4">
              SEOIndicator analyzes your website across 17 critical SEO indicators, providing a 
              comprehensive score and detailed recommendations for improvement. Our platform 
              combines automated scanning with AI-powered suggestions to help you optimize 
              your online presence.
            </p>
            <p className="text-slate-300 leading-relaxed mb-4">
              Whether you&apos;re a small business owner, a digital marketer, or an SEO professional, 
              our tools give you the insights you need to rank higher and attract more organic traffic.
            </p>
            <p className="text-slate-300 leading-relaxed">
              Built with modern technology and best practices, SEOIndicator delivers fast, 
              accurate analysis that helps you make data-driven decisions for your website&apos;s success.
            </p>
          </div>
          <div className="relative">
            <div className="aspect-square rounded-2xl bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 border border-slate-800 p-8 flex items-center justify-center">
              <div className="text-center">
                <div className="text-6xl mb-4">🎯</div>
                <div className="text-4xl font-bold text-white mb-2">Your SEO Score</div>
                <div className="text-6xl font-bold text-emerald-400">85</div>
                <div className="text-slate-400 mt-2">out of 100</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Key Features Section */}
      <section className="bg-slate-900/30 border-y border-slate-800">
        <div className="mx-auto max-w-5xl px-4 py-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-white mb-4">
              Key Features
            </h2>
            <p className="text-slate-400 max-w-2xl mx-auto">
              Everything you need to analyze, monitor, and improve your website&apos;s SEO performance.
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((feature, index) => (
              <div
                key={index}
                className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 hover:border-emerald-500/50 transition-colors"
              >
                <div className="text-4xl mb-4">{feature.icon}</div>
                <h3 className="text-lg font-semibold text-white mb-2">
                  {feature.title}
                </h3>
                <p className="text-slate-400 text-sm leading-relaxed">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works Preview */}
      <section className="mx-auto max-w-5xl px-4 py-16">
        <div className="text-center">
          <h2 className="text-3xl font-bold text-white mb-4">
            Simple. Fast. Effective.
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto mb-8">
            Get started in minutes. Enter your URL, receive your analysis, and start improving your SEO.
          </p>
          
          <div className="grid md:grid-cols-3 gap-8">
            <div className="text-center">
              <div className="w-12 h-12 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <span className="text-emerald-400 font-bold">1</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Enter Your URL</h3>
              <p className="text-slate-400 text-sm">
                Simply enter your website URL to start the analysis.
              </p>
            </div>
            <div className="text-center">
              <div className="w-12 h-12 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <span className="text-emerald-400 font-bold">2</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Get Your Score</h3>
              <p className="text-slate-400 text-sm">
                Receive a comprehensive SEO score with detailed breakdown.
              </p>
            </div>
            <div className="text-center">
              <div className="w-12 h-12 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <span className="text-emerald-400 font-bold">3</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Take Action</h3>
              <p className="text-slate-400 text-sm">
                Follow AI-powered suggestions to improve your ranking.
              </p>
            </div>
          </div>
          
          <div className="mt-8">
            <Link
              href="/how-it-works/"
              className="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 transition-colors"
            >
              Learn more about how it works
              <svg
                className="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </Link>
          </div>
        </div>
      </section>

      {/* Team/Company Section */}
      <section className="bg-slate-900/30 border-y border-slate-800">
        <div className="mx-auto max-w-5xl px-4 py-16">
          <div className="text-center mb-8">
            <h2 className="text-3xl font-bold text-white mb-4">
              Built by SEO Enthusiasts
            </h2>
            <p className="text-slate-400 max-w-3xl mx-auto">
              SEOIndicator is built by a team of developers and SEO specialists passionate about 
              making search engine optimization accessible to everyone. We believe that every 
              website deserves the tools to succeed online.
            </p>
          </div>
          
          <div className="grid md:grid-cols-3 gap-6 text-center">
            <div className="p-6">
              <div className="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">💡</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Innovation</h3>
              <p className="text-slate-400 text-sm">
                Constantly improving our algorithms and adding new features.
              </p>
            </div>
            <div className="p-6">
              <div className="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🎯</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Accuracy</h3>
              <p className="text-slate-400 text-sm">
                Reliable analysis you can trust for making important decisions.
              </p>
            </div>
            <div className="p-6">
              <div className="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center mx-auto mb-4">
                <span className="text-2xl">🔒</span>
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">Privacy</h3>
              <p className="text-slate-400 text-sm">
                Your data is protected with enterprise-grade security.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Call to Action */}
      <section className="mx-auto max-w-5xl px-4 py-16">
        <div className="text-center">
          <h2 className="text-3xl font-bold text-white mb-4">
            Ready to Improve Your SEO?
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto mb-8">
            Join thousands of website owners using SEOIndicator to boost their search visibility.
            Start your free analysis today.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/register/"
              className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
              Get Started Free
              <svg
                className="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M13 7l5 5m0 0l-5 5m5-5H6"
                />
              </svg>
            </Link>
            <Link
              href="/tools/"
              className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-800/50 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
              Explore Tools
            </Link>
          </div>
        </div>
      </section>

      {/* Back to Home Link */}
      <div className="mx-auto max-w-5xl px-4 pb-16">
        <div className="pt-8 border-t border-slate-800">
          <Link
            href="/"
            className="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 transition-colors"
          >
            <svg
              className="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Back to Home
          </Link>
        </div>
      </div>
    </div>
  );
}
