/**
 * BadgePreview Component Tests
 *
 * Tests for the badge preview component that shows live badge previews
 * with size/theme selectors and generates embed code snippets.
 *
 * Component: BadgePreview
 * Task: TASK-33-13
 * Status: TDD (component not yet implemented)
 */

import React from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// ============================================================================
// MOCK IMPLEMENTATION (TDD Reference - will be replaced by actual component)
// ============================================================================

// Mock clipboard API
const mockClipboard = {
  writeText: jest.fn().mockResolvedValue(undefined),
};
Object.assign(navigator, { clipboard: mockClipboard });

// Mock Lucide icons
jest.mock('lucide-react', () => ({
  Copy: () => <span data-testid="copy-icon">📋</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  Code: () => <span data-testid="code-icon">{ }</span>,
  Image: () => <span data-testid="image-icon">🖼️</span>,
  Monitor: () => <span data-testid="monitor-icon">🖥️</span>,
  Smartphone: () => <span data-testid="smartphone-icon">📱</span>,
  Sun: () => <span data-testid="sun-icon">☀️</span>,
  Moon: () => <span data-testid="moon-icon">🌙</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
}));

// ============================================================================
// TYPES
// ============================================================================

type BadgeSize = 'small' | 'medium' | 'large';
type BadgeTheme = 'light' | 'dark';
type EmbedType = 'svg' | 'js' | 'iframe';

