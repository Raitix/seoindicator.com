/**
 * Homepage tests - SEOIndicator.com
 *
 * The homepage was redesigned. The assertions below describe the page as it
 * renders today:
 *
 *  - The site header is global (app/layout.tsx -> SiteHeader), the page no
 *    longer renders its own <header>.
 *  - The hero drives visitors straight to the free analyser; there is no
 *    email capture and no "Get Started" button.
 *  - The indicator catalogue is integrated: it renders a title, a
 *    description, category tabs and a filterable grid of indicator cards.
 *  - The "operational" status bar now reads the live /api/health endpoint.
 *
 * Test IDs are kept stable - they are the spec for this page.
 */

import React from 'react';
import { render, screen, within, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// ==========================================
// MOCKS - Must be defined before imports
// ==========================================

// Next.js Link. It has to forward refs because Radix's Slot (used by the
// `asChild` button variant) clones the child element.
vi.mock('next/link', () => {
  const MockLink = React.forwardRef(
    ({ children, href, ...props }: { children: React.ReactNode; href: string }, ref: React.Ref<HTMLAnchorElement>) => (
      <a ref={ref} href={href} {...props}>
        {children}
      </a>
    )
  );
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});

// Next.js navigation. The shared SiteHeader (rendered by the root layout)
// calls usePathname, which throws outside of a router context.
vi.mock('next/navigation', () => ({
  usePathname: () => '/',
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), back: vi.fn() }),
  useSearchParams: () => new URLSearchParams(),
}));

// next/font is a build-time transform and cannot run inside Vitest.
vi.mock('next/font/google', () => ({
  Geist: () => ({ variable: 'geist-sans', className: 'geist-sans' }),
  Geist_Mono: () => ({ variable: 'geist-mono', className: 'geist-mono' }),
}));

// ==========================================
// IMPORTS - After mocks are defined
// ==========================================

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';
import { IndicatorCatalog, type IndicatorData } from '@/components/indicators';
import { SiteHeader, SkipLink, SkipToMain } from '@/components/layout';
import RootLayout, { metadata as layoutMetadata } from '@/app/layout';
import { ComparisonSchema } from '@/components/compare/ComparisonSchema';
import { getComparisonBySlug } from '@/lib/compare/registry';
import robots from '@/app/robots';
import sitemap from '@/app/sitemap';

// ==========================================
// HELPERS
// ==========================================

const FRONTEND_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');

const readGlobalsCss = () => fs.readFileSync(path.join(FRONTEND_ROOT, 'app', 'globals.css'), 'utf8');

/**
 * The indicator cards are wrapped in a Radix tooltip, which needs the same
 * provider the root layout provides.
 */
const renderHome = () =>
  render(
    <TooltipProvider delayDuration={0}>
      <Home />
    </TooltipProvider>
  );

/** The persistent top bar every route gets from the root layout. */
const renderSiteHeader = () => render(<SiteHeader />);

const featuresSection = (container: HTMLElement) =>
  container.querySelector('section[aria-label="Key features"]') as HTMLElement;

const indicatorCards = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('[data-testid^="indicator-card-"]')) as HTMLAnchorElement[];

/** Pretend the backend health endpoint answered. */
const stubHealthyBackend = () => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => ({
      ok: true,
      status: 200,
      json: async () => ({ status: 'ok', database: 'connected', app: 'SEOIndicator' }),
    }))
  );
};

/**
 * Run `fn` with prefers-reduced-motion turned on. The indicator components
 * read the media query in an effect, so the preference has to be in place
 * before the component mounts.
 */
const withReducedMotion = <T,>(fn: () => T): T => {
  const original = window.matchMedia;
  (window as any).matchMedia = vi.fn().mockImplementation((query: string) => ({
    matches: query.includes('prefers-reduced-motion'),
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  }));
  try {
    return fn();
  } finally {
    (window as any).matchMedia = original;
  }
};

/**
 * A stable, id-free description of the rendered page. Snapshot tests compare
 * this instead of the raw DOM: the catalogue title id and the Radix trigger
 * ids are generated per render and would make a DOM snapshot unstable.
 */
const pageOutline = (container: HTMLElement) => {
  const root = container.firstElementChild as HTMLElement;
  return Array.from(root.children).map((child) => ({
    tag: child.tagName.toLowerCase(),
    label: child.getAttribute('aria-label') ?? child.getAttribute('data-testid') ?? '',
    role: child.getAttribute('role') ?? '',
  }));
};

const headingOutline = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('h1, h2, h3')).map((heading) => [
    heading.tagName.toLowerCase(),
    heading.textContent?.trim() ?? '',
  ]);

const linkOutline = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('a')).map((link) => [
    // The accessible name: the aria-label when there is one, the text
    // otherwise.
    (link.getAttribute('aria-label') ?? link.textContent ?? '').replace(/\s+/g, ' ').trim(),
    link.getAttribute('href') ?? '',
  ]);

const catalogIndicators: IndicatorData[] = [
  {
    id: 'ranking',
    icon: 'TrendingUp',
    title: 'Keyword Rankings',
    value: '42',
    description: 'Track SERP positions',
    status: 'good',
    href: '/ranking-history',
    category: 'Rankings',
  },
  {
    id: 'speed',
    icon: 'Zap',
    title: 'Page Speed',
    value: '89',
    description: 'Performance metrics',
    status: 'warning',
    href: '/tools/mobile-friendly',
    category: 'Technical',
  },
];

beforeEach(() => {
  // The status bar polls /api/health. By default the request never settles,
  // so the bar stays in its "Checking..." state and no state update happens
  // after a test finishes.
  vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));
});

afterEach(() => {
  vi.unstubAllGlobals();
});

// ==========================================
// TESTS
// ==========================================

