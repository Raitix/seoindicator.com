
import React from 'react';

vi.mock('next/link', () => {
  const MockLink = React.forwardRef(({ children, href, ...props }: any, ref: any) => (
    <a ref={ref} href={href} {...props}>{children}</a>
  ));
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

describe('layout probe', () => {
  it('imports layout', async () => {
    const errors: any[] = [];
    const spy = vi.spyOn(console, 'error').mockImplementation((...a: any[]) => { errors.push(a); });
    let mod: any;
    try {
      mod = await import('@/app/layout');
    } catch (e: any) {
      console.log('LAYOUT_ERR:', e?.message);
    }
    console.log('LAYOUT_KEYS:', mod ? Object.keys(mod) : 'none');
    console.log('TITLE:', mod?.metadata?.title);
    console.log('DESC:', String(mod?.metadata?.description).slice(0, 80));
    console.log('OG:', JSON.stringify(mod?.metadata?.openGraph));
    console.log('TWITTER:', JSON.stringify(mod?.metadata?.twitter));
    console.log('ALTERNATES:', JSON.stringify(mod?.metadata?.alternates));
    console.log('ROBOTS_META:', JSON.stringify(mod?.metadata?.robots));
    if (mod?.default) {
      const tree = mod.default({ children: null });
      console.log('HTML_PROPS:', JSON.stringify(tree.props));
    }
    const robots = (await import('@/app/robots')).default;
    console.log('ROBOTS:', JSON.stringify(robots()));
    const sitemap = (await import('@/app/sitemap')).default;
    const sm = sitemap();
    console.log('SITEMAP_HOME:', JSON.stringify(sm.find(e => e.url === 'https://seoindicator.com')));
    console.log('CONSOLE_ERRORS:', errors.length, JSON.stringify(errors).slice(0, 400));
    spy.mockRestore();
  });
});
