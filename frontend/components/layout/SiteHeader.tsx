"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, X } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { AUTH_STORAGE_KEYS } from "@/lib/types/auth";

/**
 * SiteHeader
 *
 * The single, persistent top bar for every page of the marketing site.
 *
 * It used to be duplicated in ~21 page files, each with its own links, its own
 * styling and (on the homepage) no mobile menu at all. Rendering it here -
 * once, in the root layout - guarantees it is present on every route, that it
 * scrolls away with the page, and that the link set cannot drift between
 * pages.
 *
 * Auth-aware extras (Dashboard / Projects) appear once a visitor signs in.
 */

export interface SiteHeaderProps {
  className?: string;
}

interface HeaderLink {
  label: string;
  href: string;
}

/** The public link set. Kept in one place so every page agrees. */
const NAV_LINKS: HeaderLink[] = [
  { label: "Tools", href: "/tools" },
  { label: "Guides", href: "/seo-guides" },
  { label: "Pricing", href: "/pricing" },
  { label: "Compare", href: "/compare" },
  { label: "About", href: "/about" },
];

/** Shown only once the visitor is signed in. */
const AUTH_LINKS: HeaderLink[] = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Projects", href: "/projects" },
];

/**
 * Normalise a pathname for comparison: drop the trailing slash and ignore
 * case, so "/Pricing" and "/pricing/" both light up the Pricing link.
 */
function normalisePath(pathname: string): string {
  if (!pathname) return "/";
  const trimmed = pathname.replace(/\/+$/, "");
  return trimmed === "" ? "/" : trimmed.toLowerCase();
}

/**
 * A link is "current" when the visitor is on it, or on something nested
 * beneath it (so "/seo-guides/pwa-guide" keeps "Guides" highlighted).
 */
function isCurrent(linkHref: string, pathname: string): boolean {
  const target = normalisePath(linkHref);
  const current = normalisePath(pathname);
  if (target === "/") return current === "/";
  return current === target || current.startsWith(`${target}/`);
}

