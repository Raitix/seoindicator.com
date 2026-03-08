/**
 * Custom Icon Registry
 *
 * Registry for custom SVG icons where Lucide icons don't provide
 * suitable alternatives. Icons are created as needed.
 *
 * @see TASK-28-15
 * @see TESTS/TEST-28-15.md
 */

import React from 'react';

// ============================================================================
// Types
// ============================================================================

/**
 * Type representing valid custom icon names.
 * Populated as custom icons are created.
 */
export type CustomIconName = string;

/**
 * Props for custom icon components.
 */
export interface CustomIconProps extends React.SVGProps<SVGSVGElement> {
  size?: 'sm' | 'md' | 'lg';
}

// ============================================================================
// Custom Icon Registry
// ============================================================================

/**
 * Registry for custom SVG icons.
 *
 * Icons are added here only when Lucide doesn't provide a suitable alternative.
 * All icons follow the design guidelines:
 * - 24x24 viewBox
 * - 2px stroke width
 * - 1px corner radius (rounded)
 * - currentColor for color inheritance
 *
 * @example
 * ```tsx
 * import { CUSTOM_ICONS } from '@/components/icons/custom';
 * import { IndicatorIcon } from '@/components/indicators';
 *
 * <IndicatorIcon customIcon={<CUSTOM_ICONS['icon-name'] />} />
 * ```
 */
export const CUSTOM_ICONS: Readonly<Record<CustomIconName, React.FC<CustomIconProps>>> = Object.freeze({
  // Custom icons are added here as needed
  // Example (when Lucide doesn't provide suitable alternative):
  // 'robot-indicator': RobotIndicatorIcon,
});

/**
 * Array of all custom icon names.
 * Useful for validation and iteration.
 */
export const CustomIconNames: readonly CustomIconName[] = Object.freeze(
  Object.keys(CUSTOM_ICONS) as CustomIconName[]
);
