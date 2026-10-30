/**
 * EmbedCodeModal Component - TASK-33-24
 *
 * Modal dialog for displaying and copying embed code for badges.
 *
 * @module components/dashboard/EmbedCodeModal
 */

'use client';

import * as React from 'react';
import { X, Copy, Check } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { BadgePreview } from '@/components/audit/BadgePreview';
import type { EmbedCodeModalProps } from '@/lib/types/badges';

// ============================================================
// Component
// ============================================================

export function EmbedCodeModal({ isOpen, auditHash, onClose }: EmbedCodeModalProps) {
  const [copied, setCopied] = React.useState(false);
  const [copyError, setCopyError] = React.useState(false);

  // Generate embed code
  const embedCode = auditHash
    ? `<a href="https://seoindicator.com/audit/${auditHash}">
  <img src="https://seoindicator.com/badge/${auditHash}/medium.svg" alt="SEO Score" width="150" height="100" />
</a>`
    : '';

  // Handle copy to clipboard
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(embedCode);
      setCopied(true);
      setCopyError(false);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      setCopyError(true);
      setTimeout(() => setCopyError(false), 2000);
    }
  };

  // Handle escape key
  React.useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    window.addEventListener('keydown', handleEscape);
    return () => window.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  // Don't render if not open
  if (!isOpen || !auditHash) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
    >
      <div
        data-testid="embed-code-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="embed-modal-title"
        className="relative w-full max-w-lg rounded-lg bg-slate-800 p-6 shadow-xl"
      >
        {/* Close Button */}
        <button
          data-testid="close-modal"
          onClick={onClose}
          aria-label="Close modal"
          className="absolute right-4 top-4 rounded-md p-1 text-slate-400 hover:bg-slate-700 hover:text-white"
        >
          <X className="h-5 w-5" />
        </button>

        {/* Title */}
        <h2 id="embed-modal-title" className="text-lg font-semibold text-white mb-4">
          Embed Code
        </h2>

        {/* Preview */}
        <div className="mb-4 rounded-lg border border-slate-700 p-4">
          <BadgePreview auditHash={auditHash} score={85} />
        </div>

        {/* Embed Code */}
        <div className="mb-4">
          <label htmlFor="embed-code-textarea" className="block text-sm font-medium text-slate-300 mb-2">
            Copy this code to embed the badge:
          </label>
          <textarea
            id="embed-code-textarea"
            data-testid="embed-code-textarea"
            value={embedCode}
            readOnly
            rows={5}
            className="w-full rounded-md border border-slate-700 bg-slate-900/50 px-3 py-2 font-mono text-xs text-slate-300 focus:outline-none"
          />
        </div>

        {/* Copy Button */}
        <Button
          data-testid="copy-embed-code"
          onClick={handleCopy}
          className={cn(
            'w-full transition-colors',
            copied && 'bg-green-600 hover:bg-green-700',
            copyError && 'bg-red-600 hover:bg-red-700'
          )}
        >
          {copied ? (
            <>
              <Check className="h-4 w-4 mr-2" data-testid="check-icon" />
              Copied!
            </>
          ) : copyError ? (
            <>
              <Copy className="h-4 w-4 mr-2" />
              Failed to copy
            </>
          ) : (
            <>
              <Copy className="h-4 w-4 mr-2" data-testid="copy-icon" />
              Copy Code
            </>
          )}
        </Button>
      </div>
    </div>
  );
}

export default EmbedCodeModal;
