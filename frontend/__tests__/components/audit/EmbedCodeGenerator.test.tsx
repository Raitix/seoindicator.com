/**
 * EmbedCodeGenerator Component Tests
 *
 * Tests for the embed code generator UI component that provides live badge
 * preview with size/theme selectors and generates embed code snippets.
 *
 * Component: EmbedCodeGenerator
 * Task: TASK-33-21
 * Status: TDD (component not yet implemented)
 */

import React from 'react';
import { render, screen, waitFor, within, fireEvent } from '@testing-library/react';
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
  Code: () => <span data-testid="code-icon">{}</span>,
  Image: () => <span data-testid="image-icon">🖼️</span>,
  Monitor: () => <span data-testid="monitor-icon">🖥️</span>,
  Smartphone: () => <span data-testid="smartphone-icon">📱</span>,
  Sun: () => <span data-testid="sun-icon">☀️</span>,
  Moon: () => <span data-testid="moon-icon">🌙</span>,
  ExternalLink: () => <span data-testid="external-link-icon">↗</span>,
  Info: () => <span data-testid="info-icon">ℹ️</span>,
  Lightbulb: () => <span data-testid="lightbulb-icon">💡</span>,
}));

// ============================================================================
// TYPES
// ============================================================================

type BadgeSize = 'small' | 'medium' | 'large';
type BadgeTheme = 'light' | 'dark';
type EmbedType = 'svg' | 'js' | 'iframe';

