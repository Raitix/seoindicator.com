/**
 * ModelPicker Component
 *
 * Main component for selecting AI models with search, filter, and cost estimation.
 *
 * @see TASK-26-22
 */

'use client';

import * as React from 'react';
import { ChevronDown, ChevronUp, Check, Star, Loader2, AlertCircle, RefreshCw } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { ModelCard } from './ModelCard';
import { ModelFilter } from './ModelFilter';
import { CostEstimate } from './CostEstimate';
import {
  useModels,
  useSetDefaultModel,
  getUniqueProviders,
  getUniqueCapabilities,
  filterModelsBySearch,
  sortModels,
} from '@/lib/api/models';
import type { AIModel, ModelCapability } from '@/lib/types/models';

// ==================== Types ====================

export interface ModelPickerProps {
  onSelect: (model: AIModel) => void;
  selectedModelId?: number | null;
  analysisType?: 'quick' | 'standard' | 'deep';
  showSetDefault?: boolean;
  userBalance?: number;
  className?: string;
  triggerClassName?: string;
}

// ==================== Component ====================

export function ModelPicker({
  onSelect,
  selectedModelId,
  analysisType = 'standard',
  showSetDefault = true,
  userBalance,
  className,
  triggerClassName,
}: ModelPickerProps) {
  const [isOpen, setIsOpen] = React.useState(false);
  const [searchQuery, setSearchQuery] = React.useState('');
  const [providerFilter, setProviderFilter] = React.useState<string | null>(null);
  const [capabilityFilter, setCapabilityFilter] = React.useState<string | null>(null);
  const [freeOnly, setFreeOnly] = React.useState(false);
  const [focusedIndex, setFocusedIndex] = React.useState(-1);

  const triggerRef = React.useRef<HTMLButtonElement>(null);
  const listRef = React.useRef<HTMLDivElement>(null);

  // Fetch models
  const { models, isLoading, error, refetch } = useModels();
  const { setDefault, isPending: isSettingDefault } = useSetDefaultModel();

  // Get selected model
  const selectedModel = React.useMemo(() => {
    return models.find((m) => m.id === selectedModelId) || null;
  }, [models, selectedModelId]);

  // Filter and sort models
  const filteredModels = React.useMemo(() => {
    let result = models;

    // Apply search filter
    if (searchQuery) {
      result = filterModelsBySearch(result, searchQuery);
    }

    // Apply provider filter
    if (providerFilter) {
      result = result.filter((m) => m.provider === providerFilter);
    }

    // Apply capability filter
    if (capabilityFilter) {
      result = result.filter((m) => m.capabilities.includes(capabilityFilter as ModelCapability));
    }

    // Apply free only filter
    if (freeOnly) {
      result = result.filter((m) => m.is_free_tier);
    }

    // Sort (free first, then by price)
    return sortModels(result);
  }, [models, searchQuery, providerFilter, capabilityFilter, freeOnly]);

  // Get unique providers and capabilities for filters
  const providers = React.useMemo(() => getUniqueProviders(models), [models]);
  const capabilities = React.useMemo(() => getUniqueCapabilities(models) as ModelCapability[], [models]);

  // Handle model selection
  const handleSelect = (model: AIModel) => {
    onSelect(model);
    setIsOpen(false);
    triggerRef.current?.focus();
  };

  // Handle set as default
  const handleSetDefault = async (modelId: number) => {
    try {
      await setDefault(modelId);
      refetch(); // Refresh to update is_user_default
    } catch (error) {
      console.error('Failed to set default model:', error);
    }
  };

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setIsOpen(true);
      }
      return;
    }

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setFocusedIndex((prev) =>
          prev < filteredModels.length - 1 ? prev + 1 : 0
        );
        break;
      case 'ArrowUp':
        e.preventDefault();
        setFocusedIndex((prev) =>
          prev > 0 ? prev - 1 : filteredModels.length - 1
        );
        break;
      case 'Enter':
        e.preventDefault();
        if (focusedIndex >= 0 && focusedIndex < filteredModels.length) {
          handleSelect(filteredModels[focusedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        setIsOpen(false);
        triggerRef.current?.focus();
        break;
      case 'Home':
        e.preventDefault();
        setFocusedIndex(0);
        break;
      case 'End':
        e.preventDefault();
        setFocusedIndex(filteredModels.length - 1);
        break;
    }
  };

  // Reset filters on close
  const handleOpenChange = (open: boolean) => {
    setIsOpen(open);
    if (!open) {
      setSearchQuery('');
      setProviderFilter(null);
      setCapabilityFilter(null);
      setFreeOnly(false);
      setFocusedIndex(-1);
    }
  };

  // Scroll focused item into view
  React.useEffect(() => {
    if (focusedIndex >= 0 && listRef.current) {
      const items = listRef.current.querySelectorAll('[role="option"]');
      if (items[focusedIndex]) {
        (items[focusedIndex] as HTMLElement).focus();
      }
    }
  }, [focusedIndex]);

  return (
    <div
      data-testid="model-picker"
      role="combobox"
      aria-expanded={isOpen}
      aria-haspopup="listbox"
      aria-label="Select AI model"
      className={cn('relative', className)}
    >
      {/* Trigger Button */}
      <button
        ref={triggerRef}
        data-testid="picker-trigger"
        type="button"
        onClick={() => handleOpenChange(true)}
        onKeyDown={handleKeyDown}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        className={cn(
          'flex items-center justify-between gap-2 w-full px-4 py-3',
          'rounded-lg border bg-card text-left',
          'transition-colors',
          'hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-ring',
          'disabled:opacity-50 disabled:cursor-not-allowed',
          triggerClassName
        )}
      >
        <div className="flex items-center gap-3 flex-1 min-w-0">
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" data-testid="loader-icon" />
              <span className="text-muted-foreground">Loading models...</span>
            </>
          ) : selectedModel ? (
            <>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <span className="font-medium truncate">{selectedModel.display_name}</span>
                  {selectedModel.is_free_tier && (
                    <span className="text-xs text-green-500 font-medium">Free</span>
                  )}
                </div>
                <div className="text-sm text-muted-foreground">
                  {selectedModel.is_user_default ? (
                    <span className="flex items-center gap-1">
                      <Star className="h-3 w-3 fill-current text-primary" data-testid="star-icon" />
                      Default
                    </span>
                  ) : (
                    <span className="capitalize">{selectedModel.provider}</span>
                  )}
                </div>
              </div>
            </>
          ) : (
            <span className="text-muted-foreground">Select a model</span>
          )}
        </div>
        {isOpen ? (
          <ChevronUp className="h-5 w-5 text-muted-foreground flex-shrink-0" data-testid="chevron-up-icon" />
        ) : (
          <ChevronDown className="h-5 w-5 text-muted-foreground flex-shrink-0" data-testid="chevron-down-icon" />
        )}
      </button>

      {/* Modal/Dialog for model selection */}
      <Dialog open={isOpen} onOpenChange={handleOpenChange}>
        <DialogContent className="max-w-3xl max-h-[85vh] flex flex-col">
          <DialogHeader>
            <DialogTitle>Select AI Model</DialogTitle>
            <DialogDescription>
              Choose an AI model for your analysis. Free models have no cost.
            </DialogDescription>
          </DialogHeader>

          <div className="flex-1 flex flex-col gap-4 overflow-hidden">
            {/* Filters */}
            <ModelFilter
              onSearchChange={setSearchQuery}
              onProviderChange={setProviderFilter}
              onCapabilityChange={setCapabilityFilter}
              onFreeOnlyChange={setFreeOnly}
              providers={providers}
              capabilities={capabilities}
              searchValue={searchQuery}
              providerValue={providerFilter}
              capabilityValue={capabilityFilter}
              freeOnlyValue={freeOnly}
            />

            {/* Error State */}
            {error && (
              <div className="flex flex-col items-center justify-center gap-3 py-8 text-center">
                <AlertCircle className="h-10 w-10 text-destructive" data-testid="alert-icon" />
                <div>
                  <p className="font-medium text-destructive">Failed to load models</p>
                  <p className="text-sm text-muted-foreground mt-1">
                    {error.message || 'Please try again later.'}
                  </p>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => refetch()}
                  className="gap-2"
                  data-testid="retry-button"
                >
                  <RefreshCw className="h-4 w-4" />
                  Retry
                </Button>
              </div>
            )}

            {/* Loading State */}
            {isLoading && !error && (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <div key={i} className="rounded-lg border p-4">
                    <Skeleton className="h-5 w-32 mb-2" />
                    <Skeleton className="h-4 w-24 mb-4" />
                    <Skeleton className="h-6 w-16 mb-3" />
                    <Skeleton className="h-4 w-20" />
                  </div>
                ))}
              </div>
            )}

            {/* Empty State */}
            {!isLoading && !error && filteredModels.length === 0 && (
              <div className="flex flex-col items-center justify-center gap-2 py-8 text-center">
                <p className="text-muted-foreground">No models found</p>
                {(searchQuery || providerFilter || capabilityFilter || freeOnly) && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setSearchQuery('');
                      setProviderFilter(null);
                      setCapabilityFilter(null);
                      setFreeOnly(false);
                    }}
                  >
                    Clear filters
                  </Button>
                )}
              </div>
            )}

            {/* Models Grid */}
            {!isLoading && !error && filteredModels.length > 0 && (
              <ScrollArea className="flex-1 -mx-6 px-6" ref={listRef}>
                <div
                  role="listbox"
                  aria-label="Available models"
                  className="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-4"
                >
                  {filteredModels.map((model, index) => (
                    <ModelCard
                      key={model.id}
                      model={model}
                      isSelected={model.id === selectedModelId}
                      isDefault={model.is_user_default}
                      onSelect={() => handleSelect(model)}
                      onSetDefault={
                        showSetDefault && !model.is_user_default
                          ? () => handleSetDefault(model.id)
                          : undefined
                      }
                      showSetDefault={showSetDefault}
                      isSettingDefault={isSettingDefault}
                    />
                  ))}
                </div>
              </ScrollArea>
            )}

            {/* Cost Estimate for Selected Model */}
            {selectedModelId && (
              <CostEstimate
                modelId={selectedModelId}
                analysisType={analysisType}
                userBalance={userBalance}
                onAddCredits={() => {
                  // Navigate to credits page or open credits modal
                  // This would typically use router.push('/credits') or similar
                  console.log('Navigate to credits page');
                }}
                className="mt-auto border-t pt-4"
              />
            )}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}

export default ModelPicker;
