
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

describe('probe', () => {
  it('ids + perf', async () => {
    vi.stubGlobal('fetch', vi.fn(() => new Promise(() => {})));
    const grab = () => {
      const { container } = renderHome();
      const ids = Array.from(container.querySelectorAll('[id]')).map(e => `${e.tagName}#${e.id}`);
      const ctrl = Array.from(container.querySelectorAll('[aria-controls],[aria-describedby],[aria-labelledby]')).map(e => e.outerHTML.slice(0, 120));
      const html = container.innerHTML;
      container.remove();
      return { ids, ctrl, len: html.length, html };
    };
    const a = grab();
    const b = grab();
    console.log('IDS_A:', JSON.stringify(a.ids));
    console.log('IDS_B:', JSON.stringify(b.ids));
    console.log('CTRL_A:', JSON.stringify(a.ctrl));
    console.log('HTML_SAME:', a.html === b.html);
    console.log('HTML_LEN:', a.len);

    // perf with lucide mock
    const t0 = performance.now();
    for (let i = 0; i < 10; i++) { const r = renderHome(); r.unmount(); }
    console.log('BATCH10_LUCIDEMOCK_MS:', Math.round(performance.now() - t0));
    const t1 = performance.now();
    const r1 = renderHome();
    console.log('FIRST_MS:', Math.round(performance.now() - t1));
    const t2 = performance.now();
    const r2 = renderHome();
    console.log('SECOND_MS:', Math.round(performance.now() - t2));
    r1.unmount(); r2.unmount();
    vi.unstubAllGlobals();
  });
});
