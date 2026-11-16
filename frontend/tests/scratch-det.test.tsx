
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
vi.mock('lucide-react', () => {
  const IconStub = ({ ...props }: any) => React.createElement('span', { 'data-testid': 'lucide-icon', ...props });
  return {
    Activity: IconStub, AlertCircle: IconStub, AlertTriangle: IconStub, CheckCircle: IconStub,
    ChevronDown: IconStub, Eye: IconStub, FileText: IconStub, FolderOpen: IconStub,
    HelpCircle: IconStub, Info: IconStub, Link: IconStub, Link2: IconStub, LucideIcon: IconStub,
    Minus: IconStub, RefreshCw: IconStub, Search: IconStub, Star: IconStub, TrendingUp: IconStub,
    Users: IconStub, Wrench: IconStub, XCircle: IconStub, Zap: IconStub,
  };
});

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

const renderHome = () => render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);

describe('det', () => {
  it('html hash', async () => {
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));
    const { container } = renderHome();
    const html = container.innerHTML;
    const crypto = await import('node:crypto');
    console.log('HASH:', crypto.createHash('md5').update(html).digest('hex'), 'LEN:', html.length);
    // region queries
    const hero = screen.getByRole('region', { name: 'Hero' });
    console.log('HERO_CLASS:', hero.className);
    console.log('HERO_H1:', within(hero).getByRole('heading', { level: 1 }).textContent);
    console.log('HERO_LINKS:', within(hero).getAllByRole('link').map(a => `${a.textContent}|${a.getAttribute('href')}`));
    const feats = screen.getByRole('region', { name: 'Key features' });
    console.log('FEAT_CARDS:', feats.querySelectorAll('[data-slot="card"]').length);
    console.log('FEAT_H2:', within(feats).getByRole('heading', { level: 2 }).textContent);
    console.log('FEAT_TITLES:', Array.from(feats.querySelectorAll('[data-slot="card-title"]')).map(e => e.textContent));
    console.log('FOOTER_CN:', screen.getByRole('contentinfo').textContent);
    console.log('IND_LINKS:', Array.from(container.querySelectorAll('[data-testid^="indicator-card-"]')).map(a => a.getAttribute('data-testid') + '=' + a.getAttribute('href')));
    // click on anchor -> jsdom navigation
    const errors: any[] = [];
    const spy = vi.spyOn(console, 'error').mockImplementation((...a: any[]) => errors.push(a.map(String).join(' ')));
    const user = userEvent.setup();
    await user.click(screen.getByRole('link', { name: /analyse a website free/i }));
    console.log('CLICK_ERRORS:', JSON.stringify(errors).slice(0, 400));
    spy.mockRestore();
    vi.unstubAllGlobals();
  });
});
