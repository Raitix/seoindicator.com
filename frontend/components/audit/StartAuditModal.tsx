/**
 * StartAuditModal Component
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Modal for starting a new audit with max pages and max depth inputs
 */

'use client';

import * as React from 'react';
import { Loader2 } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

// ============================================================
// Props
// ============================================================

export interface StartAuditModalProps {
  /** Whether the modal is open */
  isOpen: boolean;
  /** Callback when modal closes */
  onClose: () => void;
  /** Callback when audit is started */
  onConfirm: (params: { max_pages: number; max_depth: number }) => Promise<void>;
  /** Loading state */
  isLoading?: boolean;
  /** Error message */
  error?: string | null;
  /** Additional CSS classes */
  className?: string;
}

// ============================================================
// Constants
// ============================================================

const DEFAULT_MAX_PAGES = 100;
const DEFAULT_MAX_DEPTH = 3;

const MAX_PAGES_MIN = 1;
const MAX_PAGES_MAX = 10000;
const MAX_DEPTH_MIN = 1;
const MAX_DEPTH_MAX = 10;

// ============================================================
// Component
// ============================================================

export function StartAuditModal({
  isOpen,
  onClose,
  onConfirm,
  isLoading = false,
  error,
  className,
}: StartAuditModalProps) {
  const [maxPages, setMaxPages] = React.useState(DEFAULT_MAX_PAGES);
  const [maxDepth, setMaxDepth] = React.useState(DEFAULT_MAX_DEPTH);
  const [validationErrors, setValidationErrors] = React.useState<Record<string, string>>({});
  const [localError, setLocalError] = React.useState<string | null>(null);

  // Reset form when modal opens
  React.useEffect(() => {
    if (isOpen) {
      setMaxPages(DEFAULT_MAX_PAGES);
      setMaxDepth(DEFAULT_MAX_DEPTH);
      setValidationErrors({});
      setLocalError(null);
    }
  }, [isOpen]);

  // Validate inputs
  const validate = (): boolean => {
    const errors: Record<string, string> = {};

    if (maxPages < MAX_PAGES_MIN) {
      errors.max_pages = `Minimum value is ${MAX_PAGES_MIN}`;
    }
    if (maxPages > MAX_PAGES_MAX) {
      errors.max_pages = `Maximum value is ${MAX_PAGES_MAX}`;
    }
    if (maxDepth < MAX_DEPTH_MIN) {
      errors.max_depth = `Minimum value is ${MAX_DEPTH_MIN}`;
    }
    if (maxDepth > MAX_DEPTH_MAX) {
      errors.max_depth = `Maximum value is ${MAX_DEPTH_MAX}`;
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Handle form submit
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLocalError(null);

    if (!validate()) {
      return;
    }

    try {
      await onConfirm({ max_pages: maxPages, max_depth: maxDepth });
      onClose();
    } catch (err) {
      setLocalError(err instanceof Error ? err.message : 'Failed to start audit');
    }
  };

  // Handle close
  const handleClose = () => {
    if (!isLoading) {
      onClose();
    }
  };

  const displayError = error || localError;
  const isValid = Object.keys(validationErrors).length === 0;

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent 
        className={cn('sm:max-w-md', className)}
        aria-describedby="start-audit-description"
      >
        <DialogHeader>
          <DialogTitle>Start New Audit</DialogTitle>
          <DialogDescription id="start-audit-description">
            Configure the audit parameters for crawling your site.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Max Pages Input */}
          <div className="space-y-2">
            <Label htmlFor="max-pages">
              Max Pages
            </Label>
            <Input
              id="max-pages"
              type="number"
              min={MAX_PAGES_MIN}
              max={MAX_PAGES_MAX}
              value={maxPages}
              onChange={(e) => setMaxPages(parseInt(e.target.value, 10) || 0)}
              disabled={isLoading}
              aria-describedby={validationErrors.max_pages ? 'max-pages-error' : undefined}
              aria-invalid={!!validationErrors.max_pages}
            />
            {validationErrors.max_pages && (
              <p 
                id="max-pages-error" 
                className="text-sm text-red-400"
                role="alert"
              >
                {validationErrors.max_pages}
              </p>
            )}
          </div>

          {/* Max Depth Input */}
          <div className="space-y-2">
            <Label htmlFor="max-depth">
              Max Depth
            </Label>
            <Input
              id="max-depth"
              type="number"
              min={MAX_DEPTH_MIN}
              max={MAX_DEPTH_MAX}
              value={maxDepth}
              onChange={(e) => setMaxDepth(parseInt(e.target.value, 10) || 0)}
              disabled={isLoading}
              aria-describedby={validationErrors.max_depth ? 'max-depth-error' : undefined}
              aria-invalid={!!validationErrors.max_depth}
            />
            {validationErrors.max_depth && (
              <p 
                id="max-depth-error" 
                className="text-sm text-red-400"
                role="alert"
              >
                {validationErrors.max_depth}
              </p>
            )}
          </div>

          {/* Error Message */}
          {displayError && (
            <p 
              className="text-sm text-red-400" 
              role="alert"
            >
              {displayError}
            </p>
          )}

          <DialogFooter className="gap-2 sm:gap-0">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isLoading || !isValid}
            >
              {isLoading && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden="true" />
              )}
              Start Audit
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default StartAuditModal;
