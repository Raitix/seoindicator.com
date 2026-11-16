import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';

describe('probe', () => {
  it('navigator.clipboard assignability', () => {
    let err = 'none';
    try {
      Object.assign(navigator, { clipboard: { writeText: vi.fn() } });
    } catch (e: any) {
      err = e.message;
    }
    console.log('ASSIGN_ERR:', err);
    let err2 = 'none';
    try {
      Object.defineProperty(navigator, 'clipboard', { value: { writeText: vi.fn() }, configurable: true, writable: true });
    } catch (e: any) {
      err2 = e.message;
    }
    console.log('DEFINE_ERR:', err2);
    let err3 = 'none';
    try {
      Object.defineProperty(navigator, 'onLine', { value: false, configurable: true });
    } catch (e: any) {
      err3 = e.message;
    }
    console.log('ONLINE_ERR:', err3);
    expect(true).toBe(true);
  });

  it('space key on button', async () => {
    const onClick = vi.fn();
    render(<button onClick={onClick}>x</button>);
    const btn = screen.getByRole('button');
    btn.focus();
    await userEvent.keyboard('{Space}');
    console.log('SPACE_CALLS:', onClick.mock.calls.length);
    await userEvent.keyboard(' ');
    console.log('SPACE2_CALLS:', onClick.mock.calls.length);
    expect(true).toBe(true);
  });

  it('radix dialog role and outside click', async () => {
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
    console.log('DIALOG_ATTRS:', dlg.getAttribute('aria-modal'), dlg.getAttribute('aria-labelledby'), dlg.getAttribute('aria-describedby'));
    const title = screen.getByRole('heading');
    console.log('TITLE_ID:', title.id, 'TAG:', title.tagName);
    fireEvent.pointerDown(document.body);
    console.log('POINTERDOWN_OUTSIDE:', onOpenChange.mock.calls.length);
    expect(true).toBe(true);
  });
});
