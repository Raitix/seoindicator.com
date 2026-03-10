/**
 * Content Decay API Hooks
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * React hooks for interacting with the Content Decay API endpoints.
 * These hooks use SWR for caching and automatic revalidation.
 * 
 * NOTE: This is an interface file for test mocking purposes.
 * The actual implementation will be created by the Developer.
 */

import useSWR, { SWRResponse, MutatorCallback } from 'swr';
import { mutate } from 'swr';
import type {
  DecaySummaryData,
  DecayCandidate,
  DecayCandidateListResponse,
  DecayCandidateDetailResponse,
  ContentRefreshTask,
  RecoveryComparison,
  AiRefreshBrief,
  ContentDecayCandidateStatus,
  DecayQueueFilterState,
} from '@/lib/types/content-decay';

// ============== Types ==============

interface UseContentDecaySummaryReturn {
  summary: DecaySummaryData | null;
  isLoading: boolean;
  error: Error | null;
  mutate: (data?: DecaySummaryData | MutatorCallback<DecaySummaryData>, shouldRevalidate?: boolean) => Promise<void>;
}

interface UseContentDecayCandidatesReturn {
  candidates: DecayCandidate[];
  meta: DecayCandidateListResponse['meta'] | null;
  isLoading: boolean;
  error: Error | null;
  mutate: (data?: DecayCandidateListResponse | MutatorCallback<DecayCandidateListResponse>, shouldRevalidate?: boolean) => Promise<void>;
}

interface UseContentDecayCandidateReturn {
  candidate: DecayCandidate | null;
  isLoading: boolean;
  error: Error | null;
  mutate: (data?: DecayCandidateDetailResponse | MutatorCallback<DecayCandidateDetailResponse>, shouldRevalidate?: boolean) => Promise<void>;
}

interface UseContentDecaySettingsReturn {
  settings: ContentDecayProjectSettings | null;
  isLoading: boolean;
  error: Error | null;
  mutate: (data?: ContentDecayProjectSettings | MutatorCallback<ContentDecayProjectSettings>, shouldRevalidate?: boolean) => Promise<void>;
}

interface UseContentDecayRecoveryReturn {
  recovery: RecoveryData | null;
  isLoading: boolean;
  error: Error | null;
  mutate: (data?: RecoveryData | MutatorCallback<RecoveryData>, shouldRevalidate?: boolean) => Promise<void>;
}

interface ContentDecayProjectSettings {
  detectionEnabled: boolean;
  windowDays: number;
  minClicksThreshold: number;
  minImpressionsThreshold: number;
  digestEnabled: boolean;
  digestFrequency: 'weekly' | 'monthly';
}

interface RecoveryData {
  summary: {
    totalRecovered: number;
    totalClicksRecovered: number;
    avgRecoveryRate: number;
    avgDaysToRecovery: number;
  };
  timeline: Array<{
    date: string;
    recovered: number;
    clicks: number;
  }>;
  comparisons: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    pageType: string;
    before: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
    after: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
    delta: {
      clicks: number;
      impressions: number;
      ctr: number;
      position: number;
    };
  }>;
  stories: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    clicksRecovered: number;
    recoveryRate: number;
    daysToRecovery: number;
    summary: string;
  }>;
}

interface RunScanResult {
  success: boolean;
  jobId?: string;
  message?: string;
}

interface UpdateStatusResult {
  success: boolean;
  candidate?: DecayCandidate;
}

interface ExportResult {
  success: boolean;
  downloadUrl?: string;
}

interface SaveSettingsResult {
  success: boolean;
  settings?: ContentDecayProjectSettings;
}

// ============== API Fetchers ==============

const fetcher = async <T>(url: string): Promise<T> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`API Error: ${response.status}`);
  }
  return response.json();
};

// ============== Hooks ==============

/**
 * Fetch content decay summary for a site
 */
