/**
 * Audit Settings API
 * 
 * Task: TASK-33-22 - Add Visibility Toggle to Audit Settings
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 * 
 * API functions for fetching and managing audit settings,
 * including visibility toggle (publish/unpublish).
 */

import useSWR, { mutate as globalMutate } from 'swr';

const API_BASE = '/api/v1';

// ============================================================
// Types
// ============================================================

export interface AuditSettings {
  id: number;
  hash: string;
  url: string;
  score: number;
  isPublic: boolean;
  publicHash: string | null;
  publicUrl: string | null;
  createdAt: string;
  owner: {
    id: number;
    name: string;
    email: string;
  };
}

export interface AuditSettingsResponse {
  data: AuditSettings;
  isOwner: boolean;
}

// ============================================================
// Fetcher
// ============================================================

const fetcher = async <T>(url: string): Promise<T> => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthorized');
    }
    if (response.status === 404) {
      throw new Error('Not found');
    }
    if (response.status === 403) {
      throw new Error('Access denied');
    }
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? `HTTP ${response.status}`);
  }

  const payload = await response.json();
  return payload;
};

// ============================================================
// Normalizer
// ============================================================

const normalizeAuditSettings = (payload: any): AuditSettingsResponse => {
  const data = payload?.data ?? payload;
  
  return {
    data: {
      id: data.id,
      hash: data.public_hash ?? data.hash ?? '',
      url: data.url ?? data.site?.url ?? '',
      score: data.score ?? 0,
      isPublic: data.visibility === 'public' || data.is_public === true,
      publicHash: data.public_hash ?? null,
      publicUrl: data.public_url ?? null,
      createdAt: data.created_at ?? data.createdAt,
      owner: {
        id: data.owner?.id ?? data.user_id ?? 0,
        name: data.owner?.name ?? 'Unknown',
        email: data.owner?.email ?? '',
      },
    },
    isOwner: payload?.isOwner ?? true,
  };
};

// ============================================================
// Hooks
// ============================================================

/**
 * Hook for fetching audit settings
 */
export function useAuditSettings(auditId: string, enabled = true) {
  const key = enabled && auditId ? `${API_BASE}/audits/${auditId}` : null;

  const { data, error, isLoading, mutate } = useSWR<any>(key, fetcher);

  const normalized = data ? normalizeAuditSettings(data) : null;

  return {
    audit: normalized?.data ?? null,
    isLoading,
    error,
    isOwner: normalized?.isOwner ?? true,
    mutate,
    refetch: () => mutate(),
  };
}

/**
 * Hook for publishing an audit
 */
export function usePublishAudit() {
  const publish = async (auditId: number | string): Promise<{ success: boolean; publicHash: string; publicUrl: string }> => {
    const response = await fetch(`${API_BASE}/audits/${auditId}/publish`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Unauthorized');
      }
      if (response.status === 403) {
        throw new Error('Access denied');
      }
      if (response.status === 404) {
        throw new Error('Audit not found');
      }
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? 'Failed to publish audit');
    }

    const payload = await response.json();
    
    // Invalidate audit cache
    await globalMutate((key) => typeof key === 'string' && key.includes(`/audits/${auditId}`));
    
    return {
      success: true,
      publicHash: payload?.data?.public_hash ?? payload?.public_hash ?? '',
      publicUrl: payload?.data?.public_url ?? payload?.public_url ?? '',
    };
  };

  return { publish };
}

/**
 * Hook for unpublishing an audit
 */
export function useUnpublishAudit() {
  const unpublish = async (auditId: number | string): Promise<{ success: boolean }> => {
    const response = await fetch(`${API_BASE}/audits/${auditId}/unpublish`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Unauthorized');
      }
      if (response.status === 403) {
        throw new Error('Access denied');
      }
      if (response.status === 404) {
        throw new Error('Audit not found');
      }
      const errorBody = await response.json().catch(() => null);
      throw new Error(errorBody?.message ?? 'Failed to unpublish audit');
    }

    // Invalidate audit cache
    await globalMutate((key) => typeof key === 'string' && key.includes(`/audits/${auditId}`));
    
    return { success: true };
  };

  return { unpublish };
}

// ============================================================
// Direct API Functions (for use outside of hooks)
// ============================================================

/**
 * Publish an audit directly
 */
export async function publishAudit(auditId: number | string): Promise<{ success: boolean; publicHash: string; publicUrl: string }> {
  const response = await fetch(`${API_BASE}/audits/${auditId}/publish`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? 'Failed to publish audit');
  }

  const payload = await response.json();
  
  // Invalidate cache
  await globalMutate((key) => typeof key === 'string' && key.includes(`/audits/${auditId}`));
  
  return {
    success: true,
    publicHash: payload?.data?.public_hash ?? payload?.public_hash ?? '',
    publicUrl: payload?.data?.public_url ?? payload?.public_url ?? '',
  };
}

/**
 * Unpublish an audit directly
 */
export async function unpublishAudit(auditId: number | string): Promise<{ success: boolean }> {
  const response = await fetch(`${API_BASE}/audits/${auditId}/unpublish`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? 'Failed to unpublish audit');
  }

  // Invalidate cache
  await globalMutate((key) => typeof key === 'string' && key.includes(`/audits/${auditId}`));
  
  return { success: true };
}
