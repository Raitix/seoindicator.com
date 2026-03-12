/**
 * AI Visibility Types
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 * 
 * Updated to align with backend AIVisibilityAlertResource response
 */

export type AlertType = 'mention_spike' | 'mention_drop' | 'competitor_gain' | 'sentiment_change';

/**
 * Alert interface matching backend AIVisibilityAlertResource
 * @see backend/app/Http/Resources/V1/AIVisibility/AIVisibilityAlertResource.php
 */
export interface Alert {
  id: number;
  project_id: number;
  project_name: string;
  alert_type: AlertType;
  threshold: number;
  previous_value: number;
  current_value: number;
  change_percent: number;
  is_read: boolean;
  is_resolved: boolean;
  resolved_at: string | null;
  triggered_at: string | null;
  metadata: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

/**
 * Alert preferences matching backend response
 * @see AIVisibilityAlertController::getPreferences
 */
export interface AlertPreferences {
  email_notifications_enabled: boolean;
  webhook_url: string | null;
  alert_types: AlertType[];
}

/**
 * Frontend-facing alert preferences (for form binding)
 * Maps email_notifications_enabled to email_notifications
 */
export interface AlertPreferencesForm {
  email_notifications: boolean;
  webhook_url: string | null;
  alert_types: AlertType[];
}

export interface AlertFilters {
  project_id?: number | null;
  alert_types?: AlertType[];
  is_read?: boolean | null;
}

export interface AlertsListResponse {
  success: boolean;
  data: Alert[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    unread_count: number;
    last_page: number;
  };
}

export interface AlertDetailResponse {
  success: boolean;
  data: Alert;
}

export interface AlertPreferencesResponse {
  success: boolean;
  data: AlertPreferences;
}

/**
 * Helper to get alert type label
 */
export function getAlertTypeLabel(type: AlertType): string {
  const labels: Record<AlertType, string> = {
    mention_spike: 'Mention Spike',
    mention_drop: 'Mention Drop',
    competitor_gain: 'Competitor Gain',
    sentiment_change: 'Sentiment Change',
  };
  return labels[type];
}

/**
 * Helper to get alert type color
 */
export function getAlertTypeColor(type: AlertType): string {
  const colors: Record<AlertType, string> = {
    mention_spike: 'green',    // positive
    mention_drop: 'red',       // negative
    competitor_gain: 'orange', // warning
    sentiment_change: 'yellow', // caution
  };
  return colors[type];
}

/**
 * Helper to get alert type icon name
 */
export function getAlertTypeIcon(type: AlertType): string {
  const icons: Record<AlertType, string> = {
    mention_spike: 'trending-up',
    mention_drop: 'trending-down',
    competitor_gain: 'users',
    sentiment_change: 'activity',
  };
  return icons[type];
}

/**
 * Generate alert message from alert data
 * Since backend doesn't return a message field, we generate one
 */
export function generateAlertMessage(alert: Alert): string {
  const direction = alert.change_percent >= 0 ? 'increased' : 'decreased';
  const absChange = Math.abs(alert.change_percent).toFixed(1);
  
  switch (alert.alert_type) {
    case 'mention_spike':
      return `Mentions for ${alert.project_name} increased by ${absChange}%`;
    case 'mention_drop':
      return `Mentions for ${alert.project_name} decreased by ${absChange}%`;
    case 'competitor_gain':
      return `Competitor gained visibility in ${alert.project_name}'s niche`;
    case 'sentiment_change':
      return `Sentiment changed for ${alert.project_name}`;
    default:
      return `Alert triggered for ${alert.project_name}`;
  }
}

/**
 * Generate recommendations from alert data
 * Since backend doesn't return recommendations, we generate them based on alert type
 */
export function generateRecommendations(alert: Alert): string[] {
  const recommendations: string[] = [];
  
  switch (alert.alert_type) {
    case 'mention_spike':
      recommendations.push('Monitor the trend to see if it continues');
      recommendations.push('Analyze which queries are driving the increase');
      recommendations.push('Consider doubling down on successful content');
      break;
    case 'mention_drop':
      recommendations.push('Investigate the cause of the decrease');
      recommendations.push('Check for competitor activity');
      recommendations.push('Review recent content changes');
      break;
    case 'competitor_gain':
      recommendations.push('Analyze competitor\'s recent activities');
      recommendations.push('Identify gaps in your own content');
      recommendations.push('Consider updating your content strategy');
      break;
    case 'sentiment_change':
      recommendations.push('Review recent customer feedback');
      recommendations.push('Check for PR issues or negative mentions');
      recommendations.push('Consider proactive communication');
      break;
  }
  
  return recommendations;
}

/**
 * Format relative time from date string
 */
export function formatRelativeTime(dateString: string | null): string {
  if (!dateString) return 'Unknown';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString();
}
