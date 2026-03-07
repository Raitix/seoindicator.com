/**
 * EmbedCodeGenerator Component
 *
 * Task: TASK-33-21 - Create Embed Code Generator UI
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Provides a UI for generating embed code snippets with live badge preview,
 * allowing users to easily copy badge code for their websites.
 *
 * @module components/audit/EmbedCodeGenerator
 */

'use client';

import * as React from 'react';
import { Copy, Check } from 'lucide-react';

import { cn } from '@/lib/utils';
import { trackEvent } from '@/lib/analytics';

// ============================================================
// Types
// ============================================================

export type BadgeSize = 'small' | 'medium' | 'large';
export type BadgeTheme = 'light' | 'dark';
export type EmbedType = 'svg' | 'js' | 'iframe';

export interface EmbedCodeGeneratorProps {
  /** Audit hash for public URL */
  auditHash: string;
  /** Audit score (0-100) */
  score: number;
  /** Site URL (optional, for display purposes) */
  siteUrl?: string;
  /** Callback when embed code is copied */
  onCopy?: (type: EmbedType, code: string) => void;
  /** Additional CSS classes */
  className?: string;
  /** Whether to show instructions section */
  showInstructions?: boolean;
}

// ============================================================
// Config
// ============================================================

interface SizeConfig {
  width: number;
  height: number;
  label: string;
}

const sizeConfig: Record<BadgeSize, SizeConfig> = {
  small: { width: 120, height: 40, label: 'Small (120x40)' },
  medium: { width: 150, height: 100, label: 'Medium (150x100)' },
  large: { width: 300, height: 150, label: 'Large (300x150)' },
};

const themeConfig: Record<BadgeTheme, { label: string; bgColor: string }> = {
  light: { label: 'Light', bgColor: '#ffffff' },
  dark: { label: 'Dark', bgColor: '#1a1a1a' },
};

// ============================================================
// Helper Functions
// ============================================================

/**
 * Get score color based on value
 */
function getScoreColor(score: number): string {
  if (score >= 90) return '#22c55e';
  if (score >= 70) return '#3b82f6';
  if (score >= 50) return '#eab308';
  if (score >= 30) return '#f97316';
  return '#ef4444';
}

/**
 * Generate SVG embed code
 */
function generateSvgEmbedCode(hash: string, size: BadgeSize, score: number): string {
  const { width, height } = sizeConfig[size];
  return `<a href="https://seoindicator.com/audit/${hash}/">
  <img src="https://seoindicator.com/badge/${hash}/${size}.svg" alt="SEO Score: ${score}/100" width="${width}" height="${height}" />
</a>`;
}

/**
 * Generate JavaScript embed code
 */
function generateJsEmbedCode(hash: string, size: BadgeSize, theme: BadgeTheme): string {
  return `<div data-seoindicator-badge="${hash}" data-size="${size}" data-theme="${theme}"></div>
<script src="https://seoindicator.com/badge.js" async></script>`;
}

/**
 * Generate iframe embed code
 */
function generateIframeEmbedCode(hash: string, theme: BadgeTheme): string {
  return `<iframe src="https://seoindicator.com/badge/${hash}/large?theme=${theme}" 
        width="300" height="150" frameborder="0" loading="lazy"></iframe>`;
}

/**
 * Get embed code for selected type
 */
function getEmbedCode(
  type: EmbedType,
  hash: string,
  size: BadgeSize,
  theme: BadgeTheme,
  score: number
): string {
  switch (type) {
    case 'svg':
      return generateSvgEmbedCode(hash, size, score);
    case 'js':
      return generateJsEmbedCode(hash, size, theme);
    case 'iframe':
      return generateIframeEmbedCode(hash, theme);
  }
}

/**
 * Get instructions text for embed type
 */
function getInstructions(type: EmbedType): string {
  switch (type) {
    case 'svg':
      return 'Copy this HTML and paste it into your website. The badge will display as a static image.';
    case 'js':
      return 'Copy this code to your website. The badge will auto-update when your SEO score changes.';
    case 'iframe':
      return 'Copy this iframe code to embed an interactive badge on your website.';
  }
}

// ============================================================
// Component
// ============================================================

