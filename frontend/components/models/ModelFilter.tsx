/**
 * ModelFilter Component
 *
 * Provides search and filter controls for model selection.
 *
 * @see TASK-26-22
 */

'use client';

import * as React from 'react';
import { Search, X, Filter } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { ModelCapability } from '@/lib/types/models';

// ==================== Types ====================

export interface ModelFilterProps {
  onSearchChange: (search: string) => void;
  onProviderChange: (provider: string | null) => void;
  onCapabilityChange: (capability: string | null) => void;
  onFreeOnlyChange: (freeOnly: boolean) => void;
  providers: string[];
  capabilities: ModelCapability[];
  searchValue?: string;
  providerValue?: string | null;
  capabilityValue?: string | null;
  freeOnlyValue?: boolean;
  className?: string;
}

// ==================== Capability Labels ====================

const capabilityLabels: Record<string, string> = {
  tools: 'Tool Support',
  vision: 'Vision',
  fast: 'Fast',
  advanced: 'Advanced',
  streaming: 'Streaming',
};

// ==================== Component ====================

export function ModelFilter({
  onSearchChange,
  onProviderChange,
  onCapabilityChange,
  onFreeOnlyChange,
  providers,
  capabilities,
  searchValue = '',
  providerValue = null,
  capabilityValue = null,
  freeOnlyValue = false,
  className,
}: ModelFilterProps) {
  const [localSearch, setLocalSearch] = React.useState(searchValue);

  // Debounced search
  React.useEffect(() => {
    const timer = setTimeout(() => {
      onSearchChange(localSearch);
    }, 300);

    return () => clearTimeout(timer);
  }, [localSearch, onSearchChange]);

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setLocalSearch(e.target.value);
  };

  const handleClearSearch = () => {
    setLocalSearch('');
    onSearchChange('');
  };

  const handleProviderChange = (value: string) => {
    onProviderChange(value === 'all' ? null : value);
  };

  const handleCapabilityChange = (value: string) => {
    onCapabilityChange(value === 'all' ? null : value);
  };

  const handleFreeOnlyToggle = () => {
    onFreeOnlyChange(!freeOnlyValue);
  };

  const handleClearAll = () => {
    setLocalSearch('');
    onSearchChange('');
    onProviderChange(null);
    onCapabilityChange(null);
    onFreeOnlyChange(false);
  };

  const hasActiveFilters =
    localSearch.trim() !== '' ||
    providerValue !== null ||
    capabilityValue !== null ||
    freeOnlyValue;

  return (
    <div
      data-testid="model-filter"
      className={cn('flex flex-col gap-3', className)}
    >
      {/* Search and Filters Row */}
      <div className="flex flex-col sm:flex-row gap-3">
        {/* Search Input */}
        <div className="relative flex-1">
          <Search
            className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
            data-testid="search-icon"
          />
          <Input
            data-testid="search-input"
            type="text"
            placeholder="Search models..."
            value={localSearch}
            onChange={handleSearchChange}
            aria-label="Search models"
            className="pl-9 pr-9"
          />
          {localSearch && (
            <button
              data-testid="clear-search"
              onClick={handleClearSearch}
              className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground hover:text-foreground"
              aria-label="Clear search"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>

        {/* Provider Select */}
        <Select value={providerValue || 'all'} onValueChange={handleProviderChange}>
          <SelectTrigger
            data-testid="provider-select"
            className="w-full sm:w-[180px]"
            aria-label="Filter by provider"
          >
            <SelectValue placeholder="All Providers" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Providers</SelectItem>
            {providers.map((provider) => (
              <SelectItem key={provider} value={provider}>
                <span className="capitalize">{provider}</span>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {/* Capability Select */}
        <Select value={capabilityValue || 'all'} onValueChange={handleCapabilityChange}>
          <SelectTrigger
            data-testid="capability-select"
            className="w-full sm:w-[180px]"
            aria-label="Filter by capability"
          >
            <SelectValue placeholder="All Capabilities" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Capabilities</SelectItem>
            {capabilities.map((capability) => (
              <SelectItem key={capability} value={capability}>
                {capabilityLabels[capability] || capability}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Free Only Toggle and Clear Filters */}
      <div className="flex items-center justify-between">
        <label
          className="flex items-center gap-2 cursor-pointer select-none"
          data-testid="free-only-label"
        >
          <input
            data-testid="free-only-checkbox"
            type="checkbox"
            checked={freeOnlyValue}
            onChange={handleFreeOnlyToggle}
            className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
          />
          <span className="text-sm text-muted-foreground">Free models only</span>
        </label>

        {hasActiveFilters && (
          <button
            data-testid="clear-filters"
            onClick={handleClearAll}
            className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            <X className="h-4 w-4" />
            Clear filters
          </button>
        )}
      </div>

      {/* Active Filters Display */}
      {hasActiveFilters && (
        <div className="flex flex-wrap gap-2">
          {localSearch && (
            <Badge variant="secondary" className="gap-1">
              Search: {localSearch}
              <button
                onClick={handleClearSearch}
                className="ml-1 hover:text-foreground"
                aria-label="Remove search filter"
              >
                <X className="h-3 w-3" />
              </button>
            </Badge>
          )}
          {providerValue && (
            <Badge variant="secondary" className="gap-1">
              Provider: {providerValue}
              <button
                onClick={() => onProviderChange(null)}
                className="ml-1 hover:text-foreground"
                aria-label="Remove provider filter"
              >
                <X className="h-3 w-3" />
              </button>
            </Badge>
          )}
          {capabilityValue && (
            <Badge variant="secondary" className="gap-1">
              Capability: {capabilityLabels[capabilityValue] || capabilityValue}
              <button
                onClick={() => onCapabilityChange(null)}
                className="ml-1 hover:text-foreground"
                aria-label="Remove capability filter"
              >
                <X className="h-3 w-3" />
              </button>
            </Badge>
          )}
          {freeOnlyValue && (
            <Badge variant="secondary" className="gap-1">
              Free only
              <button
                onClick={() => onFreeOnlyChange(false)}
                className="ml-1 hover:text-foreground"
                aria-label="Remove free only filter"
              >
                <X className="h-3 w-3" />
              </button>
            </Badge>
          )}
        </div>
      )}
    </div>
  );
}

export default ModelFilter;
