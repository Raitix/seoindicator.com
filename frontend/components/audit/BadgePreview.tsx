/**
 * BadgePreview Component
 *
 * Task: TASK-33-13 - Create BadgePreview Component
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Shows live badge previews with size/theme selectors and generates
 * embed code snippets for users to copy.
 *
 * @module components/audit/BadgePreview
 */

'use client';

import * as React from 'react';
import { Copy, Check, Code, Image, Monitor } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { trackEvent } from '@/lib/analytics';

// ============================================================
// Types
// ============================================================

export type BadgeSize = 'small' | 'medium' | 'large';
export type BadgeTheme = 'light' | 'dark';
export type EmbedType = 'svg' | 'js' | 'iframe';

export interface BadgePreviewProps {
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

interface EmbedTypeConfig {
  label: string;
  icon: typeof Image;
}

const embedTypeConfig: Record<EmbedType, EmbedTypeConfig> = {
  svg: { label: 'SVG Image', icon: Image },
  js: { label: 'JavaScript', icon: Code },
  iframe: { label: 'iframe', icon: Monitor },
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
 * Get score color class for Tailwind
 */
function getScoreColorClass(score: number): string {
  if (score >= 90) return 'text-green-500';
  if (score >= 70) return 'text-blue-500';
  if (score >= 50) return 'text-yellow-500';
  if (score >= 30) return 'text-orange-500';
  return 'text-red-500';
}

/**
 * Generate SVG embed code
 */
function generateSvgEmbedCode(hash: string, size: BadgeSize): string {
  const { width, height } = sizeConfig[size];
  return `<a href="https://seoindicator.com/audit/${hash}/">
  <img src="https://seoindicator.com/badge/${hash}/${size}.svg" alt="SEO Score" width="${width}" height="${height}" />
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
function getEmbedCode(type: EmbedType, hash: string, size: BadgeSize, theme: BadgeTheme): string {
  switch (type) {
    case 'svg':
      return generateSvgEmbedCode(hash, size);
    case 'js':
      return generateJsEmbedCode(hash, size, theme);
    case 'iframe':
      return generateIframeEmbedCode(hash, theme);
  }
}

// ============================================================
// Component
// ============================================================

export function BadgePreview({
  auditHash,
  score,
  siteUrl,
  onCopy,
  className,
}: BadgePreviewProps) {
  // State management
  const [selectedSize, setSelectedSize] = React.useState<BadgeSize>('medium');
  const [selectedTheme, setSelectedTheme] = React.useState<BadgeTheme>('light');
  const [selectedEmbedType, setSelectedEmbedType] = React.useState<EmbedType>('svg');
  const [copied, setCopied] = React.useState(false);
  const [copyError, setCopyError] = React.useState(false);

  // Derived values
  const dimensions = sizeConfig[selectedSize];
  const scoreColor = getScoreColor(score);
  const scoreColorClass = getScoreColorClass(score);
  const themeColors = themeConfig[selectedTheme];
  const embedCode = getEmbedCode(selectedEmbedType, auditHash, selectedSize, selectedTheme);

  /**
   * Handle copy to clipboard
   */
  const handleCopy = async () => {
    const code = getEmbedCode(selectedEmbedType, auditHash, selectedSize, selectedTheme);

    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      setCopyError(false);
      onCopy?.(selectedEmbedType, code);
      trackEvent('badge_embed_copied', {
        hash: auditHash,
        embedType: selectedEmbedType,
        size: selectedSize,
        theme: selectedTheme,
      });
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      setCopyError(true);
      setTimeout(() => setCopyError(false), 2000);
    }
  };

  /**
   * Handle embed type tab change
   */
  const handleEmbedTypeChange = (type: EmbedType) => {
    setSelectedEmbedType(type);
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

  return (
    <div data-testid="badge-preview" className={cn('space-y-4', className)}>
      {/* Badge Preview Container */}
      <div data-testid="badge-preview-container" className="space-y-2">
        <h3 className="text-sm font-medium text-slate-300">Badge Preview</h3>
        <div
          className="flex items-center justify-center rounded-lg border border-slate-700 p-6"
          data-testid="preview-wrapper"
        >
          <div
            data-testid="badge-display"
            className="flex items-center justify-center rounded-lg transition-all duration-200"
            style={{
              width: dimensions.width,
              height: dimensions.height,
              backgroundColor: themeColors.bgColor,
            }}
          >
            <span
              data-testid="badge-score"
              className="text-lg font-bold"
              style={{ color: scoreColor }}
            >
              {score}/100
            </span>
          </div>
        </div>
      </div>

      {/* Size Selector */}
      <div data-testid="size-selector-container" className="space-y-2">
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
      <div data-testid="theme-selector-container" className="space-y-2">
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
          {(Object.entries(embedTypeConfig) as [EmbedType, EmbedTypeConfig][]).map(([type, config]) => {
            const isSelected = selectedEmbedType === type;
            return (
              <button
                key={type}
                role="tab"
                aria-selected={isSelected}
                data-testid={`tab-${type}`}
                onClick={() => handleEmbedTypeChange(type)}
                className={cn(
                  'flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  isSelected
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-400 hover:bg-slate-700 hover:text-white'
                )}
              >
                {config.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Embed Code Display */}
      <div data-testid="embed-code-container" className="space-y-2">
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
      <Button
        type="button"
        variant="outline"
        onClick={handleCopy}
        data-testid="copy-button"
        aria-label="Copy embed code"
        aria-live="polite"
        className={cn(
          'w-full transition-colors',
          copied && 'border-green-600 bg-green-600/20 text-green-400 hover:bg-green-600/30',
          copyError && 'border-red-600 bg-red-600/20 text-red-400 hover:bg-red-600/30'
        )}
      >
        {copied ? (
          <>
            <Check className="h-4 w-4" data-testid="check-icon" />
            Copied!
          </>
        ) : copyError ? (
          <>
            <Copy className="h-4 w-4" />
            Failed to copy
          </>
        ) : (
          <>
            <Copy className="h-4 w-4" data-testid="copy-icon" />
            Copy Code
          </>
        )}
      </Button>

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

export default BadgePreview;
