/**
 * Custom Icons Barrel Export
 *
 * Exports custom SVG icons where Lucide icons don't provide suitable alternatives.
 * All custom icons follow the design guidelines from TASK-28-15.
 *
 * @see TASK-28-15
 * @see TESTS/TEST-28-15.md
 *
 * @example
 * ```tsx
 * import { CUSTOM_ICONS, CustomIconName } from '@/components/icons/custom';
 *
 * // Check if a custom icon exists
 * const iconNames = Object.keys(CUSTOM_ICONS);
 *
 * // Use with IndicatorIcon
 * import { IndicatorIcon } from '@/components/indicators';
 * <IndicatorIcon customIcon={<CUSTOM_ICONS['icon-name'] />} />
 * ```
 */

// Export types
export type { CustomIconName, CustomIconProps } from './registry';

// Export registry
export { CUSTOM_ICONS, CustomIconNames } from './registry';

// Export individual icons here as they are created
// Example: export { RobotIndicatorIcon } from './robot-indicator';
