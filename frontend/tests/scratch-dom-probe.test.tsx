import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

vi.mock('next/link', () => {
  const MockLink = ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  );
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});

vi.mock('next/navigation', () => ({
  usePathname: () => '/',
  useRouter: () => ({ push: vi.fn(), replace: vi.fn(), back: vi.fn() }),
  useSearchParams: () => new URLSearchParams(),
}));

vi.mock('next/font/google', () => ({
  Geist: () => ({ variable: 'geist-sans', className: 'geist-sans' }),
  Geist_Mono: () => ({ variable: 'geist-mono', className: 'geist-mono' }),
}));

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

describe('dom probe', () => {
  it('inventory', async () => {
    const errors: unknown[] = [];
    const spy = vi.spyOn(console, 'error').mockImplementation((...a) => { errors.push(a); });
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));

    const { container } = render(
      <TooltipProvider delayDuration={0}>
        <Home />
      </TooltipProvider>
    );

    console.log('H1:', screen.getAllByRole('heading', { level: 1 }).map(h => h.textContent));
    console.log('H2:', screen.getAllByRole('heading', { level: 2 }).map(h => h.textContent));
    console.log('H3:', screen.getAllByRole('heading', { level: 3 }).map(h => h.textContent));
    console.log('CARD_SLOTS:', container.querySelectorAll('[data-slot="card"]').length);
    console.log('CARD_TITLES:', Array.from(container.querySelectorAll('[data-slot="card-title"]')).map(e => e.textContent));
    console.log('REGIONS:', screen.getAllByRole('region').map(r => r.getAttribute('aria-label') || r.getAttribute('aria-labelledby')));
    console.log('STATUS_ROLE_COUNT:', container.querySelectorAll('[role="status"]').length);
    console.log('GRIDCELLS:', container.querySelectorAll('[role="gridcell"]').length);
    console.log('SVG_COUNT:', container.querySelectorAll('svg').length);
    console.log('IMG_COUNT:', container.querySelectorAll('img').length);
    console.log('BUTTON_COUNT:', container.querySelectorAll('button').length);
    console.log('STYLE_ATTRS:', Array.from(container.querySelectorAll('[style]')).map(e => e.getAttribute('style')));
    console.log('STYLE_COUNT:', container.querySelectorAll('[style]').length);
    console.log('CTA_CLASS:', container.querySelector('a[href="/tools/meta-tag-analyzer"]')?.className);
    console.log('TABBTN_CLASS:', container.querySelector('button[role="tab"]')?.className?.slice(0, 120));
    console.log('CARD_LINK_NAME:', screen.getByTestId('indicator-card-competitors').getAttribute('aria-label'));
    console.log('FOOTER_HTML:', container.querySelector('footer')?.outerHTML);
    console.log('CONSOLE_ERRORS:', errors.length, JSON.stringify(errors).slice(0, 600));
    spy.mockRestore();
    vi.unstubAllGlobals();
  });
});
