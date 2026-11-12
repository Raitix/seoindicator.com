/**
 * Contact Page
 *
 * Contact channels for SEOIndicator. The addresses match the contact
 * section of the Terms & Privacy page so the two never disagree.
 *
 * Route: /contact
 */

import { Metadata } from 'next';
import Link from 'next/link';
import { Mail, ShieldCheck, Scale, Clock } from 'lucide-react';

// ============================================================================
// SEO Metadata
// ============================================================================

export async function generateMetadata(): Promise<Metadata> {
  const title = 'Contact | SEOIndicator';
  const description =
    'Get in touch with the SEOIndicator team. Support, privacy, and legal enquiries, plus answers to the questions we are asked most often.';
  const url = 'https://seoindicator.com/contact/';

  return {
    title,
    description,
    alternates: {
      canonical: url,
    },
    openGraph: {
      title,
      description,
      url,
      siteName: 'SEOIndicator',
      type: 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
    },
  };
}

// ============================================================================
// Content
// ============================================================================

const CHANNELS = [
  {
    icon: Mail,
    title: 'General support',
    email: 'support@seoindicator.com',
    description:
      'Questions about analyses, billing, account access, or anything else about using the product.',
  },
  {
    icon: ShieldCheck,
    title: 'Privacy enquiries',
    email: 'privacy@seoindicator.com',
    description:
      'Data requests, deletion requests, or questions about how we handle personal data.',
  },
  {
    icon: Scale,
    title: 'Legal enquiries',
    email: 'legal@seoindicator.com',
    description:
      'Terms of service, licensing, takedown requests, or other legal matters.',
  },
];

const FAQS = [
  {
    question: 'How quickly do you reply?',
    answer:
      'We aim to answer every enquiry within two business days. For anything urgent, put "URGENT" in the subject line.',
  },
  {
    question: 'Can I submit a website for analysis without an account?',
    answer:
      'Yes. Every free tool works without signing in - run an analysis from the homepage and you will get all 17 indicators straight away. An account is only needed to save results, track rankings over time, and monitor competitors.',
  },
  {
    question: 'Do you analyse any website?',
    answer:
      'You can analyse any publicly accessible website, including client sites. You are responsible for making sure you have permission to do so.',
  },
  {
    question: 'Can I get a refund?',
    answer:
      'Email support@seoindicator.com with the email address on the account and the date of the charge. We will take a look and come back to you within two business days.',
  },
];

// ============================================================================
// Page Component
// ============================================================================

export default function ContactPage() {
  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
      {/* Breadcrumb Navigation */}
      <nav
        aria-label="Breadcrumb"
        className="border-b border-slate-800 bg-slate-900/50"
      >
        <div className="mx-auto max-w-4xl px-4 py-3">
          <ol className="flex items-center gap-2 text-sm text-slate-400">
            <li>
              <Link href="/" className="hover:text-emerald-400 transition-colors">
                Home
              </Link>
            </li>
            <li className="text-slate-400" aria-hidden="true">/</li>
            <li className="text-white" aria-current="page">
              Contact
            </li>
          </ol>
        </div>
      </nav>

      {/* Page Header */}
      <header className="mx-auto max-w-4xl px-4 py-10 text-center">
        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">
          Contact us
        </h1>
        <p className="text-lg text-slate-400 max-w-2xl mx-auto">
          Questions about the product, your data, or the terms? Use the channel
          that fits and we will get back to you within two business days.
        </p>
      </header>

      {/* Contact channels */}
      <section
        aria-label="Contact channels"
        className="mx-auto max-w-4xl px-4 pb-8"
      >
        <div className="grid gap-4 md:grid-cols-3">
          {CHANNELS.map((channel) => {
            const Icon = channel.icon;
            return (
              <div
                key={channel.email}
                className="rounded-lg border border-slate-700 bg-slate-800/50 p-6 flex flex-col"
              >
                <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                  <Icon className="h-5 w-5" aria-hidden="true" />
                </span>
                <h2 className="mt-4 text-lg font-semibold text-white">
                  {channel.title}
                </h2>
                <p className="mt-2 flex-1 text-sm text-slate-400">
                  {channel.description}
                </p>
                <a
                  href={`mailto:${channel.email}`}
                  className="mt-4 break-all text-sm font-medium text-emerald-400 hover:text-emerald-300"
                >
                  {channel.email}
                </a>
              </div>
            );
          })}
        </div>
      </section>

      {/* Response time */}
      <section className="mx-auto max-w-4xl px-4 pb-12">
        <div className="flex items-start gap-3 rounded-lg border border-blue-500/20 bg-blue-500/10 p-5">
          <Clock className="mt-0.5 h-5 w-5 shrink-0 text-blue-400" aria-hidden="true" />
          <p className="text-sm text-slate-300">
            We answer every message within two business days. For data subject
            requests, email{' '}
            <a
              href="mailto:privacy@seoindicator.com"
              className="text-emerald-400 hover:text-emerald-300"
            >
              privacy@seoindicator.com
            </a>{' '}
            with the subject line &quot;Data Request&quot; and enough information for us
            to verify your identity.
          </p>
        </div>
      </section>

      {/* FAQ */}
      <section aria-label="Frequently asked questions" className="mx-auto max-w-4xl px-4 pb-16">
        <h2 className="text-2xl font-bold text-white mb-6">Common questions</h2>
        <div className="space-y-4">
          {FAQS.map((faq) => (
            <details
              key={faq.question}
              className="group rounded-lg border border-slate-700 bg-slate-800/50"
            >
              <summary className="cursor-pointer list-none px-5 py-4 text-left font-medium text-white marker:hidden hover:text-emerald-400">
                {faq.question}
              </summary>
              <p className="px-5 pb-4 text-sm text-slate-400">{faq.answer}</p>
            </details>
          ))}
        </div>

        <p className="mt-8 text-sm text-slate-500">
          Looking for the legal text instead? Read our{' '}
          <Link
            href="/terms/"
            className="text-emerald-400 hover:text-emerald-300 underline underline-offset-4"
          >
            Terms of Service
          </Link>{' '}
          and{' '}
          <Link
            href="/privacy/"
            className="text-emerald-400 hover:text-emerald-300 underline underline-offset-4"
          >
            Privacy Policy
          </Link>
          .
        </p>
      </section>
    </main>
  );
}
