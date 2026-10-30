// Notification-related types

export interface Notification {
  id: number;
  user_id: number;
  type: NotificationType;
  keyword_id: number | null;
  data: NotificationData;
  read_at: string | null;
  created_at: string;
  keyword?: {
    id: number;
    keyword: string;
  } | null;
}

export interface NotificationData {
  keyword?: string;
  old_position?: number | null;
  new_position?: number | null;
  change?: number | null;
  feature_type?: string | null;
  threshold?: number | null;
}

export type NotificationType =
  | 'position_drop'
  | 'position_gain'
  | 'entered_top_10'
  | 'left_top_10'
  | 'entered_top_3'
  | 'left_top_3'
  | 'serp_feature_gained'
  | 'serp_feature_lost';

export interface NotificationsListResponse {
  data: Notification[];
  unread_count: number;
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface UnreadCountResponse {
  count: number;
}

export interface NotificationsQueryParams {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
}

export const NOTIFICATION_TYPE_LABELS: Record<NotificationType, string> = {
  position_drop: 'Position Dropped',
  position_gain: 'Position Improved',
  entered_top_10: 'Entered Top 10',
  left_top_10: 'Left Top 10',
  entered_top_3: 'Entered Top 3',
  left_top_3: 'Left Top 3',
  serp_feature_gained: 'SERP Feature Gained',
  serp_feature_lost: 'SERP Feature Lost',
};

export const NOTIFICATION_TYPE_ICONS: Record<NotificationType, string> = {
  position_drop: 'trending_down',
  position_gain: 'trending_up',
  entered_top_10: 'arrow_upward',
  left_top_10: 'arrow_downward',
  entered_top_3: 'star',
  left_top_3: 'star_border',
  serp_feature_gained: 'add_circle',
  serp_feature_lost: 'remove_circle',
};
