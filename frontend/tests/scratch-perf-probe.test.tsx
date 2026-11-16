import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

vi.mock('next/link', () => {
  const MockLink = ({ children, href, ...props }: any) => (
    <a href={href} {...props}>{children}</a>
  );
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});

vi.mock('lucide-react', () => {
  const IconStub = ({ ...props }: any) =>
    React.createElement('span', { 'data-testid': 'lucide-icon', ...props });
  return {
    Activity: IconStub, AlertCircle: IconStub, AlertTriangle: IconStub,
    CheckCircle: IconStub, ChevronDown: IconStub, Eye: IconStub,
    FileText: IconStub, FolderOpen: IconStub, HelpCircle: IconStub,
    Info: IconStub, Link: IconStub, Link2: IconStub, LucideIcon: IconStub,
    Menu: IconStub, Minus: IconStub, RefreshCw: IconStub, Search: IconStub,
    Star: IconStub, TrendingUp: IconStub, Users: IconStub, Wrench: IconStub,
    X: IconStub, XCircle: IconStub, Zap: IconStub,
  };
});

import Home from '@/app/page';
import { TooltipProvider } from '@/components/ui/tooltip';

describe('perf', () => {
  it('timings', async () => {
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));
    const t0 = performance.now();
    render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
    const t1 = performance.now();
    console.log('FIRST_RENDER_MS:', (t1 - t0).toFixed(1));

    const t2 = performance.now();
    for (let i = 0; i < 10; i++) {
      const { unmount } = render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
      unmount();
    }
    console.log('BATCH10_MS:', (performance.now() - t2).toFixed(1));

    const t3 = performance.now();
    const { unmount } = render(<TooltipProvider delayDuration={0}><Home /></TooltipProvider>);
    console.log('WARM_RENDER_MS:', (performance.now() - t3).toFixed(1));
    unmount();
    vi.unstubAllGlobals();
  });
});
