"use client";

import Link from "next/link";
import { useConsentContext } from "@/lib/contexts/ConsentContext";

/**
 * Footer component with Cookie Settings link
 * TASK-30-5: Cookie Consent Integration
 *
 * Provides access to cookie consent management via "Cookie Settings" link.
 * Clicking the link shows the consent banner again for users to change
 * their preferences.
 *
 * Note: This component must be rendered inside a ConsentProvider
 */
interface FooterProps {
  /** Optional callback to show the cookie consent banner (deprecated - uses context directly) */
  onShowCookieSettings?: () => void;
}

export default function Footer({ onShowCookieSettings }: FooterProps) {
  // Try to use context directly for banner control
  // Falls back to prop callback if context is unavailable (for backward compatibility)
  let setShowBanner: (show: boolean) => void = () => {};
  try {
    const context = useConsentContext();
    setShowBanner = context.setShowBanner;
  } catch {
    // Context not available, rely on prop callback
  }

  const handleCookieSettingsClick = (
    e: React.MouseEvent<HTMLButtonElement>
  ) => {
    e.preventDefault();
    // Prefer context, fall back to prop callback
    setShowBanner(true);
    if (onShowCookieSettings) {
      onShowCookieSettings();
    }
    // Scroll to top so banner is visible
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const currentYear = new Date().getFullYear();

  return (
    <footer
      className="bg-slate-900 border-t border-slate-800"
      role="contentinfo"
      aria-label="Site footer"
      data-testid="site-footer"
    >
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand Column */}
          <div className="md:col-span-1">
            <Link
              href="/"
              className="text-xl font-bold text-white hover:text-blue-400 transition-colors"
            >
              SEOIndicator
            </Link>
            <p className="mt-2 text-sm text-slate-400">
              AI-powered SEO analysis tool for better rankings.
            </p>
          </div>

          {/* Tools Column */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">
              Tools
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href="/tools/meta-tag-analyzer/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Meta Tag Analyzer
                </Link>
              </li>
              <li>
                <Link
                  href="/tools/keyword-density/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Keyword Density
                </Link>
              </li>
              <li>
                <Link
                  href="/tools/social-preview/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Social Preview
                </Link>
              </li>
              <li>
                <Link
                  href="/tools/mobile-friendly/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Mobile Friendly
                </Link>
              </li>
            </ul>
          </div>

          {/* Resources Column */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">
              Resources
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href="/seo-guides/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  SEO Guides
                </Link>
              </li>
              <li>
                <Link
                  href="/blog/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Blog
                </Link>
              </li>
              <li>
                <Link
                  href="/about/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  About
                </Link>
              </li>
              <li>
                <Link
                  href="/contact/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Contact
                </Link>
              </li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">
              Legal
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href="/terms/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Terms of Service
                </Link>
              </li>
              <li>
                <Link
                  href="/privacy/"
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                >
                  Privacy Policy
                </Link>
              </li>
              <li>
                <button
                  onClick={handleCookieSettingsClick}
                  className="text-sm text-slate-400 hover:text-white transition-colors"
                  aria-label="Open cookie settings"
                  data-testid="cookie-settings-link"
                >
                  Cookie Settings
                </button>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-8 pt-8 border-t border-slate-800">
          <div className="flex flex-col md:flex-row justify-between items-center gap-4">
            <p className="text-sm text-slate-400">
              © {currentYear} SEOIndicator.com. All rights reserved.
            </p>
            <div className="flex items-center gap-4">
              <Link
                href="/terms/"
                className="text-xs text-slate-500 hover:text-slate-400 transition-colors"
              >
                Terms
              </Link>
              <span className="text-slate-700">|</span>
              <Link
                href="/privacy/"
                className="text-xs text-slate-500 hover:text-slate-400 transition-colors"
              >
                Privacy
              </Link>
              <span className="text-slate-700">|</span>
              <button
                onClick={handleCookieSettingsClick}
                className="text-xs text-slate-500 hover:text-slate-400 transition-colors"
                aria-label="Open cookie settings"
              >
                Cookies
              </button>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
