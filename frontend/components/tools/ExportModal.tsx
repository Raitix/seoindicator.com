'use client';

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
  DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
  X,
  FileText,
  FileSpreadsheet,
  Loader2,
  AlertCircle,
  Clock,
} from 'lucide-react';

/**
 * Type definition for ToolResultData
 */
export interface ToolResultData {
  analyzedAt?: string;
  results?: Record<string, unknown>;
  indicators?: Array<{
    name: string;
    value: string | number;
    status: 'ok' | 'warning' | 'error';
    recommendation?: string;
  }>;
  summary?: {
    score?: number;
    passCount?: number;
    warningCount?: number;
    failCount?: number;
  };
}

/**
 * Export format type
 */
export type ExportFormat = 'pdf' | 'xls';

/**
 * Props for ExportModal component
 */
export interface ExportModalProps {
  /** Controls modal visibility */
  isOpen: boolean;
  /** Callback when modal should close */
  onClose: () => void;
  /** Tool identifier (e.g., 'meta-tag-analyzer') */
  toolName: string;
  /** Analyzed URL */
  url: string;
  /** Analysis results data */
  data: ToolResultData;
}

/**
 * Modal component for selecting export format (PDF/XLS) and initiating download.
 * Handles loading states, errors, and rate limits.
 */
