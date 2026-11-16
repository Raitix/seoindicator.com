import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';

describe('probe3', () => {
  it('outside click dismissal', async () => {
    const onOpenChange = vi.fn();
    render(
      <Dialog open={true} onOpenChange={onOpenChange}>
        <DialogContent>
          <DialogTitle>Title</DialogTitle>
          <DialogDescription>Desc</DialogDescription>
          <button>Inside</button>
        </DialogContent>
      </Dialog>
    );
    const dlg = screen.getByRole('dialog');
    console.log('PARENT:', dlg.parentElement?.tagName, dlg.parentElement?.className?.slice(0,80));
    console.log('BODY_CHILDREN:', Array.from(document.body.children).map(c => c.tagName + '.' + (c.className||'').slice(0,60)));
    const overlay = document.querySelector('div.fixed.inset-0');
    console.log('OVERLAY_FOUND:', !!overlay, overlay?.className?.slice(0,80));
    if (overlay) {
      await userEvent.click(overlay);
    }
    console.log('ONOPENCHANGE:', onOpenChange.mock.calls.length, JSON.stringify(onOpenChange.mock.calls[0]?.[0]));
  });

  it('autofocus target', async () => {
    render(
      <Dialog open={true} onOpenChange={vi.fn()}>
        <DialogContent>
          <DialogTitle>Title</DialogTitle>
          <DialogDescription>Desc</DialogDescription>
          <button>Inside</button>
        </DialogContent>
      </Dialog>
    );
    await new Promise(r => setTimeout(r, 50));
    console.log('ACTIVE:', (document.activeElement as HTMLElement)?.tagName, (document.activeElement as HTMLElement)?.textContent?.slice(0,40), (document.activeElement as HTMLElement)?.getAttribute('aria-label'));
  });
});
