/**
 * Pricing Page
 * Task: TASK-32-16 - Internal Linking Implementation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Pricing page with tier comparison and "Compare with Alternatives" section.
 */

import { Metadata } from 'next';
import Link from 'next/link';
import { Check, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

export const metadata: Metadata = {
  title: 'Pricing - SEOIndicator',
  description: 'Choose the right SEOIndicator plan for your needs. From free tier for individuals to agency plans for teams.',
  alternates: {
    canonical: '/pricing',
  },
  openGraph: {
    title: 'Pricing - SEOIndicator',
    description: 'Choose the right SEOIndicator plan for your needs. From free tier for individuals to agency plans for teams.',
    type: 'website',
  },
};

// ============================================================================
// Pricing Tier Data
// ============================================================================

interface PricingTier {
  name: string;
  price: number | 'Free';
  period: string;
  description: string;
  features: { text: string; included: boolean }[];
  highlighted?: boolean;
  cta: string;
  ctaHref: string;
}

const pricingTiers: PricingTier[] = [
  {
    name: 'Free',
    price: 'Free',
    period: 'forever',
    description: 'Perfect for individuals getting started with SEO',
    features: [
      { text: 'Up to 10 keywords', included: true },
      { text: 'Basic ranking tracking', included: true },
      { text: 'Weekly reports', included: true },
      { text: '1 project', included: true },
      { text: 'Competitor analysis', included: false },
      { text: 'API access', included: false },
    ],
    cta: 'Get Started Free',
    ctaHref: '/register',
  },
  {
    name: 'Pro',
    price: 19,
    period: '/month',
    description: 'Ideal for freelancers and small businesses',
    features: [
      { text: 'Up to 100 keywords', included: true },
      { text: 'Daily ranking updates', included: true },
      { text: 'Competitor analysis (3)', included: true },
      { text: 'Up to 5 projects', included: true },
      { text: 'API access', included: true },
      { text: 'Priority support', included: false },
    ],
    highlighted: true,
    cta: 'Start Pro Trial',
    ctaHref: '/register?plan=pro',
  },
  {
    name: 'Business',
    price: 49,
    period: '/month',
    description: 'For growing teams with more SEO needs',
    features: [
      { text: 'Up to 500 keywords', included: true },
      { text: 'Hourly ranking updates', included: true },
      { text: 'Competitor analysis (10)', included: true },
      { text: 'Unlimited projects', included: true },
      { text: 'API access', included: true },
      { text: 'Priority support', included: true },
    ],
    cta: 'Start Business Trial',
    ctaHref: '/register?plan=business',
  },
  {
    name: 'Agency',
    price: 99,
    period: '/month',
    description: 'For agencies managing multiple clients',
    features: [
      { text: 'Unlimited keywords', included: true },
      { text: 'Real-time updates', included: true },
      { text: 'Unlimited competitors', included: true },
      { text: 'White-label reports', included: true },
      { text: 'Team collaboration', included: true },
      { text: 'Dedicated support', included: true },
    ],
    cta: 'Contact Sales',
    ctaHref: '/contact',
  },
];

// ============================================================================
// Comparison Preview Data
// ============================================================================

const comparisonPreviews = [
  {
    slug: 'seoindicator-vs-ahrefs',
    title: 'vs Ahrefs',
    highlight: '90% cheaper',
  },
  {
    slug: 'seoindicator-vs-semrush',
    title: 'vs SEMrush',
    highlight: 'Essential features',
  },
  {
    slug: 'seoindicator-vs-moz',
    title: 'vs Moz',
    highlight: 'More intuitive',
  },
];

// ============================================================================
// Pricing Page Component
// ============================================================================

export default function PricingPage() {
  return (
    <div className="min-h-screen bg-slate-950 text-white">
      {/* Header */}
      <header className="border-b border-slate-800 bg-slate-900/50">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <Link href="/" className="text-xl font-bold text-white">
            SEOIndicator
          </Link>
          <nav className="flex items-center gap-4">
            <Link href="/dashboard" className="text-slate-300 hover:text-white transition-colors">
              Dashboard
            </Link>
            <Link href="/seo-guides/" className="text-slate-300 hover:text-white transition-colors">
              Guides
            </Link>
            <Link href="/tools" className="text-slate-300 hover:text-white transition-colors">
              Tools
            </Link>
          </nav>
        </div>
      </header>

      <main className="container mx-auto px-4 py-16">
        {/* Page Title */}
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold text-white mb-4">
            Simple, Transparent Pricing
          </h1>
          <p className="text-lg text-slate-400 max-w-2xl mx-auto">
            Choose the plan that fits your SEO needs. All plans include a 14-day free trial.
          </p>
        </div>

        {/* Pricing Tiers */}
        <section aria-label="Pricing tiers" data-testid="pricing-tiers" className="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
          {pricingTiers.map((tier) => (
            <Card
              key={tier.name}
              className={`bg-slate-900 border-slate-800 ${
                tier.highlighted
                  ? 'border-emerald-500 ring-1 ring-emerald-500/20'
                  : ''
              }`}
              data-testid={`pricing-tier-${tier.name.toLowerCase()}`}
            >
              <CardHeader>
                {tier.highlighted && (
                  <span className="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2">
                    Most Popular
                  </span>
                )}
                <CardTitle className="text-white">{tier.name}</CardTitle>
                <div className="mt-2">
                  <span className="text-3xl font-bold text-white">
                    {tier.price === 'Free' ? 'Free' : `$${tier.price}`}
                  </span>
                  {tier.price !== 'Free' && (
                    <span className="text-slate-400">{tier.period}</span>
                  )}
                </div>
                <CardDescription className="text-slate-400 mt-2">
                  {tier.description}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-3">
                  {tier.features.map((feature, index) => (
                    <li key={index} className="flex items-center gap-2">
                      {feature.included ? (
                        <Check className="h-4 w-4 text-emerald-500" />
                      ) : (
                        <X className="h-4 w-4 text-slate-600" />
                      )}
                      <span
                        className={
                          feature.included ? 'text-slate-300' : 'text-slate-500'
                        }
                      >
                        {feature.text}
                      </span>
                    </li>
                  ))}
                </ul>
              </CardContent>
              <CardFooter>
                <Button
                  asChild
                  className={`w-full ${
                    tier.highlighted
                      ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
                      : 'bg-slate-800 hover:bg-slate-700 text-white'
                  }`}
                >
                  <Link href={tier.ctaHref}>{tier.cta}</Link>
                </Button>
              </CardFooter>
            </Card>
          ))}
        </section>

        {/* Compare with Alternatives Section */}
        <section
          aria-label="Compare with alternatives"
          data-testid="compare-alternatives-section"
          className="border-t border-slate-800 pt-16"
        >
          <h2 className="text-2xl md:text-3xl font-bold text-white text-center mb-4">
            Compare with Alternatives
          </h2>
          <p className="text-slate-400 text-center mb-8 max-w-2xl mx-auto">
            See how SEOIndicator stacks up against other popular SEO tools.
          </p>

          {/* Comparison Preview Cards */}
          <div
            className="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-8"
            data-testid="comparison-preview-cards"
          >
            {comparisonPreviews.map((preview) => (
              <Link
                key={preview.slug}
                href={`/compare/${preview.slug}`}
                className="group block p-6 bg-slate-900 border border-slate-800 rounded-lg hover:border-emerald-500/50 transition-colors"
                data-testid={`preview-${preview.slug.split('-vs-')[1]}`}
              >
                <h3 className="text-lg font-semibold text-white group-hover:text-emerald-400 transition-colors mb-2">
                  {preview.title}
                </h3>
                <p className="text-sm text-emerald-400">{preview.highlight}</p>
                <span className="text-sm text-slate-500 mt-2 block">
                  View comparison →
                </span>
              </Link>
            ))}
          </div>

          {/* View All Link */}
          <div className="text-center">
            <Button asChild variant="outline" className="border-slate-700 text-slate-300 hover:text-white hover:border-emerald-500">
              <Link href="/compare" data-testid="view-all-pricing-comparisons">
                View all comparisons
              </Link>
            </Button>
          </div>
        </section>

        {/* FAQ Section */}
        <section className="border-t border-slate-800 pt-16 mt-16">
          <h2 className="text-2xl font-bold text-white text-center mb-8">
            Frequently Asked Questions
          </h2>
          <div className="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            {[
              {
                q: 'Can I switch plans later?',
                a: 'Yes, you can upgrade or downgrade your plan at any time. Changes take effect at the start of your next billing cycle.',
              },
              {
                q: 'Is there a free trial?',
                a: 'All paid plans include a 14-day free trial. No credit card required to start.',
              },
              {
                q: 'What payment methods do you accept?',
                a: 'We accept all major credit cards, PayPal, and bank transfers for annual plans.',
              },
              {
                q: 'Do you offer refunds?',
                a: 'Yes, we offer a 30-day money-back guarantee on all paid plans.',
              },
            ].map((faq, index) => (
              <div key={index} className="p-6 bg-slate-900 rounded-lg border border-slate-800">
                <h3 className="font-semibold text-white mb-2">{faq.q}</h3>
                <p className="text-slate-400 text-sm">{faq.a}</p>
              </div>
            ))}
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="border-t border-slate-800 mt-16">
        <div className="container mx-auto px-4 py-8 text-center text-slate-500">
          <p>© {new Date().getFullYear()} SEOIndicator. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}