export function SiteHeader({ className }: SiteHeaderProps) {
  const pathname = usePathname();
  const [menuOpen, setMenuOpen] = React.useState(false);
  const [isAuthenticated, setIsAuthenticated] = React.useState(false);
  const toggleRef = React.useRef<HTMLButtonElement>(null);

  // The header renders in the static export, so auth state is not known at
  // build time. Read it after mount, and keep it in sync with storage so a
  // sign-in elsewhere in the app updates the bar.
  React.useEffect(() => {
    const read = () => {
      try {
        setIsAuthenticated(
          Boolean(window.localStorage.getItem(AUTH_STORAGE_KEYS.TOKEN))
        );
      } catch {
        setIsAuthenticated(false);
      }
    };
    read();
    window.addEventListener("storage", read);
    window.addEventListener("auth-changed", read);
    return () => {
      window.removeEventListener("storage", read);
      window.removeEventListener("auth-changed", read);
    };
  }, []);

  // Close the mobile menu whenever the route changes, otherwise it stays open
  // over the page the visitor just navigated to.
  React.useEffect(() => {
    setMenuOpen(false);
  }, [pathname]);

  // Lock body scroll while the mobile menu is open.
  React.useEffect(() => {
    if (!menuOpen) return;
    const previous = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = previous;
    };
  }, [menuOpen]);

  // Escape closes the mobile menu, and focus returns to the button that
  // opened it so a keyboard user is not stranded further down the page.
  React.useEffect(() => {
    if (!menuOpen) return;

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setMenuOpen(false);
        toggleRef.current?.focus();
      }
    };

    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [menuOpen]);

  const links = isAuthenticated ? [...NAV_LINKS, ...AUTH_LINKS] : NAV_LINKS;

  return (
    <header
      role="banner"
      className={cn(
        "sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-md",
        className
      )}
    >
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {/* Brand */}
        <Link
          href="/"
          className="flex shrink-0 items-center gap-2 text-lg font-bold text-white transition-colors hover:text-emerald-400"
        >
          <span
            aria-hidden="true"
            className="grid h-8 w-8 place-items-center rounded-lg bg-emerald-500/15 text-emerald-400"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              strokeLinecap="round"
              strokeLinejoin="round"
              className="h-5 w-5"
            >
              <path d="M3 3v18h18" />
              <path d="m19 9-5 5-4-4-3 3" />
            </svg>
          </span>
          <span>
            SEOIndicator<span className="text-emerald-400">.com</span>
          </span>
        </Link>

        {/* Desktop navigation */}
        <nav
          aria-label="Main navigation"
          className="hidden items-center gap-1 md:flex"
        >
          {links.map((link) => {
            const current = isCurrent(link.href, pathname);
            return (
              <Link
                key={link.href}
                href={link.href}
                aria-current={current ? "page" : undefined}
                className={cn(
                  "rounded-md px-3 py-2 text-sm font-medium transition-colors",
                  current
                    ? "bg-emerald-500/10 text-emerald-400"
                    : "text-slate-300 hover:bg-slate-800/60 hover:text-white"
                )}
              >
                {link.label}
              </Link>
            );
          })}
        </nav>

        {/* Desktop actions */}
        <div className="hidden items-center gap-2 md:flex">
          {isAuthenticated ? (
            <Link href="/dashboard">
              <Button
                size="sm"
                className="bg-emerald-700 text-white hover:bg-emerald-600"
              >
                Go to dashboard
              </Button>
            </Link>
          ) : (
            <>
              <Link href="/login">
                <Button
                  variant="ghost"
                  size="sm"
                  className="bg-transparent text-slate-300 hover:bg-slate-800/60 hover:text-white"
                >
                  Sign in
                </Button>
              </Link>
              <Link href="/register">
                <Button
                  size="sm"
                  className="bg-emerald-700 text-white hover:bg-emerald-600"
                >
                  Get started
                </Button>
              </Link>
            </>
          )}
        </div>

        {/* Mobile menu toggle */}
        <button
          type="button"
          ref={toggleRef}
          onClick={() => setMenuOpen((open) => !open)}
          aria-expanded={menuOpen}
          aria-controls="site-header-mobile-menu"
          aria-label={menuOpen ? "Close menu" : "Open menu"}
          className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-700 text-slate-200 transition-colors hover:bg-slate-800 hover:text-white md:hidden"
        >
          {menuOpen ? (
            <X className="h-5 w-5" aria-hidden="true" />
          ) : (
            <Menu className="h-5 w-5" aria-hidden="true" />
          )}
        </button>
      </div>

      {/* Mobile navigation panel */}
      {menuOpen && (
        <div
          id="site-header-mobile-menu"
          className="border-t border-slate-800 bg-slate-950 md:hidden"
        >
          <nav
            aria-label="Mobile navigation"
            className="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6"
          >
            {links.map((link) => {
              const current = isCurrent(link.href, pathname);
              return (
                <Link
                  key={link.href}
                  href={link.href}
                  aria-current={current ? "page" : undefined}
                  onClick={() => setMenuOpen(false)}
                  className={cn(
                    "block rounded-md px-3 py-2.5 text-sm font-medium transition-colors",
                    current
                      ? "bg-emerald-500/10 text-emerald-400"
                      : "text-slate-300 hover:bg-slate-800/60 hover:text-white"
                  )}
                >
                  {link.label}
                </Link>
              );
            })}

            <div className="flex flex-col gap-2 pt-3">
              {isAuthenticated ? (
                <Link href="/dashboard" onClick={() => setMenuOpen(false)}>
                  <Button
                    className="w-full bg-emerald-600 text-white hover:bg-emerald-700"
                  >
                    Go to dashboard
                  </Button>
                </Link>
              ) : (
                <>
                  <Link href="/login" onClick={() => setMenuOpen(false)}>
                    <Button
                      variant="outline"
                      className="w-full border-slate-600 bg-transparent text-slate-200 hover:bg-slate-800 hover:text-white"
                    >
                      Sign in
                    </Button>
                  </Link>
                  <Link href="/register" onClick={() => setMenuOpen(false)}>
                    <Button className="w-full bg-emerald-600 text-white hover:bg-emerald-700">
                      Get started
                    </Button>
                  </Link>
                </>
              )}
            </div>
          </nav>
        </div>
      )}
    </header>
  );
}

export default SiteHeader;
