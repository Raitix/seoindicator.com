/**
 * AI Visibility Types
 * Task: TASK-34-21 - Frontend Alert Notifications
 * Spec: SPEC-034 (AI Visibility Tracking)
 */

export type AlertType = 'mention_spike' | 'mention_drop' | 'competitor_gain' | 'sentiment_change';

export interface Alert {
  id: string;
  project_id: string;
  project_name: string;
  alert_type: AlertType;
  message: string;
  previous_value: number;
  current_value: number;
  percentage_change: number;
  is_read: boolean;
  is_resolved: boolean;
  created_at: string;
  resolved_at: string | null;
  recommendations: string[];
}

export interface AlertPreferences {
  email_notifications: boolean;
  webhook_url: string | null;
  alert_types: AlertType[];
}

export interface AlertsListResponse {
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
  data: Alert;
}

export interface AlertPreferencesResponse {
  data: AlertPreferences;
}
