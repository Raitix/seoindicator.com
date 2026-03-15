/**
 * ModelCard Component
 *
 * Displays an individual AI model card with pricing and capabilities.
 *
 * @see TASK-26-22
 */

'use client';

import * as React from 'react';
import { Check, Star, Zap, Eye, Wrench, Sparkles, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import type { AIModel, ModelCapability } from '@/lib/types/models';
import { formatCost, formatContextWindow } from '@/lib/api/models';

// ==================== Types ====================

export interface ModelCardProps {
  model: AIModel;
  isSelected: boolean;
  isDefault: boolean;
  onSelect: () => void;
  onSetDefault?: () => void;
  showSetDefault?: boolean;
  isSettingDefault?: boolean;
  className?: string;
}

// ==================== Capability Icon Map ====================

const capabilityIcons: Record<ModelCapability, React.ElementType> = {
  tools: Wrench,
  vision: Eye,
  fast: Zap,
  advanced: Sparkles,
  streaming: Zap, // Using Zap for streaming as well
};

const capabilityLabels: Record<ModelCapability, string> = {
  tools: 'Tool Support',
  vision: 'Vision',
  fast: 'Fast',
  advanced: 'Advanced',
  streaming: 'Streaming',
};

// ==================== Component ====================

export function ModelCard({
  model,
  isSelected,
  isDefault,
  onSelect,
  onSetDefault,
  showSetDefault = true,
  isSettingDefault = false,
  className,
}: ModelCardProps) {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onSelect();
    }
  };

  const handleSetDefaultClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onSetDefault?.();
  };

  const handleSetDefaultKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      e.stopPropagation();
      onSetDefault?.();
    }
  };

  return (
    <div
      data-testid={`model-card-${model.id}`}
      role="option"
      aria-selected={isSelected}
      tabIndex={0}
      onClick={onSelect}
      onKeyDown={handleKeyDown}
      className={cn(
        'relative flex flex-col gap-3 rounded-lg border p-4 cursor-pointer transition-all duration-200',
        'hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
        isSelected
          ? 'border-primary bg-primary/10 ring-2 ring-primary'
          : 'border-border bg-card hover:border-primary/50',
        model.is_free_tier && 'border-green-500/30 bg-green-500/5',
        className
      )}
    >
      {/* Selected check mark */}
      {isSelected && (
        <div className="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
          <Check className="h-4 w-4" data-testid="check-icon" />
        </div>
      )}

      {/* Header: Name + Provider */}
      <div className="flex items-start gap-3 pr-8">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <h3
              data-testid="model-name"
              className="font-semibold text-base truncate"
              title={model.display_name}
            >
              {model.display_name}
            </h3>
            {model.is_free_tier && (
              <Badge
                variant="outline"
                className="text-green-500 border-green-500 bg-green-500/10"
                data-testid="free-badge"
              >
                Free
              </Badge>
            )}
          </div>
          <p
            data-testid="model-provider"
            className="text-sm text-muted-foreground capitalize"
          >
            {model.provider}
          </p>
        </div>
      </div>

      {/* Cost */}
      <div className="flex items-baseline gap-2">
        <span
          data-testid="model-cost"
          className={cn(
            'text-lg font-bold',
            model.is_free_tier ? 'text-green-500' : 'text-foreground'
          )}
        >
          {formatCost(model.estimated_cost_per_analysis)}
        </span>
        <span className="text-sm text-muted-foreground">per analysis</span>
      </div>

      {/* Context Window */}
      <div className="text-sm text-muted-foreground">
        {formatContextWindow(model.context_window)} context
      </div>

      {/* Capability Badges */}
      {model.capabilities.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {model.capabilities.map((cap) => {
            const Icon = capabilityIcons[cap];
            return (
              <Badge
                key={cap}
                variant="secondary"
                className="gap-1 text-xs"
                data-testid={`capability-${cap}`}
              >
                {Icon && <Icon className="h-3 w-3" />}
                {capabilityLabels[cap]}
              </Badge>
            );
          })}
        </div>
      )}

      {/* Footer: Default badge or Set as default button */}
      <div className="flex items-center justify-between mt-auto pt-2 border-t border-border/50">
        {isDefault ? (
          <div
            className="flex items-center gap-1.5 text-sm text-primary"
            data-testid="default-badge"
          >
            <Star className="h-4 w-4 fill-current" data-testid="star-icon" />
            <span>Default</span>
          </div>
        ) : showSetDefault && onSetDefault ? (
          <button
            data-testid="set-default-btn"
            onClick={handleSetDefaultClick}
            onKeyDown={handleSetDefaultKeyDown}
            disabled={isSettingDefault}
            className={cn(
              'flex items-center gap-1.5 text-sm text-muted-foreground',
              'hover:text-primary transition-colors',
              'disabled:opacity-50 disabled:cursor-not-allowed'
            )}
          >
            {isSettingDefault ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                <span>Setting...</span>
              </>
            ) : (
              <>
                <Star className="h-4 w-4" />
                <span>Set as default</span>
              </>
            )}
          </button>
        ) : (
          <div />
        )}
      </div>
    </div>
  );
}

export default ModelCard;
