import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SWRConfig } from 'swr';
import { PaymentMethodSelector } from '@/components/payments/PaymentMethodSelector';
import { PaymentHistory } from '@/components/payments/PaymentHistory';

const mockFetch = vi.fn();
global.fetch = mockFetch;

function iso(ui: React.ReactElement) {
  return render(<SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>{ui}</SWRConfig>);
}

const cardMethods = [
  { id: 'pm_visa', type: 'card' as const, card: { brand: 'visa', last4: '4242', exp_month: 12, exp_year: 2030 }, is_default: true },
  { id: 'pm_mc', type: 'card' as const, card: { brand: 'mastercard', last4: '5555', exp_month: 6, exp_year: 2031 }, is_default: false },
];

describe('probe7', () => {
  it('selector: menu, set default, delete', async () => {
    mockFetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ success: true, data: [] }) });
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);
    iso(
      <PaymentMethodSelector methods={cardMethods} selectedId={null} onMethodSelect={vi.fn()} onAddNew={vi.fn()} />
    );
    const card = screen.getByTestId('method-pm_mc');
    const menuBtn = within(card).getAllByRole('button')[0];
    console.log('MENU BTN HTML:', menuBtn.outerHTML.slice(0, 160));
    await userEvent.click(menuBtn);
    console.log('MENU BUTTONS:', screen.getAllByRole('button').map(b => `[${b.textContent?.trim()}]`));
    await userEvent.click(screen.getByRole('button', { name: /set.*default/i }));
    await waitFor(() => console.log('AFTER SET DEFAULT:', JSON.stringify(mockFetch.mock.calls.map(c => [c[0], c[1]?.method]))));
    await userEvent.click(within(screen.getByTestId('method-pm_mc')).getAllByRole('button')[0]);
    await userEvent.click(screen.getByRole('button', { name: /^delete$/i }));
    console.log('CONFIRM:', confirmSpy.mock.calls.length, JSON.stringify(confirmSpy.mock.calls[0]));
    await waitFor(() => console.log('AFTER DELETE:', JSON.stringify(mockFetch.mock.calls.map(c => [c[0], c[1]?.method]))));
  });

  it('history: structure', async () => {
    mockFetch.mockResolvedValue({
      ok: true, status: 200,
      json: async () => ({ success: true, data: [], meta: { current_page: 1, per_page: 10, total: 50, last_page: 5 } }),
    });
    const txs = Array.from({ length: 3 }, (_, i) => ({
      id: `txn_${i + 1}`, amount: 29.99 + i, currency: 'USD', credits: 100 + i * 10,
      status: ['completed', 'pending', 'failed', 'refunded'][i], payment_method: 'Visa ****4242',
      created_at: new Date(2026, 2, 14 - i).toISOString(), description: 'Pro Credit Package',
      receipt_url: i === 0 ? 'https://x/r.pdf' : undefined,
    }));
    iso(<PaymentHistory transactions={txs as any} total={50} />);
    await waitFor(() => screen.getByRole('table'));
    console.log('FETCH:', JSON.stringify(mockFetch.mock.calls.map(c => [c[0], c[1]?.method])));
    console.log('ROWS:', screen.getAllByRole('row').length);
    console.log('COUNT TEXT:', screen.getByText(/transactions/i).textContent);
    console.log('DATE TEXTS:', screen.getAllByText(/mar 14/i).map(e => e.textContent));
    console.log('STATUS TEXTS:', screen.getAllByText(/completed|pending|failed/i).map(e => `${e.textContent}|${e.className.slice(0,60)}`));
    console.log('BUTTONS:', screen.getAllByRole('button').map(b => `[${b.textContent?.trim()}|${b.getAttribute('aria-label')}]`));
    console.log('PAGINATION:', screen.getByText(/page 1 of 5/i)?.textContent);
    console.log('MOBILE CLASS:', document.querySelector('.lg\\:hidden')?.className);
    console.log('DESKTOP CLASS:', document.querySelector('.hidden.lg\\:block')?.className);
    console.log('INPUT:', screen.getByPlaceholderText(/search/i)?.outerHTML.slice(0, 120));
    console.log('SELECT:', screen.getByRole('combobox')?.outerHTML.slice(0, 200));
  });
});
