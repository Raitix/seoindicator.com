import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SWRConfig } from 'swr';
import { PaymentMethodSelector } from '@/components/payments/PaymentMethodSelector';

const mockFetch = vi.fn();
global.fetch = mockFetch;

function renderIsolated(ui: React.ReactElement) {
  return render(
    <SWRConfig value={{ provider: () => new Map(), dedupingInterval: 0 }}>{ui}</SWRConfig>
  );
}

const methods = [
  {
    id: 'pm_visa',
    provider: 'card',
    brand: 'visa',
    last_four: '4242',
    exp_month: 12,
    exp_year: 2030,
    is_default: true,
    created_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 'pm_mc',
    provider: 'card',
    brand: 'mastercard',
    last_four: '5555',
    exp_month: 6,
    exp_year: 2031,
    is_default: false,
    created_at: '2026-01-01T00:00:00Z',
  },
];

describe('probe6', () => {
  it('renders external methods + fetch url', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ success: true, data: methods }),
    });
    renderIsolated(
      <PaymentMethodSelector
        methods={[
          { id: 'pm_visa', type: 'card', card: { brand: 'visa', last4: '4242', exp_month: 12, exp_year: 2030 }, is_default: true },
          { id: 'pm_mc', type: 'card', card: { brand: 'mastercard', last4: '5555', exp_month: 6, exp_year: 2031 }, is_default: false },
        ]}
        selectedId="pm_visa"
        onMethodSelect={vi.fn()}
        onAddNew={vi.fn()}
      />
    );
    await waitFor(() => expect(mockFetch).toHaveBeenCalled());
    console.log('FETCH CALLS:', JSON.stringify(mockFetch.mock.calls.map(c => [c[0], c[1]?.method])));
    console.log('CARD:', screen.getByTestId('method-pm_visa').outerHTML.slice(0, 300));
    console.log('BUTTONS:', screen.getAllByRole('button').map(b => `${b.textContent?.trim()}|${b.getAttribute('aria-label')}`));
  });

  it('set default + delete flows', async () => {
    mockFetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ success: true, data: methods }) });
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);
    renderIsolated(
      <PaymentMethodSelector
        methods={[
          { id: 'pm_visa', type: 'card', card: { brand: 'visa', last4: '4242', exp_month: 12, exp_year: 2030 }, is_default: true },
          { id: 'pm_mc', type: 'card', card: { brand: 'mastercard', last4: '5555', exp_month: 6, exp_year: 2031 }, is_default: false },
        ]}
        selectedId={null}
        onMethodSelect={vi.fn()}
        onAddNew={vi.fn()}
      />
    );
    const card = screen.getByTestId('method-pm_mc');
    const menuBtn = within(card).getByTestId('more-vertical-icon').closest('button')!;
    await userEvent.click(menuBtn);
    console.log('MENU ITEMS:', screen.getAllByRole('button').map(b => b.textContent?.trim()).filter(Boolean));
    await userEvent.click(screen.getByRole('button', { name: /set.*default/i }));
    await waitFor(() => {
      const calls = mockFetch.mock.calls.map(c => [c[0], c[1]?.method]);
      console.log('AFTER SET DEFAULT:', JSON.stringify(calls));
    });
    // delete
    await userEvent.click(within(screen.getByTestId('method-pm_mc')).getByTestId('more-vertical-icon').closest('button')!);
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));
    console.log('CONFIRM CALLED:', confirmSpy.mock.calls.length, JSON.stringify(confirmSpy.mock.calls[0]));
    await waitFor(() => {
      console.log('AFTER DELETE:', JSON.stringify(mockFetch.mock.calls.map(c => [c[0], c[1]?.method])));
    });
  });

  it('keyboard + tab order', async () => {
    mockFetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ success: true, data: methods }) });
    renderIsolated(
      <PaymentMethodSelector
        methods={[
          { id: 'pm_visa', type: 'card', card: { brand: 'visa', last4: '4242', exp_month: 12, exp_year: 2030 }, is_default: true },
          { id: 'pm_mc', type: 'card', card: { brand: 'mastercard', last4: '5555', exp_month: 6, exp_year: 2031 }, is_default: false },
        ]}
        selectedId={null}
        onMethodSelect={vi.fn()}
        onAddNew={vi.fn()}
      />
    );
    const card = screen.getByTestId('method-pm_visa');
    console.log('CARD TABINDEX:', card.getAttribute('tabindex'), 'role:', card.getAttribute('role'));
  });
});

import { within } from '@testing-library/react';
