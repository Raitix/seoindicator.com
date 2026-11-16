
import React from 'react';
import { render, screen } from '@testing-library/react';
import { act } from '@testing-library/react';
import '@testing-library/jest-dom';

vi.mock('next/link', () => {
  const MockLink = React.forwardRef(({ children, href, ...props }: any, ref: any) => (
    <a ref={ref} href={href} {...props}>{children}</a>
  ));
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

const flush = async () => { await act(async () => { await new Promise(r => setTimeout(r, 0)); }); };

describe('perf', () => {
  it('measures', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => ({ ok: true, status: 200, json: async () => ({ status: 'ok', database: 'connected', app: 'SEOIndicator' }) })));
    const single: number[] = [];
    for (let i = 0; i < 6; i++) {
      const t0 = performance.now();
      const r = render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
      await flush();
      single.push(performance.now() - t0);
      r.unmount();
    }
    console.log('SINGLE_MS:', single.map(n => Math.round(n)).join(','));
    const t0 = performance.now();
    for (let i = 0; i < 10; i++) {
      const r = render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
      r.unmount();
    }
    console.log('BATCH10_MS:', Math.round(performance.now() - t0));
    console.log('STATUS:', screen.queryAllByText(/System Status/).length);
    vi.unstubAllGlobals();
  });
});
