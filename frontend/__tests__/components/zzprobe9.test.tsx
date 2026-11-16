import { render, screen, waitFor } from '@testing-library/react';
import { SWRConfig } from 'swr';
import { PaymentHistory } from '@/components/payments/PaymentHistory';

const mockFetch = vi.fn();
global.fetch = mockFetch;

function iso(ui: React.ReactElement) {
  return render(<SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>{ui}</SWRConfig>);
}

describe('probe9', () => {
  it('loading + error states', async () => {
    mockFetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ success: true, data: [], meta: {} }) });
    const { unmount } = iso(<PaymentHistory transactions={[]} isLoading={true} />);
    console.log('SKELETON:', !!screen.queryByTestId('loading-skeleton'), !!screen.queryByTestId('loader-icon'));
    unmount();
    iso(<PaymentHistory transactions={[]} error="Boom" />);
    console.log('ERROR TEXTS:', screen.getAllByText(/failed.*load/i).map(e => e.textContent));
    console.log('ERROR MSG:', screen.getByText('Boom')?.textContent);
    console.log('TRY AGAIN:', screen.getByRole('button', { name: /try again/i })?.textContent);
  });

  it('buttons + receipts + copy', async () => {
    mockFetch.mockResolvedValue({
      ok: true, status: 200,
      json: async () => ({ success: true, data: [], meta: { current_page: 1, per_page: 10, total: 1, last_page: 1 } }),
    });
    const txs = [{
      id: 'txn_1', amount: 29.99, currency: 'USD', credits: 100, status: 'completed',
      payment_method: 'Visa ****4242', created_at: new Date(2026, 2, 14).toISOString(),
      description: 'Pro Credit Package', receipt_url: 'https://x/r.pdf',
    }];
    iso(<PaymentHistory transactions={txs as any} total={1} />);
    await waitFor(() => screen.getByRole('table'));
    console.log('ALL BUTTONS:', screen.getAllByRole('button').map(b => `[${b.textContent?.trim()}|${b.getAttribute('aria-label')}]`));
    console.log('RECEIPT COUNT:', screen.getAllByRole('button', { name: /receipt/i }).length);
    console.log('COPY COUNT:', screen.getAllByRole('button', { name: /copy/i }).length);
    console.log('TEXTBOX:', screen.queryAllByRole('textbox').length, 'SEARCHBOX:', screen.queryAllByRole('searchbox').length);
    console.log('COMBOBOX:', screen.queryAllByRole('combobox').length);
    console.log('STATUS SPANS:', screen.getAllByText(/completed/i).map(e => `${e.tagName}|${e.textContent}|${e.className.slice(0,70)}`));
    console.log('DATES:', screen.getAllByText(/mar 14/i).map(e => e.textContent));
  });
});