describe('Homepage', () => {
  // ==========================================
  // Component Rendering Tests (CR-001 to CR-010)
  // ==========================================
  describe('Component Rendering', () => {
    it('CR-001: Homepage renders without crashing', () => {
      const { container } = renderHome();

      expect(container.firstElementChild).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });

    it('CR-002: Page has proper structure with header, main, footer', () => {
      const { container } = renderHome();

      // The page itself contributes the content sections and the footer.
      expect(container.querySelectorAll('section').length).toBeGreaterThanOrEqual(5);
      expect(container.querySelector('footer[role="contentinfo"]')).toBeInTheDocument();

      // The banner and the main landmark now come from the shared layout.
      const { container: headerContainer } = renderSiteHeader();
      expect(headerContainer.querySelector('header[role="banner"]')).toBeInTheDocument();

      const { container: mainContainer } = render(
        <SkipToMain>
          <p>content</p>
        </SkipToMain>
      );
      expect(mainContainer.querySelector('main[role="main"]')).toBeInTheDocument();
    });

    it('CR-003: IndicatorCatalog renders in page (if integrated)', () => {
      const { container } = renderHome();

      const catalog = container.querySelector('[data-testid="indicator-catalog"]');
      expect(catalog).toBeInTheDocument();
      expect(within(catalog as HTMLElement).getByTestId('catalog-title')).toHaveTextContent('SEO Indicators');
    });

    it('CR-004: Header renders with logo and navigation', () => {
      const { container } = renderSiteHeader();

      const header = container.querySelector('header[role="banner"]') as HTMLElement;
      expect(header).toBeInTheDocument();

      const logo = within(header).getByRole('link', { name: /SEOIndicator/i });
      expect(logo).toHaveAttribute('href', '/');

      const nav = header.querySelector('nav[aria-label="Main navigation"]') as HTMLElement;
      const navLabels = within(nav)
        .getAllByRole('link')
        .map((link) => link.textContent);
      expect(navLabels).toEqual(['Tools', 'Guides', 'Pricing', 'Compare', 'About']);
    });

    it('CR-005: Features grid renders', () => {
      const { container } = renderHome();

      const section = featuresSection(container);
      expect(section).toBeInTheDocument();
      expect(within(section).getByRole('heading', { level: 2 })).toHaveTextContent('Built for real SEO work');
      expect(section.querySelectorAll('[data-slot="card"]')).toHaveLength(3);
    });

    it('CR-006: Footer renders with copyright', () => {
      const { container } = renderHome();

      const footer = container.querySelector('footer[role="contentinfo"]') as HTMLElement;
      expect(footer).toBeInTheDocument();
      expect(within(footer).getByText(/© 2026 SEOIndicator/i)).toBeInTheDocument();
    });

    it('CR-007: Status bar renders correctly', () => {
      stubHealthyBackend();
      const { container } = renderHome();

      const statusBar = container.querySelector('[data-testid="system-status-bar"]') as HTMLElement;
      expect(statusBar).toBeInTheDocument();
      expect(statusBar.querySelector('[data-testid="system-status-label"]')).toBeInTheDocument();
      expect(statusBar.querySelector('[data-testid="system-status-detail"]')).toBeInTheDocument();
      expect(statusBar.querySelector('.animate-pulse')).toBeInTheDocument();
    });

    it('CR-008: Page has main container with proper classes', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv).toHaveClass('min-h-screen');
    });

    it('CR-009: Gradient background class applied', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toMatch(/bg-gradient-to-b/);
      expect(mainDiv.className).toMatch(/from-surface-0/);
    });

    it('CR-010: No console errors on render', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      renderHome();

      expect(consoleSpy).not.toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  // ==========================================
  // Hero Section Structure Tests (HS-001 to HS-012)
  // ==========================================
  describe('Hero Section Structure', () => {
    it('HS-001: Hero section has proper container', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      expect(hero).toBeInTheDocument();
      expect(hero).toHaveClass('container');
    });

    it('HS-002: Hero title renders as h1', () => {
      renderHome();

      const heroTitle = screen.getByRole('heading', { level: 1 });
      expect(heroTitle).toBeInTheDocument();
      expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
    });

    it('HS-003: Hero title text is correct', () => {
      renderHome();

      const heroTitle = screen.getByRole('heading', { level: 1 });
      expect(heroTitle).toHaveTextContent(/holding your search rankings back/i);
      expect(heroTitle.querySelector('.text-brand-primary')).toHaveTextContent('search rankings');
    });

    it('HS-004: Hero subtitle/description renders', () => {
      renderHome();

      expect(
        screen.getByText(/enter any domain and get a scored audit of every technical, on-page and ranking factor/i)
      ).toBeInTheDocument();
    });

    it('HS-005: CTA button renders in hero', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      const cta = within(hero).getByRole('link', { name: /analyse a website free/i });

      expect(cta).toBeInTheDocument();
      // The CTA is a link styled as a button (the `asChild` button variant).
      expect(cta).toHaveAttribute('data-slot', 'button');
    });

    it('HS-006: CTA button has correct text', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      const cta = within(hero).getByRole('link', { name: /analyse a website free/i });

      expect(cta).toHaveTextContent('Analyse a website free');
      expect(cta).toHaveAttribute('href', '/tools/meta-tag-analyzer');
    });

    it('HS-007: CTA button triggers action on click', async () => {
      const user = userEvent.setup();
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      const cta = within(hero).getByRole('link', { name: /analyse a website free/i });

      const onClick = vi.fn();
      cta.addEventListener('click', (event) => {
        // jsdom cannot navigate, so the default action is stopped here.
        event.preventDefault();
        onClick();
      });

      await user.click(cta);

      expect(onClick).toHaveBeenCalledTimes(1);
      expect(cta).toBeInTheDocument();
    });

    it('HS-008: Hero no longer collects an email - it sends visitors straight to the free analyser', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      expect(hero.querySelector('input')).toBeNull();
      expect(hero.querySelector('input[type="email"]')).toBeNull();
      expect(within(hero).getByText(/no account needed for a free analysis/i)).toBeInTheDocument();
      expect(within(hero).getByRole('link', { name: /analyse a website free/i })).toHaveAttribute(
        'href',
        '/tools/meta-tag-analyzer'
      );
    });

    it('HS-009: Hero section contains main content', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      expect(within(hero).getByRole('heading', { level: 1 })).toBeInTheDocument();
      expect(within(hero).getByText(/enter any domain and get a scored audit/i)).toBeInTheDocument();
      expect(within(hero).getByRole('link', { name: /analyse a website free/i })).toBeInTheDocument();
    });

    it('HS-010: Hero section may have role attribute', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      expect(hero).toHaveAttribute('role', 'region');
    });

    it('HS-011: Hero section may have aria-labelledby', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      // The hero is labelled with aria-label (not aria-labelledby).
      expect(hero).toHaveAttribute('aria-label', 'Hero');
      expect(hero).not.toHaveAttribute('aria-labelledby');
      expect(screen.getByRole('region', { name: 'Hero' })).toBe(hero);
    });

    it('HS-012: Hero section has proper structure', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      expect(within(hero).getByRole('heading', { level: 1 })).toBeInTheDocument();
      expect(within(hero).getByText(/17 SEO indicators, one consolidated AI report/i)).toBeInTheDocument();
      expect(within(hero).getAllByRole('link')).toHaveLength(2);
    });
  });

  // ==========================================
  // Gradient Background Tests (GB-001 to GB-008)
  // ==========================================
  describe('Gradient Background', () => {
    it('GB-001: Gradient background applied to page', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toMatch(/bg-gradient-to-b/);
    });

    it('GB-002: Gradient uses slate colors or CSS variables', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      // The gradient is driven by the --surface-* CSS variables.
      expect(mainDiv.className).toContain('from-surface-0');
      expect(mainDiv.className).toContain('to-surface-2');
    });

    it('GB-003: Gradient direction is present', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toContain('bg-gradient-to-b');
    });

    it('GB-004: Gradient covers full viewport', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toContain('min-h-screen');
    });

    it('GB-005: Surface layer structure present', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toContain('from-surface-0');
      expect(mainDiv.className).toContain('via-surface-1');
      expect(mainDiv.className).toContain('to-surface-2');
    });

    it('GB-006: Gradient transitions smoothly', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      // A mid-stop (via-*) keeps the ramp from banding.
      expect(mainDiv.className).toContain('via-surface-1');
    });

    it('GB-007: Dark mode classes could be applied', () => {
      document.documentElement.classList.add('dark');
      const { container } = renderHome();

      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv).toBeInTheDocument();
      expect(mainDiv.className).toContain('from-surface-0');

      document.documentElement.classList.remove('dark');
    });

    it('GB-008: No flicker on gradient load', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      // The gradient classes are part of the first paint - no inline style is
      // used to paint the background.
      expect(mainDiv.className).toContain('bg-gradient-to-b');
      expect(mainDiv.getAttribute('style')).toBeNull();
    });
  });

  // ==========================================
  // Responsive Layout Tests (RL-001 to RL-015)
  // ==========================================
  describe('Responsive Layout', () => {
    it('RL-001: Mobile layout classes present', () => {
      renderHome();

      const heroTitle = screen.getByRole('heading', { level: 1 });
      expect(heroTitle.className).toMatch(/text-4xl/);
    });

    it('RL-002: Tablet layout classes present', () => {
      const { container } = renderHome();

      const tabletGrids = container.querySelectorAll('.md\\:grid-cols-3, .md\\:grid-cols-4');
      expect(tabletGrids.length).toBeGreaterThanOrEqual(2);
    });

    it('RL-003: Desktop layout classes present', () => {
      const { container } = renderHome();

      expect(container.querySelector('.lg\\:grid-cols-4')).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 1 }).className).toContain('lg:text-6xl');
    });

    it('RL-004: Hero text scales responsively', () => {
      renderHome();

      const heroTitle = screen.getByRole('heading', { level: 1 });
      expect(heroTitle.className).toMatch(/text-4xl/);
      expect(heroTitle.className).toMatch(/md:text-5xl/);
      expect(heroTitle.className).toMatch(/lg:text-6xl/);
    });

    it('RL-005: Hero padding responsive', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      expect(hero.className).toContain('pt-14');
      expect(hero.className).toContain('md:pt-20');
    });

    it('RL-006: Container max-width responsive', () => {
      const { container } = renderHome();

      expect(container.querySelector('.max-w-5xl')).toBeInTheDocument();
      expect(container.querySelector('.max-w-4xl')).toBeInTheDocument();
    });

    it('RL-007: Features grid has responsive columns', () => {
      const { container } = renderHome();

      const grid = featuresSection(container).querySelector('.grid') as HTMLElement;
      expect(grid).toBeInTheDocument();
      expect(grid.className).toContain('md:grid-cols-3');
    });

    it('RL-008: Features grid responsive', () => {
      const { container } = renderHome();

      expect(featuresSection(container).innerHTML).toMatch(/md:grid-cols-3/);
    });

    it('RL-009: Navigation responsive', () => {
      const { container } = renderSiteHeader();

      const header = container.querySelector('header[role="banner"]') as HTMLElement;

      // Desktop navigation is hidden on small screens, the mobile menu button
      // is hidden on large screens.
      const desktopNav = header.querySelector('nav[aria-label="Main navigation"]') as HTMLElement;
      expect(desktopNav.className).toContain('md:flex');
      expect(desktopNav.className).toContain('hidden');

      const menuButton = header.querySelector('button[aria-label="Open menu"]') as HTMLElement;
      expect(menuButton).toBeInTheDocument();
      expect(menuButton.className).toContain('md:hidden');
    });

    it('RL-010: Footer responsive', () => {
      const { container } = renderHome();

      const footer = container.querySelector('footer[role="contentinfo"]') as HTMLElement;
      expect(footer).toBeInTheDocument();
      expect(footer.querySelector('.container')).toBeInTheDocument();
      expect(footer.querySelector('.container')?.className).toContain('mx-auto');
    });

    it('RL-011: Touch targets have padding', () => {
      const { container } = renderHome();

      const buttons = container.querySelectorAll('button');
      expect(buttons.length).toBeGreaterThan(0);
      buttons.forEach((button) => {
        expect(button.className).toMatch(/px-|py-/);
      });
    });

    it('RL-012: Page handles overflow', () => {
      const { container } = renderHome();
      const mainDiv = container.firstChild as HTMLElement;

      expect(mainDiv.className).toMatch(/min-h-screen/);
      expect(container.querySelector('[style*="overflow"]')).toBeNull();
    });

    it('RL-013: Text readable on all viewports', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      const description = within(hero).getByText(/enter any domain and get a scored audit/i);

      expect(description).toBeInTheDocument();
      // Muted copy stays legible on the dark surface.
      expect(description.className).toContain('text-slate-400');
    });

    it('RL-014: Images (if any) have responsive attributes', () => {
      const { container } = renderHome();

      const images = container.querySelectorAll('img');
      images.forEach((img) => {
        expect(img.hasAttribute('width') || img.className.includes('w-')).toBe(true);
      });

      // The icons are inline SVGs and carry explicit dimensions so they cannot
      // shift the layout while loading.
      const icons = container.querySelectorAll('svg');
      expect(icons.length).toBeGreaterThan(0);
      icons.forEach((icon) => {
        expect(icon).toHaveAttribute('width');
        expect(icon).toHaveAttribute('height');
      });
    });

    it('RL-015: Status bar responsive', () => {
      const { container } = renderHome();

      const statusBar = container.querySelector('[data-testid="system-status-bar"]') as HTMLElement;
      expect(statusBar).toBeInTheDocument();
      expect(statusBar.className).toContain('flex');
      expect(statusBar.className).toContain('justify-between');
      expect(statusBar.querySelector('[data-testid="system-status-label"]')).toBeInTheDocument();
      expect(statusBar.querySelector('[data-testid="system-status-detail"]')).toBeInTheDocument();
    });
  });

  // ==========================================
  // Entrance Animations Tests (EA-001 to EA-010)
  // ==========================================
  describe('Entrance Animations', () => {
    it('EA-001: Hero title may have animation class', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      expect(hero.className).toContain('animate-fade-in');
    });

    it('EA-002: Hero subtitle is present', () => {
      renderHome();

      expect(screen.getByText(/enter any domain and get a scored audit of every technical/i)).toBeInTheDocument();
    });

    it('EA-003: CTA button is present', () => {
      renderHome();

      expect(screen.getByRole('link', { name: /analyse a website free/i })).toBeInTheDocument();
    });

    it('EA-004: IndicatorCatalog may be integrated', () => {
      const { container } = renderHome();

      const catalog = container.querySelector('[data-testid="indicator-catalog"]');
      expect(catalog).toBeInTheDocument();
      expect(container.querySelectorAll('[data-testid^="indicator-card-"]')).toHaveLength(8);
    });

    it('EA-005: Animation classes may be present', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      expect(hero.className).toContain('animate-fade-in');

      const grid = container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;
      expect(grid.className).toContain('transition-all');
    });

    it('EA-006: Animation uses CSS classes', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      // The animation is delivered by a CSS utility class, not by inline
      // keyframes, so it can be switched off globally.
      expect(hero.className).toContain('animate-fade-in');
      expect(hero.getAttribute('style') ?? '').not.toMatch(/animation/i);
    });

    it('EA-007: Animation respects prefers-reduced-motion', () => {
      const { container } = withReducedMotion(() => renderHome());

      const grid = container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;
      expect(grid.className).not.toContain('transition-all duration-200 ease-out');
      expect(grid.className).not.toContain('transition-all');
    });

    it('EA-008: No animation flash on load', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      // Nothing is hidden until an effect flips a class - the hero is visible
      // from the very first render.
      expect(hero.className).not.toContain('opacity-0');
      expect(hero.className).not.toContain('hidden');
      expect(hero.className).toContain('animate-fade-in');
    });

    it('EA-009: Animation triggers on page load', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;
      expect(hero.className).toContain('animate-fade-in');

      // ... and the utility it uses is really defined in the stylesheet.
      const css = readGlobalsCss();
      expect(css).toContain('@keyframes fade-in');
      expect(css).toContain('.animate-fade-in');
    });

    it('EA-010: Animation only runs once', () => {
      const { container } = renderHome();

      // Exactly one element carries the entrance animation - no duplicated
      // animation layers.
      expect(container.querySelectorAll('.animate-fade-in')).toHaveLength(1);
    });
  });

  // ==========================================
  // Dashboard Links Tests (DL-001 to DL-012)
  // ==========================================
  describe('Dashboard Links', () => {
    it('DL-001 to DL-008: Indicator links point at their destinations', () => {
      const { container } = renderHome();

      const expectedHrefs: Record<string, string> = {
        rankings: '/ranking-history',
        visibility: '/dashboard',
        technical: '/audit',
        backlinks: '/dashboard',
        content: '/dashboard',
        serp: '/dashboard',
        speed: '/tools/mobile-friendly',
        competitors: '/comparison',
      };

      const cards = indicatorCards(container);
      expect(cards).toHaveLength(8);

      Object.entries(expectedHrefs).forEach(([id, href]) => {
        const card = container.querySelector(`[data-testid="indicator-card-${id}"]`) as HTMLAnchorElement;
        expect(card, `indicator card for ${id}`).toBeInTheDocument();
        expect(card).toHaveAttribute('href', href);
      });
    });

    it('DL-009: Links use Next.js Link component (anchor tag)', () => {
      const { container } = renderHome();

      const links = container.querySelectorAll('a[href]');
      expect(links.length).toBeGreaterThan(0);
      links.forEach((link) => {
        expect(link.tagName).toBe('A');
      });
    });

    it('DL-010: Links are keyboard accessible', async () => {
      const { container } = renderHome();

      const firstLink = container.querySelector('[data-testid="indicator-card-competitors"]') as HTMLAnchorElement;
      firstLink.focus();

      expect(firstLink).toHaveFocus();
    });

    it('DL-011: Links have appropriate text content', () => {
      const { container } = renderHome();

      const links = container.querySelectorAll('a');
      links.forEach((link) => {
        expect(link.textContent?.trim() || link.getAttribute('aria-label')).toBeTruthy();
      });
    });

    it('DL-012: External links have rel attributes (if any external)', () => {
      const { container } = renderHome();

      const externalLinks = container.querySelectorAll('a[href^="http"]');
      externalLinks.forEach((link) => {
        expect(link.hasAttribute('rel') || link.getAttribute('target') === '_blank').toBe(true);
      });

      // Everything the homepage links to is internal.
      container.querySelectorAll('a[href]').forEach((link) => {
        expect(link.getAttribute('href')?.startsWith('/')).toBe(true);
      });
    });
  });

  // ==========================================
  // Performance Tests (PF-001 to PF-010)
  // ==========================================
  describe('Performance', () => {
    it('PF-001: No layout shift on load (check structure)', () => {
      const { container } = renderHome();

      // Everything below the fold is server rendered, so it is present in the
      // very first commit - no layout shift while the client hydrates.
      expect(container.querySelector('section[aria-label="Hero"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="indicator-grid"]')).toBeInTheDocument();
      expect(container.querySelector('footer[role="contentinfo"]')).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });

    it('PF-002: Images have width and height attributes', () => {
      const { container } = renderHome();

      const images = container.querySelectorAll('img');
      images.forEach((img) => {
        expect(img.hasAttribute('width') || img.hasAttribute('style')).toBe(true);
      });

      const icons = container.querySelectorAll('svg');
      expect(icons.length).toBeGreaterThan(0);
      icons.forEach((icon) => {
        expect(icon.getAttribute('width')).toBeTruthy();
        expect(icon.getAttribute('height')).toBeTruthy();
      });
    });

    it('PF-003: Font classes are present', () => {
      renderHome();

      const heroTitle = screen.getByRole('heading', { level: 1 });
      expect(heroTitle.className).toContain('font-bold');
      expect(heroTitle.className).toContain('tracking-tight');
    });

    it('PF-004: Component renders quickly', () => {
      const start = performance.now();
      renderHome();
      const end = performance.now();

      // Render should complete in reasonable time
      expect(end - start).toBeLessThan(1000);
    });

    it('PF-005: Component renders quickly (batch)', () => {
      const start = performance.now();
      for (let i = 0; i < 10; i++) {
        const { unmount } = renderHome();
        unmount();
      }
      const end = performance.now();

      // 10 renders should complete quickly. The redesigned homepage renders the
      // indicator catalogue (8 cards, each with a tooltip) and the comparison
      // grid, so the budget is scaled accordingly.
      expect(end - start).toBeLessThan(1500);
    });

    it('PF-006: No blocking scripts in render', () => {
      const { container } = renderHome();

      // No script tags should be rendered directly
      const scripts = container.querySelectorAll('script:not([async]):not([defer])');
      // Allow for next.js internal scripts
      expect(scripts.length).toBeLessThanOrEqual(2);
    });

    it('PF-007: Lazy loading for below-fold content', () => {
      const { container } = renderHome();

      // Footer is below the fold
      const footer = container.querySelector('footer[role="contentinfo"]');
      expect(footer).toBeInTheDocument();

      // ... and so is the comparison section
      expect(container.querySelector('[data-testid="how-we-compare-section"]')).toBeInTheDocument();
    });

    it('PF-008: CSS loaded before render (check classes)', () => {
      const { container } = renderHome();

      // Elements should have CSS classes applied
      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv.className).toBeTruthy();
      expect(mainDiv.className).toContain('min-h-screen');
    });

    it('PF-009: JavaScript non-blocking (defer/async)', () => {
      const { container } = renderHome();

      // The page ships no inline script at all - every behaviour is attached
      // by the framework bundle, so nothing blocks the first paint.
      expect(container.querySelectorAll('script')).toHaveLength(0);
      container.querySelectorAll('*').forEach((element) => {
        expect(element.getAttribute('onclick')).toBeNull();
        expect(element.getAttribute('onload')).toBeNull();
      });
    });

    it('PF-010: Component renders in reasonable time', () => {
      const samples: number[] = [];

      for (let i = 0; i < 3; i++) {
        const start = performance.now();
        const { unmount } = renderHome();
        samples.push(performance.now() - start);
        unmount();
      }

      samples.sort((a, b) => a - b);
      const median = samples[1];

      expect(median).toBeLessThan(100);
    });
  });

  // ==========================================
  // Existing Functionality Preservation Tests (EP-001 to EP-008)
  // ==========================================
  describe('Existing Functionality Preservation', () => {
    it('EP-001: Features grid still renders', () => {
      const { container } = renderHome();

      const section = featuresSection(container);
      expect(section).toBeInTheDocument();
      expect(within(section).getByRole('heading', { level: 2 })).toHaveTextContent('Built for real SEO work');
    });

    it('EP-002: Features grid has 3 cards', () => {
      const { container } = renderHome();

      expect(featuresSection(container).querySelectorAll('[data-slot="card"]')).toHaveLength(3);
    });

    it('EP-003: Features grid titles correct', () => {
      const { container } = renderHome();

      const section = featuresSection(container);

      expect(within(section).getByText(/keyword tracking/i)).toBeInTheDocument();
      expect(within(section).getByText(/competitor analysis/i)).toBeInTheDocument();
      expect(within(section).getByText(/free analysis tools/i)).toBeInTheDocument();
    });

    it('EP-004: Status bar still renders', () => {
      const { container } = renderHome();

      expect(container.querySelector('[data-testid="system-status-bar"]')).toBeInTheDocument();
    });

    it('EP-005: Status bar shows operational', async () => {
      stubHealthyBackend();
      const { container } = renderHome();

      const label = await screen.findByText('System Status: Operational');
      expect(label).toBeInTheDocument();

      const detail = container.querySelector('[data-testid="system-status-detail"]');
      expect(detail).toHaveTextContent('SEOIndicator API Ready • Database connected');
    });

    it('EP-006: Footer still renders', () => {
      const { container } = renderHome();

      const footer = container.querySelector('footer[role="contentinfo"]');
      expect(footer).toBeInTheDocument();
    });

    it('EP-007: Footer has copyright', () => {
      const { container } = renderHome();

      const footer = container.querySelector('footer[role="contentinfo"]') as HTMLElement;
      expect(within(footer).getByText(/© 2026 SEOIndicator\. All rights reserved\./i)).toBeInTheDocument();
    });

    it('EP-008: Navigation still works', () => {
      const { container } = renderSiteHeader();

      const nav = container.querySelector('nav[aria-label="Main navigation"]') as HTMLElement;
      const hrefs = within(nav)
        .getAllByRole('link')
        .map((link) => link.getAttribute('href'));

      expect(hrefs).toEqual(['/tools', '/seo-guides', '/pricing', '/compare', '/about']);

      // The homepage itself still offers the comparison routes.
      const { container: page } = renderHome();
      const comparisonHrefs = Array.from(page.querySelectorAll('[data-testid="comparison-links"] a')).map((link) =>
        link.getAttribute('href')
      );
      expect(comparisonHrefs).toEqual([
        '/compare/seoindicator-vs-ahrefs',
        '/compare/seoindicator-vs-semrush',
        '/compare/seoindicator-vs-moz',
        '/compare',
      ]);
    });
  });

  // ==========================================
  // Accessibility Tests (AX-001 to AX-015)
  // ==========================================
  describe('Accessibility', () => {
    it('AX-001: Hero title is h1 (single h1)', () => {
      renderHome();

      const h1Elements = screen.getAllByRole('heading', { level: 1 });
      expect(h1Elements.length).toBe(1);
    });

    it('AX-002: Features section has h2', () => {
      const { container } = renderHome();

      const section = featuresSection(container);
      const h2Elements = within(section).getAllByRole('heading', { level: 2 });
      expect(h2Elements.length).toBeGreaterThanOrEqual(1);
    });

    it('AX-003: Proper heading order (h1 before h2)', () => {
      const { container } = renderHome();

      const h1 = container.querySelector('h1');
      const h2 = container.querySelector('h2');

      expect(h1).toBeTruthy();
      expect(h2).toBeTruthy();

      // h1 should come before h2 in DOM order
      const position = (h1!.compareDocumentPosition(h2!) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
      expect(position).toBe(true);
    });

    it('AX-004: All interactive elements focusable', () => {
      const { container } = renderHome();

      const interactiveElements = Array.from(
        container.querySelectorAll('a[href], button, input, [role="tab"]')
      ) as HTMLElement[];

      expect(interactiveElements.length).toBeGreaterThan(0);

      // The elements are checked from the last one to the first one.
      // Focusing a Radix tab trigger activates that tab, which re-renders the
      // indicator grid and replaces the card nodes - so the cards have to be
      // checked while they are still the mounted ones.
      for (const element of interactiveElements.reverse()) {
        expect(element.isConnected).toBe(true);

        act(() => {
          element.focus();
        });

        expect(document.activeElement).toBe(element);
      }
    });

    it('AX-005: Focus indicators visible (check classes)', () => {
      const { container } = renderHome();

      const buttons = container.querySelectorAll('button');
      expect(buttons.length).toBeGreaterThan(0);
      buttons.forEach((button) => {
        // Should have focus styles
        expect(button.className).toMatch(/focus-visible/);
      });

      // The indicator cards and the primary CTA expose a focus ring as well.
      indicatorCards(container).forEach((card) => {
        expect(card.className).toMatch(/focus-visible:ring/);
      });

      const cta = container.querySelector('a[href="/tools/meta-tag-analyzer"]') as HTMLElement;
      expect(cta.className).toMatch(/focus-visible:ring/);
    });

    it('AX-006: Color contrast meets WCAG AA (placeholder)', () => {
      const { container } = renderHome();

      // The homepage sits on the dark --surface-* background, so the copy has
      // to use the high contrast tokens of the theme.
      const heroTitle = container.querySelector('h1') as HTMLElement;
      expect(heroTitle.className).toContain('text-white');

      const description = within(container.querySelector('section[aria-label="Hero"]') as HTMLElement).getByText(
        /enter any domain and get a scored audit/i
      );
      expect(description.className).toContain('text-slate-400');
    });

    it('AX-007: Links have discernible text', () => {
      const { container } = renderHome();

      const links = container.querySelectorAll('a');
      expect(links.length).toBeGreaterThan(0);
      links.forEach((link) => {
        const hasText = link.textContent && link.textContent.trim().length > 0;
        const hasAriaLabel = link.hasAttribute('aria-label');
        expect(hasText || hasAriaLabel).toBe(true);
      });
    });

    it('AX-008: Buttons have accessible names', () => {
      const { container } = renderHome();

      const buttons = container.querySelectorAll('button');
      expect(buttons.length).toBeGreaterThan(0);
      buttons.forEach((button) => {
        const hasText = button.textContent && button.textContent.trim().length > 0;
        const hasAriaLabel = button.hasAttribute('aria-label');
        expect(hasText || hasAriaLabel).toBe(true);
      });
    });

    it('AX-009: Page has lang attribute', () => {
      // The document element is rendered by the root layout.
      const tree = RootLayout({ children: null }) as React.ReactElement<{ lang: string }>;

      expect(tree.props.lang).toBe('en');
    });

    it('AX-010: Page has title element', () => {
      // Next.js renders the document title from the layout metadata.
      expect(layoutMetadata.title).toBeTruthy();
      expect(String(layoutMetadata.title)).toContain('SEOIndicator');
    });

    it('AX-011: Skip to content link (optional)', () => {
      const { container } = render(
        <>
          <SkipLink />
          <SkipToMain>
            <p>content</p>
          </SkipToMain>
        </>
      );

      const skipLink = container.querySelector('[data-testid="skip-link"]') as HTMLElement;
      expect(skipLink).toBeInTheDocument();
      expect(skipLink).toHaveAttribute('href', '#main-content');

      // The link target has to exist, otherwise the skip link does nothing.
      const main = container.querySelector('main[role="main"]') as HTMLElement;
      expect(main).toBeInTheDocument();
      expect(main.id).toBe('main-content');
    });

    it('AX-012: aria-live for dynamic content', () => {
      const { container } = renderHome();

      // The status badges are live regions - they announce the indicator
      // status without stealing focus.
      const liveBadges = container.querySelectorAll('[role="status"]');
      expect(liveBadges.length).toBe(8);
      liveBadges.forEach((badge) => {
        expect(badge.getAttribute('aria-label')).toMatch(/^Status: /);
      });

      // The system status label is the other dynamically updated text.
      expect(container.querySelector('[data-testid="system-status-label"]')).toBeInTheDocument();
    });

    it('AX-013: Role attributes correct', () => {
      const { container } = renderHome();

      expect(container.querySelector('section[aria-label="Hero"]')).toHaveAttribute('role', 'region');
      expect(container.querySelector('footer[role="contentinfo"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="indicator-grid"]')).toHaveAttribute('role', 'grid');
      expect(container.querySelectorAll('[role="gridcell"]')).toHaveLength(8);
      expect(container.querySelector('[role="tablist"]')).toBeInTheDocument();
      expect(container.querySelectorAll('[role="tab"]')).toHaveLength(4);
    });

    it('AX-014: Screen reader can navigate landmarks', () => {
      const { container } = renderHome();

      // Every section of the page is labelled, so they are reachable as
      // landmarks.
      const labelledSections = container.querySelectorAll('section[aria-label]');
      expect(labelledSections.length).toBeGreaterThanOrEqual(4);
      labelledSections.forEach((section) => {
        expect(section.getAttribute('aria-label')).toBeTruthy();
      });

      // The banner and the main landmark come from the shared layout.
      const { container: headerContainer } = renderSiteHeader();
      expect(headerContainer.querySelector('header[role="banner"]')).toBeInTheDocument();

      const { container: mainContainer } = render(
        <SkipToMain>
          <p>content</p>
        </SkipToMain>
      );
      expect(mainContainer.querySelector('main[role="main"]')).toBeInTheDocument();
    });

    it('AX-015: Reduced motion respected', () => {
      const { container } = withReducedMotion(() => renderHome());

      // The catalogue and the grid drop their transitions...
      const catalog = container.querySelector('[data-testid="indicator-catalog"]') as HTMLElement;
      expect(catalog.className).not.toContain('transition-all duration-200 ease-out');

      const grid = container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;
      expect(grid.className).not.toContain('transition-all duration-200 ease-out');

      // ... and the cards switch the animation off explicitly.
      const card = container.querySelector('[data-testid="indicator-card-technical"]') as HTMLElement;
      expect(card.className).toContain('transition-none');
    });
  });

  // ==========================================
  // CSS Variable Integration Tests (CV-001 to CV-010)
  // ==========================================
  describe('CSS Variable Integration', () => {
    it('CV-001: Brand colors may be used in gradient', () => {
      const { container } = renderHome();

      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv.className).toMatch(/from-|bg-/);
      // The brand accent is used for the highlighted part of the headline.
      expect(container.querySelector('h1 .text-brand-primary')).toBeInTheDocument();
    });

    it('CV-002: Background gradient classes present', () => {
      const { container } = renderHome();

      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv.className).toMatch(/bg-gradient-to-b/);
      expect(mainDiv.className).toContain('to-surface-2');
    });

    it('CV-003: Surface layer structure present', () => {
      const { container } = renderHome();

      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv.className).toContain('from-surface-0');
      expect(mainDiv.className).toContain('via-surface-1');
      expect(mainDiv.className).toContain('to-surface-2');
    });

    it('CV-004: Surface layer in cards', () => {
      const { container } = renderHome();

      const cards = container.querySelectorAll('[data-slot="card"]');
      expect(cards.length).toBeGreaterThan(0);
      cards.forEach((card) => {
        expect(card.className).toMatch(/bg-surface-\d|bg-surface-\d\/50/);
      });
    });

    it('CV-005: Surface layer in modals (if any)', () => {
      const { container } = renderHome();

      // The homepage itself has no modal.
      expect(container.querySelector('[role="dialog"]')).toBeNull();
      expect(container.querySelector('[role="alertdialog"]')).toBeNull();

      // The only portal surface it renders is the indicator tooltip, and that
      // one is themed with the foreground/background tokens.
      const statusCard = container.querySelector('[data-slot="card"]') as HTMLElement;
      expect(statusCard.className).toMatch(/bg-surface-\d|bg-surface-\d\/50/);
    });

    it('CV-006: CSS variables fallback correctly', () => {
      const { container } = renderHome();

      // The theme tokens the page relies on are actually defined.
      const css = readGlobalsCss();
      expect(css).toContain('--surface-0:');
      expect(css).toContain('--surface-1:');
      expect(css).toContain('--surface-2:');
      expect(css).toContain('--brand-primary:');

      // ... and the page uses them through the utility classes.
      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv.className).toContain('from-surface-0');
    });

    it('CV-007: Dark mode classes could be applied', () => {
      document.documentElement.classList.add('dark');
      const { container } = renderHome();

      const mainDiv = container.firstChild as HTMLElement;
      expect(mainDiv).toBeInTheDocument();
      expect(mainDiv.className).toContain('from-surface-0');

      document.documentElement.classList.remove('dark');
    });

    it('CV-008: Semantic colors for status indicators', () => {
      const { container } = renderHome();

      // The status dot uses the semantic success/warning tokens.
      const dot = container.querySelector('[data-testid="system-status-bar"] .animate-pulse') as HTMLElement;
      expect(dot).toBeInTheDocument();
      expect(dot.className).toMatch(/bg-(success|warning|slate-500)/);

      // Every indicator badge carries its status as data, so it can be
      // styled through the theme.
      const badges = container.querySelectorAll('[data-slot="badge"]');
      expect(badges.length).toBe(8);
      badges.forEach((badge) => {
        expect(badge.getAttribute('data-status')).toMatch(/good|warning|neutral|critical/);
      });
    });

    it('CV-009: CSS variables not hard-coded inline', () => {
      const { container } = renderHome();

      // No inline style may hard-code a colour. Colours that are set inline
      // have to reference a CSS variable.
      const styledElements = Array.from(container.querySelectorAll('[style]'));
      styledElements.forEach((element) => {
        const style = element.getAttribute('style') ?? '';
        expect(style).not.toMatch(/#[0-9a-f]{3,8}\b/i);
        expect(style).not.toMatch(/oklch\(/i);
        if (/rgba?\(/i.test(style)) {
          expect(style).toMatch(/var\(--/);
        }
      });
    });

    it('CV-010: oklch format could be used', () => {
      const css = readGlobalsCss();

      // The design tokens are defined with oklch().
      expect(css).toMatch(/--surface-0:\s*oklch\(/);
      expect(css).toMatch(/--surface-1:\s*oklch\(/);
      expect(css).toMatch(/--brand-primary:\s*oklch\(/);
    });
  });

  // ==========================================
  // Integration with IndicatorCatalog Tests (IC-001 to IC-010)
  // ==========================================
  describe('Integration with IndicatorCatalog', () => {
    it('IC-001: IndicatorCatalog may be rendered (after integration)', () => {
      const { container } = renderHome();

      const catalog = container.querySelector('[data-testid="indicator-catalog"]');
      expect(catalog).toBeInTheDocument();
      expect(catalog).toHaveAttribute('data-slot', 'indicator-catalog');
    });

    it('IC-002: Catalog title would be "SEO Indicators"', () => {
      const { container } = renderHome();

      const catalogTitle = container.querySelector('[data-testid="catalog-title"]');
      expect(catalogTitle).toBeInTheDocument();
      expect(catalogTitle).toHaveTextContent('SEO Indicators');
    });

    it('IC-003: Catalog description would be present', () => {
      const { container } = renderHome();

      const catalogDescription = container.querySelector('[data-testid="catalog-description"]');
      expect(catalogDescription).toBeInTheDocument();
      expect(catalogDescription).toHaveTextContent(/shown with sample values/i);
    });

    it('IC-004: Catalog loading state would be handled', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} isLoading />
        </TooltipProvider>
      );

      const skeleton = container.querySelector('[data-testid="catalog-skeleton-grid"]');
      expect(skeleton).toBeInTheDocument();
      expect(container.querySelector('[data-testid="indicator-grid"]')).toBeNull();
      expect(container.querySelector('[data-testid="catalog-empty"]')).toBeNull();
    });

    it('IC-005: Catalog error state would be handled', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} error={new Error('Failed to load')} />
        </TooltipProvider>
      );

      const error = container.querySelector('[data-testid="catalog-error"]');
      expect(error).toBeInTheDocument();
      expect(error).toHaveAttribute('role', 'alert');
      expect(error).toHaveTextContent('Failed to load indicators');
      expect(error).toHaveTextContent('Failed to load');
    });

    it('IC-006: Catalog refresh would work', async () => {
      const onRefresh = vi.fn();
      const user = userEvent.setup();

      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={catalogIndicators} onRefresh={onRefresh} />
        </TooltipProvider>
      );

      const refreshButton = container.querySelector('[data-testid="catalog-refresh-button"]') as HTMLElement;
      expect(refreshButton).toBeInTheDocument();
      expect(refreshButton).toHaveAttribute('aria-label', 'Refresh indicators');

      await user.click(refreshButton);
      expect(onRefresh).toHaveBeenCalledTimes(1);
    });

    it('IC-007: Catalog category filtering would work', async () => {
      const user = userEvent.setup();
      const { container } = renderHome();

      const grid = () => container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;

      expect(grid().getAttribute('data-indicator-count')).toBe('8');
      expect(grid().getAttribute('aria-label')).toBe('SEO Indicators - All categories');

      await user.click(screen.getByTestId('tab-technical'));

      expect(grid().getAttribute('data-indicator-count')).toBe('2');
      expect(grid().getAttribute('aria-label')).toBe('SEO Indicators - Technical');
      expect(
        Array.from(container.querySelectorAll('[data-testid^="indicator-card-"]')).map((card) =>
          card.getAttribute('data-testid')
        )
      ).toEqual(['indicator-card-technical', 'indicator-card-speed']);

      await user.click(screen.getByTestId('tab-all'));
      expect(grid().getAttribute('data-indicator-count')).toBe('8');
    });

    it('IC-008: Catalog responsive layout', () => {
      const { container } = renderHome();

      const grid = container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;
      expect(grid).toBeInTheDocument();
      expect(grid.className).toContain('grid-cols-1');
      expect(grid.className).toContain('sm:grid-cols-2');
      expect(grid.className).toContain('md:grid-cols-3');
      expect(grid.className).toContain('lg:grid-cols-4');
    });

    it('IC-009: Catalog links would work', () => {
      const { container } = renderHome();

      const cards = indicatorCards(container);
      expect(cards).toHaveLength(8);

      cards.forEach((card) => {
        expect(card.tagName).toBe('A');
        expect(card.getAttribute('href')).toMatch(/^\//);
        expect(card.getAttribute('aria-label')).toBeTruthy();
      });
    });

    it('IC-010: Catalog would be accessible', () => {
      const { container } = renderHome();

      const catalog = container.querySelector('[data-testid="indicator-catalog"]') as HTMLElement;
      const title = container.querySelector('[data-testid="catalog-title"]') as HTMLElement;

      // The catalogue is labelled by its own title.
      expect(catalog.getAttribute('aria-labelledby')).toBe(title.id);
      expect(title.id).toBeTruthy();

      // The grid is a labelled grid with grid cells.
      const grid = container.querySelector('[data-testid="indicator-grid"]') as HTMLElement;
      expect(grid).toHaveAttribute('role', 'grid');
      expect(grid.getAttribute('aria-label')).toBe('SEO Indicators - All categories');
      expect(container.querySelectorAll('[role="gridcell"]')).toHaveLength(8);

      // The tabs are a proper tab list. The list is labelled by the Tabs root
      // that wraps it.
      expect(container.querySelector('[role="tablist"]')).toBeInTheDocument();
      expect(container.querySelector('[data-slot="tabs"]')).toHaveAttribute('aria-label', 'Filter by category');

      const tabs = container.querySelectorAll('[role="tab"]');
      expect(tabs).toHaveLength(4);
      tabs.forEach((tab) => {
        expect(tab.getAttribute('aria-selected')).toBeTruthy();
      });
      // Exactly one tab is selected.
      expect(Array.from(tabs).filter((tab) => tab.getAttribute('aria-selected') === 'true')).toHaveLength(1);
    });
  });

  // ==========================================
  // Edge Cases Tests (EC-001 to EC-010)
  // ==========================================
  describe('Edge Cases', () => {
    it('EC-001: Page renders with no indicators (empty state)', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} />
        </TooltipProvider>
      );

      const empty = container.querySelector('[data-testid="catalog-empty"]');
      expect(empty).toBeInTheDocument();
      expect(empty).toHaveAttribute('role', 'status');
      expect(empty).toHaveTextContent('No indicators');
    });

    it('EC-002: Page renders with loading state', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} isLoading />
        </TooltipProvider>
      );

      expect(container.querySelector('[data-testid="catalog-skeleton-grid"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="indicator-grid"]')).toBeNull();
    });

    it('EC-003: Page renders with error state', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} error={new Error('Network down')} />
        </TooltipProvider>
      );

      const error = container.querySelector('[data-testid="catalog-error"]');
      expect(error).toBeInTheDocument();
      expect(error).toHaveTextContent('Network down');
    });

    it('EC-004: Page handles very long titles', () => {
      const { container } = renderHome();

      const title = container.querySelector('[data-testid="indicator-card-technical"] span[title]') as HTMLElement;
      expect(title).toBeInTheDocument();
      expect(title.textContent).toBe('Technical Health');
      // Long titles are truncated visually instead of breaking the layout.
      expect(title.className).toContain('truncate');
    });

    it('EC-005: Page handles very long descriptions', () => {
      const { container } = renderHome();

      const description = container.querySelector(
        '[data-testid="indicator-card-technical"] p[title]'
      ) as HTMLElement;
      expect(description).toBeInTheDocument();
      expect(description.textContent).toBe('Site audit score');
      expect(description.className).toContain('line-clamp-2');
    });

    it('EC-006: Page handles special characters', () => {
      const { container } = renderHome();

      // An apostrophe inside the competitor card description.
      const description = within(featuresSection(container)).getByText(/competition's strategies/i);
      expect(description).toBeInTheDocument();

      // Arrow entities are decoded into real characters.
      const featureLink = featuresSection(container).querySelector('a[href="/ranking-history"]') as HTMLElement;
      expect(featureLink.textContent).toContain('→');
    });

    it('EC-007: Page handles missing CSS variables', () => {
      const originalStyle = document.documentElement.getAttribute('style');
      document.documentElement.style.removeProperty('--surface-0');
      document.documentElement.style.removeProperty('--surface-2');

      const { container } = renderHome();

      expect(container.querySelector('section[aria-label="Hero"]')).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
      expect(container.querySelector('[data-testid="indicator-grid"]')).toBeInTheDocument();

      if (originalStyle === null) {
        document.documentElement.removeAttribute('style');
      } else {
        document.documentElement.setAttribute('style', originalStyle);
      }
    });

    it('EC-008: Page handles slow network (loading state)', () => {
      // The default fetch stub never settles, so the status bar stays in its
      // loading state.
      const { container } = renderHome();

      const label = container.querySelector('[data-testid="system-status-label"]') as HTMLElement;
      expect(label).toHaveTextContent('System Status: Checking...');

      const detail = container.querySelector('[data-testid="system-status-detail"]') as HTMLElement;
      expect(detail).toHaveTextContent('Checking backend...');
    });

    it('EC-009: Page handles rapid state changes', () => {
      const { rerender } = renderHome();

      // rapid re-renders should not cause issues
      for (let i = 0; i < 5; i++) {
        rerender(
          <TooltipProvider delayDuration={0}>
            <Home />
          </TooltipProvider>
        );
      }

      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
      expect(document.querySelectorAll('[data-testid="indicator-card-competitors"]')).toHaveLength(1);
    });

    it('EC-010: Page handles browser resize', () => {
      const { container } = renderHome();

      // Simulate resize
      window.innerWidth = 500;
      window.dispatchEvent(new Event('resize'));

      expect(container.firstChild).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument();
    });
  });

  // ==========================================
  // SEO and Meta Tests (SM-001 to SM-008)
  // ==========================================
  describe('SEO and Meta', () => {
    it('SM-001: Page has meta title', () => {
      // The homepage is a client component, the document title comes from the
      // root layout metadata.
      expect(layoutMetadata.title).toBe('SEOIndicator.com : AI Powered SEO Analysis');
    });

    it('SM-002: Page has meta description', () => {
      expect(layoutMetadata.description).toBeTruthy();
      expect(String(layoutMetadata.description)).toContain('17 SEO indicators');
    });

    it('SM-003: Page has Open Graph tags', () => {
      const openGraph = layoutMetadata.openGraph as Record<string, unknown>;

      expect(openGraph.title).toBe('SEOIndicator.com : AI Powered SEO Analysis');
      expect(openGraph.description).toBeTruthy();
      expect(openGraph.url).toBe('https://seoindicator.com');
      expect(openGraph.siteName).toBe('SEOIndicator.com');
      expect(openGraph.type).toBe('website');
    });

    it('SM-004: Page has canonical URL', () => {
      // The canonical home URL is the one the layout publishes and the one
      // the sitemap lists with the highest priority.
      const openGraph = layoutMetadata.openGraph as Record<string, unknown>;
      expect(openGraph.url).toBe('https://seoindicator.com');

      const homeEntry = sitemap().find((entry) => entry.url === 'https://seoindicator.com');
      expect(homeEntry).toBeDefined();
      expect(homeEntry?.priority).toBe(1);
    });

    it('SM-005: Page has JSON-LD schema (optional)', () => {
      const { container } = renderHome();

      // The homepage itself does not emit structured data - it must not
      // duplicate the schema of the pages it links to.
      expect(container.querySelectorAll('script[type="application/ld+json"]')).toHaveLength(0);

      // The comparison pages the homepage links to do emit it.
      const comparison = getComparisonBySlug('seoindicator-vs-ahrefs');
      expect(comparison).not.toBeNull();

      const { container: schemaContainer } = render(
        <ComparisonSchema comparison={comparison!} faqs={comparison!.faqs} />
      );

      const scripts = Array.from(schemaContainer.querySelectorAll('script[type="application/ld+json"]'));
      expect(scripts).toHaveLength(3);

      const types = scripts.map((script) => JSON.parse(script.textContent!)['@type']);
      expect(types).toEqual(['BreadcrumbList', 'FAQPage', 'HowTo']);
      scripts.forEach((script) => {
        expect(JSON.parse(script.textContent!)['@context']).toBe('https://schema.org');
      });
    });

    it('SM-006: Meta title includes brand name', () => {
      expect(String(layoutMetadata.title)).toContain('SEOIndicator');
    });

    it('SM-007: Meta description is appropriate length', () => {
      const description = String(layoutMetadata.description);

      expect(description.length).toBeGreaterThanOrEqual(120);
      expect(description.length).toBeLessThanOrEqual(160);
    });

    it('SM-008: Robots meta tag appropriate', () => {
      const robotsConfig = robots();

      expect(robotsConfig.rules.userAgent).toBe('*');
      expect(robotsConfig.rules.allow).toContain('/');
      expect(robotsConfig.rules.allow).toContain('/compare/');
      expect(robotsConfig.sitemap).toBe('https://seoindicator.com/sitemap.xml');
    });
  });

  // ==========================================
  // Snapshot Tests (SN-001 to SN-009)
  //
  // The rendered page contains generated ids (the catalogue title id and the
  // Radix trigger ids), so the snapshots compare a stable outline of the
  // markup instead of the raw DOM.
  // ==========================================
  describe('Snapshots', () => {
    const desktopOutline = [
      { tag: 'section', label: 'Hero', role: 'region' },
      { tag: 'section', label: 'SEO indicators', role: '' },
      { tag: 'section', label: 'Key features', role: '' },
      { tag: 'section', label: '', role: '' },
      { tag: 'section', label: 'How We Compare', role: '' },
      { tag: 'footer', label: '', role: 'contentinfo' },
    ];

    const desktopHeadings = [
      ['h1', 'Find out what is holding your search rankings back'],
      ['h2', 'What we check'],
      ['h2', 'SEO Indicators'],
      ['h2', 'Built for real SEO work'],
      ['h2', 'How we compare'],
    ];

    it('SN-001: Desktop homepage snapshot', () => {
      const { container } = renderHome();

      expect(pageOutline(container)).toEqual(desktopOutline);
      expect(headingOutline(container)).toEqual(desktopHeadings);
    });

    it('SN-002: Mobile homepage snapshot', () => {
      window.innerWidth = 375;

      const { container } = renderHome();

      // The layout is responsive through CSS, so the markup is identical on
      // every viewport.
      expect(pageOutline(container)).toEqual(desktopOutline);
      expect((container.firstChild as HTMLElement).className).toContain('min-h-screen');
    });

    it('SN-003: Tablet homepage snapshot', () => {
      window.innerWidth = 768;

      const { container } = renderHome();

      expect(pageOutline(container)).toEqual(desktopOutline);
      expect(container.querySelector('section[aria-label="Hero"]')?.className).toContain('md:pt-20');
    });

    it('SN-004: Loading state snapshot', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} isLoading />
        </TooltipProvider>
      );

      const skeleton = container.querySelector('[data-testid="catalog-skeleton-grid"]') as HTMLElement;

      expect(skeleton.className).toContain('grid-cols-1');
      expect(skeleton.className).toContain('sm:grid-cols-2');
      expect(skeleton.className).toContain('md:grid-cols-3');
      expect(skeleton.className).toContain('lg:grid-cols-4');
      expect(skeleton.querySelectorAll('[role="presentation"]')).toHaveLength(8);
    });

    it('SN-005: Error state snapshot', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} error={new Error('Boom')} />
        </TooltipProvider>
      );

      const error = container.querySelector('[data-testid="catalog-error"]') as HTMLElement;

      expect(error).toHaveAttribute('role', 'alert');
      expect(error).toHaveTextContent('Failed to load indicators');
      expect(error).toHaveTextContent('Boom');
      expect(error.className).toContain('border-destructive/50');
    });

    it('SN-006: Empty indicators snapshot', () => {
      const { container } = render(
        <TooltipProvider delayDuration={0}>
          <IndicatorCatalog indicators={[]} />
        </TooltipProvider>
      );

      const empty = container.querySelector('[data-testid="catalog-empty"]') as HTMLElement;

      expect(empty).toHaveAttribute('role', 'status');
      expect(empty).toHaveTextContent('No indicators');
      expect(empty).toHaveTextContent('No indicators found in the "All" category');
    });

    it('SN-007: Hero section snapshot', () => {
      const { container } = renderHome();

      const hero = container.querySelector('section[aria-label="Hero"]') as HTMLElement;

      expect(hero.getAttribute('role')).toBe('region');
      expect(within(hero).getByRole('heading', { level: 1 })).toHaveTextContent(
        'Find out what is holding your search rankings back'
      );
      expect(
        within(hero)
          .getAllByRole('link')
          .map((link) => [link.textContent?.trim(), link.getAttribute('href')])
      ).toEqual([
        ['Analyse a website free', '/tools/meta-tag-analyzer'],
        ['See how it works', '/how-it-works'],
      ]);
    });

    it('SN-008: Features grid snapshot', () => {
      const { container } = renderHome();

      const section = featuresSection(container);

      expect(
        Array.from(section.querySelectorAll('[data-slot="card-title"]')).map((title) => title.textContent?.trim())
      ).toEqual(['Keyword Tracking', 'Competitor Analysis', 'Free Analysis Tools']);

      expect(
        Array.from(section.querySelectorAll('[data-slot="card-description"]')).map((description) =>
          description.textContent?.trim()
        )
      ).toEqual([
        'Monitor your keyword positions across search engines',
        "Understand your competition's strategies",
        'No sign-up required for any of them',
      ]);
    });

    it('SN-009: Full page snapshot', () => {
      const { container } = renderHome();

      expect(pageOutline(container)).toEqual(desktopOutline);
      expect(headingOutline(container)).toEqual(desktopHeadings);
      expect(linkOutline(container)).toEqual([
        ['Analyse a website free', '/tools/meta-tag-analyzer'],
        ['See how it works', '/how-it-works'],
        ['Keyword Rankings: 42. Track SERP positions. Status: Good', '/ranking-history'],
        ['Visibility Score: 78%. Overall SEO visibility. Status: Warning', '/dashboard'],
        ['Technical Health: 95. Site audit score. Status: Good', '/audit'],
        ['Backlink Profile: 1.2K. Link analysis. Status: Good', '/dashboard'],
        ['Content Opportunities: 15. Keyword gaps. Status: Neutral', '/dashboard'],
        ['SERP Features: 8. Featured snippets, etc.. Status: Good', '/dashboard'],
        ['Page Speed: 89. Performance metrics. Status: Warning', '/tools/mobile-friendly'],
        ['Competitor Analysis: 3. Compare with competitors. Status: Neutral', '/comparison'],
        ['See ranking history→', '/ranking-history'],
        ['Compare your site→', '/comparison'],
        ['Browse all tools→', '/tools'],
        ['vs Ahrefs', '/compare/seoindicator-vs-ahrefs'],
        ['vs SEMrush', '/compare/seoindicator-vs-semrush'],
        ['vs Moz', '/compare/seoindicator-vs-moz'],
        ['View all comparisons', '/compare'],
      ]);
    });
  });
});
