/**
 * Hooks Index - TASK-28-11
 *
 * Export all custom hooks from a single entry point.
 */

export { useLoadingState, type LoadingState, type UseLoadingStateOptions, type UseLoadingStateReturn } from "./useLoadingState";
export { useOptimisticUpdate, type OptimisticUpdateState, type UseOptimisticUpdateOptions, type UseOptimisticUpdateReturn } from "./useOptimisticUpdate";

// Re-export existing hooks
export { useDashboard, type UseDashboardReturn } from "./useDashboard";
export { useAnalysisHistory } from "./useAnalysisHistory";
export { useAnalysisProgress } from "./useAnalysisProgress";
export { useConsent } from "./useConsent";
