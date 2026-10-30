// Alert-related types

export interface AlertSettings {
  id: number;
  user_id: number;
  global_enabled: boolean;
  default_threshold: number;
  quiet_hours_start: string | null;
  quiet_hours_end: string | null;
  digest_mode: 'instant' | 'daily' | 'weekly';
  serp_feature_alerts_enabled: boolean;
  serp_feature_types: string[] | null;
  created_at: string;
  updated_at: string;
}

export interface KeywordAlertSettings {
  id: number;
  keyword_id: number;
  enabled: boolean;
  custom_threshold: number | null;
  alert_events: string[] | null;
  notification_channels: string[] | null;
  created_at: string;
  updated_at: string;
}

export interface AlertSettingsUpdateRequest {
  global_enabled?: boolean;
  default_threshold?: number;
  quiet_hours_start?: string | null;
  quiet_hours_end?: string | null;
  digest_mode?: 'instant' | 'daily' | 'weekly';
  serp_feature_alerts_enabled?: boolean;
  serp_feature_types?: string[] | null;
}

export interface KeywordAlertSettingsUpdateRequest {
  enabled?: boolean;
  custom_threshold?: number | null;
  alert_events?: string[] | null;
  notification_channels?: string[] | null;
}

export const ALERT_EVENT_TYPES = [
  'position_drop',
  'position_gain',
  'entered_top_10',
  'left_top_10',
  'entered_top_3',
  'left_top_3',
  'serp_feature_gained',
  'serp_feature_lost',
] as const;

export const NOTIFICATION_CHANNELS = [
  'email',
  'in_app',
] as const;

export const SERP_FEATURE_TYPES = [
  'featured_snippet',
  'local_pack',
  'knowledge_panel',
  'image_pack',
  'video_carousel',
  'people_also_ask',
  'related_searches',
  'sitelinks',
] as const;

export type AlertEventType = typeof ALERT_EVENT_TYPES[number];
export type NotificationChannel = typeof NOTIFICATION_CHANNELS[number];
export type SerpFeatureType = typeof SERP_FEATURE_TYPES[number];
