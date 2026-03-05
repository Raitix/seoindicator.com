/**
 * HowItWorksContent Component
 * Task: TASK-30-3 - How It Works Page
 *
 * Displays step-by-step user journey, indicator categories breakdown,
 * scoring methodology, and FAQ section.
 */

import React from 'react';
import Link from 'next/link';
import {
  Search,
  Settings,
  BarChart3,
  Lightbulb,
  RefreshCw,
  Shield,
  FileText,
  Globe,
  Smartphone,
  Link2,
  Languages,
  Type,
  AlignLeft,
  Heading1,
  Hash,
  Image as ImageIcon,
  Zap,
  Activity,
  Monitor,
  Eye,
  ChevronRight,
} from 'lucide-react';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';
import { Progress } from '@/components/ui/progress';

// ============================================================================
// Types
// ============================================================================

interface HowItWorksContentProps {
  className?: string;
}

interface Step {
  number: number;
  icon: React.ReactNode;
  title: string;
  description: string;
}

interface IndicatorCategory {
  name: string;
  weight: number;
  color: string;
  bgColor: string;
  indicators: { name: string; icon: React.ReactNode }[];
}

interface FAQItem {
  question: string;
  answer: string;
}

// ============================================================================
// Data
// ============================================================================

const steps: Step[] = [
  {
    number: 1,
    icon: <Search className="h-6 w-6" aria-hidden="true" />,
    title: 'Enter Your URL',
    description:
      'Enter the URL of the page you want to analyze for SEO performance. Our system accepts any publicly accessible webpage.',
  },
  {
    number: 2,
    icon: <Settings className="h-6 w-6" aria-hidden="true" />,
    title: 'Configure Analysis',
    description:
      'Select the type of analysis: quick, standard, or deep scan. Each option provides different levels of detail and processing time.',
  },
  {
    number: 3,
    icon: <BarChart3 className="h-6 w-6" aria-hidden="true" />,
    title: 'Get Results',
    description:
      'Receive a comprehensive SEO score with detailed breakdown across all 17 indicators, complete with specific recommendations.',
  },
  {
    number: 4,
    icon: <Lightbulb className="h-6 w-6" aria-hidden="true" />,
    title: 'Implement Fixes',
    description:
      'Follow actionable, AI-powered recommendations to improve your score. Each suggestion includes step-by-step implementation guides.',
  },
  {
    number: 5,
    icon: <RefreshCw className="h-6 w-6" aria-hidden="true" />,
    title: 'Track Progress',
    description:
      'Monitor improvements over time with historical tracking. Set up alerts for score changes and new optimization opportunities.',
  },
];

