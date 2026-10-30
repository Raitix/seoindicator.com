"use client";

/**
 * EmailCaptureModal Component
 * TASK-16-5: Email Capture Modal & API
 * 
 * Modal for capturing email leads from SEO tools.
 * Users get basic results (top 3 issues) for free, but must provide email
 * to see the full analysis - this is the lead generation mechanism.
 */

import * as React from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from '@/components/ui/dialog';

// Valid tool types
type ToolType = 'meta-tag-analyzer' | 'keyword-density' | 'mobile-friendly' | 'social-preview';

// Basic result item
interface BasicResult {
  type: 'warning' | 'info' | 'error' | 'success';
  message: string;
}

// Component props
export interface EmailCaptureModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (email: string) => void;
  tool: ToolType;
  url?: string;
  basicResults: BasicResult[];
}

// Local storage keys
const STORAGE_KEYS = {
  captured: 'seoindicator_tools_captured',
  captureDate: 'seoindicator_capture_date',
  captureTool: (tool: string) => `seoindicator_capture_tool_${tool}`,
};

// Capture expiry days
const CAPTURE_EXPIRY_DAYS = 30;

// Email validation regex (RFC 5322 compliant-ish)
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Maximum email length
const MAX_EMAIL_LENGTH = 255;

/**
 * Check if user has already captured for this tool
 */
function hasCapturedForTool(tool: string): boolean {
  try {
    // Check tool-specific capture
    const toolCapture = localStorage.getItem(STORAGE_KEYS.captureTool(tool));
    if (toolCapture) {
      const data = JSON.parse(toolCapture);
      const captureDate = new Date(data.date);
      const daysSinceCapture = (Date.now() - captureDate.getTime()) / (1000 * 60 * 60 * 24);
      
      if (daysSinceCapture < CAPTURE_EXPIRY_DAYS) {
        return true;
      }
    }
    
    // Check global capture (for backward compatibility)
    const globalCaptured = localStorage.getItem(STORAGE_KEYS.captured);
    if (globalCaptured === 'true') {
      const captureDateStr = localStorage.getItem(STORAGE_KEYS.captureDate);
      if (captureDateStr) {
        const captureDate = new Date(captureDateStr);
        const daysSinceCapture = (Date.now() - captureDate.getTime()) / (1000 * 60 * 60 * 24);
        
        if (daysSinceCapture < CAPTURE_EXPIRY_DAYS) {
          return true;
        }
      }
    }
    
    return false;
  } catch {
    return false;
  }
}

/**
 * Store capture status
 */
function storeCaptureStatus(tool: string): void {
  try {
    const now = new Date().toISOString();
    
    // Store tool-specific capture
    localStorage.setItem(STORAGE_KEYS.captureTool(tool), JSON.stringify({
      captured: true,
      date: now,
    }));
    
    // Store global capture for backward compatibility
    localStorage.setItem(STORAGE_KEYS.captured, 'true');
    localStorage.setItem(STORAGE_KEYS.captureDate, now);
  } catch {
    // Ignore storage errors
  }
}

/**
 * Validate email address
 */
function validateEmail(email: string): { valid: boolean; error?: string } {
  if (!email || email.trim() === '') {
    return { valid: false, error: 'Email is required' };
  }
  
  if (email.length > MAX_EMAIL_LENGTH) {
    return { valid: false, error: 'Email must be 255 characters or less' };
  }
  
  if (!EMAIL_REGEX.test(email)) {
    return { valid: false, error: 'Please enter a valid email address' };
  }
  
  return { valid: true };
}

/**
 * Get icon for result type
 */
