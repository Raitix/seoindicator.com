// ExportModal component stub - implementation in TASK-29-7
// frontend/components/tools/ExportModal.tsx

'use client';

import React from 'react';

/**
 * Props for ExportModal component
 * Implementation will be completed in TASK-29-7
 */
export interface ExportModalProps {
  isOpen: boolean;
  onClose: () => void;
  toolName: string;
  url: string;
  data: Record<string, unknown>;
}

/**
 * Modal component for selecting export format (PDF/XLS)
 * This is a stub implementation for TDD purposes
 * Full implementation in TASK-29-7
 */
export function ExportModal({ isOpen, onClose, toolName, url, data }: ExportModalProps) {
  if (!isOpen) return null;

  return (
    <div data-testid="export-modal" data-tool={toolName} data-url={url}>
      <span data-testid="modal-data">{JSON.stringify(data)}</span>
      <button onClick={onClose} data-testid="modal-close">
        Close
      </button>
    </div>
  );
}

export default ExportModal;
