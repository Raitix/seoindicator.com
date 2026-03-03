/**
 * IndicatorCatalog Component Stub
 * 
 * This is a placeholder stub for the IndicatorCatalog component.
 * The actual implementation will be done in TASK-28-7.
 * 
 * This stub allows tests to pass while the component is being developed.
 */

import React from 'react';

interface IndicatorData {
  id: string;
  icon?: string | React.ReactNode;
  title: string;
  value: string | number;
  description?: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  category?: string;
  tooltip?: string;
}

interface IndicatorCatalogProps {
  indicators?: IndicatorData[];
  title?: string;
  description?: string;
  categories?: string[];
  selectedCategory?: string;
  onCategoryChange?: (category: string) => void;
  onRefresh?: () => void;
  isLoading?: boolean;
  error?: Error | null;
  className?: string;
}

/**
 * IndicatorCatalog Stub Component
 * 
 * Renders a placeholder that will be replaced by the actual implementation.
 */
export function IndicatorCatalog({
  indicators = [],
  title,
  description,
  isLoading,
  error,
  className,
}: IndicatorCatalogProps) {
  return (
    <div
      data-testid="indicator-catalog"
      data-loading={isLoading ? 'true' : 'false'}
      data-error={error ? 'true' : 'false'}
      className={className}
    >
      {title && <h2 data-testid="catalog-title">{title}</h2>}
      {description && <p data-testid="catalog-description">{description}</p>}
      {isLoading && <div data-testid="catalog-skeleton">Loading...</div>}
      {error && <div data-testid="catalog-error">{error.message}</div>}
      {!isLoading && !error && indicators && indicators.length > 0 && (
        <div data-testid="catalog-grid">
          {indicators.map((indicator) => (
            <a
              key={indicator.id}
              href={indicator.href}
              data-testid={`indicator-${indicator.id}`}
            >
              <span>{indicator.title}</span>
              <span>{indicator.value}</span>
            </a>
          ))}
        </div>
      )}
      {!isLoading && !error && (!indicators || indicators.length === 0) && (
        <div data-testid="catalog-empty">No indicators</div>
      )}
    </div>
  );
}

export type { IndicatorCatalogProps, IndicatorData };

export default IndicatorCatalog;
