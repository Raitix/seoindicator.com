/**
 * TASK-34-10: RecentMentionsList Component Tests
 * 
 * Tests for the recent mentions list in the AI Visibility dashboard.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

import React, { useState } from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  ChevronRight: () => <span data-testid="chevron-icon">›</span>,
  ChevronDown: () => <span data-testid="chevron-down-icon">⌄</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  MessageSquare: () => <span data-testid="message-icon">💬</span>,
  ThumbsUp: () => <span data-testid="thumbs-up-icon">👍</span>,
  ThumbsDown: () => <span data-testid="thumbs-down-icon">👎</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
}));

// Types
interface Mention {
  id: string;
  brandContext: string;
  platform: 'chatgpt' | 'perplexity' | 'claude';
  sentiment: 'positive' | 'negative' | 'neutral' | 'mixed';
  queriedAt: string;
  queryText?: string;
}

interface RecentMentionsListProps {
  mentions: Mention[];
  maxVisible?: number;
  viewAllHref?: string;
  onMentionClick?: (mention: Mention) => void;
  className?: string;
}

// Mock component for TDD reference
const RecentMentionsList: React.FC<RecentMentionsListProps> = ({
  mentions,
  maxVisible = 10,
  viewAllHref,
  onMentionClick,
  className,
}) => {
  const [expandedId, setExpandedId] = useState<string | null>(null);

  const visibleMentions = mentions.slice(0, maxVisible);

  const formatDate = (isoString: string) => {
    const date = new Date(isoString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const getSentimentIcon = (sentiment: string) => {
    switch (sentiment) {
      case 'positive':
        return <span data-testid="thumbs-up-icon">👍</span>;
      case 'negative':
        return <span data-testid="thumbs-down-icon">👎</span>;
      default:
        return <span data-testid="minus-icon">−</span>;
    }
  };

  const getSentimentColor = (sentiment: string) => {
    switch (sentiment) {
      case 'positive':
        return 'green';
      case 'negative':
        return 'red';
      case 'neutral':
        return 'gray';
      case 'mixed':
        return 'yellow';
      default:
        return 'gray';
    }
  };

  const toggleExpand = (id: string) => {
    setExpandedId(expandedId === id ? null : id);
  };

  if (mentions.length === 0) {
    return (
      <div data-testid="empty-mentions" className={className}>
        <span data-testid="message-icon">💬</span>
        <p>No mentions yet. Run a query to start tracking.</p>
      </div>
    );
  }

  return (
    <div data-testid="mentions-list" className={className}>
      <div data-testid="mentions-header">
        <h3>Recent Mentions</h3>
        <span data-testid="mentions-count">{mentions.length}</span>
      </div>

      <ul data-testid="mentions-items">
        {visibleMentions.map((mention) => (
          <li
            key={mention.id}
            data-testid={`mention-item-${mention.id}`}
            data-expanded={expandedId === mention.id}
          >
            <button
              data-testid={`expand-btn-${mention.id}`}
              onClick={() => toggleExpand(mention.id)}
            >
              {expandedId === mention.id ? (
                <span data-testid="chevron-down-icon">⌄</span>
              ) : (
                <span data-testid="chevron-icon">›</span>
              )}
            </button>

            <div data-testid={`context-${mention.id}`}>
              {expandedId === mention.id
                ? mention.brandContext
                : mention.brandContext.length > 100
                  ? `${mention.brandContext.substring(0, 100)}...`
                  : mention.brandContext
              }
            </div>

            <div data-testid={`meta-${mention.id}`}>
              <span
                data-testid={`platform-badge-${mention.id}`}
                data-platform={mention.platform}
              >
                {mention.platform}
              </span>

              <span
                data-testid={`sentiment-badge-${mention.id}`}
                data-sentiment={mention.sentiment}
                data-color={getSentimentColor(mention.sentiment)}
              >
                {getSentimentIcon(mention.sentiment)}
                {mention.sentiment}
              </span>

              <span data-testid={`date-${mention.id}`}>
                {formatDate(mention.queriedAt)}
              </span>
            </div>

            {onMentionClick && (
              <button
                data-testid={`view-btn-${mention.id}`}
                onClick={() => onMentionClick(mention)}
              >
                <span data-testid="external-link-icon">↗</span>
                View
              </button>
            )}
          </li>
        ))}
      </ul>

      {viewAllHref && mentions.length > maxVisible && (
        <a data-testid="view-all-link" href={viewAllHref}>
          View All ({mentions.length})
          <span data-testid="chevron-icon">›</span>
        </a>
      )}
    </div>
  );
};

// Mock data
const mockMentions: Mention[] = [
  {
    id: 'm1',
    brandContext: 'For comprehensive SEO analysis, SEOIndicator offers a robust set of features including keyword tracking and competitor analysis. The platform provides detailed insights into your website performance.',
    platform: 'chatgpt',
    sentiment: 'positive',
    queriedAt: '2026-03-07T14:30:00Z',
  },
  {
    id: 'm2',
    brandContext: 'When comparing SEO tools, Ahrefs and Semrush are often mentioned alongside SEOIndicator for their backlink databases and keyword research capabilities.',
    platform: 'perplexity',
    sentiment: 'neutral',
    queriedAt: '2026-03-07T12:15:00Z',
  },
  {
    id: 'm3',
    brandContext: 'SEOIndicator is a newer player in the SEO tool space, offering competitive pricing for smaller businesses looking for affordable SEO solutions.',
    platform: 'chatgpt',
    sentiment: 'positive',
    queriedAt: '2026-03-06T18:45:00Z',
  },
  {
    id: 'm4',
    brandContext: 'Some users have reported that SEOIndicator lacks certain advanced features compared to more established competitors like Ahrefs.',
    platform: 'perplexity',
    sentiment: 'negative',
    queriedAt: '2026-03-06T10:00:00Z',
  },
  {
    id: 'm5',
    brandContext: 'The sentiment around SEOIndicator is mixed, with praise for pricing but criticism for feature depth in enterprise scenarios.',
    platform: 'chatgpt',
    sentiment: 'mixed',
    queriedAt: '2026-03-05T16:20:00Z',
  },
];

// ============================================
// TEST CATEGORY: RENDERING (REND)
// ============================================
describe('REND: Rendering', () => {
  test('REND-001: List renders with mentions', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByTestId('mentions-list')).toBeInTheDocument();
  });

  test('REND-002: All mentions displayed up to maxVisible', () => {
    render(<RecentMentionsList mentions={mockMentions} maxVisible={3} />);

    expect(screen.getByTestId('mention-item-m1')).toBeInTheDocument();
    expect(screen.getByTestId('mention-item-m2')).toBeInTheDocument();
    expect(screen.getByTestId('mention-item-m3')).toBeInTheDocument();
    expect(screen.queryByTestId('mention-item-m4')).not.toBeInTheDocument();
  });

  test('REND-003: Mentions count displayed', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByTestId('mentions-count')).toHaveTextContent('5');
  });

  test('REND-004: Custom className applied', () => {
    render(
      <RecentMentionsList mentions={mockMentions} className="custom-class" />
    );

    expect(screen.getByTestId('mentions-list')).toHaveClass('custom-class');
  });
});

// ============================================
// TEST CATEGORY: BRAND CONTEXT (BC)
// ============================================
describe('BC: Brand Context Display', () => {
  test('BC-001: Context truncated by default', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const context = screen.getByTestId('context-m1');
    expect(context.textContent?.length).toBeLessThanOrEqual(103); // 100 + "..."
  });

  test('BC-002: Context expands on click', async () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    // Initially truncated
    const context = screen.getByTestId('context-m1');
    const truncatedText = context.textContent;
    expect(truncatedText?.endsWith('...')).toBe(true);

    // Click expand button
    await userEvent.click(screen.getByTestId('expand-btn-m1'));

    // Now expanded (no trailing ...)
    const expandedContext = screen.getByTestId('context-m1');
    expect(expandedContext.textContent?.endsWith('...')).toBe(false);
    expect(expandedContext.textContent?.length).toBeGreaterThan(100);
  });

  test('BC-003: Expand toggle works', async () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    // Expand
    await userEvent.click(screen.getByTestId('expand-btn-m1'));
    expect(screen.getByTestId('mention-item-m1')).toHaveAttribute('data-expanded', 'true');

    // Collapse
    await userEvent.click(screen.getByTestId('expand-btn-m1'));
    expect(screen.getByTestId('mention-item-m1')).toHaveAttribute('data-expanded', 'false');
  });

  test('BC-004: Only one mention expanded at a time', async () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    // Expand first mention
    await userEvent.click(screen.getByTestId('expand-btn-m1'));
    expect(screen.getByTestId('mention-item-m1')).toHaveAttribute('data-expanded', 'true');

    // Expand second mention
    await userEvent.click(screen.getByTestId('expand-btn-m2'));
    expect(screen.getByTestId('mention-item-m1')).toHaveAttribute('data-expanded', 'false');
    expect(screen.getByTestId('mention-item-m2')).toHaveAttribute('data-expanded', 'true');
  });

  test('BC-005: Short context not truncated', () => {
    const shortMentions: Mention[] = [
      {
        id: 'short',
        brandContext: 'Short context',
        platform: 'chatgpt',
        sentiment: 'positive',
        queriedAt: '2026-03-07T14:30:00Z',
      },
    ];

    render(<RecentMentionsList mentions={shortMentions} />);

    const context = screen.getByTestId('context-short');
    expect(context.textContent).toBe('Short context');
    expect(context.textContent?.endsWith('...')).toBe(false);
  });
});

// ============================================
// TEST CATEGORY: PLATFORM BADGES (PB)
// ============================================
describe('PB: Platform Badges', () => {
  test('PB-001: ChatGPT badge rendered', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('platform-badge-m1');
    expect(badge).toHaveAttribute('data-platform', 'chatgpt');
    expect(badge).toHaveTextContent('chatgpt');
  });

  test('PB-002: Perplexity badge rendered', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('platform-badge-m2');
    expect(badge).toHaveAttribute('data-platform', 'perplexity');
    expect(badge).toHaveTextContent('perplexity');
  });

  test('PB-003: All platform types supported', () => {
    const allPlatforms: Mention[] = [
      {
        id: 'chatgpt',
        brandContext: 'ChatGPT mention',
        platform: 'chatgpt',
        sentiment: 'neutral',
        queriedAt: '2026-03-07T14:30:00Z',
      },
      {
        id: 'perplexity',
        brandContext: 'Perplexity mention',
        platform: 'perplexity',
        sentiment: 'neutral',
        queriedAt: '2026-03-07T14:30:00Z',
      },
      {
        id: 'claude',
        brandContext: 'Claude mention',
        platform: 'claude',
        sentiment: 'neutral',
        queriedAt: '2026-03-07T14:30:00Z',
      },
    ];

    render(<RecentMentionsList mentions={allPlatforms} />);

    expect(screen.getByTestId('platform-badge-chatgpt')).toHaveAttribute('data-platform', 'chatgpt');
    expect(screen.getByTestId('platform-badge-perplexity')).toHaveAttribute('data-platform', 'perplexity');
    expect(screen.getByTestId('platform-badge-claude')).toHaveAttribute('data-platform', 'claude');
  });
});

// ============================================
// TEST CATEGORY: SENTIMENT BADGES (SB)
// ============================================
describe('SB: Sentiment Badges', () => {
  test('SB-001: Positive sentiment shows green', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('sentiment-badge-m1');
    expect(badge).toHaveAttribute('data-sentiment', 'positive');
    expect(badge).toHaveAttribute('data-color', 'green');
    // Check that thumbs-up-icon exists within this specific badge
    expect(within(badge).getByTestId('thumbs-up-icon')).toBeInTheDocument();
  });

  test('SB-002: Negative sentiment shows red', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('sentiment-badge-m4');
    expect(badge).toHaveAttribute('data-sentiment', 'negative');
    expect(badge).toHaveAttribute('data-color', 'red');
    expect(screen.getByTestId('thumbs-down-icon')).toBeInTheDocument();
  });

  test('SB-003: Neutral sentiment shows gray', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('sentiment-badge-m2');
    expect(badge).toHaveAttribute('data-sentiment', 'neutral');
    expect(badge).toHaveAttribute('data-color', 'gray');
  });

  test('SB-004: Mixed sentiment shows yellow', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('sentiment-badge-m5');
    expect(badge).toHaveAttribute('data-sentiment', 'mixed');
    expect(badge).toHaveAttribute('data-color', 'yellow');
  });
});

// ============================================
// TEST CATEGORY: DATE DISPLAY (DD)
// ============================================
describe('DD: Date Display', () => {
  test('DD-001: Date formatted correctly', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const date = screen.getByTestId('date-m1');
    expect(date).toHaveTextContent('Mar 7, 2026');
  });

  test('DD-002: Different dates display correctly', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByTestId('date-m1')).toHaveTextContent('Mar 7, 2026');
    expect(screen.getByTestId('date-m4')).toHaveTextContent('Mar 6, 2026');
    expect(screen.getByTestId('date-m5')).toHaveTextContent('Mar 5, 2026');
  });
});

// ============================================
// TEST CATEGORY: VIEW ALL LINK (VA)
// ============================================
describe('VA: View All Link', () => {
  test('VA-001: Link renders when provided and mentions exceed maxVisible', () => {
    render(
      <RecentMentionsList
        mentions={mockMentions}
        viewAllHref="/ai-visibility/123/queries"
        maxVisible={3}
      />
    );

    expect(screen.getByTestId('view-all-link')).toBeInTheDocument();
    expect(screen.getByTestId('view-all-link')).toHaveAttribute(
      'href',
      '/ai-visibility/123/queries'
    );
  });

  test('VA-002: Link not rendered when not provided', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.queryByTestId('view-all-link')).not.toBeInTheDocument();
  });

  test('VA-003: Link shows total count', () => {
    render(
      <RecentMentionsList
        mentions={mockMentions}
        viewAllHref="/ai-visibility/123/queries"
        maxVisible={3}
      />
    );

    expect(screen.getByTestId('view-all-link')).toHaveTextContent('View All (5)');
  });
});

// ============================================
// TEST CATEGORY: EMPTY STATE (ES)
// ============================================
describe('ES: Empty State', () => {
  test('ES-001: Empty state renders when no mentions', () => {
    render(<RecentMentionsList mentions={[]} />);

    expect(screen.getByTestId('empty-mentions')).toBeInTheDocument();
    expect(screen.getByTestId('message-icon')).toBeInTheDocument();
  });

  test('ES-002: Empty state has friendly message', () => {
    render(<RecentMentionsList mentions={[]} />);

    expect(screen.getByText(/No mentions yet/)).toBeInTheDocument();
    expect(screen.getByText(/Run a query to start tracking/)).toBeInTheDocument();
  });

  test('ES-003: Empty state applies custom className', () => {
    render(<RecentMentionsList mentions={[]} className="empty-class" />);

    expect(screen.getByTestId('empty-mentions')).toHaveClass('empty-class');
  });
});

// ============================================
// TEST CATEGORY: CLICK HANDLERS (CH)
// ============================================
describe('CH: Click Handlers', () => {
  test('CH-001: onMentionClick called when view button clicked', async () => {
    const handleClick = jest.fn();

    render(
      <RecentMentionsList mentions={mockMentions} onMentionClick={handleClick} />
    );

    await userEvent.click(screen.getByTestId('view-btn-m1'));

    expect(handleClick).toHaveBeenCalledWith(mockMentions[0]);
  });

  test('CH-002: View button not rendered without handler', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.queryByTestId('view-btn-m1')).not.toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: EDGE CASES (EC)
// ============================================
describe('EC: Edge Cases', () => {
  test('EC-001: Very long context handled', () => {
    const longMentions: Mention[] = [
      {
        id: 'long',
        brandContext: 'A'.repeat(1000),
        platform: 'chatgpt',
        sentiment: 'neutral',
        queriedAt: '2026-03-07T14:30:00Z',
      },
    ];

    render(<RecentMentionsList mentions={longMentions} />);

    const context = screen.getByTestId('context-long');
    expect(context.textContent?.length).toBeLessThanOrEqual(103);
  });

  test('EC-002: Unicode in context displayed', () => {
    const unicodeMentions: Mention[] = [
      {
        id: 'unicode',
        brandContext: 'SEO分析工具 🚀 最佳选择 for international users',
        platform: 'chatgpt',
        sentiment: 'positive',
        queriedAt: '2026-03-07T14:30:00Z',
      },
    ];

    render(<RecentMentionsList mentions={unicodeMentions} />);

    expect(screen.getByTestId('context-unicode')).toHaveTextContent(/SEO分析工具/);
  });

  test('EC-003: Many mentions with pagination', () => {
    const manyMentions: Mention[] = Array(50)
      .fill(null)
      .map((_, i) => ({
        id: `m${i}`,
        brandContext: `Mention ${i}`,
        platform: 'chatgpt' as const,
        sentiment: 'neutral' as const,
        queriedAt: '2026-03-07T14:30:00Z',
      }));

    render(<RecentMentionsList mentions={manyMentions} maxVisible={10} />);

    expect(screen.getByTestId('mentions-count')).toHaveTextContent('50');
    // Only 10 visible
    const items = screen.getAllByTestId(/^mention-item-/);
    expect(items.length).toBe(10);
  });

  test('EC-004: Special characters in context', () => {
    const specialMentions: Mention[] = [
      {
        id: 'special',
        brandContext: 'Test <script>alert("xss")</script> & "quotes" \'apostrophes\'',
        platform: 'chatgpt',
        sentiment: 'neutral',
        queriedAt: '2026-03-07T14:30:00Z',
      },
    ];

    render(<RecentMentionsList mentions={specialMentions} />);

    // Should render without executing script
    expect(screen.getByTestId('context-special')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================
describe('A11Y: Accessibility', () => {
  test('A11Y-001: List uses semantic markup', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByRole('list')).toBeInTheDocument();
  });

  test('A11Y-002: Expand buttons are accessible', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const expandBtn = screen.getByTestId('expand-btn-m1');
    expect(expandBtn.tagName.toLowerCase()).toBe('button');
  });

  test('A11Y-003: View All link is accessible', () => {
    render(
      <RecentMentionsList
        mentions={mockMentions}
        viewAllHref="/ai-visibility/123/queries"
        maxVisible={3}
      />
    );

    const link = screen.getByTestId('view-all-link');
    expect(link.tagName.toLowerCase()).toBe('a');
    expect(link).toHaveAttribute('href');
  });

  test('A11Y-004: Sentiment badges have text content', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    const badge = screen.getByTestId('sentiment-badge-m1');
    expect(badge).toHaveTextContent('positive');
  });
});

// ============================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================
describe('AC: Acceptance Criteria', () => {
  test('AC-001: Shows last 10 mentions with all required fields', () => {
    render(<RecentMentionsList mentions={mockMentions} maxVisible={10} />);

    // Brand context
    expect(screen.getByTestId('context-m1')).toBeInTheDocument();

    // Platform badge
    expect(screen.getByTestId('platform-badge-m1')).toBeInTheDocument();

    // Sentiment badge
    expect(screen.getByTestId('sentiment-badge-m1')).toBeInTheDocument();

    // Date/time
    expect(screen.getByTestId('date-m1')).toBeInTheDocument();
  });

  test('AC-002: Brand context truncated, expandable', async () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    // Initially truncated
    const context = screen.getByTestId('context-m1');
    expect(context.textContent?.endsWith('...')).toBe(true);

    // Expandable
    await userEvent.click(screen.getByTestId('expand-btn-m1'));
    expect(context.textContent?.endsWith('...')).toBe(false);
  });

  test('AC-003: Platform badge displayed', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByTestId('platform-badge-m1')).toHaveTextContent('chatgpt');
    expect(screen.getByTestId('platform-badge-m2')).toHaveTextContent('perplexity');
  });

  test('AC-004: Sentiment badge color-coded', () => {
    render(<RecentMentionsList mentions={mockMentions} />);

    expect(screen.getByTestId('sentiment-badge-m1')).toHaveAttribute('data-color', 'green');
    expect(screen.getByTestId('sentiment-badge-m4')).toHaveAttribute('data-color', 'red');
  });

  test('AC-005: View All link to query history', () => {
    render(
      <RecentMentionsList
        mentions={mockMentions}
        viewAllHref="/ai-visibility/123/queries"
        maxVisible={3}
      />
    );

    expect(screen.getByTestId('view-all-link')).toHaveAttribute(
      'href',
      '/ai-visibility/123/queries'
    );
  });
});
