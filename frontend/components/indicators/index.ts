/**
 * Indicators Component Library
 *
 * Reusable components for displaying SEO indicators throughout the application.
 */

export { IndicatorIcon, type IndicatorIconProps, type LucideIconName } from './IndicatorIcon';
export { default as IndicatorIconDefault } from './IndicatorIcon';

export { IndicatorCard, type IndicatorCardProps, type IndicatorStatus, statusConfig } from './IndicatorCard';
export { default as IndicatorCardDefault } from './IndicatorCard';

export { IndicatorGrid, type IndicatorGridProps, type IndicatorData, type ColumnConfig } from './IndicatorGrid';
export { default as IndicatorGridDefault } from './IndicatorGrid';

export { IndicatorCatalog, type IndicatorCatalogProps } from './IndicatorCatalog';
export type { IndicatorData as IndicatorCatalogData } from './IndicatorGrid';

// Icon Registry
export { INDICATOR_ICONS, IndicatorIconName, type IndicatorIconName as IndicatorIconNameType } from './icons';
