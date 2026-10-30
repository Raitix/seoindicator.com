/**
 * TASK-34-17: ComparisonActions Component
 * 
 * Action bar for competitor comparison page with:
 * - Export comparison (CSV)
 * - Add competitor (modal)
 * - Remove competitor (confirmation dialog)
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-17.md
 */

'use client';

import * as React from 'react';
import { Download, Plus, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

// ============================================
// Types
// ============================================

interface ComparisonActionsProps {
  projectId: string;
  competitorCount: number;
  maxCompetitors?: number;
  onExport: () => Promise<void>;
  onAddCompetitor: (name: string, keywords: string[]) => Promise<void>;
  onRemoveCompetitor: () => Promise<void>;
  isExporting?: boolean;
  isLoading?: boolean;
  className?: string;
}

// ============================================
// Component
// ============================================

export function ComparisonActions({
  projectId,
  competitorCount,
  maxCompetitors = 5,
  onExport,
  onAddCompetitor,
  onRemoveCompetitor,
  isExporting,
  isLoading,
  className,
}: ComparisonActionsProps) {
  const [showAddModal, setShowAddModal] = React.useState(false);
  const [showRemoveDialog, setShowRemoveDialog] = React.useState(false);
  const [newCompetitorName, setNewCompetitorName] = React.useState('');
  const [newCompetitorKeywords, setNewCompetitorKeywords] = React.useState('');
  const [isSubmitting, setIsSubmitting] = React.useState(false);

  // Handle export
  const handleExport = async () => {
    try {
      await onExport();
      toast.success('Comparison exported successfully');
    } catch (error) {
      toast.error('Failed to export comparison');
    }
  };

  // Handle add competitor
  const handleAddCompetitor = async () => {
    if (newCompetitorName.length < 2) {
      toast.error('Competitor name must be at least 2 characters');
      return;
    }

    setIsSubmitting(true);
    try {
      const keywords = newCompetitorKeywords
        .split(',')
        .map(k => k.trim())
        .filter(k => k.length > 0);
      
      await onAddCompetitor(newCompetitorName, keywords);
      toast.success('Competitor added successfully');
      setShowAddModal(false);
      setNewCompetitorName('');
      setNewCompetitorKeywords('');
    } catch (error) {
      toast.error('Failed to add competitor');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Handle remove competitor
  const handleRemoveCompetitor = async () => {
    setIsSubmitting(true);
    try {
      await onRemoveCompetitor();
      toast.success('Competitor removed successfully');
      setShowRemoveDialog(false);
    } catch (error) {
      toast.error('Failed to remove competitor');
    } finally {
      setIsSubmitting(false);
    }
  };

  const canAddCompetitor = competitorCount < maxCompetitors;
  const canRemoveCompetitor = competitorCount > 0;

  return (
    <div data-testid="comparison-actions" className={cn('flex items-center gap-3', className)}>
      {/* Export Button */}
      <button
        onClick={handleExport}
        disabled={isExporting || isLoading}
        className="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isExporting ? (
          <>
            <Loader2 data-testid="loader-icon" className="h-4 w-4 animate-spin" />
            Exporting...
          </>
        ) : (
          <>
            <Download data-testid="download-icon" className="h-4 w-4" />
            Export Comparison
          </>
        )}
      </button>

      {/* Add Competitor Button */}
      <button
        onClick={() => setShowAddModal(true)}
        disabled={!canAddCompetitor || isLoading}
        className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <Plus data-testid="plus-icon" className="h-4 w-4" />
        Add Competitor
      </button>

      {/* Remove Competitor Button */}
      <button
        onClick={() => setShowRemoveDialog(true)}
        disabled={!canRemoveCompetitor || isLoading}
        className="inline-flex items-center gap-2 rounded-lg bg-red-600/20 px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-600/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <Trash2 data-testid="trash-icon" className="h-4 w-4" />
        Remove Competitor
      </button>

      {/* Add Competitor Modal */}
      {showAddModal && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
          <div className="w-full max-w-md rounded-lg bg-slate-800 p-6 shadow-xl">
            <h3 className="text-lg font-semibold text-white mb-4">Add Competitor</h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-400 mb-1">
                  Competitor Name
                </label>
                <input
                  type="text"
                  role="textbox"
                  aria-label="Competitor name"
                  value={newCompetitorName}
                  onChange={(e) => setNewCompetitorName(e.target.value)}
                  placeholder="Enter competitor name"
                  className="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                {newCompetitorName.length > 0 && newCompetitorName.length < 2 && (
                  <p className="mt-1 text-sm text-red-400">At least 2 characters required</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-400 mb-1">
                  Keywords (comma-separated)
                </label>
                <input
                  type="text"
                  value={newCompetitorKeywords}
                  onChange={(e) => setNewCompetitorKeywords(e.target.value)}
                  placeholder="keyword1, keyword2, keyword3"
                  className="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <button
                onClick={() => setShowAddModal(false)}
                className="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleAddCompetitor}
                disabled={isSubmitting || newCompetitorName.length < 2}
                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Adding...' : 'Add Competitor'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Remove Competitor Confirmation Dialog */}
      {showRemoveDialog && (
        <div
          role="alertdialog"
          aria-modal="true"
          aria-labelledby="remove-dialog-title"
          aria-describedby="remove-dialog-description"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
          <div className="w-full max-w-md rounded-lg bg-slate-800 p-6 shadow-xl">
            <h3 id="remove-dialog-title" className="text-lg font-semibold text-white mb-2">
              Remove Competitor
            </h3>
            <p id="remove-dialog-description" className="text-slate-400 mb-6">
              Are you sure you want to remove the last competitor? This action cannot be undone.
            </p>

            <div className="flex justify-end gap-3">
              <button
                onClick={() => setShowRemoveDialog(false)}
                className="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleRemoveCompetitor}
                disabled={isSubmitting}
                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Removing...' : 'Confirm'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default ComparisonActions;
