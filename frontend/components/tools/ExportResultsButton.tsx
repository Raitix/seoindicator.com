// ExportResultsButton component - stub for TDD
// frontend/components/tools/ExportResultsButton.tsx

'use client';

import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import { ExportModal } from './ExportModal';

/**
 * Props for ExportResultsButton component
 */
export interface ExportResultsButtonProps {
  /** Tool identifier (e.g., 'meta-tag-analyzer') */
  toolName: string;
  /** Analyzed URL */
  url: string;
  /** Analysis results data */
  data: Record<string, unknown>;
  /** Optional additional CSS classes */
  className?: string;
  /** Loading state for export generation */
  isGenerating?: boolean;
}

/**
 * Button component that triggers export functionality from tool results view.
 * Opens ExportModal for format selection (PDF/XLS).
 */
export function ExportResultsButton({
  toolName,
  url,
  data,
  className = '',
  isGenerating = false,
}: ExportResultsButtonProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Check if there are results to export
  const hasResults = data && Object.keys(data).length > 0;

  // Button is disabled if no results or generating
  const isDisabled = !hasResults || isGenerating;

  const handleClick = () => {
    if (!isDisabled) {
      setIsModalOpen(true);
    }
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
  };

  // Determine aria-label based on state
  const getAriaLabel = () => {
    if (!hasResults) {
      return 'No results to export';
    }
    if (isGenerating) {
      return 'Generating export...';
    }
    return 'Export analysis results';
  };

  return (
    <>
      <Button
        onClick={handleClick}
        disabled={isDisabled}
        className={className}
        aria-label={getAriaLabel()}
      >
        {isGenerating ? (
          <span className="flex items-center gap-2">
            <svg
              className="animate-spin h-4 w-4 mr-2"
              xmlns="http://www.w3.org/2000/svg"
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
            Generating...
          </span>
        ) : (
          <>
            <Download className="mr-2 h-4 w-4" />
            Export Results
          </>
        )}
      </Button>

      <ExportModal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        toolName={toolName}
        url={url}
        data={data}
      />
    </>
  );
}

export default ExportResultsButton;