export function useContentDecaySummary(siteId: string | number): UseContentDecaySummaryReturn {
  const { data, error, isLoading, mutate } = useSWR<DecaySummaryData>(
    `/api/v1/sites/${siteId}/content-decay/summary`,
    fetcher
  );

  return {
    summary: data ?? null,
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Fetch paginated list of decay candidates
 */
export function useContentDecayCandidates(
  siteId: string | number,
  filters?: Partial<DecayQueueFilterState>
): UseContentDecayCandidatesReturn {
  const params = new URLSearchParams();
  
  if (filters?.status && filters.status !== 'all') {
    params.append('status', filters.status);
  }
  if (filters?.severity && filters.severity !== 'all') {
    params.append('severity', filters.severity);
  }
  if (filters?.pageType && filters.pageType !== 'all') {
    params.append('page_type', filters.pageType);
  }
  if (filters?.search) {
    params.append('search', filters.search);
  }
  if (filters?.sort) {
    params.append('sort', filters.sort);
  }
  if (filters?.order) {
    params.append('order', filters.order);
  }

  const queryString = params.toString();
  const url = `/api/v1/sites/${siteId}/content-decay/candidates${queryString ? `?${queryString}` : ''}`;

  const { data, error, isLoading, mutate } = useSWR<DecayCandidateListResponse>(url, fetcher);

  return {
    candidates: data?.data ?? [],
    meta: data?.meta ?? null,
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Fetch single candidate detail
 */
export function useContentDecayCandidate(candidateId: string | number): UseContentDecayCandidateReturn {
  const { data, error, isLoading, mutate } = useSWR<DecayCandidateDetailResponse>(
    `/api/v1/content-decay/candidates/${candidateId}`,
    fetcher
  );

  return {
    candidate: data?.candidate ?? null,
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Fetch content decay project settings
 */
export function useContentDecaySettings(siteId: string | number): UseContentDecaySettingsReturn {
  const { data, error, isLoading, mutate } = useSWR<ContentDecayProjectSettings>(
    `/api/v1/sites/${siteId}/content-decay/settings`,
    fetcher
  );

  return {
    settings: data ?? null,
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Fetch recovery report data
 */
export function useContentDecayRecovery(
  siteId: string | number,
  dateRange?: { start: string; end: string }
): UseContentDecayRecoveryReturn {
  const params = new URLSearchParams();
  
  if (dateRange) {
    params.append('start', dateRange.start);
    params.append('end', dateRange.end);
  }

  const queryString = params.toString();
  const url = `/api/v1/sites/${siteId}/content-decay/recovery${queryString ? `?${queryString}` : ''}`;

  const { data, error, isLoading, mutate } = useSWR<RecoveryData>(url, fetcher);

  return {
    recovery: data ?? null,
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Run manual content decay scan
 */
export function useRunContentDecayScan(siteId: string | number) {
  let isRunning = false;

  const runScan = async (): Promise<RunScanResult> => {
    isRunning = true;
    try {
      const response = await fetch(`/api/v1/sites/${siteId}/content-decay/run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, jobId: data.job_id };
    } catch (error) {
      return { success: false, message: (error as Error).message };
    } finally {
      isRunning = false;
    }
  };

  return { runScan, isRunning };
}

/**
 * Update candidate status
 */
export function useUpdateCandidateStatus(candidateId: string | number) {
  let isUpdating = false;

  const updateStatus = async (status: ContentDecayCandidateStatus): Promise<UpdateStatusResult> => {
    isUpdating = true;
    try {
      const response = await fetch(`/api/v1/content-decay/candidates/${candidateId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status }),
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, candidate: data.candidate };
    } catch (error) {
      return { success: false };
    } finally {
      isUpdating = false;
    }
  };

  return { updateStatus, isUpdating };
}

/**
 * Export content decay data
 */
export function useExportContentDecay(siteId: string | number) {
  let isExporting = false;

  const exportData = async (format: 'csv' | 'pdf'): Promise<ExportResult> => {
    isExporting = true;
    try {
      const response = await fetch(`/api/v1/sites/${siteId}/content-decay/export?format=${format}`);
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      // For file downloads, we typically get a blob
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      
      // Trigger download
      const a = document.createElement('a');
      a.href = url;
      a.download = `content-decay-${siteId}.${format}`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      return { success: true };
    } catch (error) {
      return { success: false };
    } finally {
      isExporting = false;
    }
  };

  return { exportData, isExporting };
}

/**
 * Save content decay settings
 */
export function useSaveContentDecaySettings(siteId: string | number) {
  let isSaving = false;

  const saveSettings = async (settings: Partial<ContentDecayProjectSettings>): Promise<SaveSettingsResult> => {
    isSaving = true;
    try {
      const response = await fetch(`/api/v1/sites/${siteId}/content-decay/settings`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings),
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, settings: data.settings };
    } catch (error) {
      return { success: false };
    } finally {
      isSaving = false;
    }
  };

  return { saveSettings, isSaving };
}

/**
 * Request AI refresh brief for a candidate
 */
export function useGenerateAiBrief(candidateId: string | number) {
  let isGenerating = false;

  const generateBrief = async (): Promise<{ success: boolean; brief?: AiRefreshBrief }> => {
    isGenerating = true;
    try {
      const response = await fetch(`/api/v1/content-decay/candidates/${candidateId}/ai-brief`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, brief: data.brief };
    } catch (error) {
      return { success: false };
    } finally {
      isGenerating = false;
    }
  };

  return { generateBrief, isGenerating };
}

/**
 * Manage refresh tasks for a candidate
 */
export function useContentRefreshTasks(candidateId: string | number) {
  const { data, error, isLoading, mutate } = useSWR<ContentRefreshTask[]>(
    `/api/v1/content-decay/candidates/${candidateId}/tasks`,
    fetcher
  );

  return {
    tasks: data ?? [],
    isLoading,
    error: error ?? null,
    mutate: async (newData: any, shouldRevalidate?: boolean) => {
      await mutate(newData as any, shouldRevalidate);
    },
  };
}

/**
 * Create a new refresh task
 */
export function useCreateRefreshTask(candidateId: string | number) {
  let isCreating = false;

  const createTask = async (task: {
    title: string;
    description?: string;
    dueDate?: string;
    ownerUserId?: number;
  }): Promise<{ success: boolean; task?: ContentRefreshTask }> => {
    isCreating = true;
    try {
      const response = await fetch(`/api/v1/content-decay/candidates/${candidateId}/tasks`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(task),
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, task: data.task };
    } catch (error) {
      return { success: false };
    } finally {
      isCreating = false;
    }
  };

  return { createTask, isCreating };
}

/**
 * Restore an ignored candidate to backlog
 */
export function useRestoreCandidate() {
  let isRestoring = false;

  const restore = async (candidateId: string | number): Promise<UpdateStatusResult> => {
    isRestoring = true;
    try {
      const response = await fetch(`/api/v1/content-decay/candidates/${candidateId}/restore`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      
      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }
      
      const data = await response.json();
      return { success: true, candidate: data.candidate };
    } catch (error) {
      return { success: false };
    } finally {
      isRestoring = false;
    }
  };

  return { restore, isRestoring };
}
