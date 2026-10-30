/**
 * TASK-34-8: DeleteProjectModal Component
 * 
 * Confirmation modal for deleting an AI Visibility project.
 * Uses Radix UI Dialog for accessibility.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import * as React from 'react';
import { AlertTriangle, Trash2, X, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DeleteProjectModalProps } from '@/lib/types/ai-visibility-projects';

export function DeleteProjectModal({
  isOpen,
  onClose,
  onConfirm,
  projectName,
  isLoading = false,
}: DeleteProjectModalProps) {
  // Handle escape key
  React.useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen && !isLoading) {
        onClose();
      }
    };

    window.addEventListener('keydown', handleEscape);
    return () => window.removeEventListener('keydown', handleEscape);
  }, [isOpen, isLoading, onClose]);

  if (!isOpen) return null;

  const handleConfirm = async () => {
    try {
      await onConfirm();
    } catch (error) {
      // Error is handled by parent component
    }
  };

  return (
    <div
      data-testid="delete-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      aria-describedby="modal-description"
    >
      {/* Overlay */}
      <div
        data-testid="modal-overlay"
        className="fixed inset-0 bg-black/50 z-40"
        onClick={isLoading ? undefined : onClose}
      />

      {/* Content */}
      <div
        data-testid="modal-content"
        className="fixed inset-0 flex items-center justify-center p-4 z-50"
      >
        <div
          className="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-md w-full shadow-xl"
          role="document"
        >
          {/* Header */}
          <div className="flex items-center gap-3 mb-4">
            <AlertTriangle
              className="text-red-500 w-6 h-6"
              data-testid="alert-triangle-icon"
            />
            <h2
              id="modal-title"
              className="text-lg font-semibold"
            >
              Delete Project
            </h2>
          </div>

          {/* Warning Message */}
          <p
            id="modal-description"
            className="text-gray-600 dark:text-gray-300 mb-6"
          >
            Are you sure you want to delete{' '}
            <strong>{projectName}</strong>? This action cannot be undone and all
            associated data will be permanently removed.
          </p>

          {/* Actions */}
          <div className="flex gap-3 justify-end">
            <button
              onClick={onClose}
              disabled={isLoading}
              data-testid="cancel-button"
              className="px-4 py-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              onClick={handleConfirm}
              disabled={isLoading}
              data-testid="confirm-button"
              className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50 inline-flex items-center gap-2"
            >
              {isLoading ? (
                <>
                  <Loader2
                    className="w-4 h-4 animate-spin"
                    data-testid="loader-icon"
                  />
                  Deleting...
                </>
              ) : (
                <>
                  <Trash2
                    className="w-4 h-4"
                    data-testid="trash-icon"
                  />
                  Delete
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default DeleteProjectModal;
