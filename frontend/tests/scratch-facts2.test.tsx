
import React from 'react';
import { render, screen } from '@testing-library/react';
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
vi.mock('next/font/google', () => ({
  Geist: () => ({ variable: 'geist-sans', className: 'geist-sans' }),
  Geist_Mono: () => ({ variable: 'geist-mono', className: 'geist-mono' }),
}));

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

describe('facts2', () => {
  it('tab roving + layout lang', async () => {
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));
    const { container } = render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
    await new Promise(r => setTimeout(r, 30));
    console.log('TABS:', Array.from(container.querySelectorAll('[role="tab"]')).map(b => `${b.textContent}:tabindex=${b.getAttribute('tabindex')},selected=${b.getAttribute('aria-selected')},state=${b.getAttribute('data-state')}`).join(' | '));
    console.log('TABS_WRAPPER_LABEL:', container.querySelector('[data-slot="tabs"]')?.getAttribute('aria-label'));
    console.log('ALL_BUTTONS:', container.querySelectorAll('button').length);
    console.log('LINK_TABINDEX:', Array.from(container.querySelectorAll('a')).map(a => a.getAttribute('tabindex')).join(','));

    const layout: any = await import('@/app/layout');
    const tree = layout.default({ children: null });
    console.log('LAYOUT_HTML_PROPS:', JSON.stringify(tree.props));
    vi.unstubAllGlobals();
  });
});