export function EmbedCodeGenerator({
  auditHash,
  score,
  siteUrl,
  onCopy,
  className,
  showInstructions = true,
}: EmbedCodeGeneratorProps) {
  // State management
  const [selectedSize, setSelectedSize] = React.useState<BadgeSize>('medium');
  const [selectedTheme, setSelectedTheme] = React.useState<BadgeTheme>('light');
  const [selectedEmbedType, setSelectedEmbedType] = React.useState<EmbedType>('svg');
  const [copied, setCopied] = React.useState(false);

  // Derived values
  const dimensions = sizeConfig[selectedSize];
  const scoreColor = getScoreColor(score);
  const themeColors = themeConfig[selectedTheme];
  const embedCode = getEmbedCode(selectedEmbedType, auditHash, selectedSize, selectedTheme, score);
  const instructions = getInstructions(selectedEmbedType);

  /**
   * Handle copy to clipboard
   */
  const handleCopy = async () => {
    const code = getEmbedCode(selectedEmbedType, auditHash, selectedSize, selectedTheme, score);

    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      onCopy?.(selectedEmbedType, code);
      trackEvent('embed_code_copied', {
        hash: auditHash,
        embedType: selectedEmbedType,
        size: selectedSize,
        theme: selectedTheme,
      });
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      // Silently fail - the button state won't change
    }
  };

  /**
   * Handle size change
   */
  const handleSizeChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    setSelectedSize(event.target.value as BadgeSize);
  };

  /**
   * Handle theme change
   */
  const handleThemeChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    setSelectedTheme(event.target.value as BadgeTheme);
  };

  /**
   * Handle embed type tab change
   */
  const handleTabClick = (type: EmbedType) => {
    setSelectedEmbedType(type);
  };

  return (
    <div data-testid="embed-code-generator" className={cn('space-y-4', className)}>
      {/* Badge Preview Section */}
      <div data-testid="preview-section" className="space-y-2">
        <h3 className="text-sm font-medium text-slate-300">Badge Preview</h3>
        <div
          data-testid="badge-preview"
          className="flex items-center justify-center rounded-lg border border-slate-700 p-6"
          style={{
            width: dimensions.width,
            height: dimensions.height,
            backgroundColor: themeColors.bgColor,
          }}
        >
          <span
            data-testid="preview-score"
            className="text-lg font-bold"
            style={{ color: scoreColor }}
          >
            {score}/100
          </span>
        </div>
      </div>

      {/* Size Selector */}
      <div data-testid="size-section" className="space-y-2">
        <label htmlFor="size-select" className="text-sm font-medium text-slate-300">
          Size
        </label>
        <select
          id="size-select"
          data-testid="size-selector"
          value={selectedSize}
          onChange={handleSizeChange}
          aria-label="Select badge size"
          className="w-full rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
          {Object.entries(sizeConfig).map(([value, config]) => (
            <option key={value} value={value}>
              {config.label}
            </option>
          ))}
        </select>
      </div>

      {/* Theme Selector */}
      <div data-testid="theme-section" className="space-y-2">
        <label htmlFor="theme-select" className="text-sm font-medium text-slate-300">
          Theme
        </label>
        <select
          id="theme-select"
          data-testid="theme-selector"
          value={selectedTheme}
          onChange={handleThemeChange}
          aria-label="Select badge theme"
          className="w-full rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
          {Object.entries(themeConfig).map(([value, config]) => (
            <option key={value} value={value}>
              {config.label}
            </option>
          ))}
        </select>
      </div>

      {/* Embed Type Tabs */}
      <div className="space-y-2">
        <label className="text-sm font-medium text-slate-300">Embed Type</label>
        <div
          data-testid="embed-type-tabs"
          role="tablist"
          className="flex rounded-lg border border-slate-700 p-1"
        >
          {(['svg', 'js', 'iframe'] as const).map((type) => {
            const isSelected = selectedEmbedType === type;
            const label = type === 'svg' ? 'SVG Image' : type === 'js' ? 'JavaScript' : 'iframe';
            return (
              <button
                key={type}
                role="tab"
                aria-selected={isSelected}
                data-testid={`tab-${type}`}
                onClick={() => handleTabClick(type)}
                className={cn(
                  'flex flex-1 items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  isSelected
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-400 hover:bg-slate-700 hover:text-white'
                )}
              >
                {label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Embed Code Display */}
      <div data-testid="code-section" className="space-y-2">
        <label htmlFor="embed-code" className="text-sm font-medium text-slate-300">
          Embed Code
        </label>
        <textarea
          id="embed-code"
          data-testid="embed-code"
          value={embedCode}
          readOnly
          aria-label="Embed code"
          rows={4}
          className="w-full rounded-md border border-slate-700 bg-slate-800/50 px-3 py-2 font-mono text-xs text-slate-300 focus:outline-none"
        />
      </div>

      {/* Copy Button */}
      <button
        type="button"
        onClick={handleCopy}
        data-testid="copy-button"
        aria-label="Copy embed code"
        aria-live="polite"
        className={cn(
          'flex w-full items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
          copied
            ? 'border-green-600 bg-green-600/20 text-green-400'
            : 'border-slate-700 bg-slate-800 text-white hover:bg-slate-700'
        )}
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

      {/* Instructions Section */}
      {showInstructions && (
        <div data-testid="instructions-section" className="space-y-2 rounded-lg bg-slate-800/50 p-4">
          <h4 className="text-sm font-medium text-slate-300">Instructions</h4>
          <p data-testid="instructions-text" className="text-sm text-slate-400">
            {instructions}
          </p>
        </div>
      )}

      {/* Current Settings Display (for testing) */}
      <div
        data-testid="current-settings"
        aria-hidden="true"
        className="hidden"
      >
        <span data-testid="current-size">{selectedSize}</span>
        <span data-testid="current-theme">{selectedTheme}</span>
        <span data-testid="current-embed">{selectedEmbedType}</span>
      </div>
    </div>
  );
}

export default EmbedCodeGenerator;
