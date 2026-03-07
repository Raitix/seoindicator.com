/**
 * ModelPicker Component - TASK-31-15
 *
 * React component for selecting AI models for analysis.
 *
 * Features:
 * - Displays all available models from API
 * - Shows context window and latency for each model
 * - Indicates recommended model
 * - Highlights selected model
 * - Disables models that can't fit content
 * - Handles loading and error states
 * - Accessible with keyboard navigation
 * - Mobile-responsive layout
 *
 * @see TASK-31-15
 * @see TEST-31-15
 */

'use client';

import React from 'react';
import { Check, Database, Clock, AlertCircle, RefreshCw } from 'lucide-react';
import { useAIModels } from '@/hooks/useAIModels';
import type { ModelPickerProps, AIModel } from '@/types/ai-models';
import {
  formatContextWindow,
  formatLatency,
  contentFitsModel,
} from '@/types/ai-models';

// Loading skeleton component
function LoadingSkeleton() {
  return (
    <div
      data-testid="model-picker-skeleton"
      role="status"
      aria-busy="true"
      className="space-y-4"
    >
      <div className="skeleton-item h-24 bg-gray-200 animate-pulse rounded-lg" />
      <div className="skeleton-item h-24 bg-gray-200 animate-pulse rounded-lg" />
      <div className="skeleton-item h-24 bg-gray-200 animate-pulse rounded-lg" />
    </div>
  );
}

// Error state component
function ErrorState({ onRetry }: { onRetry: () => void }) {
  return (
    <div
      data-testid="model-picker-error"
      role="alert"
      className="p-4 bg-red-50 border border-red-200 rounded-lg"
    >
      <div className="flex items-center gap-2 text-red-700">
        <AlertCircle className="w-5 h-5" />
        <span>Failed to load models</span>
      </div>
      <button
        onClick={onRetry}
        data-testid="retry-button"
        className="mt-3 flex items-center gap-2 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md transition-colors"
      >
        <RefreshCw className="w-4 h-4" />
        Retry
      </button>
    </div>
  );
}

// Empty state component
function EmptyState() {
  return (
    <div
      data-testid="model-picker-empty"
      className="p-4 bg-gray-50 border border-gray-200 rounded-lg text-gray-600"
    >
      No models available
    </div>
  );
}

// Model card component
interface ModelCardProps {
  model: AIModel;
  isSelected: boolean;
  isDisabled: boolean;
  fitsContent: boolean;
  onSelect: () => void;
}

function ModelCard({
  model,
  isSelected,
  isDisabled,
  fitsContent,
  onSelect,
}: ModelCardProps) {
  const handleClick = () => {
    if (!isDisabled) {
      onSelect();
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if ((e.key === 'Enter' || e.key === ' ') && !isDisabled) {
      e.preventDefault();
      onSelect();
    }
  };

  return (
    <button
      type="button"
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      disabled={isDisabled}
      data-testid={`model-card-${model.id}`}
      aria-pressed={isSelected}
      aria-disabled={isDisabled}
      className={`
        relative p-4 rounded-lg border-2 text-left transition-all
        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
        ${isSelected
          ? 'border-blue-500 bg-blue-50 selected'
          : 'border-gray-200 bg-white hover:border-gray-300'
        }
        ${isDisabled ? 'opacity-50 cursor-not-allowed disabled' : 'cursor-pointer'}
      `}
    >
      {/* Selected check icon */}
      {isSelected && (
        <span
          data-testid={`check-${model.id}`}
          className="absolute top-2 right-2 check-icon"
        >
          <Check className="w-5 h-5 text-blue-500" data-testid="check-icon" />
        </span>
      )}

      {/* Recommended badge */}
      {model.is_recommended && (
        <span
          data-testid={`recommended-badge-${model.id}`}
          className="recommended-badge absolute top-2 left-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded"
        >
          Recommended
        </span>
      )}

      {/* Model info */}
      <div className="model-info mt-6">
        <div className="model-name text-base font-semibold text-gray-900">
          {model.name}
        </div>
        <div className="model-provider text-sm text-gray-500 mt-1">
          {model.provider}
        </div>

        {/* Model stats */}
        <div className="model-stats flex items-center gap-4 mt-3 text-sm text-gray-600">
          <span
            className="model-context flex items-center gap-1"
            data-testid={`context-${model.id}`}
          >
            <Database className="w-4 h-4" data-testid="database-icon" />
            {formatContextWindow(model.context_window)}
          </span>
          <span
            className="model-latency flex items-center gap-1"
            data-testid={`latency-${model.id}`}
          >
            <Clock className="w-4 h-4" data-testid="clock-icon" />
            {formatLatency(model.avg_latency_ms)}
          </span>
        </div>

        {/* Content fit warning */}
        {!fitsContent && (
          <div
            className="content-warning mt-3 text-sm text-amber-600"
            data-testid={`warning-${model.id}`}
          >
            Content too large for this model
          </div>
        )}
      </div>
    </button>
  );
}

// Main component
export function ModelPicker({
  selectedModel,
  onModelChange,
  estimatedTokens,
  disabled = false,
  className = '',
}: ModelPickerProps) {
  const { models, defaultModel, userPreference, isLoading, error, refetch } = useAIModels();

  // Loading state
  if (isLoading) {
    return (
      <div className={`model-picker-container ${className}`}>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          AI Model
        </label>
        <LoadingSkeleton />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className={`model-picker-container ${className}`}>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          AI Model
        </label>
        <ErrorState onRetry={refetch} />
      </div>
    );
  }

  // Empty state
  if (models.length === 0) {
    return (
      <div className={`model-picker-container ${className}`}>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          AI Model
        </label>
        <EmptyState />
      </div>
    );
  }

  // Determine which model should be shown as selected
  const getModelToSelect = (): string | null => {
    if (selectedModel) return selectedModel;
    if (userPreference) return userPreference;
    return defaultModel;
  };

  const modelToSelect = getModelToSelect();

  return (
    <div
      className={`model-picker ${className}`}
      data-testid="model-picker"
    >
      {/* Label */}
      <label className="block text-sm font-medium text-gray-700 mb-2">
        AI Model
      </label>

      {/* Models grid */}
      <div className="models-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {models.map((model) => {
          const isSelected = modelToSelect === model.id;
          const fitsContent = contentFitsModel(estimatedTokens, model.context_window);
          const isDisabled = disabled || !fitsContent;

          return (
            <ModelCard
              key={model.id}
              model={model}
              isSelected={isSelected}
              isDisabled={isDisabled}
              fitsContent={fitsContent}
              onSelect={() => onModelChange(model.id)}
            />
          );
        })}
      </div>

      {/* Help text */}
      <p className="help-text text-sm text-gray-500 mt-3">
        Model is automatically selected based on page size. Override for specific needs.
      </p>
    </div>
  );
}

export default ModelPicker;
