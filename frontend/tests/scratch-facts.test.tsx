
import React from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

vi.mock('next/link', () => {
  const MockLink = React.forwardRef(({ children, href, ...props }: any, ref: any) => (
    <a ref={ref} href={href} {...props}>{children}</a>
  ));
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});
vi.mock('next/navigation', () => ({
  usePathname: () => '/',
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), back: vi.fn() }),
  useSearchParams: () => new URLSearchParams(),
}));

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

const renderHome = () => render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);

describe('facts', () => {
  it('collect', async () => {
    console.log('HTML_LANG:', JSON.stringify(document.documentElement.outerHTML.slice(0, 120)));
    vi.stubGlobal('fetch', vi.fn(async () => ({ ok: true, status: 200, json: async () => ({ status: 'ok', database: 'connected', app: 'SEOIndicator' }) })));
    const { container } = renderHome();
    await screen.findByText(/System Status: Operational/);
    console.log('TABINDEX:', Array.from(container.querySelectorAll('button')).map(b => `${b.textContent}=${b.getAttribute('tabindex')}`));
    console.log('TABLIST_NAME:', screen.getByRole('tablist').getAttribute('aria-label'));
    const feats = screen.getByRole('region', { name: 'Key features' });
    console.log('FEAT_CARD_CLASS:', feats.querySelector('[data-slot="card"]')?.className);
    console.log('STATUS_CARD_CLASS:', container.querySelector('section > [data-slot="card"]')?.className);
    const dot = container.querySelector('[data-testid="system-status-bar"] div[aria-hidden="true"]');
    console.log('DOT_CLASS:', dot?.className);
    console.log('SVG_COUNT:', container.querySelectorAll('svg').length);
    console.log('FIRST_SVG:', container.querySelector('svg')?.outerHTML.slice(0, 200));
    console.log('GRID_ARIA:', container.querySelector('[data-testid="indicator-grid"]')?.getAttribute('aria-label'));
    console.log('CATALOG_ATTRS:', container.querySelector('[data-testid="indicator-catalog"]')?.getAttributeNames().join(','));
    console.log('COMPARISON_REGION:', !!screen.queryByRole('region', { name: 'How We Compare' }));
    console.log('ANCHOR_TEXT_COMP:', screen.queryAllByText(/Competitor Analysis/i).length);
    const grid = container.querySelector('[data-testid="indicator-grid"]')!;
    console.log('IND_LINKS:', Array.from(grid.querySelectorAll('a')).map(a => a.getAttribute('data-testid') + '->' + a.getAttribute('href')).join(' '));
    // click anchor
    const errs: any[] = [];
    const spy = vi.spyOn(console, 'error').mockImplementation((...a: any[]) => errs.push(a.map(String).join(' ')));
    const user = userEvent.setup();
    await user.click(screen.getByRole('link', { name: /analyse a website free/i }));
    console.log('CLICK_CONSOLE_ERRORS:', JSON.stringify(errs).slice(0, 300));
    spy.mockRestore();
    vi.unstubAllGlobals();
  });
});