export function ExportModal({
  isOpen,
  onClose,
  toolName,
  url,
  data,
}: ExportModalProps) {
  const [format, setFormat] = useState<ExportFormat>('pdf');
  const [isExporting, setIsExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [retryAfter, setRetryAfter] = useState<number | null>(null);
  
  // Ref to track exporting state synchronously (prevents race conditions on rapid clicks)
  const isExportingRef = useRef(false);

  // Reset state when modal opens
  useEffect(() => {
    if (isOpen) {
      setError(null);
      setRetryAfter(null);
      setIsExporting(false);
      isExportingRef.current = false;
    }
  }, [isOpen]);

  // Check if data is valid for export
  const hasValidData = data && (
    (data.indicators && data.indicators.length > 0) ||
    (data.results && Object.keys(data.results).length > 0)
  );

  // Get indicator count for preview
  const indicatorCount = data?.indicators?.length || 0;

  // Get score for preview
  const score = data?.summary?.score;

  // Format retry time for display
  const formatRetryTime = (seconds: number): string => {
    if (seconds < 60) {
      return `${seconds} seconds`;
    }
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    if (remainingSeconds === 0) {
      return `${minutes} minute${minutes > 1 ? 's' : ''}`;
    }
    return `${minutes} minute${minutes > 1 ? 's' : ''} ${remainingSeconds} seconds`;
  };

  // Handle export action
  const handleExport = useCallback(async () => {
    // Use ref for synchronous check to prevent race conditions
    if (isExportingRef.current || !hasValidData) return;
    
    // Set ref immediately to prevent duplicate calls
    isExportingRef.current = true;
    setIsExporting(true);
    setError(null);
    setRetryAfter(null);

    try {
      const response = await fetch('/api/v1/tools/export', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          tool: toolName,
          url,
          format,
          data,
        }),
      });

      const result = await response.json();

      if (!response.ok) {
        if (response.status === 429) {
          const retrySeconds = result.retryAfter || 3600;
          setRetryAfter(retrySeconds);
          setError(`Rate limit exceeded. Please wait ${formatRetryTime(retrySeconds)} before exporting again.`);
        } else {
          setError(result.message || 'Failed to generate export. Please try again.');
        }
        return;
      }

      if (result.success && result.downloadUrl) {
        // Trigger file download
        window.location.href = result.downloadUrl;
        // Close modal after successful download initiation
        onClose();
      } else {
        setError('Failed to generate export. Please try again.');
      }
    } catch (err) {
      setError('Failed to generate export. Please try again.');
    } finally {
      isExportingRef.current = false;
      setIsExporting(false);
    }
  }, [hasValidData, toolName, url, format, data, onClose]);

  // Handle cancel button
  const handleCancel = () => {
    if (!isExporting) {
      onClose();
    }
  };

  // Generate modal title ID for aria-labelledby
  const titleId = 'export-modal-title';
  const descriptionId = 'export-modal-description';

  // Extract domain from URL for display
  const getDisplayUrl = (urlString: string): string => {
    try {
      const urlObj = new URL(urlString);
      return urlObj.hostname;
    } catch {
      return urlString;
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && handleCancel()}>
      <DialogContent
        data-testid="export-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={descriptionId}
        className="sm:max-w-md"
        onEscapeKeyDown={(e) => {
          if (!isExporting) {
            onClose();
          } else {
            e.preventDefault();
          }
        }}
      >
        <DialogHeader>
          <DialogTitle id={titleId}>Export Results</DialogTitle>
          <DialogDescription id={descriptionId}>
            Choose a format to export your analysis results.
          </DialogDescription>
        </DialogHeader>

        {/* Preview Section */}
        <div
          data-testid="export-preview"
          className="rounded-lg bg-slate-800/50 p-4 space-y-2"
        >
          <div className="text-sm text-slate-300">
            <span className="text-slate-500">Tool:</span>{' '}
            <span className="text-white font-medium">{toolName}</span>
          </div>
          <div className="text-sm text-slate-300">
            <span className="text-slate-500">URL:</span>{' '}
            <span className="text-white">{getDisplayUrl(url)}</span>
          </div>
          {indicatorCount > 0 && (
            <div className="text-sm text-slate-300">
              <span className="text-slate-500">Indicators:</span>{' '}
              <span className="text-white">{indicatorCount} indicators</span>
            </div>
          )}
          {score !== undefined && (
            <div className="text-sm text-slate-300">
              <span className="text-slate-500">Score:</span>{' '}
              <span className="text-white font-medium">{score}/100</span>
            </div>
          )}
        </div>

        {/* Format Selection */}
        <div className="space-y-3">
          <Label className="text-slate-200">Export Format</Label>
          <RadioGroup
            value={format}
            onValueChange={(value) => setFormat(value as ExportFormat)}
            className="space-y-3"
            disabled={isExporting}
          >
            {/* PDF Option */}
            <div className="flex items-start space-x-3 rounded-lg border border-slate-700 p-3 hover:bg-slate-800/50 transition-colors">
              <RadioGroupItem value="pdf" id="format-pdf" className="mt-1" />
              <div className="flex-1">
                <Label
                  htmlFor="format-pdf"
                  className="flex items-center gap-2 cursor-pointer"
                >
                  <FileText className="h-4 w-4 text-blue-400" />
                  <span className="font-medium text-white">PDF</span>
                </Label>
                <p className="text-sm text-slate-400 mt-1">
                  Branded document with summary, indicators, and recommendations
                </p>
              </div>
            </div>

            {/* XLS Option */}
            <div className="flex items-start space-x-3 rounded-lg border border-slate-700 p-3 hover:bg-slate-800/50 transition-colors">
              <RadioGroupItem value="xls" id="format-xls" className="mt-1" />
              <div className="flex-1">
                <Label
                  htmlFor="format-xls"
                  className="flex items-center gap-2 cursor-pointer"
                >
                  <FileSpreadsheet className="h-4 w-4 text-green-400" />
                  <span className="font-medium text-white">XLS</span>
                </Label>
                <p className="text-sm text-slate-400 mt-1">
                  Spreadsheet with indicator data for further analysis
                </p>
              </div>
            </div>
          </RadioGroup>
        </div>

        {/* Error Message */}
        {error && (
          <div
            data-testid="export-error"
            className="rounded-lg bg-red-900/30 border border-red-800 p-3 flex items-start gap-2"
          >
            {retryAfter ? (
              <Clock className="h-5 w-5 text-orange-400 mt-0.5 shrink-0" />
            ) : (
              <AlertCircle className="h-5 w-5 text-red-400 mt-0.5 shrink-0" />
            )}
            <p className="text-sm text-red-200">{error}</p>
          </div>
        )}

        <DialogFooter className="gap-2 sm:gap-0">
          <Button
            variant="outline"
            onClick={handleCancel}
            disabled={isExporting}
          >
            Cancel
          </Button>
          <Button
            onClick={handleExport}
            disabled={isExporting || !hasValidData}
            className="min-w-[100px]"
          >
            {isExporting ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                Exporting...
              </>
            ) : (
              'Export'
            )}
          </Button>
        </DialogFooter>

        {/* Close button (X) */}
        <DialogClose asChild>
          <button
            data-testid="close-modal-button"
            className="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
            disabled={isExporting}
          >
            <X className="h-4 w-4" />
            <span className="sr-only">Close</span>
          </button>
        </DialogClose>
      </DialogContent>
    </Dialog>
  );
}

export default ExportModal;
