
import React from 'react';
import { render, screen, within, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

vi.mock('next/link', () => {
  const MockLink = ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  );
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});
const mockSearchParams = new URLSearchParams();
vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), back: vi.fn() }),
  usePathname: () => '/',
  useSearchParams: () => mockSearchParams,
}));
vi.mock('next/font/google', () => ({
  Geist: () => ({ variable: 'geist-sans', className: 'geist-sans' }),
  Geist_Mono: () => ({ variable: 'geist-mono', className: 'geist-mono' }),
}));

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';
import { SiteHeader } from '@/components/layout/SiteHeader';

const renderHome = () => render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);

describe('probe2', () => {
  it('header + tabs + reduced motion + status', async () => {
    const errors: any[] = [];
    const spy = vi.spyOn(console, 'error').mockImplementation((...a: any[]) => { errors.push(a); });
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));

    // SiteHeader
    render(<SiteHeader />);
    console.log('BANNER:', screen.getAllByRole('banner').length);
    console.log('NAV:', screen.getByRole('navigation', { name: 'Main navigation' }).textContent);
    console.log('MENU_BTN:', screen.getByRole('button', { name: /open menu/i }).className.slice(0, 60));

    // Home
    const { container } = renderHome();
    const catalog = container.querySelector('[data-testid="indicator-catalog"]')!;
    console.log('CATALOG_CLASS:', catalog.className);
    console.log('CATALOG_LBL:', catalog.getAttribute('aria-labelledby'));
    console.log('TITLE_ID:', container.querySelector('[data-testid="catalog-title"]')!.id);
    console.log('GRID_CLASS:', container.querySelector('[data-testid="indicator-grid"]')!.className);
    console.log('GRID_LOADING:', container.querySelector('[data-testid="indicator-grid"]')!.getAttribute('data-loading'));
    console.log('GRID_COUNT:', container.querySelector('[data-testid="indicator-grid"]')!.getAttribute('data-indicator-count'));
    console.log('TABS:', screen.getAllByRole('tab').map(t => t.textContent));
    console.log('STATUS_LABEL:', screen.getByTestId('system-status-label').textContent);
    console.log('STATUS_DETAIL:', screen.getByTestId('system-status-detail').textContent);
    console.log('CARD_SLOT_COUNT:', container.querySelectorAll('[data-slot="card"]').length);
    console.log('CARD_TITLE_COUNT:', container.querySelectorAll('[data-slot="card-title"]').length);
    const features = screen.getByRole('region', { name: 'Key features' });
    console.log('FEATURES_CARDS:', features.querySelectorAll('[data-slot="card"]').length);
    console.log('FEATURES_H2:', within(features).getByRole('heading', { level: 2 }).textContent);
    console.log('IND_LINKS:', Array.from(container.querySelectorAll('[data-testid^="indicator-card-"]')).map(a => a.getAttribute('href')));
    console.log('STYLE_COUNT:', container.querySelectorAll('[style]').length);
    console.log('STYLES:', Array.from(container.querySelectorAll('[style]')).map(e => e.getAttribute('style')));
    console.log('REGIONS:', screen.getAllByRole('region').map(r => r.getAttribute('aria-label') || r.getAttribute('aria-labelledby')));
    console.log('SECTIONS:', container.querySelectorAll('section').length);
    console.log('BUTTONS:', container.querySelectorAll('button').length);
    console.log('H1:', screen.getByRole('heading', { level: 1 }).textContent);

    // tab filtering
    const user = userEvent.setup();
    await user.click(screen.getByRole('tab', { name: 'Technical' }));
    console.log('AFTER_TECH:', Array.from(container.querySelectorAll('[data-testid^="indicator-card-"]')).map(a => a.getAttribute('data-testid')));

    console.log('ERRORS:', errors.length, JSON.stringify(errors).slice(0, 500));
    spy.mockRestore();
    vi.unstubAllGlobals();
  });

  it('reduced motion', async () => {
    vi.stubGlobal('matchMedia', vi.fn((query: string) => ({
      matches: query.includes('prefers-reduced-motion'),
      media: query,
      onchange: null,
      addListener: vi.fn(), removeListener: vi.fn(),
      addEventListener: vi.fn(), removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })));
    const { container } = renderHome();
    const catalog = container.querySelector('[data-testid="indicator-catalog"]')!;
    console.log('RM_CATALOG_CLASS:', catalog.className);
    console.log('RM_CARD_CLASS:', container.querySelector('[data-testid="indicator-card-rankings"]')!.className);
    vi.unstubAllGlobals();
  });

  it('health ok', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => ({ ok: true, status: 200, json: async () => ({ status: 'ok', database: 'connected', app: 'SEOIndicator' }) })));
    renderHome();
    console.log('OK_LABEL:', (await screen.findByTestId('system-status-label')).textContent);
    console.log('OK_DETAIL:', screen.getByTestId('system-status-detail').textContent);
    vi.unstubAllGlobals();
  });
});