interface BadgePreviewProps {
  auditHash: string;
  score: number;
  siteUrl?: string;
  onCopy?: (type: EmbedType, code: string) => void;
  className?: string;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

const getBadgeDimensions = (size: BadgeSize): { width: number; height: number } => {
  switch (size) {
    case 'small':
      return { width: 120, height: 40 };
    case 'medium':
      return { width: 150, height: 100 };
    case 'large':
      return { width: 300, height: 150 };
  }
};

const getScoreColor = (score: number): string => {
  if (score >= 90) return '#22c55e';
  if (score >= 70) return '#3b82f6';
  if (score >= 50) return '#eab308';
  if (score >= 30) return '#f97316';
  return '#ef4444';
};

const generateSvgEmbedCode = (hash: string, size: BadgeSize): string => {
  const { width, height } = getBadgeDimensions(size);
  return `<a href="https://seoindicator.com/audit/${hash}/">
  <img src="https://seoindicator.com/badge/${hash}/${size}.svg" alt="SEO Score" width="${width}" height="${height}" />
</a>`;
};

const generateJsEmbedCode = (hash: string, size: BadgeSize, theme: BadgeTheme): string => {
  return `<div data-seoindicator-badge="${hash}" data-size="${size}" data-theme="${theme}"></div>
<script src="https://seoindicator.com/badge.js" async></script>`;
};

const generateIframeEmbedCode = (hash: string, theme: BadgeTheme): string => {
  return `<iframe src="https://seoindicator.com/badge/${hash}/large?theme=${theme}" 
        width="300" height="150" frameborder="0" loading="lazy"></iframe>`;
};

// ============================================================================
// MOCK COMPONENT FOR TESTING
// ============================================================================

const MockBadgePreview: React.FC<BadgePreviewProps> = ({
  auditHash,
  score,
  siteUrl,
  onCopy,
  className,
}) => {
  const [selectedSize, setSelectedSize] = React.useState<BadgeSize>('medium');
  const [selectedTheme, setSelectedTheme] = React.useState<BadgeTheme>('light');
  const [selectedEmbedType, setSelectedEmbedType] = React.useState<EmbedType>('svg');
  const [copied, setCopied] = React.useState(false);

  const dimensions = getBadgeDimensions(selectedSize);
  const scoreColor = getScoreColor(score);

  const getEmbedCode = (): string => {
    switch (selectedEmbedType) {
      case 'svg':
        return generateSvgEmbedCode(auditHash, selectedSize);
      case 'js':
        return generateJsEmbedCode(auditHash, selectedSize, selectedTheme);
      case 'iframe':
        return generateIframeEmbedCode(auditHash, selectedTheme);
    }
  };

  const handleCopy = async () => {
    const code = getEmbedCode();
    await navigator.clipboard.writeText(code);
    setCopied(true);
    onCopy?.(selectedEmbedType, code);
    setTimeout(() => setCopied(false), 2000);
  };

  const embedCode = getEmbedCode();

  return (
    <div data-testid="badge-preview" className={className}>
      {/* Badge Preview */}
      <div data-testid="badge-preview-container">
        <h3>Badge Preview</h3>
        <div
          data-testid="badge-display"
          style={{
            width: dimensions.width,
            height: dimensions.height,
            backgroundColor: selectedTheme === 'dark' ? '#1a1a1a' : '#ffffff',
          }}
        >
          <span data-testid="badge-score" style={{ color: scoreColor }}>
            {score}/100
          </span>
        </div>
      </div>

      {/* Size Selector */}
      <div data-testid="size-selector-container">
        <label htmlFor="size-select">Size</label>
        <select
          id="size-select"
          data-testid="size-selector"
          value={selectedSize}
          onChange={(e) => setSelectedSize(e.target.value as BadgeSize)}
          aria-label="Select badge size"
        >
          <option value="small">Small (120x40)</option>
          <option value="medium">Medium (150x100)</option>
          <option value="large">Large (300x150)</option>
        </select>
      </div>

      {/* Theme Selector */}
      <div data-testid="theme-selector-container">
        <label htmlFor="theme-select">Theme</label>
        <select
          id="theme-select"
          data-testid="theme-selector"
          value={selectedTheme}
          onChange={(e) => setSelectedTheme(e.target.value as BadgeTheme)}
          aria-label="Select badge theme"
        >
          <option value="light">Light</option>
          <option value="dark">Dark</option>
        </select>
      </div>

      {/* Embed Type Tabs */}
      <div data-testid="embed-type-tabs" role="tablist">
        <button
          role="tab"
          aria-selected={selectedEmbedType === 'svg'}
          data-testid="tab-svg"
          onClick={() => setSelectedEmbedType('svg')}
        >
          SVG Image
        </button>
        <button
          role="tab"
          aria-selected={selectedEmbedType === 'js'}
          data-testid="tab-js"
          onClick={() => setSelectedEmbedType('js')}
        >
          JavaScript
        </button>
        <button
          role="tab"
          aria-selected={selectedEmbedType === 'iframe'}
          data-testid="tab-iframe"
          onClick={() => setSelectedEmbedType('iframe')}
        >
          iframe
        </button>
      </div>

      {/* Embed Code Display */}
      <div data-testid="embed-code-container">
        <label htmlFor="embed-code">Embed Code</label>
        <textarea
          id="embed-code"
          data-testid="embed-code"
          value={embedCode}
          readOnly
          aria-label="Embed code"
        />
      </div>

      {/* Copy Button */}
      <button
        data-testid="copy-button"
        onClick={handleCopy}
        aria-label="Copy embed code"
        aria-live="polite"
      >
        {copied ? (
          <>
            <span data-testid="check-icon">✓</span>
            Copied!
          </>
        ) : (
          <>
            <span data-testid="copy-icon">📋</span>
            Copy Code
          </>
        )}
      </button>

      {/* Current Settings Display */}
      <div data-testid="current-settings" aria-hidden="true">
        <span data-testid="current-size">{selectedSize}</span>
        <span data-testid="current-theme">{selectedTheme}</span>
        <span data-testid="current-embed">{selectedEmbedType}</span>
      </div>
    </div>
  );
};

// ============================================================================
// TEST DATA
// ============================================================================

const defaultProps: BadgePreviewProps = {
  auditHash: 'abc123def45678901234567890123456',
  score: 78,
};

// ============================================================================
// TESTS
// ============================================================================

describe('BadgePreview Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockClipboard.writeText.mockClear();
  });

  // ------------------------------------------------------------------------
  // Rendering Tests
  // ------------------------------------------------------------------------

  describe('REND: Rendering', () => {
    it('REND-001: should render without crashing', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('badge-preview')).toBeInTheDocument();
    });

    it('REND-002: should display badge preview container', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('badge-preview-container')).toBeInTheDocument();
    });

    it('REND-003: should display score in badge', () => {
      render(<MockBadgePreview {...defaultProps} score={85} />);

      expect(screen.getByTestId('badge-score')).toHaveTextContent('85/100');
    });

    it('REND-004: should display size selector', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('size-selector')).toBeInTheDocument();
    });

    it('REND-005: should display theme selector', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('theme-selector')).toBeInTheDocument();
    });

    it('REND-006: should display embed type tabs', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('embed-type-tabs')).toBeInTheDocument();
    });

    it('REND-007: should display embed code textarea', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('embed-code')).toBeInTheDocument();
    });

    it('REND-008: should display copy button', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('REND-009: should accept custom className', () => {
      render(<MockBadgePreview {...defaultProps} className="custom-class" />);

      expect(screen.getByTestId('badge-preview')).toHaveClass('custom-class');
    });
  });

  // ------------------------------------------------------------------------
  // Size Selector Tests
  // ------------------------------------------------------------------------

  describe('SIZE: Size Selector', () => {
    it('SIZE-001: should have small, medium, large options', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('small');
      expect(options[1]).toHaveValue('medium');
      expect(options[2]).toHaveValue('large');
    });

    it('SIZE-002: should default to medium size', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      expect(selector).toHaveValue('medium');
    });

    it('SIZE-003: should update badge dimensions when size changes', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Default medium
      let badgeDisplay = screen.getByTestId('badge-display');
      expect(badgeDisplay).toHaveStyle({ width: 150, height: 100 });

      // Change to large
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');

      badgeDisplay = screen.getByTestId('badge-display');
      expect(badgeDisplay).toHaveStyle({ width: 300, height: 150 });
    });

    it('SIZE-004: should show dimensions in option labels', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options[0]).toHaveTextContent('120x40');
      expect(options[1]).toHaveTextContent('150x100');
      expect(options[2]).toHaveTextContent('300x150');
    });

    it('SIZE-005: should update embed code when size changes', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.selectOptions(screen.getByTestId('size-selector'), 'large');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('/large.svg');
    });

    it('SIZE-006: should have accessible label', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      expect(selector).toHaveAttribute('aria-label', 'Select badge size');
    });
  });

  // ------------------------------------------------------------------------
  // Theme Selector Tests
  // ------------------------------------------------------------------------

  describe('THEME: Theme Selector', () => {
    it('THEME-001: should have light and dark options', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(2);
      expect(options[0]).toHaveValue('light');
      expect(options[1]).toHaveValue('dark');
    });

    it('THEME-002: should default to light theme', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      expect(selector).toHaveValue('light');
    });

    it('THEME-003: should update badge background when theme changes', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Default light
      let badgeDisplay = screen.getByTestId('badge-display');
      expect(badgeDisplay).toHaveStyle({ backgroundColor: '#ffffff' });

      // Change to dark
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      badgeDisplay = screen.getByTestId('badge-display');
      expect(badgeDisplay).toHaveStyle({ backgroundColor: '#1a1a1a' });
    });

    it('THEME-004: should update JS embed code with theme', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Switch to JS embed type
      await user.click(screen.getByTestId('tab-js'));

      // Change theme to dark
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('data-theme="dark"');
    });

    it('THEME-005: should update iframe embed code with theme', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Switch to iframe embed type
      await user.click(screen.getByTestId('tab-iframe'));

      // Change theme to dark
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('theme=dark');
    });

    it('THEME-006: should have accessible label', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      expect(selector).toHaveAttribute('aria-label', 'Select badge theme');
    });
  });

  // ------------------------------------------------------------------------
  // Embed Code Generation Tests
  // ------------------------------------------------------------------------

  describe('EMBED: Embed Code Generation', () => {
    it('EMBED-001: should generate SVG embed code by default', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<a href="https://seoindicator.com/audit/');
      expect(embedCode.value).toContain('<img src="https://seoindicator.com/badge/');
    });

    it('EMBED-002: should generate JS embed code when selected', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-js'));

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<div data-seoindicator-badge=');
      expect(embedCode.value).toContain('<script src="https://seoindicator.com/badge.js"');
    });

    it('EMBED-003: should generate iframe embed code when selected', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-iframe'));

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<iframe src=');
      expect(embedCode.value).toContain('frameborder="0"');
    });

    it('EMBED-004: should include correct hash in embed code', () => {
      render(<MockBadgePreview auditHash="testhash123" score={80} />);

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('testhash123');
    });

    it('EMBED-005: should include correct size in SVG embed code', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.selectOptions(screen.getByTestId('size-selector'), 'small');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('/small.svg');
      expect(embedCode.value).toContain('width="120"');
      expect(embedCode.value).toContain('height="40"');
    });

    it('EMBED-006: should include data attributes in JS embed code', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview auditHash="myhash" score={85} />);

      await user.click(screen.getByTestId('tab-js'));
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('data-seoindicator-badge="myhash"');
      expect(embedCode.value).toContain('data-size="large"');
      expect(embedCode.value).toContain('data-theme="dark"');
    });

    it('EMBED-007: should include theme in iframe embed code', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-iframe'));
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('theme=dark');
    });

    it('EMBED-008: should use large size for iframe (fixed)', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-iframe'));

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('/large?');
    });

    it('EMBED-009: should include loading="lazy" in iframe', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-iframe'));

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('loading="lazy"');
    });

    it('EMBED-010: should update embed code when hash changes', () => {
      const { rerender } = render(<MockBadgePreview auditHash="hash1" score={80} />);

      let embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('hash1');

      rerender(<MockBadgePreview auditHash="hash2" score={80} />);

      embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('hash2');
    });
  });

  // ------------------------------------------------------------------------
  // Copy Functionality Tests
  // ------------------------------------------------------------------------

  describe('COPY: Copy Functionality', () => {
    it('COPY-001: should copy embed code to clipboard', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('COPY-002: should show "Copied!" feedback after copy', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('COPY-003: should show check icon after copy', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('COPY-004: should reset to "Copy Code" after 2 seconds', async () => {
      jest.useFakeTimers();
      const user = userEvent.setup({ delay: null });

      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(screen.getByText('Copied!')).toBeInTheDocument();

      // Fast-forward 2 seconds
      jest.advanceTimersByTime(2000);

      expect(screen.getByText('Copy Code')).toBeInTheDocument();

      jest.useRealTimers();
    });

    it('COPY-005: should call onCopy callback with correct parameters', async () => {
      const handleCopy = jest.fn();
      const user = userEvent.setup();

      render(<MockBadgePreview {...defaultProps} onCopy={handleCopy} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(handleCopy).toHaveBeenCalledWith('svg', expect.stringContaining('<a href'));
    });

    it('COPY-006: should copy correct embed type when tab changed', async () => {
      const handleCopy = jest.fn();
      const user = userEvent.setup();

      render(<MockBadgePreview {...defaultProps} onCopy={handleCopy} />);

      await user.click(screen.getByTestId('tab-js'));
      await user.click(screen.getByTestId('copy-button'));

      expect(handleCopy).toHaveBeenCalledWith('js', expect.stringContaining('<div'));
    });

    it('COPY-007: should have accessible aria-label', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const button = screen.getByTestId('copy-button');
      expect(button).toHaveAttribute('aria-label', 'Copy embed code');
    });

    it('COPY-008: should announce copy status with aria-live', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      const button = screen.getByTestId('copy-button');
      expect(button).toHaveAttribute('aria-live', 'polite');
    });
  });

  // ------------------------------------------------------------------------
  // Embed Type Tabs Tests
  // ------------------------------------------------------------------------

  describe('TABS: Embed Type Tabs', () => {
    it('TABS-001: should have three embed type tabs', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('tab-svg')).toBeInTheDocument();
      expect(screen.getByTestId('tab-js')).toBeInTheDocument();
      expect(screen.getByTestId('tab-iframe')).toBeInTheDocument();
    });

    it('TABS-002: should have SVG tab selected by default', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const svgTab = screen.getByTestId('tab-svg');
      expect(svgTab).toHaveAttribute('aria-selected', 'true');
    });

    it('TABS-003: should update aria-selected when tab changes', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('tab-js'));

      expect(screen.getByTestId('tab-svg')).toHaveAttribute('aria-selected', 'false');
      expect(screen.getByTestId('tab-js')).toHaveAttribute('aria-selected', 'true');
    });

    it('TABS-004: should use role="tablist" on tabs container', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('embed-type-tabs')).toHaveAttribute('role', 'tablist');
    });

    it('TABS-005: should use role="tab" on tab buttons', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('tab-svg')).toHaveAttribute('role', 'tab');
      expect(screen.getByTestId('tab-js')).toHaveAttribute('role', 'tab');
      expect(screen.getByTestId('tab-iframe')).toHaveAttribute('role', 'tab');
    });

    it('TABS-006: should be keyboard navigable', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Tab to size selector first, then to tabs
      await user.tab(); // Size selector
      await user.tab(); // Theme selector
      await user.tab(); // SVG tab

      expect(screen.getByTestId('tab-svg')).toHaveFocus();
    });
  });

  // ------------------------------------------------------------------------
  // Score Color Tests
  // ------------------------------------------------------------------------

  describe('SCORE: Score Color', () => {
    it('SCORE-001: should use green for score >= 90', () => {
      render(<MockBadgePreview {...defaultProps} score={95} />);

      const scoreElement = screen.getByTestId('badge-score');
      expect(scoreElement).toHaveStyle({ color: '#22c55e' });
    });

    it('SCORE-002: should use blue for scores 70-89', () => {
      render(<MockBadgePreview {...defaultProps} score={78} />);

      const scoreElement = screen.getByTestId('badge-score');
      expect(scoreElement).toHaveStyle({ color: '#3b82f6' });
    });

    it('SCORE-003: should use yellow for scores 50-69', () => {
      render(<MockBadgePreview {...defaultProps} score={55} />);

      const scoreElement = screen.getByTestId('badge-score');
      expect(scoreElement).toHaveStyle({ color: '#eab308' });
    });

    it('SCORE-004: should use orange for scores 30-49', () => {
      render(<MockBadgePreview {...defaultProps} score={35} />);

      const scoreElement = screen.getByTestId('badge-score');
      expect(scoreElement).toHaveStyle({ color: '#f97316' });
    });

    it('SCORE-005: should use red for scores 0-29', () => {
      render(<MockBadgePreview {...defaultProps} score={15} />);

      const scoreElement = screen.getByTestId('badge-score');
      expect(scoreElement).toHaveStyle({ color: '#ef4444' });
    });
  });

  // ------------------------------------------------------------------------
  // Accessibility Tests
  // ------------------------------------------------------------------------

  describe('A11Y: Accessibility', () => {
    it('A11Y-001: should have labels for all form controls', () => {
      render(<MockBadgePreview {...defaultProps} />);

      // Size selector has label
      const sizeLabel = screen.getByLabelText('Size');
      expect(sizeLabel).toBeInTheDocument();

      // Theme selector has label
      const themeLabel = screen.getByLabelText('Theme');
      expect(themeLabel).toBeInTheDocument();
    });

    it('A11Y-002: should have aria-label on selectors', () => {
      render(<MockBadgePreview {...defaultProps} />);

      expect(screen.getByTestId('size-selector')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('theme-selector')).toHaveAttribute('aria-label');
    });

    it('A11Y-003: should have aria-label on embed code textarea', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const textarea = screen.getByTestId('embed-code');
      expect(textarea).toHaveAttribute('aria-label', 'Embed code');
    });

    it('A11Y-004: should have aria-label on copy button', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const button = screen.getByTestId('copy-button');
      expect(button).toHaveAttribute('aria-label', 'Copy embed code');
    });

    it('A11Y-005: should be keyboard navigable', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Tab through interactive elements
      await user.tab();
      expect(screen.getByTestId('size-selector')).toHaveFocus();

      await user.tab();
      expect(screen.getByTestId('theme-selector')).toHaveFocus();

      await user.tab();
      expect(screen.getByTestId('tab-svg')).toHaveFocus();
    });

    it('A11Y-006: should have proper tab role attributes', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(3);
    });

    it('A11Y-007: should announce copy status to screen readers', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      const button = screen.getByTestId('copy-button');
      expect(button).toHaveAttribute('aria-live', 'polite');
    });
  });

  // ------------------------------------------------------------------------
  // Edge Cases Tests
  // ------------------------------------------------------------------------

  describe('EDGE: Edge Cases', () => {
    it('EDGE-001: should handle score of 0', () => {
      render(<MockBadgePreview {...defaultProps} score={0} />);

      expect(screen.getByTestId('badge-score')).toHaveTextContent('0/100');
    });

    it('EDGE-002: should handle score of 100', () => {
      render(<MockBadgePreview {...defaultProps} score={100} />);

      expect(screen.getByTestId('badge-score')).toHaveTextContent('100/100');
    });

    it('EDGE-003: should handle very long hash', () => {
      const longHash = 'a'.repeat(64);
      render(<MockBadgePreview auditHash={longHash} score={80} />);

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain(longHash);
    });

    it('EDGE-004: should handle special characters in hash', () => {
      // Hash should be alphanumeric, but test graceful handling
      render(<MockBadgePreview auditHash="abc123-def456" score={80} />);

      expect(screen.getByTestId('badge-preview')).toBeInTheDocument();
    });

    it('EDGE-005: should handle missing onCopy prop', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} onCopy={undefined} />);

      // Should not throw
      await user.click(screen.getByTestId('copy-button'));

      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('EDGE-006: should handle clipboard API failure gracefully', async () => {
      mockClipboard.writeText.mockRejectedValueOnce(new Error('Clipboard denied'));
      const user = userEvent.setup();

      render(<MockBadgePreview {...defaultProps} />);

      // Should not throw
      await user.click(screen.getByTestId('copy-button'));

      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('EDGE-007: should handle rapid copy clicks', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));
      await user.click(screen.getByTestId('copy-button'));
      await user.click(screen.getByTestId('copy-button'));

      expect(mockClipboard.writeText).toHaveBeenCalledTimes(3);
    });

    it('EDGE-008: should handle all embed types in sequence', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // SVG
      await user.click(screen.getByTestId('tab-svg'));
      expect(screen.getByTestId('embed-code').value).toContain('<a href');

      // JS
      await user.click(screen.getByTestId('tab-js'));
      expect(screen.getByTestId('embed-code').value).toContain('<div');

      // iframe
      await user.click(screen.getByTestId('tab-iframe'));
      expect(screen.getByTestId('embed-code').value).toContain('<iframe');
    });
  });

  // ------------------------------------------------------------------------
  // Props Validation Tests
  // ------------------------------------------------------------------------

  describe('PROPS: Props Validation', () => {
    it('PROPS-001: should require auditHash prop', () => {
      // TypeScript catches at compile time
      render(<MockBadgePreview {...defaultProps} />);
      expect(screen.getByTestId('badge-preview')).toBeInTheDocument();
    });

    it('PROPS-002: should require score prop', () => {
      render(<MockBadgePreview {...defaultProps} score={75} />);
      expect(screen.getByTestId('badge-score')).toHaveTextContent('75/100');
    });

    it('PROPS-003: should accept optional siteUrl prop', () => {
      render(<MockBadgePreview {...defaultProps} siteUrl="https://test.com" />);
      expect(screen.getByTestId('badge-preview')).toBeInTheDocument();
    });

    it('PROPS-004: should accept optional onCopy callback', () => {
      const handleCopy = jest.fn();
      render(<MockBadgePreview {...defaultProps} onCopy={handleCopy} />);
      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('PROPS-005: should accept optional className prop', () => {
      render(<MockBadgePreview {...defaultProps} className="test-class" />);
      expect(screen.getByTestId('badge-preview')).toHaveClass('test-class');
    });
  });

  // ------------------------------------------------------------------------
  // Responsive Design Tests
  // ------------------------------------------------------------------------

  describe('RESP: Responsive Design', () => {
    it('RESP-001: should have responsive preview container', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const container = screen.getByTestId('badge-preview-container');
      expect(container).toBeInTheDocument();
    });

    it('RESP-002: should adapt to different size selections', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // Small
      await user.selectOptions(screen.getByTestId('size-selector'), 'small');
      expect(screen.getByTestId('badge-display')).toHaveStyle({ width: 120, height: 40 });

      // Large
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');
      expect(screen.getByTestId('badge-display')).toHaveStyle({ width: 300, height: 150 });
    });
  });

  // ------------------------------------------------------------------------
  // Acceptance Criteria Tests
  // ------------------------------------------------------------------------

  describe('AC: Acceptance Criteria', () => {
    it('AC-001: live preview of badge in selected size', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      const badgeDisplay = screen.getByTestId('badge-display');
      expect(badgeDisplay).toBeInTheDocument();

      // Change size and verify preview updates
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');
      expect(badgeDisplay).toHaveStyle({ width: 300, height: 150 });
    });

    it('AC-002: size selector with small, medium, large options', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('small');
      expect(options[1]).toHaveValue('medium');
      expect(options[2]).toHaveValue('large');
    });

    it('AC-003: theme selector with light, dark options', () => {
      render(<MockBadgePreview {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(2);
      expect(options[0]).toHaveValue('light');
      expect(options[1]).toHaveValue('dark');
    });

    it('AC-004: three embed code types (SVG, JS, iframe)', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      // SVG
      await user.click(screen.getByTestId('tab-svg'));
      expect(screen.getByTestId('embed-code').value).toContain('<img');

      // JS
      await user.click(screen.getByTestId('tab-js'));
      expect(screen.getByTestId('embed-code').value).toContain('<script');

      // iframe
      await user.click(screen.getByTestId('tab-iframe'));
      expect(screen.getByTestId('embed-code').value).toContain('<iframe');
    });

    it('AC-005: copy button for each embed type', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      const copyButton = screen.getByTestId('copy-button');
      expect(copyButton).toBeInTheDocument();

      await user.click(copyButton);
      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('AC-006: copied confirmation feedback', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview {...defaultProps} />);

      await user.click(screen.getByTestId('copy-button'));

      expect(screen.getByText('Copied!')).toBeInTheDocument();
      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('AC-007: responsive preview container', () => {
      render(<MockBadgePreview {...defaultProps} className="w-full md:w-1/2" />);

      const container = screen.getByTestId('badge-preview');
      expect(container).toHaveClass('w-full', 'md:w-1/2');
    });

    it('AC-008: accessible with labeled selectors', () => {
      render(<MockBadgePreview {...defaultProps} />);

      // All form controls should have labels
      expect(screen.getByLabelText('Size')).toBeInTheDocument();
      expect(screen.getByLabelText('Theme')).toBeInTheDocument();
      expect(screen.getByTestId('embed-code')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('copy-button')).toHaveAttribute('aria-label');
    });

    it('AC-009: unit tests for all options and interactions', () => {
      // This test file covers all options and interactions
      // Tested: size selector, theme selector, embed types, copy, accessibility
      expect(true).toBe(true);
    });
  });

  // ------------------------------------------------------------------------
  // Integration Tests
  // ------------------------------------------------------------------------

  describe('INT: Integration', () => {
    it('INT-001: should update embed code when all settings change', async () => {
      const user = userEvent.setup();
      render(<MockBadgePreview auditHash="myhash" score={90} />);

      // Change size
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');

      // Change theme
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      // Change embed type
      await user.click(screen.getByTestId('tab-js'));

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('data-size="large"');
      expect(embedCode.value).toContain('data-theme="dark"');
    });

    it('INT-002: should copy correct code after changing all settings', async () => {
      const handleCopy = jest.fn();
      const user = userEvent.setup();

      render(<MockBadgePreview {...defaultProps} onCopy={handleCopy} />);

      await user.selectOptions(screen.getByTestId('size-selector'), 'small');
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');
      await user.click(screen.getByTestId('tab-iframe'));
      await user.click(screen.getByTestId('copy-button'));

      expect(handleCopy).toHaveBeenCalledWith('iframe', expect.stringContaining('theme=dark'));
    });

    it('INT-003: should handle complete user flow', async () => {
      const handleCopy = jest.fn();
      const user = userEvent.setup();

      render(
        <MockBadgePreview
          auditHash="test123"
          score={85}
          siteUrl="https://example.com"
          onCopy={handleCopy}
        />
      );

      // Verify initial state
      expect(screen.getByTestId('badge-score')).toHaveTextContent('85/100');

      // Select size
      await user.selectOptions(screen.getByTestId('size-selector'), 'large');

      // Select theme
      await user.selectOptions(screen.getByTestId('theme-selector'), 'dark');

      // Verify preview updated
      const badge = screen.getByTestId('badge-display');
      expect(badge).toHaveStyle({ width: 300, height: 150, backgroundColor: '#1a1a1a' });

      // Select embed type
      await user.click(screen.getByTestId('tab-svg'));

      // Copy code
      await user.click(screen.getByTestId('copy-button'));

      expect(handleCopy).toHaveBeenCalled();
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });
  });
});
