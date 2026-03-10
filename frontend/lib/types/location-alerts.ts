export type LocalSeoAlertSeverity = "info" | "warning" | "critical";
export type LocalSeoAlertType =
  | "ranking_improved"
  | "ranking_dropped"
  | "new_review"
  | "negative_review"
  | "rating_dropped"
  | "competitor_overtake"
  | "gbp_disconnected";

export interface LocalSeoAlertLocation {
  id: string;
  name: string;
}

export interface LocalSeoAlertRelatedData {
  label: string;
  value: string;
}

export interface LocalSeoAlertQuickAction {
  label: string;
  href: string;
}

export interface LocalSeoAlert {
  id: string;
  numericId: number | null;
  type: LocalSeoAlertType;
  severity: LocalSeoAlertSeverity;
  title: string;
  message: string;
  isRead: boolean;
  readAt: string | null;
  createdAt: string;
  location: LocalSeoAlertLocation | null;
  relatedData: LocalSeoAlertRelatedData[];
  quickAction: LocalSeoAlertQuickAction | null;
}

export interface LocalSeoAlertListMeta {
  currentPage: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface LocalSeoAlertListResponse {
  alerts: LocalSeoAlert[];
  meta: LocalSeoAlertListMeta;
}

export interface LocalSeoUnreadCount {
  total: number;
  bySeverity: Record<LocalSeoAlertSeverity, number>;
}

export type LocalSeoAlertReadStatus = "all" | "read" | "unread";
export type LocalSeoAlertSort = "-created_at" | "created_at";

export interface LocalSeoAlertFilterState {
  locationId: string;
  severity: "all" | LocalSeoAlertSeverity;
  type: "all" | LocalSeoAlertType;
  readStatus: LocalSeoAlertReadStatus;
  sort: LocalSeoAlertSort;
}

export const LOCAL_SEO_ALERT_PAGE_SIZE = 15;

export const defaultLocalSeoAlertFilters: LocalSeoAlertFilterState = {
  locationId: "",
  severity: "all",
  type: "all",
  readStatus: "all",
  sort: "-created_at",
};

export const LOCAL_SEO_ALERT_TYPE_CONFIG: Record<
  LocalSeoAlertType,
  {
    label: string;
    iconLabel: string;
    colorClass: string;
    defaultQuickActionLabel: string;
  }
> = {
  ranking_improved: {
    label: "Ranking improved",
    iconLabel: "Arrow up",
    colorClass: "text-emerald-400",
    defaultQuickActionLabel: "View rankings",
  },
  ranking_dropped: {
    label: "Ranking dropped",
    iconLabel: "Arrow down",
    colorClass: "text-red-400",
    defaultQuickActionLabel: "View rankings",
  },
  new_review: {
    label: "New review",
    iconLabel: "Message square",
    colorClass: "text-sky-400",
    defaultQuickActionLabel: "View reviews",
  },
  negative_review: {
    label: "Negative review",
    iconLabel: "Alert triangle",
    colorClass: "text-orange-400",
    defaultQuickActionLabel: "View reviews",
  },
  rating_dropped: {
    label: "Rating dropped",
    iconLabel: "Star",
    colorClass: "text-rose-400",
    defaultQuickActionLabel: "View reviews",
  },
  competitor_overtake: {
    label: "Competitor overtake",
    iconLabel: "Users",
    colorClass: "text-yellow-300",
    defaultQuickActionLabel: "View rankings",
  },
  gbp_disconnected: {
    label: "GBP disconnected",
    iconLabel: "Link off",
    colorClass: "text-red-500",
    defaultQuickActionLabel: "Reconnect GBP",
  },
};

export const LOCAL_SEO_ALERT_SEVERITY_LABELS: Record<LocalSeoAlertSeverity, string> = {
  info: "Info",
  warning: "Warning",
  critical: "Critical",
};