const indicatorCategories: IndicatorCategory[] = [
  {
    name: 'Technical',
    weight: 30,
    color: 'text-blue-400',
    bgColor: 'bg-blue-500',
    indicators: [
      { name: 'robots.txt', icon: <FileText className="h-4 w-4" aria-hidden="true" /> },
      { name: 'sitemap.xml', icon: <Globe className="h-4 w-4" aria-hidden="true" /> },
      { name: 'SSL Certificate', icon: <Shield className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Mobile-Friendly', icon: <Smartphone className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Canonical Tags', icon: <Link2 className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Hreflang', icon: <Languages className="h-4 w-4" aria-hidden="true" /> },
    ],
  },
  {
    name: 'Content',
    weight: 40,
    color: 'text-emerald-400',
    bgColor: 'bg-emerald-500',
    indicators: [
      { name: 'Title Tag', icon: <Type className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Meta Description', icon: <AlignLeft className="h-4 w-4" aria-hidden="true" /> },
      { name: 'H1 Heading', icon: <Heading1 className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Keyword Density', icon: <Hash className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Image Optimization', icon: <ImageIcon className="h-4 w-4" aria-hidden="true" /> },
    ],
  },
  {
    name: 'Performance',
    weight: 20,
    color: 'text-yellow-400',
    bgColor: 'bg-yellow-500',
    indicators: [
      { name: 'Page Speed', icon: <Zap className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Core Web Vitals', icon: <Activity className="h-4 w-4" aria-hidden="true" /> },
    ],
  },
  {
    name: 'UX',
    weight: 10,
    color: 'text-purple-400',
    bgColor: 'bg-purple-500',
    indicators: [
      { name: 'Mobile Viewport', icon: <Monitor className="h-4 w-4" aria-hidden="true" /> },
      { name: 'Font Readability', icon: <Eye className="h-4 w-4" aria-hidden="true" /> },
    ],
  },
];

const faqItems: FAQItem[] = [
  {
    question: 'How is my SEO score calculated?',
    answer:
      'Your SEO score is calculated based on 17 different indicators across 4 categories: Technical (30%), Content (40%), Performance (20%), and UX (10%). Each indicator is scored from 0-100, and the final score is a weighted average of all indicators. We use industry-standard benchmarks and Google\'s official guidelines to determine scoring thresholds.',
  },
  {
    question: 'How often should I run an SEO analysis?',
    answer:
      'We recommend running an analysis at least once a month or after significant content changes. For high-traffic websites or those in competitive niches, weekly analysis can help you stay ahead of issues. Our automated monitoring feature can alert you to significant changes in your SEO health.',
  },
  {
    question: 'What is the difference between quick, standard, and deep analysis?',
    answer:
      'Quick analysis checks essential indicators in under a minute, perfect for regular monitoring. Standard analysis covers all 17 indicators with moderate depth, taking 2-3 minutes. Deep analysis provides comprehensive insights including competitor comparison and historical data, typically taking 5-10 minutes.',
  },
  {
    question: 'Can I analyze multiple pages at once?',
    answer:
      'Yes! You can add multiple pages to a project and analyze them together. This is especially useful for comprehensive site audits. Project-level analysis also provides aggregated scores and identifies common issues across your site.',
  },
  {
    question: 'How accurate are the SEO recommendations?',
    answer:
      'Our recommendations are based on Google\'s official guidelines and industry best practices. We continuously update our algorithms to reflect the latest SEO standards. While no automated tool can guarantee search rankings, following our recommendations typically leads to measurable improvements.',
  },
  {
    question: 'What are Core Web Vitals?',
    answer:
      'Core Web Vitals are specific metrics that measure user experience: Largest Contentful Paint (LCP) measures loading performance, First Input Delay (FID) measures interactivity, and Cumulative Layout Shift (CLS) measures visual stability. Google uses these as ranking factors, making them crucial for SEO.',
  },
  {
    question: 'Do I need technical knowledge to use SEOIndicator?',
    answer:
      'No! Our tool is designed for users of all skill levels. While advanced users can dive deep into technical metrics, beginners get plain-language explanations and step-by-step guides for every recommendation. We translate complex SEO concepts into actionable tasks.',
  },
  {
    question: 'How do I fix the issues found?',
    answer:
      'Each issue includes detailed recommendations with step-by-step guides. For common issues, we provide code snippets and implementation examples. You can also export reports to share with your development team. Our AI assistant can answer specific questions about any recommendation.',
  },
  {
    question: 'Can I export my analysis results?',
    answer:
      'Yes, you can export results in PDF for presentations, CSV for data analysis, or JSON for integration with other tools. Scheduled reports can be automatically delivered to your email. Team plans include API access for automated reporting workflows.',
  },
  {
    question: 'Is my data secure?',
    answer:
      'Absolutely. We use industry-standard encryption (TLS 1.3) for all data in transit and AES-256 for data at rest. We never share your analysis data with third parties. Your URLs and reports are private to your account, and you can delete them at any time.',
  },
];

// ============================================================================
// Component
// ============================================================================

/**
 * HowItWorksContent Component
 * Renders How It Works page content with steps, categories, scoring, and FAQ
 */
export default function HowItWorksContent({
  className = '',
}: HowItWorksContentProps) {
  return (
    <div className={className}>
      {/* Hero Section */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-slate-900/50" />

        <div className="relative mx-auto max-w-5xl px-4 py-16 md:py-24">
          <div className="text-center">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
              How{' '}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
                It Works
              </span>
            </h1>
            <p className="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
              Understand how SEOIndicator analyzes your website and delivers
              actionable insights to improve your search visibility.
            </p>
          </div>
        </div>
      </section>

      {/* User Journey Steps Section */}
      <section
        className="mx-auto max-w-5xl px-4 py-16"
        aria-labelledby="steps-heading"
      >
        <div className="text-center mb-12">
          <h2 id="steps-heading" className="text-3xl font-bold text-white mb-4">
            Your SEO Journey in 5 Steps
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto">
            From entering your URL to tracking improvements, here&apos;s how our
            platform guides you through the optimization process.
          </p>
        </div>

        <div className="relative">
          {/* Connection Line */}
          <div
            className="hidden md:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-slate-800 -translate-x-1/2"
            aria-hidden="true"
          />

          <div className="space-y-8 md:space-y-0">
            {steps.map((step, index) => (
              <div
                key={step.number}
                className={`relative flex flex-col md:flex-row items-center gap-6 ${
                  index % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'
                }`}
              >
                {/* Content */}
                <div
                  className={`flex-1 ${
                    index % 2 === 0 ? 'md:text-right' : 'md:text-left'
                  }`}
                >
                  <div
                    className={`rounded-xl border border-slate-800 bg-slate-900/50 p-6 ${
                      index % 2 === 0 ? 'md:mr-8' : 'md:ml-8'
                    }`}
                  >
                    <div className="flex items-center gap-3 mb-3">
                      <div
                        className={`flex items-center gap-2 ${
                          index % 2 === 0
                            ? 'md:flex-row-reverse'
                            : 'md:flex-row'
                        }`}
                      >
                        <span className="text-2xl font-bold text-emerald-400">
                          {step.number}
                        </span>
                        <div
                          className="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400"
                          aria-hidden="true"
                        >
                          {step.icon}
                        </div>
                      </div>
                    </div>
                    <h3 className="text-lg font-semibold text-white mb-2">
                      {step.title}
                    </h3>
                    <p className="text-slate-400 text-sm leading-relaxed">
                      {step.description}
                    </p>
                  </div>
                </div>

                {/* Center Dot */}
                <div
                  className="relative z-10 w-4 h-4 rounded-full bg-emerald-500 border-4 border-slate-950"
                  aria-hidden="true"
                />

                {/* Empty space for alignment */}
                <div className="flex-1 hidden md:block" aria-hidden="true" />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Indicator Categories Section */}
      <section
        className="bg-slate-900/30 border-y border-slate-800"
        aria-labelledby="categories-heading"
      >
        <div className="mx-auto max-w-5xl px-4 py-16">
          <div className="text-center mb-12">
            <h2
              id="categories-heading"
              className="text-3xl font-bold text-white mb-4"
            >
              17 SEO Indicators Across 4 Categories
            </h2>
            <p className="text-slate-400 max-w-2xl mx-auto">
              We analyze your website across technical, content, performance,
              and UX factors to give you a complete SEO health picture.
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {indicatorCategories.map((category) => (
              <div
                key={category.name}
                className="rounded-xl border border-slate-800 bg-slate-900/50 p-6"
              >
                <div className="flex items-center justify-between mb-4">
                  <h3 className={`text-xl font-bold ${category.color}`}>
                    {category.name}
                  </h3>
                  <span
                    className={`text-sm font-medium px-3 py-1 rounded-full ${category.bgColor}/10 ${category.color}`}
                  >
                    {category.weight}%
                  </span>
                </div>

                {/* Weight Progress Bar */}
                <div className="mb-6">
                  <div className="flex items-center justify-between text-sm text-slate-400 mb-2">
                    <span>Weight in Overall Score</span>
                    <span>{category.weight}%</span>
                  </div>
                  <div className="relative h-2 w-full overflow-hidden rounded-full bg-slate-800">
                    <div
                      className={`h-full ${category.bgColor} transition-all`}
                      style={{ width: `${category.weight}%` }}
                    />
                  </div>
                </div>

                {/* Indicators List */}
                <ul className="space-y-3" role="list">
                  {category.indicators.map((indicator) => (
                    <li
                      key={indicator.name}
                      className="flex items-center gap-3 text-slate-300"
                    >
                      <span className="text-slate-400">{indicator.icon}</span>
                      <span className="text-sm">{indicator.name}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Scoring Methodology Section */}
      <section
        className="mx-auto max-w-5xl px-4 py-16"
        aria-labelledby="scoring-heading"
      >
        <div className="text-center mb-12">
          <h2 id="scoring-heading" className="text-3xl font-bold text-white mb-4">
            Scoring Methodology
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto">
            Our weighted scoring system provides a balanced assessment of your
            website&apos;s SEO health.
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-8">
          {/* Score Range */}
          <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-lg font-semibold text-white mb-4">
              Score Range
            </h3>
            <div className="space-y-4">
              <div className="flex items-center gap-4">
                <div className="text-4xl font-bold text-emerald-400">0-100</div>
                <div className="text-slate-400">
                  Final score is normalized to a 0-100 scale for easy
                  interpretation.
                </div>
              </div>

              <div className="space-y-3 mt-6">
                <div className="flex items-center gap-3">
                  <div className="w-4 h-4 rounded bg-emerald-500" />
                  <span className="text-slate-300">
                    <strong className="text-emerald-400">80-100:</strong>{' '}
                    Excellent - Your page is well-optimized
                  </span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-4 h-4 rounded bg-yellow-500" />
                  <span className="text-slate-300">
                    <strong className="text-yellow-400">60-79:</strong> Good -
                    Room for improvement exists
                  </span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-4 h-4 rounded bg-orange-500" />
                  <span className="text-slate-300">
                    <strong className="text-orange-400">40-59:</strong> Needs
                    Work - Several issues to address
                  </span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-4 h-4 rounded bg-red-500" />
                  <span className="text-slate-300">
                    <strong className="text-red-400">0-39:</strong> Poor -
                    Critical issues need immediate attention
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Weight System */}
          <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-lg font-semibold text-white mb-4">
              Weight Distribution
            </h3>
            <p className="text-slate-400 text-sm mb-6">
              Categories are weighted based on their impact on search rankings
              and user experience.
            </p>

            <div className="space-y-4">
              {indicatorCategories.map((category) => (
                <div key={category.name}>
                  <div className="flex items-center justify-between text-sm mb-1">
                    <span className={`${category.color} font-medium`}>
                      {category.name}
                    </span>
                    <span className="text-slate-400">{category.weight}%</span>
                  </div>
                  <Progress value={category.weight} className="h-2" />
                </div>
              ))}
            </div>

            <div className="mt-6 pt-4 border-t border-slate-800">
              <div className="flex items-center justify-between text-sm">
                <span className="text-slate-400">Total</span>
                <span className="text-emerald-400 font-bold">100%</span>
              </div>
            </div>
          </div>
        </div>

        {/* Formula Explanation */}
        <div className="mt-8 rounded-xl border border-slate-800 bg-slate-900/50 p-6">
          <h3 className="text-lg font-semibold text-white mb-4">
            How Scores Are Calculated
          </h3>
          <div className="text-slate-300 text-sm leading-relaxed space-y-3">
            <p>
              Each indicator is scored from 0-100 based on specific criteria.
              For example, a title tag is evaluated on length (optimal:
              50-60 characters), keyword presence, and uniqueness.
            </p>
            <p>
              Category scores are calculated as the average of their indicator
              scores. The final score is a weighted sum:
            </p>
            <div className="bg-slate-950 rounded-lg p-4 font-mono text-xs text-slate-400 overflow-x-auto">
              <code>
                Final Score = (Technical × 0.30) + (Content × 0.40) +
                (Performance × 0.20) + (UX × 0.10)
              </code>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section
        className="bg-slate-900/30 border-y border-slate-800"
        aria-labelledby="faq-heading"
      >
        <div className="mx-auto max-w-3xl px-4 py-16">
          <div className="text-center mb-12">
            <h2 id="faq-heading" className="text-3xl font-bold text-white mb-4">
              Frequently Asked Questions
            </h2>
            <p className="text-slate-400">
              Common questions about SEOIndicator and SEO analysis.
            </p>
          </div>

          <Accordion
            type="single"
            collapsible
            className="w-full"
            defaultValue="item-0"
          >
            {faqItems.map((item, index) => (
              <AccordionItem key={index} value={`item-${index}`}>
                <AccordionTrigger className="text-left">
                  {item.question}
                </AccordionTrigger>
                <AccordionContent className="text-slate-400 leading-relaxed">
                  {item.answer}
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        </div>
      </section>

      {/* Call to Action */}
      <section className="mx-auto max-w-5xl px-4 py-16">
        <div className="text-center">
          <h2 className="text-3xl font-bold text-white mb-4">
            Ready to Analyze Your Website?
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto mb-8">
            Start your SEO journey today. Get instant insights and actionable
            recommendations to improve your search visibility.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/tools/seo-analyzer/"
              className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
              Start Free Analysis
              <ChevronRight className="h-4 w-4" aria-hidden="true" />
            </Link>
            <Link
              href="/register/"
              className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-800/50 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950"
            >
              Create Free Account
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
