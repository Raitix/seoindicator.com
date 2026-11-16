import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { VisibilitySettings } from '@/components/audit/VisibilitySettings';

vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));

describe('probe2', () => {
  it('focus order in confirm dialog', async () => {
    render(
      <VisibilitySettings
        auditId={1}
        auditHash="abc"
        isPublic={true}
        onPublish={vi.fn().mockResolvedValue(undefined)}
        onUnpublish={vi.fn().mockResolvedValue(undefined)}
      />
    );
    const toggle = screen.getByRole('switch');
    await userEvent.click(toggle);
    const dialog = screen.getByTestId('confirm-unpublish-dialog');
    await waitFor(() => expect(dialog.contains(document.activeElement)).toBe(true));
    console.log('ACTIVE_BEFORE_TAB:', (document.activeElement as HTMLElement)?.outerHTML?.slice(0, 120));
    await userEvent.tab();
    console.log('ACTIVE_AFTER_TAB:', (document.activeElement as HTMLElement)?.outerHTML?.slice(0, 160));
    console.log('IN_DIALOG_AFTER_TAB:', dialog.contains(document.activeElement));
    expect(true).toBe(true);
  });

  it('loader icons inside confirm button', async () => {
    let resolveUnpublish: () => void;
    const onUnpublish = vi.fn().mockImplementation(() => new Promise<void>((r) => { resolveUnpublish = r; }));
    render(
      <VisibilitySettings
        auditId={1}
        auditHash="abc"
        isPublic={true}
        onPublish={vi.fn().mockResolvedValue(undefined)}
        onUnpublish={onUnpublish}
      />
    );
    const toggle = screen.getByRole('switch');
    await userEvent.click(toggle);
    const confirmButton = screen.getByTestId('confirm-unpublish-button');
    await userEvent.click(confirmButton);
    const spinners = screen.getAllByTestId('loader-icon');
    console.log('SPINNER_COUNT:', spinners.length);
    spinners.forEach((s, i) => console.log('SPINNER', i, 'IN_CONFIRM:', confirmButton.contains(s), s.outerHTML.slice(0, 100)));
    expect(true).toBe(true);
  });
});