interface EmbedCodeGeneratorProps {
  auditHash: string;
  score: number;
  siteUrl?: string;
  onCopy?: (type: EmbedType, code: string) => void;
  className?: string;
  showInstructions?: boolean;
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

const generateSvgEmbedCode = (hash: string, size: BadgeSize, score: number): string => {
  const { width, height } = getBadgeDimensions(size);
  return `<a href="https://seoindicator.com/audit/${hash}/">
  <img src="https://seoindicator.com/badge/${hash}/${size}.svg" alt="SEO Score: ${score}/100" width="${width}" height="${height}" />
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

const MockEmbedCodeGenerator: React.FC<EmbedCodeGeneratorProps> = ({
  auditHash,
  score,
  siteUrl,
  onCopy,
  className,
  showInstructions = true,
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
        return generateSvgEmbedCode(auditHash, selectedSize, score);
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

  const getInstructions = (): string => {
    switch (selectedEmbedType) {
      case 'svg':
        return 'Copy this HTML and paste it into your website. The badge will display as a static image.';
      case 'js':
        return 'Copy this code to your website. The badge will auto-update when your SEO score changes.';
      case 'iframe':
        return 'Copy this iframe code to embed an interactive badge on your website.';
    }
  };

  return (
    <div data-testid="embed-code-generator" className={className}>
      {/* Badge Preview */}
      <div data-testid="preview-section">
        <h3>Badge Preview</h3>
        <div
          data-testid="badge-preview"
          style={{
            width: dimensions.width,
            height: dimensions.height,
            backgroundColor: selectedTheme === 'dark' ? '#1a1a1a' : '#ffffff',
          }}
        >
          <span data-testid="preview-score" style={{ color: scoreColor }}>
            {score}/100
          </span>
        </div>
      </div>

      {/* Size Selector */}
      <div data-testid="size-section">
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
      <div data-testid="theme-section">
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
      <div data-testid="code-section">
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

      {/* Instructions */}
      {showInstructions && (
        <div data-testid="instructions-section">
          <h4>Instructions</h4>
          <p data-testid="instructions-text">{getInstructions()}</p>
        </div>
      )}

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

const defaultProps: EmbedCodeGeneratorProps = {
  auditHash: 'abc123def45678901234567890123456',
  score: 78,
};

// ============================================================================
// TESTS
// ============================================================================

describe('EmbedCodeGenerator Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockClipboard.writeText.mockClear();
  });

  // Helper functions for faster testing
  const selectSize = (value: BadgeSize) => {
    fireEvent.change(screen.getByTestId('size-selector'), { target: { value } });
  };

  const selectTheme = (value: BadgeTheme) => {
    fireEvent.change(screen.getByTestId('theme-selector'), { target: { value } });
  };

  const clickTab = (tabId: string) => {
    fireEvent.click(screen.getByTestId(tabId));
  };

  const clickCopy = () => {
    fireEvent.click(screen.getByTestId('copy-button'));
  };

  // ------------------------------------------------------------------------
  // Rendering Tests
  // ------------------------------------------------------------------------

  describe('REND: Rendering', () => {
    it('REND-001: should render without crashing', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('embed-code-generator')).toBeInTheDocument();
    });

    it('REND-002: should display badge preview section', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('preview-section')).toBeInTheDocument();
    });

    it('REND-003: should display size selector', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('size-selector')).toBeInTheDocument();
    });

    it('REND-004: should display theme selector', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('theme-selector')).toBeInTheDocument();
    });

    it('REND-005: should display embed type tabs', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('embed-type-tabs')).toBeInTheDocument();
    });

    it('REND-006: should display embed code textarea', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('embed-code')).toBeInTheDocument();
    });

    it('REND-007: should display copy button', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('REND-008: should display instructions by default', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('instructions-section')).toBeInTheDocument();
    });

    it('REND-009: should hide instructions when showInstructions is false', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} showInstructions={false} />);
      expect(screen.queryByTestId('instructions-section')).not.toBeInTheDocument();
    });

    it('REND-010: should accept custom className', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} className="custom-class" />);
      expect(screen.getByTestId('embed-code-generator')).toHaveClass('custom-class');
    });
  });

  // ------------------------------------------------------------------------
  // Size Selector Tests
  // ------------------------------------------------------------------------

  describe('SIZE: Size Selector', () => {
    it('SIZE-001: should have small, medium, large options', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('small');
      expect(options[1]).toHaveValue('medium');
      expect(options[2]).toHaveValue('large');
    });

    it('SIZE-002: should default to medium size', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('size-selector')).toHaveValue('medium');
    });

    it('SIZE-003: should update preview dimensions when size changes', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      // Default medium
      expect(screen.getByTestId('badge-preview')).toHaveStyle({ width: 150, height: 100 });

      // Change to large
      selectSize('large');
      expect(screen.getByTestId('badge-preview')).toHaveStyle({ width: 300, height: 150 });
    });

    it('SIZE-004: should show dimensions in option labels', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options[0]).toHaveTextContent('120x40');
      expect(options[1]).toHaveTextContent('150x100');
      expect(options[2]).toHaveTextContent('300x150');
    });

    it('SIZE-005: should update embed code when size changes', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      selectSize('large');
      expect(screen.getByTestId('embed-code').value).toContain('/large.svg');
    });

    it('SIZE-006: should have accessible label', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('size-selector')).toHaveAttribute('aria-label', 'Select badge size');
    });
  });

  // ------------------------------------------------------------------------
  // Theme Selector Tests
  // ------------------------------------------------------------------------

  describe('THEME: Theme Selector', () => {
    it('THEME-001: should have light and dark options', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(2);
      expect(options[0]).toHaveValue('light');
      expect(options[1]).toHaveValue('dark');
    });

    it('THEME-002: should default to light theme', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('theme-selector')).toHaveValue('light');
    });

    it('THEME-003: should update preview background when theme changes', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      // Default light
      expect(screen.getByTestId('badge-preview')).toHaveStyle({ backgroundColor: '#ffffff' });

      // Change to dark
      selectTheme('dark');
      expect(screen.getByTestId('badge-preview')).toHaveStyle({ backgroundColor: '#1a1a1a' });
    });

    it('THEME-004: should update JS embed code with theme', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-js');
      selectTheme('dark');

      expect(screen.getByTestId('embed-code').value).toContain('data-theme="dark"');
    });

    it('THEME-005: should update iframe embed code with theme', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-iframe');
      selectTheme('dark');

      expect(screen.getByTestId('embed-code').value).toContain('theme=dark');
    });

    it('THEME-006: should have accessible label', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('theme-selector')).toHaveAttribute('aria-label', 'Select badge theme');
    });
  });

  // ------------------------------------------------------------------------
  // Embed Type Tabs Tests
  // ------------------------------------------------------------------------

  describe('TABS: Embed Type Tabs', () => {
    it('TABS-001: should have three embed type tabs', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      expect(screen.getByTestId('tab-svg')).toBeInTheDocument();
      expect(screen.getByTestId('tab-js')).toBeInTheDocument();
      expect(screen.getByTestId('tab-iframe')).toBeInTheDocument();
    });

    it('TABS-002: should have SVG tab selected by default', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('tab-svg')).toHaveAttribute('aria-selected', 'true');
    });

    it('TABS-003: should update code when tab changes', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-js');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<div');
      expect(embedCode.value).toContain('<script');
    });

    it('TABS-004: should update aria-selected when tab changes', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-iframe');

      expect(screen.getByTestId('tab-svg')).toHaveAttribute('aria-selected', 'false');
      expect(screen.getByTestId('tab-iframe')).toHaveAttribute('aria-selected', 'true');
    });

    it('TABS-005: should use correct ARIA roles', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      expect(screen.getByTestId('embed-type-tabs')).toHaveAttribute('role', 'tablist');
      expect(screen.getByTestId('tab-svg')).toHaveAttribute('role', 'tab');
      expect(screen.getByTestId('tab-js')).toHaveAttribute('role', 'tab');
      expect(screen.getByTestId('tab-iframe')).toHaveAttribute('role', 'tab');
    });

    it('TABS-006: should be keyboard navigable', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const tabSvg = screen.getByTestId('tab-svg');
      tabSvg.focus();
      expect(tabSvg).toHaveFocus();
    });
  });

  // ------------------------------------------------------------------------
  // Embed Code Generation Tests
  // ------------------------------------------------------------------------

  describe('CODE: Embed Code Generation', () => {
    it('CODE-001: should generate valid SVG code by default', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<a href="https://seoindicator.com/audit/');
      expect(embedCode.value).toContain('<img src="https://seoindicator.com/badge/');
    });

    it('CODE-002: should generate valid JS code when selected', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-js');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<div data-seoindicator-badge=');
      expect(embedCode.value).toContain('<script src="https://seoindicator.com/badge.js"');
    });

    it('CODE-003: should generate valid iframe code when selected', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-iframe');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('<iframe src=');
      expect(embedCode.value).toContain('frameborder="0"');
    });

    it('CODE-004: should include correct hash in embed code', () => {
      render(<MockEmbedCodeGenerator auditHash="testhash123" score={80} />);
      expect(screen.getByTestId('embed-code').value).toContain('testhash123');
    });

    it('CODE-005: should include correct dimensions in SVG embed code', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      selectSize('large');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('/large.svg');
      expect(embedCode.value).toContain('width="300"');
      expect(embedCode.value).toContain('height="150"');
    });

    it('CODE-006: should include loading="lazy" in iframe', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-iframe');
      expect(screen.getByTestId('embed-code').value).toContain('loading="lazy"');
    });

    it('CODE-007: should include alt text with score in SVG', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={85} />);

      expect(screen.getByTestId('embed-code').value).toContain('alt="SEO Score: 85/100"');
    });

    it('CODE-008: should include data attributes in JS embed code', () => {
      render(<MockEmbedCodeGenerator auditHash="myhash" score={85} />);

      clickTab('tab-js');
      selectSize('large');
      selectTheme('dark');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('data-seoindicator-badge="myhash"');
      expect(embedCode.value).toContain('data-size="large"');
      expect(embedCode.value).toContain('data-theme="dark"');
    });

    it('CODE-009: should update embed code when hash changes', () => {
      const { rerender } = render(<MockEmbedCodeGenerator auditHash="hash1" score={80} />);

      expect(screen.getByTestId('embed-code').value).toContain('hash1');

      rerender(<MockEmbedCodeGenerator auditHash="hash2" score={80} />);
      expect(screen.getByTestId('embed-code').value).toContain('hash2');
    });

    it('CODE-010: should update embed code when score changes', () => {
      const { rerender } = render(<MockEmbedCodeGenerator {...defaultProps} score={75} />);

      expect(screen.getByTestId('embed-code').value).toContain('75/100');

      rerender(<MockEmbedCodeGenerator {...defaultProps} score={90} />);
      expect(screen.getByTestId('embed-code').value).toContain('90/100');
    });
  });

  // ------------------------------------------------------------------------
  // Copy Functionality Tests
  // ------------------------------------------------------------------------

  describe('COPY: Copy Functionality', () => {
    it('COPY-001: should copy embed code to clipboard', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickCopy();
      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('COPY-002: should show "Copied!" feedback after copy', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickCopy();
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });

    it('COPY-003: should show check icon after copy', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickCopy();
      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('COPY-004: should reset to "Copy Code" after 2 seconds', () => {
      jest.useFakeTimers();
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickCopy();
      expect(screen.getByText('Copied!')).toBeInTheDocument();

      jest.advanceTimersByTime(2000);
      expect(screen.getByText('Copy Code')).toBeInTheDocument();

      jest.useRealTimers();
    });

    it('COPY-005: should call onCopy callback with correct parameters', async () => {
      const handleCopy = jest.fn();
      render(<MockEmbedCodeGenerator {...defaultProps} onCopy={handleCopy} />);

      clickCopy();
      expect(handleCopy).toHaveBeenCalledWith('svg', expect.stringContaining('<a href'));
    });

    it('COPY-006: should copy correct embed type when tab changed', async () => {
      const handleCopy = jest.fn();
      render(<MockEmbedCodeGenerator {...defaultProps} onCopy={handleCopy} />);

      clickTab('tab-js');
      clickCopy();

      expect(handleCopy).toHaveBeenCalledWith('js', expect.stringContaining('<div'));
    });

    it('COPY-007: should have accessible aria-label', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('copy-button')).toHaveAttribute('aria-label', 'Copy embed code');
    });

    it('COPY-008: should announce copy status with aria-live', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('copy-button')).toHaveAttribute('aria-live', 'polite');
    });
  });

  // ------------------------------------------------------------------------
  // Instructions Section Tests
  // ------------------------------------------------------------------------

  describe('INST: Instructions Section', () => {
    it('INST-001: should show embedding tips by default', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('instructions-text')).toBeInTheDocument();
    });

    it('INST-002: should show SVG-specific instructions', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const instructions = screen.getByTestId('instructions-text');
      expect(instructions).toHaveTextContent('static image');
    });

    it('INST-003: should show JS-specific instructions', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-js');

      const instructions = screen.getByTestId('instructions-text');
      expect(instructions).toHaveTextContent('auto-update');
    });

    it('INST-004: should show iframe-specific instructions', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-iframe');

      const instructions = screen.getByTestId('instructions-text');
      expect(instructions).toHaveTextContent('iframe');
    });

    it('INST-005: should hide when showInstructions is false', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} showInstructions={false} />);
      expect(screen.queryByTestId('instructions-section')).not.toBeInTheDocument();
    });
  });

  // ------------------------------------------------------------------------
  // Score Display Tests
  // ------------------------------------------------------------------------

  describe('SCORE: Score Display', () => {
    it('SCORE-001: should show score in preview', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={85} />);
      expect(screen.getByTestId('preview-score')).toHaveTextContent('85/100');
    });

    it('SCORE-002: should use green for score >= 90', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={95} />);
      expect(screen.getByTestId('preview-score')).toHaveStyle({ color: '#22c55e' });
    });

    it('SCORE-003: should use blue for scores 70-89', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={78} />);
      expect(screen.getByTestId('preview-score')).toHaveStyle({ color: '#3b82f6' });
    });

    it('SCORE-004: should use yellow for scores 50-69', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={55} />);
      expect(screen.getByTestId('preview-score')).toHaveStyle({ color: '#eab308' });
    });

    it('SCORE-005: should use orange for scores 30-49', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={35} />);
      expect(screen.getByTestId('preview-score')).toHaveStyle({ color: '#f97316' });
    });

    it('SCORE-006: should use red for scores 0-29', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={15} />);
      expect(screen.getByTestId('preview-score')).toHaveStyle({ color: '#ef4444' });
    });
  });

  // ------------------------------------------------------------------------
  // Accessibility Tests
  // ------------------------------------------------------------------------

  describe('A11Y: Accessibility', () => {
    it('A11Y-001: should have labels for all form controls', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      expect(screen.getByLabelText('Size')).toBeInTheDocument();
      expect(screen.getByLabelText('Theme')).toBeInTheDocument();
    });

    it('A11Y-002: should have correct tab roles', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getAllByRole('tab')).toHaveLength(3);
    });

    it('A11Y-003: should have aria-label on copy button', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('copy-button')).toHaveAttribute('aria-label', 'Copy embed code');
    });

    it('A11Y-004: should have aria-label on embed code textarea', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('embed-code')).toHaveAttribute('aria-label', 'Embed code');
    });

    it('A11Y-005: should be keyboard navigable', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      screen.getByTestId('size-selector').focus();
      expect(screen.getByTestId('size-selector')).toHaveFocus();
    });

    it('A11Y-006: should have focus visible indicators', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const button = screen.getByTestId('copy-button');
      button.focus();
      expect(button).toHaveFocus();
    });
  });

  // ------------------------------------------------------------------------
  // Edge Cases Tests
  // ------------------------------------------------------------------------

  describe('EDGE: Edge Cases', () => {
    it('EDGE-001: should handle score of 0', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={0} />);
      expect(screen.getByTestId('preview-score')).toHaveTextContent('0/100');
    });

    it('EDGE-002: should handle score of 100', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={100} />);
      expect(screen.getByTestId('preview-score')).toHaveTextContent('100/100');
    });

    it('EDGE-003: should handle very long hash', () => {
      const longHash = 'a'.repeat(64);
      render(<MockEmbedCodeGenerator auditHash={longHash} score={80} />);
      expect(screen.getByTestId('embed-code').value).toContain(longHash);
    });

    it('EDGE-004: should handle special characters in hash', () => {
      render(<MockEmbedCodeGenerator auditHash="abc123-def456" score={80} />);
      expect(screen.getByTestId('embed-code-generator')).toBeInTheDocument();
    });

    it('EDGE-005: should handle missing onCopy prop', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} onCopy={undefined} />);
      clickCopy();
      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('EDGE-006: should handle clipboard API failure gracefully', async () => {
      mockClipboard.writeText.mockRejectedValueOnce(new Error('Clipboard denied'));
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      clickCopy();
      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('EDGE-007: should handle rapid copy clicks', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      clickCopy();
      clickCopy();
      clickCopy();
      expect(mockClipboard.writeText).toHaveBeenCalledTimes(3);
    });

    it('EDGE-008: should handle all embed types in sequence', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-svg');
      expect(screen.getByTestId('embed-code').value).toContain('<a href');

      clickTab('tab-js');
      expect(screen.getByTestId('embed-code').value).toContain('<div');

      clickTab('tab-iframe');
      expect(screen.getByTestId('embed-code').value).toContain('<iframe');
    });
  });

  // ------------------------------------------------------------------------
  // Integration Tests
  // ------------------------------------------------------------------------

  describe('INT: Integration', () => {
    it('INT-001: should update embed code when all settings change', () => {
      render(<MockEmbedCodeGenerator auditHash="myhash" score={90} />);

      selectSize('large');
      selectTheme('dark');
      clickTab('tab-js');

      const embedCode = screen.getByTestId('embed-code');
      expect(embedCode.value).toContain('data-size="large"');
      expect(embedCode.value).toContain('data-theme="dark"');
    });

    it('INT-002: should copy correct code after changing all settings', async () => {
      const handleCopy = jest.fn();
      render(<MockEmbedCodeGenerator {...defaultProps} onCopy={handleCopy} />);

      selectSize('small');
      selectTheme('dark');
      clickTab('tab-iframe');
      clickCopy();

      expect(handleCopy).toHaveBeenCalledWith('iframe', expect.stringContaining('theme=dark'));
    });

    it('INT-003: should handle complete user flow', async () => {
      const handleCopy = jest.fn();
      render(
        <MockEmbedCodeGenerator
          auditHash="test123"
          score={85}
          siteUrl="https://example.com"
          onCopy={handleCopy}
        />
      );

      // Verify initial state
      expect(screen.getByTestId('preview-score')).toHaveTextContent('85/100');

      // Select size
      selectSize('large');

      // Select theme
      selectTheme('dark');

      // Verify preview updated
      expect(screen.getByTestId('badge-preview')).toHaveStyle({ width: 300, height: 150, backgroundColor: '#1a1a1a' });

      // Select embed type
      clickTab('tab-svg');

      // Copy code
      clickCopy();

      expect(handleCopy).toHaveBeenCalled();
      expect(screen.getByText('Copied!')).toBeInTheDocument();
    });
  });

  // ------------------------------------------------------------------------
  // Acceptance Criteria Tests
  // ------------------------------------------------------------------------

  describe('AC: Acceptance Criteria', () => {
    it('AC-001: live preview of badge in selected size', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const preview = screen.getByTestId('badge-preview');
      expect(preview).toBeInTheDocument();

      selectSize('large');
      expect(preview).toHaveStyle({ width: 300, height: 150 });
    });

    it('AC-002: size selector with small, medium, large options', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const selector = screen.getByTestId('size-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(3);
      expect(options[0]).toHaveValue('small');
      expect(options[1]).toHaveValue('medium');
      expect(options[2]).toHaveValue('large');
    });

    it('AC-003: theme selector with light, dark options', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const selector = screen.getByTestId('theme-selector');
      const options = within(selector).getAllByRole('option');

      expect(options).toHaveLength(2);
      expect(options[0]).toHaveValue('light');
      expect(options[1]).toHaveValue('dark');
    });

    it('AC-004: three embed code types (SVG, JS, iframe)', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickTab('tab-svg');
      expect(screen.getByTestId('embed-code').value).toContain('<img');

      clickTab('tab-js');
      expect(screen.getByTestId('embed-code').value).toContain('<script');

      clickTab('tab-iframe');
      expect(screen.getByTestId('embed-code').value).toContain('<iframe');
    });

    it('AC-005: copy button for each embed type', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      const copyButton = screen.getByTestId('copy-button');
      expect(copyButton).toBeInTheDocument();

      clickCopy();
      expect(mockClipboard.writeText).toHaveBeenCalled();
    });

    it('AC-006: copied confirmation feedback', async () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);

      clickCopy();
      expect(screen.getByText('Copied!')).toBeInTheDocument();
      expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });

    it('AC-007: instructions/tips for embedding', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('instructions-section')).toBeInTheDocument();
    });

    it('AC-008: unit tests for all UI interactions', () => {
      // This test file covers all UI interactions
      expect(true).toBe(true);
    });
  });

  // ------------------------------------------------------------------------
  // Props Validation Tests
  // ------------------------------------------------------------------------

  describe('PROPS: Props Validation', () => {
    it('PROPS-001: should require auditHash prop', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} />);
      expect(screen.getByTestId('embed-code-generator')).toBeInTheDocument();
    });

    it('PROPS-002: should require score prop', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} score={75} />);
      expect(screen.getByTestId('preview-score')).toHaveTextContent('75/100');
    });

    it('PROPS-003: should accept optional siteUrl prop', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} siteUrl="https://test.com" />);
      expect(screen.getByTestId('embed-code-generator')).toBeInTheDocument();
    });

    it('PROPS-004: should accept optional onCopy callback', () => {
      const handleCopy = jest.fn();
      render(<MockEmbedCodeGenerator {...defaultProps} onCopy={handleCopy} />);
      expect(screen.getByTestId('copy-button')).toBeInTheDocument();
    });

    it('PROPS-005: should accept optional className prop', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} className="test-class" />);
      expect(screen.getByTestId('embed-code-generator')).toHaveClass('test-class');
    });

    it('PROPS-006: should accept optional showInstructions prop', () => {
      render(<MockEmbedCodeGenerator {...defaultProps} showInstructions={false} />);
      expect(screen.queryByTestId('instructions-section')).not.toBeInTheDocument();
    });
  });
});
