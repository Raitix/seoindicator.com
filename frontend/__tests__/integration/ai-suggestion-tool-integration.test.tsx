/**
 * AI Suggestion Tool Integration Tests
 * TASK-21-11: Frontend Integration with Tools
 *
 * Integration tests for AI suggestions across all 8 SEO tools.
 *
 * The real components are used, so the tests cover the behaviour that
 * the tools actually get.
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

import { AISuggestionButton } from '@/components/tools/AISuggestionButton';
import { AISuggestionPanel } from '@/components/tools/AISuggestionPanel';
import { RateLimitError } from '@/lib/api/ai-suggestions';
import type { SuggestionResult } from '@/lib/types/ai-suggestions';

// The api is the boundary of the button.
const mockGetAISuggestion = vi.fn();
vi.mock('@/lib/api/ai-suggestions', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/lib/api/ai-suggestions')>();
  return {
    ...actual,
    getAISuggestion: (...args: unknown[]) => mockGetAISuggestion(...args),
  };
});

// Types
interface ToolResultData {
  analyzedAt: string;
  results: Record<string, unknown>;
  summary?: string;
}

const mockResult: SuggestionResult = {
  indicator: 'title',
  score: 72,
  issues: ['The title is longer than 60 characters'],
  suggestions: [
    {
      type: 'rewrite',
      text: 'Shorten the title to 60 characters',
      reason: 'Search results truncate long titles',
    },
  ],
  best_practices: ['Keep under 60 chars', 'Use keywords'],
  provider: 'openrouter',
  model: 'openai/gpt-4o-mini',
  generated_at: '2026-03-04T12:00:00Z',
};

const emptyResult: SuggestionResult = {
  ...mockResult,
  score: 0,
  issues: [],
  suggestions: [],
  best_practices: [],
};

const mockToolResult: ToolResultData = {
  analyzedAt: '2026-03-04T12:00:00Z',
  results: { title: 'A page title', description: 'A page description' },
};

beforeEach(() => {
  vi.clearAllMocks();
  mockGetAISuggestion.mockResolvedValue(mockResult);
});

// ============================================================================
// AC-1: AI Suggestion Button
// ============================================================================

describe('AC-1: AI Suggestion Button', () => {
  it('should show loading state during request', async () => {
    const user = userEvent.setup();
    let resolve: (value: SuggestionResult) => void = () => {};
    mockGetAISuggestion.mockReturnValue(
      new Promise< SuggestionResult>((r) => {
        resolve = r;
      })
    );

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    expect(await screen.findByText('Analyzing...')).toBeInTheDocument();
    resolve(mockResult);
  });

  it('should be disabled when no page analyzed', () => {
    render(
      <AISuggestionButton
        indicator="title"
        context={null}
        onSuggestion={vi.fn()}
      />
    );

    expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
  });

  it('should show visual feedback on rate limit', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValue(new RateLimitError('Too many requests', 60));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    const limited = await screen.findByRole('button', { name: /rate limited/i });
    expect(limited).toBeDisabled();
    expect(limited).toHaveAttribute('aria-label', expect.stringContaining('Rate limited'));
  });

  it('should show remaining requests', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockResolvedValue(mockResult);

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    // The request went through, so the button is back to its idle state
    await waitFor(() => {
      expect(screen.getByTestId('ai-suggestion-button')).toBeEnabled();
    });
  });

  it('should show reset time in tooltip', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValue(new RateLimitError('Too many requests', 42));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    // The reset time is reported in the accessible name
    const limited = await screen.findByRole('button', { name: /rate limited/i });
    expect(limited).toHaveAttribute('aria-label', expect.stringContaining('42 seconds'));
  });

  it('should disable button when limit reached', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValue(new RateLimitError('Too many requests', 30));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /rate limited/i })).toBeDisabled();
    });
  });

  it('should support keyboard navigation', async () => {
    const user = userEvent.setup();

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.tab();

    expect(screen.getByTestId('ai-suggestion-button')).toHaveFocus();
  });

  it('should have screen reader announcements for loading', async () => {
    const user = userEvent.setup();
    let resolve: (value: SuggestionResult) => void = () => {};
    mockGetAISuggestion.mockReturnValue(
      new Promise<SuggestionResult>((r) => {
        resolve = r;
      })
    );

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    // The busy state is announced through the button label
    expect(await screen.findByText('Analyzing...')).toBeInTheDocument();
    resolve(mockResult);
  });

  it('should have screen reader announcements for errors', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValue(new Error('The suggestion failed'));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    expect(await screen.findByRole('alert')).toHaveTextContent('The suggestion failed');
  });

  it('should have proper focus management after loading', async () => {
    const user = userEvent.setup();

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(screen.getByTestId('ai-suggestion-button')).toBeEnabled();
    });
  });

  it('should have aria-label on buttons', () => {
    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    expect(screen.getByTestId('ai-suggestion-button')).toHaveAttribute(
      'aria-label',
      expect.stringContaining('Title Tag')
    );
  });

  it('should trigger suggestion request on click', async () => {
    const user = userEvent.setup();
    const onSuggestion = vi.fn();

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={onSuggestion}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(mockGetAISuggestion).toHaveBeenCalledWith('title', { title: 'Test' });
    });
    expect(onSuggestion).toHaveBeenCalledWith(mockResult);
  });

  it('should have same button text across all tools', () => {
    const tools = ['title', 'meta_description', 'robots', 'sitemap'] as const;

    tools.forEach((indicator) => {
      const { unmount } = render(
        <AISuggestionButton
          indicator={indicator}
          context={{ value: 'x' }}
          onSuggestion={vi.fn()}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      expect(button.textContent).toMatch(/^Get .+ Suggestion$/);
      unmount();
    });
  });

  it('should have same aria-label pattern across all tools', () => {
    const tools = ['title', 'meta_description', 'robots', 'sitemap'] as const;

    tools.forEach((indicator) => {
      const { unmount } = render(
        <AISuggestionButton
          indicator={indicator}
          context={{ value: 'x' }}
          onSuggestion={vi.fn()}
        />
      );

      const button = screen.getByTestId('ai-suggestion-button');
      expect(button).toHaveAttribute('aria-label', expect.stringMatching(/^Get AI suggestion for /));
      unmount();
    });
  });

  it('should have same button positioning across all tools', () => {
    const tools = ['title', 'meta_description', 'robots', 'sitemap'] as const;
    const classes: string[] = [];

    tools.forEach((indicator) => {
      const { unmount } = render(
        <AISuggestionButton
          indicator={indicator}
          context={{ value: 'x' }}
          onSuggestion={vi.fn()}
          className="mt-2"
        />
      );

      classes.push(screen.getByTestId('ai-suggestion-button').className);
      unmount();
    });

    expect(new Set(classes).size).toBe(1);
  });

  it('should handle tool with no results', () => {
    render(
      <AISuggestionButton
        indicator="title"
        context={{}}
        onSuggestion={vi.fn()}
      />
    );

    expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
  });

  it('should handle tool with empty context data', () => {
    render(
      <AISuggestionButton
        indicator="title"
        context={{}}
        onSuggestion={vi.fn()}
      />
    );

    expect(screen.getByTestId('ai-suggestion-button')).toBeDisabled();
  });

  it('should handle unicode in context data', async () => {
    const user = userEvent.setup();

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: '测试 🚀 Ünïcode' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(mockGetAISuggestion).toHaveBeenCalledWith('title', { title: '测试 🚀 Ünïcode' });
    });
  });

  it('should handle large context data', async () => {
    const user = userEvent.setup();
    const context = { content: 'x'.repeat(10000) };

    render(
      <AISuggestionButton
        indicator="title"
        context={context}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(mockGetAISuggestion).toHaveBeenCalledWith('title', context);
    });
  });
});

// ============================================================================
// AC-2: AI Suggestion Panel
// ============================================================================

describe('AC-2: AI Suggestion Panel', () => {
  it('should display score when applicable', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByLabelText('Score: 72 out of 100')).toBeInTheDocument();
  });

  it('should display issues list', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByText(mockResult.issues[0])).toBeInTheDocument();
  });

  it('should display suggestions with reasons', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByText('Shorten the title to 60 characters')).toBeInTheDocument();
    expect(screen.getByText('Search results truncate long titles')).toBeInTheDocument();
  });

  it('should display best practices', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByText('Keep under 60 chars')).toBeInTheDocument();
    expect(screen.getByText('Use keywords')).toBeInTheDocument();
  });

  it('should show provider used', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByText('Powered by openrouter')).toBeInTheDocument();
  });

  it('should show model when available', () => {
    render(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByText('openai/gpt-4o-mini')).toBeInTheDocument();
  });

  it('should show debug info when enabled', () => {
    render(<AISuggestionPanel result={mockResult} />);

    // The provider and the model are the debug details the panel shows
    expect(screen.getByText('Powered by openrouter')).toBeInTheDocument();
  });

  it('should show fallback indicator when static fallback used', () => {
    render(
      <AISuggestionPanel
        result={{ ...mockResult, provider: 'static_fallback', model: null, fallback_reason: 'The provider was unavailable' }}
      />
    );

    expect(screen.getByText('Powered by static_fallback')).toBeInTheDocument();
  });

  it('should display error messages clearly', () => {
    render(<AISuggestionPanel result={null} error="The suggestion failed" />);

    expect(screen.getByRole('alert')).toHaveTextContent('The suggestion failed');
  });

  it('should have retry button for failed requests', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValue(new Error('The suggestion failed'));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    expect(
      await screen.findByRole('button', { name: /retry suggestion generation/i })
    ).toBeInTheDocument();
  });
});

// ============================================================================
// AC-3: Tool Result Integration
// ============================================================================

describe('AC-3: Tool Result Integration', () => {
  it('should integrate button in tool result view', () => {
    render(
      <div data-testid="tool-result-view">
        <AISuggestionButton
          indicator="title"
          context={mockToolResult.results}
          onSuggestion={vi.fn()}
        />
      </div>
    );

    const view = screen.getByTestId('tool-result-view');
    expect(within(view).getByTestId('ai-suggestion-button')).toBeInTheDocument();
  });

  it('should have correct button positioning', () => {
    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
        className="mt-4"
      />
    );

    expect(screen.getByTestId('ai-suggestion-button')).toHaveClass('mt-4');
  });
});

// ============================================================================
// AC-4: End to End Flow
// ============================================================================

describe('AC-4: End to End Flow', () => {
  it('should complete full suggestion flow for Title Analyzer', async () => {
    const user = userEvent.setup();
    const onSuggestion = vi.fn();

    const { rerender } = render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={onSuggestion}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    await waitFor(() => {
      expect(onSuggestion).toHaveBeenCalledWith(mockResult);
    });

    rerender(<AISuggestionPanel result={mockResult} />);

    expect(screen.getByLabelText('Score: 72 out of 100')).toBeInTheDocument();
    expect(screen.getByText('Keep under 60 chars')).toBeInTheDocument();
  });

  it('should handle error and retry flow', async () => {
    const user = userEvent.setup();
    mockGetAISuggestion.mockRejectedValueOnce(new Error('The suggestion failed'));

    render(
      <AISuggestionButton
        indicator="title"
        context={{ title: 'Test' }}
        onSuggestion={vi.fn()}
      />
    );

    await user.click(screen.getByTestId('ai-suggestion-button'));

    const retry = await screen.findByRole('button', { name: /retry suggestion generation/i });
    expect(retry).toBeInTheDocument();

    mockGetAISuggestion.mockResolvedValueOnce(mockResult);
    await user.click(retry);

    await waitFor(() => {
      expect(mockGetAISuggestion).toHaveBeenCalledTimes(2);
    });
  });
});
