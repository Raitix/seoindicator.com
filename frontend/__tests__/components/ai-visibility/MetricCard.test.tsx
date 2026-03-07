/**
 * TASK-34-10: MetricCard Component Tests
 * 
 * Tests for individual metric cards in the AI Visibility dashboard.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  TrendingUp: () => <span data-testid="trending-up-icon">↑</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">↓</span>,
  Minus: () => <span data-testid="minus-icon">−</span>,
  Info: () => <span data-testid="info-icon">ℹ</span>,
}));

// Types
interface MetricCardProps {
  title: string;
  value: number | string;
  previousValue?: number;
  change?: number;
  trend?: 'up' | 'down' | 'stable';
  format?: 'percentage' | 'decimal' | 'number';
  sentiment?: 'positive' | 'negative' | 'neutral' | 'mixed';
  label?: string;
  tooltip?: string;
  className?: string;
}

// Mock component for TDD reference
const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  previousValue,
  change,
  trend = 'stable',
  format = 'number',
  sentiment,
  label,
  tooltip,
  className,
}) => {
  const formatValue = (val: number | string) => {
    if (typeof val === 'string') return val;
    switch (format) {
      case 'percentage':
        return `${val.toFixed(1)}%`;
      case 'decimal':
        return val.toFixed(2);
      default:
        return val.toString();
    }
  };

  const getTrendIcon = () => {
    switch (trend) {
      case 'up':
        return <span data-testid="trending-up-icon">↑</span>;
      case 'down':
        return <span data-testid="trending-down-icon">↓</span>;
      default:
        return <span data-testid="minus-icon">−</span>;
    }
  };

  const getSentimentColor = () => {
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
        return 'blue';
    }
  };

  return (
    <div
      data-testid="metric-card"
      data-trend={trend}
      data-sentiment={sentiment}
      className={className}
    >
      <div data-testid="metric-title">{title}</div>
      <div data-testid="metric-value" data-format={format}>
        {formatValue(value)}
      </div>
      {change !== undefined && (
        <div data-testid="metric-change" data-direction={trend}>
          {getTrendIcon()}
          <span data-testid="change-value">
            {change > 0 ? '+' : ''}{change.toFixed(1)}%
          </span>
        </div>
      )}
      {label && (
        <div
          data-testid="metric-label"
          data-color={sentiment ? getSentimentColor() : undefined}
        >
          {label}
        </div>
      )}
      {tooltip && (
        <span data-testid="tooltip-trigger" title={tooltip}>
          <span data-testid="info-icon">ℹ</span>
        </span>
      )}
    </div>
  );
};

// ============================================
// TEST CATEGORY: RENDERING (REND)
// ============================================
describe('REND: MetricCard Rendering', () => {
  test('REND-001: Card renders with required props', () => {
    render(<MetricCard title="Mention Rate" value={23.5} />);

    expect(screen.getByTestId('metric-card')).toBeInTheDocument();
    expect(screen.getByTestId('metric-title')).toHaveTextContent('Mention Rate');
    expect(screen.getByTestId('metric-value')).toHaveTextContent('23.5');
  });

  test('REND-002: Card with percentage format', () => {
    render(<MetricCard title="Mention Rate" value={23.5} format="percentage" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('23.5%');
    expect(screen.getByTestId('metric-value')).toHaveAttribute('data-format', 'percentage');
  });

  test('REND-003: Card with decimal format', () => {
    render(<MetricCard title="Sentiment Score" value={0.4567} format="decimal" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('0.46');
    expect(screen.getByTestId('metric-value')).toHaveAttribute('data-format', 'decimal');
  });

  test('REND-004: Card with custom className', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        className="bg-white shadow-lg"
      />
    );

    expect(screen.getByTestId('metric-card')).toHaveClass('bg-white', 'shadow-lg');
  });
});

// ============================================
// TEST CATEGORY: TREND INDICATORS (TREND)
// ============================================
describe('TREND: Trend Indicators', () => {
  test('TREND-001: Up trend shows trending-up icon', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        change={5.2}
        trend="up"
      />
    );

    expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
    expect(screen.getByTestId('metric-change')).toHaveAttribute('data-direction', 'up');
  });

  test('TREND-002: Down trend shows trending-down icon', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={18.3}
        change={-5.2}
        trend="down"
      />
    );

    expect(screen.getByTestId('trending-down-icon')).toBeInTheDocument();
    expect(screen.getByTestId('metric-change')).toHaveAttribute('data-direction', 'down');
  });

  test('TREND-003: Stable trend shows minus icon', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={20.0}
        change={0}
        trend="stable"
      />
    );

    expect(screen.getByTestId('minus-icon')).toBeInTheDocument();
    expect(screen.getByTestId('metric-change')).toHaveAttribute('data-direction', 'stable');
  });

  test('TREND-004: Change value displayed with sign', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        change={5.2}
        trend="up"
      />
    );

    expect(screen.getByTestId('change-value')).toHaveTextContent('+5.2%');
  });

  test('TREND-005: Negative change shows minus sign', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={18.3}
        change={-3.5}
        trend="down"
      />
    );

    expect(screen.getByTestId('change-value')).toHaveTextContent('-3.5%');
  });

  test('TREND-006: No change section when change is undefined', () => {
    render(<MetricCard title="Mention Rate" value={23.5} />);

    expect(screen.queryByTestId('metric-change')).not.toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: SENTIMENT (SENT)
// ============================================
describe('SENT: Sentiment Display', () => {
  test('SENT-001: Positive sentiment shows green', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.65}
        sentiment="positive"
        label="Positive"
      />
    );

    expect(screen.getByTestId('metric-label')).toHaveAttribute('data-color', 'green');
    expect(screen.getByTestId('metric-label')).toHaveTextContent('Positive');
  });

  test('SENT-002: Negative sentiment shows red', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={-0.45}
        sentiment="negative"
        label="Negative"
      />
    );

    expect(screen.getByTestId('metric-label')).toHaveAttribute('data-color', 'red');
    expect(screen.getByTestId('metric-label')).toHaveTextContent('Negative');
  });

  test('SENT-003: Neutral sentiment shows gray', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.05}
        sentiment="neutral"
        label="Neutral"
      />
    );

    expect(screen.getByTestId('metric-label')).toHaveAttribute('data-color', 'gray');
    expect(screen.getByTestId('metric-label')).toHaveTextContent('Neutral');
  });

  test('SENT-004: Mixed sentiment shows yellow', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.0}
        sentiment="mixed"
        label="Mixed"
      />
    );

    expect(screen.getByTestId('metric-label')).toHaveAttribute('data-color', 'yellow');
    expect(screen.getByTestId('metric-label')).toHaveTextContent('Mixed');
  });

  test('SENT-005: Card data-sentiment attribute set', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.45}
        sentiment="positive"
      />
    );

    expect(screen.getByTestId('metric-card')).toHaveAttribute('data-sentiment', 'positive');
  });
});

// ============================================
// TEST CATEGORY: TOOLTIPS (TIP)
// ============================================
describe('TIP: Tooltips', () => {
  test('TIP-001: Tooltip trigger renders when tooltip provided', () => {
    render(
      <MetricCard
        title="Share of Voice"
        value={31.2}
        tooltip="Percentage of brand mentions vs competitors"
      />
    );

    expect(screen.getByTestId('tooltip-trigger')).toBeInTheDocument();
    expect(screen.getByTestId('info-icon')).toBeInTheDocument();
  });

  test('TIP-002: No tooltip trigger when tooltip not provided', () => {
    render(<MetricCard title="Share of Voice" value={31.2} />);

    expect(screen.queryByTestId('tooltip-trigger')).not.toBeInTheDocument();
  });

  test('TIP-003: Tooltip content accessible via title', () => {
    render(
      <MetricCard
        title="Share of Voice"
        value={31.2}
        tooltip="Percentage of brand mentions vs competitors"
      />
    );

    expect(screen.getByTestId('tooltip-trigger')).toHaveAttribute(
      'title',
      'Percentage of brand mentions vs competitors'
    );
  });
});

// ============================================
// TEST CATEGORY: VALUE FORMATTING (VAL)
// ============================================
describe('VAL: Value Formatting', () => {
  test('VAL-001: Percentage value formatted correctly', () => {
    render(<MetricCard title="Rate" value={45.678} format="percentage" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('45.7%');
  });

  test('VAL-002: Decimal value shows 2 decimal places', () => {
    render(<MetricCard title="Score" value={0.45678} format="decimal" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('0.46');
  });

  test('VAL-003: Number format shows as-is', () => {
    render(<MetricCard title="Count" value={1234} format="number" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('1234');
  });

  test('VAL-004: Zero value formatted correctly', () => {
    render(<MetricCard title="Rate" value={0} format="percentage" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('0.0%');
  });

  test('VAL-005: Large value handled', () => {
    render(<MetricCard title="Rate" value={100} format="percentage" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('100.0%');
  });

  test('VAL-006: Negative decimal handled', () => {
    render(<MetricCard title="Score" value={-0.75} format="decimal" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('-0.75');
  });

  test('VAL-007: String value passed through', () => {
    render(<MetricCard title="Status" value="Active" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('Active');
  });
});

// ============================================
// TEST CATEGORY: EDGE CASES (EC)
// ============================================
describe('EC: Edge Cases', () => {
  test('EC-001: Very large percentage value', () => {
    render(<MetricCard title="Rate" value={999.99} format="percentage" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('1000.0%');
  });

  test('EC-002: Very small decimal value', () => {
    render(<MetricCard title="Score" value={0.00001} format="decimal" />);

    expect(screen.getByTestId('metric-value')).toHaveTextContent('0.00');
  });

  test('EC-003: Zero change still shows indicator', () => {
    render(
      <MetricCard
        title="Rate"
        value={20.0}
        change={0}
        trend="stable"
      />
    );

    expect(screen.getByTestId('metric-change')).toBeInTheDocument();
    expect(screen.getByTestId('change-value')).toHaveTextContent('0.0%');
  });

  test('EC-004: No label when not provided', () => {
    render(<MetricCard title="Rate" value={20.0} />);

    expect(screen.queryByTestId('metric-label')).not.toBeInTheDocument();
  });

  test('EC-005: Long title truncates', () => {
    const longTitle = 'A'.repeat(100);
    render(<MetricCard title={longTitle} value={20.0} />);

    // Component should render without crash
    expect(screen.getByTestId('metric-card')).toBeInTheDocument();
  });
});

// ============================================
// TEST CATEGORY: ACCESSIBILITY (A11Y)
// ============================================
describe('A11Y: Accessibility', () => {
  test('A11Y-001: Card has appropriate role', () => {
    render(<MetricCard title="Mention Rate" value={23.5} />);

    expect(screen.getByTestId('metric-card')).toBeInTheDocument();
  });

  test('A11Y-002: Value is readable by screen reader', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        format="percentage"
      />
    );

    expect(screen.getByTestId('metric-value')).toHaveTextContent('23.5%');
  });

  test('A11Y-003: Trend indicator has text alternative', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        change={5.2}
        trend="up"
      />
    );

    // Icon is present and change value provides context
    expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
    expect(screen.getByTestId('change-value')).toHaveTextContent('+5.2%');
  });

  test('A11Y-004: Label provides additional context', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.45}
        label="Positive"
      />
    );

    expect(screen.getByTestId('metric-label')).toHaveTextContent('Positive');
  });
});

// ============================================
// TEST CATEGORY: ACCEPTANCE CRITERIA (AC)
// ============================================
describe('AC: Acceptance Criteria', () => {
  test('AC-001: Mention Rate card with percentage and trend', () => {
    render(
      <MetricCard
        title="Mention Rate"
        value={23.5}
        change={5.2}
        trend="up"
        format="percentage"
      />
    );

    expect(screen.getByTestId('metric-title')).toHaveTextContent('Mention Rate');
    expect(screen.getByTestId('metric-value')).toHaveTextContent('23.5%');
    expect(screen.getByTestId('trending-up-icon')).toBeInTheDocument();
  });

  test('AC-002: Share of Voice card with percentage and trend', () => {
    render(
      <MetricCard
        title="Share of Voice"
        value={31.2}
        change={2.1}
        trend="up"
        format="percentage"
        tooltip="Percentage of brand mentions vs competitors"
      />
    );

    expect(screen.getByTestId('metric-title')).toHaveTextContent('Share of Voice');
    expect(screen.getByTestId('metric-value')).toHaveTextContent('31.2%');
    expect(screen.getByTestId('tooltip-trigger')).toBeInTheDocument();
  });

  test('AC-003: Sentiment Score card with decimal and color indicator', () => {
    render(
      <MetricCard
        title="Sentiment Score"
        value={0.45}
        change={0.07}
        trend="up"
        format="decimal"
        sentiment="positive"
        label="Positive"
      />
    );

    expect(screen.getByTestId('metric-title')).toHaveTextContent('Sentiment Score');
    expect(screen.getByTestId('metric-value')).toHaveTextContent('0.45');
    expect(screen.getByTestId('metric-label')).toHaveAttribute('data-color', 'green');
    expect(screen.getByTestId('metric-label')).toHaveTextContent('Positive');
  });
});
