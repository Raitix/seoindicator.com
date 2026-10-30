/**
 * TermsContent Component
 * Task: TASK-30-1 - Terms & Privacy Page
 * 
 * Displays combined Terms of Service and Privacy Policy content.
 * Includes 5 sections: Introduction, Terms of Use, Privacy Policy, Cookie Policy, Contact.
 */

import React from 'react';
import Link from 'next/link';

// ============================================================================
// Types
// ============================================================================

interface TermsContentProps {
  className?: string;
}

// ============================================================================
// Section Data
// ============================================================================

const LAST_UPDATED = 'March 5, 2026';

const sections = [
  {
    id: 'introduction',
    title: 'Introduction',
    content: `
      Welcome to SEOIndicator.com. These Terms of Service and Privacy Policy govern your use of our website and services. By accessing or using SEOIndicator, you agree to be bound by these terms.

      SEOIndicator provides AI-powered SEO analysis tools to help website owners and marketers improve their search engine visibility. Our services include website analysis, keyword tracking, competitor analysis, and SEO recommendations.

      Please read these terms carefully before using our services. If you do not agree with any part of these terms, you must not use our services.
    `,
  },
  {
    id: 'terms-of-use',
    title: 'Terms of Use',
    content: `
      By using SEOIndicator, you agree to the following terms:

      **Account Registration:** You must provide accurate and complete information when creating an account. You are responsible for maintaining the security of your account credentials.

      **Acceptable Use:** You agree to use our services only for lawful purposes. You may not use our services to analyze websites you do not own or have permission to analyze, or to violate any applicable laws or regulations.

      **Service Limitations:** Our free tier services are provided with certain limitations. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.

      **Intellectual Property:** All content, features, and functionality of SEOIndicator are owned by us or our licensors and are protected by intellectual property laws.

      **User Content:** You retain ownership of any content you submit to our services. By submitting content, you grant us a license to process it for the purpose of providing our services.

      **Disclaimer:** Our services are provided "as is" without warranties of any kind. We do not guarantee the accuracy of SEO recommendations or analysis results.
    `,
  },
  {
    id: 'privacy-policy',
    title: 'Privacy Policy',
    content: `
      We collect and process information to provide and improve our services:

      **Information We Collect:** We collect information you provide directly (such as account registration details), information about your website URLs you analyze, and usage data about how you interact with our services.

      **How We Use Information:** We use collected information to provide our services, communicate with you, improve our offerings, and ensure the security of our platform.

      **Data Retention:** We retain your personal information for as long as your account is active or as needed to provide services. You can request deletion of your account and associated data.

      **Data Sharing:** We do not sell your personal information. We may share information with service providers who assist in operating our platform, subject to confidentiality obligations.

      **Your Rights:** You have the right to access, correct, or delete your personal information. Contact us at privacy@seoindicator.com to exercise these rights.

      **International Transfers:** Your information may be transferred to and processed in countries other than your own. We implement appropriate safeguards for such transfers.

      **Children's Privacy:** Our services are not intended for children under 13. We do not knowingly collect information from children under 13.
    `,
  },
  {
    id: 'cookie-policy',
    title: 'Cookie Policy',
    content: `
      We use cookies and similar technologies to operate our website and improve your experience:

      **Essential Cookies:** These cookies are necessary for the website to function properly. They enable core functionality such as security, network management, and accessibility.

      **Analytics Cookies:** We use analytics cookies to understand how visitors interact with our website. This helps us improve our services and user experience.

      **Preference Cookies:** These cookies remember your preferences and settings to provide a more personalized experience.

      **Managing Cookies:** You can control cookies through your browser settings. However, disabling certain cookies may affect the functionality of our services.

      **Third-Party Cookies:** Some third-party services we use may set their own cookies. We do not control these cookies and recommend reviewing the privacy policies of those services.

      **Cookie Duration:** Most cookies we set are session cookies that expire when you close your browser. Persistent cookies have varying durations based on their purpose.
    `,
  },
  {
    id: 'contact',
    title: 'Contact',
    content: `
      If you have questions about these Terms of Service, Privacy Policy, or our services, please contact us:

      **Email:** support@seoindicator.com
      **Privacy Inquiries:** privacy@seoindicator.com
      **Legal Inquiries:** legal@seoindicator.com

      **Business Address:**
      SEOIndicator
      [Business Address]

      We aim to respond to all inquiries within 2 business days. For urgent matters, please include "URGENT" in your email subject line.

      For data subject requests under applicable privacy laws, please email privacy@seoindicator.com with the subject line "Data Request" and include sufficient information to verify your identity.
    `,
  },
];

// ============================================================================
// Component
// ============================================================================

/**
 * TermsContent Component
 * Renders Terms of Service and Privacy Policy content with navigation
 */
export default function TermsContent({ className = '' }: TermsContentProps) {
  return (
    <div className={`prose prose-invert prose-slate max-w-none ${className}`}>
      {/* Last Updated Badge */}
      <div className="mb-6 flex items-center gap-2">
        <time
          data-testid="last-updated"
          className="inline-flex items-center rounded-full border border-slate-700 bg-slate-800/50 px-3 py-1 text-sm text-slate-400"
          dateTime="2026-03-05"
        >
          <svg
            className="mr-2 h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Last Updated: {LAST_UPDATED}
        </time>
      </div>

      {/* Table of Contents */}
      <nav
        aria-label="Table of contents"
        className="mb-8 rounded-lg border border-slate-800 bg-slate-900/50 p-4"
      >
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-400">
          Table of Contents
        </h2>
        <ul className="space-y-2">
          {sections.map((section) => (
            <li key={section.id}>
              <a
                href={`#${section.id}`}
                className="text-emerald-400 hover:text-emerald-300 transition-colors"
              >
                {section.title}
              </a>
            </li>
          ))}
        </ul>
      </nav>

      {/* Content Sections */}
      {sections.map((section, index) => (
        <section
          key={section.id}
          id={section.id}
          className="mb-10 scroll-mt-20"
        >
          <h2 className="text-2xl font-semibold text-white mb-4 flex items-center gap-3">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 text-sm font-bold">
              {index + 1}
            </span>
            {section.title}
          </h2>
          <div className="text-slate-300 leading-relaxed whitespace-pre-line">
            {section.content.split('\n').map((paragraph, pIndex) => {
              const trimmed = paragraph.trim();
              if (!trimmed) return null;
              
              // Handle bold text (markdown-style **text**)
              if (trimmed.startsWith('**') && trimmed.includes(':**')) {
                const match = trimmed.match(/\*\*(.+?)\*\*/);
                if (match) {
                  const labelText = match[1];
                  const restOfText = trimmed.replace(/\*\*.+?\*\*/, '').trim();
                  return (
                    <p key={pIndex} className="mb-3">
                      <strong className="text-white">{labelText}</strong>
                      {restOfText}
                    </p>
                  );
                }
              }
              
              return (
                <p key={pIndex} className="mb-3">
                  {trimmed}
                </p>
              );
            })}
          </div>
        </section>
      ))}

      {/* Back to Home Link */}
      <div className="mt-12 pt-8 border-t border-slate-800">
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
  );
}