function getResultIcon(type: BasicResult['type']): React.ReactNode {
  switch (type) {
    case 'warning':
      return (
        <svg className="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      );
    case 'error':
      return (
        <svg className="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
    case 'success':
      return (
        <svg className="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
    case 'info':
    default:
      return (
        <svg className="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
  }
}

export function EmailCaptureModal({
  isOpen,
  onClose,
  onSubmit,
  tool,
  url,
  basicResults,
}: EmailCaptureModalProps) {
  const [email, setEmail] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);
  const [isLoading, setIsLoading] = React.useState(false);
  const [networkError, setNetworkError] = React.useState<string | null>(null);
  const [isSubmitted, setIsSubmitted] = React.useState(false);
  const inputRef = React.useRef<HTMLInputElement>(null);
  const submitButtonRef = React.useRef<HTMLButtonElement>(null);

  // Check if already captured on mount
  React.useEffect(() => {
    if (isOpen && hasCapturedForTool(tool)) {
      // Auto-submit for returning users
      onSubmit('');
    }
  }, [isOpen, tool, onSubmit]);

  // Focus input when modal opens
  React.useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 100);
    }
  }, [isOpen]);

  // Reset state when modal closes
  React.useEffect(() => {
    if (!isOpen) {
      setEmail('');
      setError(null);
      setNetworkError(null);
      setIsLoading(false);
      setIsSubmitted(false);
    }
  }, [isOpen]);

  // Handle email change
  const handleEmailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setEmail(e.target.value);
    if (error) {
      setError(null);
    }
    if (networkError) {
      setNetworkError(null);
    }
  };

  // Handle email blur
  const handleEmailBlur = () => {
    if (email.trim()) {
      const validation = validateEmail(email);
      if (!validation.valid) {
        setError(validation.error || 'Invalid email');
      }
    }
  };

  // Handle form submit
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setNetworkError(null);

    // Validate email
    const validation = validateEmail(email);
    if (!validation.valid) {
      setError(validation.error || 'Please enter a valid email');
      return;
    }

    setIsLoading(true);

    try {
      const response = await fetch('/api/v1/tools/capture-email', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: email.toLowerCase().trim(),
          tool,
          url,
        }),
      });

      if (response.status === 429) {
        setNetworkError('Too many requests. Please wait and try again.');
        return;
      }

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        setNetworkError(data.message || 'Failed to capture email. Please try again.');
        return;
      }

      // Success
      storeCaptureStatus(tool);
      setIsSubmitted(true);
      onSubmit(email);
    } catch (err) {
      setNetworkError('Network error. Please check your connection and try again.');
    } finally {
      setIsLoading(false);
    }
  };

  // Handle escape key
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      onClose();
    }
  };

  // Handle backdrop click
  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  // Get result type styles
  const getResultStyles = (type: BasicResult['type']): string => {
    switch (type) {
      case 'warning':
        return 'bg-yellow-500/10 border-yellow-500/30';
      case 'error':
        return 'bg-red-500/10 border-red-500/30';
      case 'success':
        return 'bg-green-500/10 border-green-500/30';
      case 'info':
      default:
        return 'bg-blue-500/10 border-blue-500/30';
    }
  };

  // Check if submit button should be enabled
  const isSubmitEnabled = email.trim().length > 0 && !isLoading && !error;

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent
        className="sm:max-w-md w-full"
        onPointerDown={handleBackdropClick}
        onKeyDown={handleKeyDown}
        aria-modal="true"
      >
        <DialogHeader>
          <DialogTitle>Get Full Analysis</DialogTitle>
          <DialogDescription>
            Enter your email to unlock the complete analysis with all recommendations.
          </DialogDescription>
        </DialogHeader>

        {/* Basic Results Preview */}
        <div className="space-y-2 mb-4">
          <p className="text-sm text-slate-400 font-medium">Preview (Top 3 Issues):</p>
          <div className="space-y-2">
            {basicResults.slice(0, 3).map((result, index) => (
              <div
                key={index}
                className={cn(
                  'flex items-start gap-3 p-3 rounded-lg border',
                  getResultStyles(result.type)
                )}
              >
                {getResultIcon(result.type)}
                <span className="text-sm text-slate-300">{result.message}</span>
              </div>
            ))}
          </div>
          {basicResults.length > 3 && (
            <p className="text-xs text-slate-500 text-center">
              +{basicResults.length - 3} more issues in full analysis
            </p>
          )}
        </div>

        {/* Email Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email-input" className="text-slate-300">
              Email Address
            </Label>
            <Input
              ref={inputRef}
              id="email-input"
              type="email"
              value={email}
              onChange={handleEmailChange}
              onBlur={handleEmailBlur}
              placeholder="you@example.com"
              disabled={isLoading}
              className="bg-slate-800 border-slate-700 focus:border-blue-500"
              aria-describedby={error ? 'email-error' : undefined}
              aria-invalid={!!error}
              maxLength={MAX_EMAIL_LENGTH}
            />
            {error && (
              <p
                id="email-error"
                className="text-sm text-red-400"
                role="alert"
              >
                {error}
              </p>
            )}
            {networkError && (
              <p
                className="text-sm text-red-400"
                role="alert"
              >
                {networkError}
              </p>
            )}
          </div>

          <div className="flex items-center gap-3">
            <Button
              ref={submitButtonRef}
              type="submit"
              disabled={!isSubmitEnabled}
              className="flex-1"
            >
              {isLoading ? (
                <>
                  <svg
                    className="animate-spin -ml-1 mr-2 h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      className="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      strokeWidth="4"
                    />
                    <path
                      className="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                  </svg>
                  Submitting...
                </>
              ) : (
                'Get Full Analysis'
              )}
            </Button>
            <DialogClose asChild>
              <Button type="button" variant="outline" onClick={onClose}>
                Close
              </Button>
            </DialogClose>
          </div>
        </form>

        {/* Privacy notice */}
        <p className="text-xs text-slate-500 text-center mt-2">
          We respect your privacy. Your email is only used to send you analysis results.
        </p>
      </DialogContent>
    </Dialog>
  );
}

export default EmailCaptureModal;
