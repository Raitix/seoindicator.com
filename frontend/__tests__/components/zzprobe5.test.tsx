import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteConfirmationDialog from '@/components/history/DeleteConfirmationDialog';

const analysis = {
  id: 127,
  toolName: 'meta-tag-analyzer',
  toolDisplayName: 'Meta Tag Analyzer',
  inputUrl: 'https://example.com',
  status: 'completed',
  analyzedAt: '2026-03-01T10:00:00Z',
  analysisDurationMs: 1234,
  cost: { total: 0.05, currency: 'USD' },
} as any;

describe('probe5', () => {
  it('tab focus', async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog analysis={analysis} isOpen={true} onClose={vi.fn()} onConfirm={vi.fn()} />);
    const dlg = screen.getByRole('dialog');
    console.log('PREV SIBLING:', dlg.previousElementSibling?.tagName, (dlg.previousElementSibling as HTMLElement)?.getAttribute('data-state'), (dlg.previousElementSibling as HTMLElement)?.className?.slice(0,40));
    await user.tab();
    const f1 = document.activeElement;
    console.log('TAB1:', f1?.tagName, (f1 as HTMLElement)?.getAttribute('data-testid'), (f1 as HTMLElement)?.getAttribute('data-radix-focus-guard'), (f1 as HTMLElement)?.className?.slice(0,50));
    await user.tab();
    const f2 = document.activeElement;
    console.log('TAB2:', f2?.tagName, (f2 as HTMLElement)?.getAttribute('data-testid'), (f2 as HTMLElement)?.getAttribute('data-radix-focus-guard'), (f2 as HTMLElement)?.className?.slice(0,50));
    console.log('BOTH IN DIALOG:', dlg.contains(f1), dlg.contains(f2));
  });

  it('outside click via previous sibling', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();
    render(<DeleteConfirmationDialog analysis={analysis} isOpen={true} onClose={onClose} onConfirm={vi.fn()} />);
    const dlg = screen.getByRole('dialog');
    const overlay = dlg.previousElementSibling as HTMLElement;
    await user.click(overlay);
    console.log('ONCLOSE AFTER OVERLAY:', onClose.mock.calls.length);
  });

  it('null analysis renders nothing', () => {
    const { container } = render(<DeleteConfirmationDialog analysis={null} isOpen={true} onClose={vi.fn()} onConfirm={vi.fn()} />);
    console.log('NULL ANALYSIS HTML:', container.innerHTML.length);
  });

  it('button classes', async () => {
    render(<DeleteConfirmationDialog analysis={analysis} isOpen={true} onClose={vi.fn()} onConfirm={vi.fn()} />);
    const cancel = screen.getByTestId('cancel-delete-button');
    const del = screen.getByTestId('confirm-delete-button');
    console.log('CANCEL CLASS:', cancel.className);
    console.log('CANCEL VARIANT:', cancel.getAttribute('data-variant'));
    console.log('DELETE CLASS:', del.className);
    console.log('DELETE VARIANT:', del.getAttribute('data-variant'));
    console.log('DELETE ARIA:', del.getAttribute('aria-label'), '|', cancel.getAttribute('aria-label'));
  });
});
