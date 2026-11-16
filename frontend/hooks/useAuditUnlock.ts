/**
 * useAuditUnlock
 * Task: TASK-33-23
 * Spec: SPEC-33 - Public SEO Audit Reports & Embeddable Score Widget
 *
 * Manages the unlock state of a public audit: whether the visitor has
 * unlocked the full report by submitting their email, whether they have
 * chosen to skip it, and the error state of the submission.
 *
 * The unlock itself is a lead submission to
 * `POST /api/v1/public-audit/{hash}/lead`. On success the visitor is
 * unlocked through the cookie helpers in `lib/audit/public-audit.ts`,
 * which is the same mechanism `components/audit/PublicAuditCTA.tsx`
 * uses, so the hook and the component stay consistent.
 */

import { useCallback, useEffect, useState } from 'react';

import {
  setUnlockedAudit,
  isAuditUnlocked,
  clearUnlockCookie,
  storeEmailCaptured,
} from '@/lib/audit/public-audit';

export interface UseAuditUnlockOptions {
  auditHash: string;
  autoCheck?: boolean;
}

export interface UseAuditUnlockReturn {
  isUnlocked: boolean;
  isSkipped: boolean;
  isLoading: boolean;
  error: string | null;
  unlock: (email: string) => Promise<boolean>;
  skip: () => void;
  checkUnlockStatus: () => Promise<void>;
  clearError: () => void;
}

/** The key the "skipped" flag is stored under in session storage. */
const SKIP_STORAGE_PREFIX = 'audit_skipped_';

/** How long the skip is remembered, in milliseconds (24 hours). */
const SKIP_TTL_MS = 24 * 60 * 60 * 1000;

function skipKey(hash: string): string {
  return `${SKIP_STORAGE_PREFIX}${hash}`;
}

function readSkipped(hash: string): boolean {
  try {
    const raw = sessionStorage.getItem(skipKey(hash));
    if (!raw) return false;

    const parsed = JSON.parse(raw) as { timestamp: number };
    if (Date.now() - parsed.timestamp > SKIP_TTL_MS) {
      sessionStorage.removeItem(skipKey(hash));
      return false;
    }
    return true;
  } catch {
    return false;
  }
}

function writeSkipped(hash: string): void {
  try {
    sessionStorage.setItem(
      skipKey(hash),
      JSON.stringify({ timestamp: Date.now() })
    );
  } catch {
    // sessionStorage can be unavailable (private mode, disabled storage).
    // Skipping is a convenience, so a failure here is not fatal.
  }
}

function clearSkipped(hash: string): void {
  try {
    sessionStorage.removeItem(skipKey(hash));
  } catch {
    // Nothing to do - see writeSkipped.
  }
}

/**
 * Reads the unlock state for an audit.
 *
 * The state lives in a cookie (see lib/audit/public-audit.ts), so it is
 * readable on the server as well as in the browser.
 */
export function useAuditUnlock(options: UseAuditUnlockOptions): UseAuditUnlockReturn {
  const { auditHash, autoCheck = true } = options;

  const [isUnlocked, setIsUnlocked] = useState<boolean>(() =>
    auditHash ? isAuditUnlocked(auditHash) : false
  );
  const [isSkipped, setIsSkipped] = useState<boolean>(() =>
    auditHash ? readSkipped(auditHash) : false
  );
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // A different audit hash means a different report, so the state has to
  // be read again.
  useEffect(() => {
    setIsUnlocked(auditHash ? isAuditUnlocked(auditHash) : false);
    setIsSkipped(auditHash ? readSkipped(auditHash) : false);
    setError(null);
  }, [auditHash]);

  const unlock = useCallback(
    async (email: string): Promise<boolean> => {
      if (!auditHash) {
        setError('No audit to unlock');
        return false;
      }

      setIsLoading(true);
      setError(null);

      try {
        const response = await fetch(
          `/api/v1/public-audit/${auditHash}/lead`,
          {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email.trim() }),
          }
        );

        if (!response.ok) {
          if (response.status === 429) {
            throw new Error('Too many requests, try later');
          }
          if (response.status === 404) {
            throw new Error('Audit not found');
          }

          // The backend may send a message; fall back to a generic one.
          let message = 'Failed to unlock';
          try {
            const data = await response.json();
            if (data && typeof data.message === 'string' && data.message) {
              message = data.message;
            } else if (data && typeof data.error === 'string' && data.error) {
              message = data.error;
            }
          } catch {
            // The body was not JSON - keep the generic message.
          }
          throw new Error(message);
        }

        setUnlockedAudit(auditHash, email.trim());
        storeEmailCaptured(email.trim());
        clearSkipped(auditHash);
        setIsUnlocked(true);
        setIsSkipped(false);
        return true;
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to unlock');
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [auditHash]
  );

  const skip = useCallback(() => {
    if (!auditHash) return;
    writeSkipped(auditHash);
    setIsSkipped(true);
  }, [auditHash]);

  const checkUnlockStatus = useCallback(async () => {
    if (!auditHash) return;
    setIsLoading(true);
    try {
      setIsUnlocked(isAuditUnlocked(auditHash));
      setIsSkipped(readSkipped(auditHash));
    } finally {
      setIsLoading(false);
    }
  }, [auditHash]);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // `autoCheck` re-reads the state when the hook mounts, so a visitor
  // who unlocked the report on an earlier page load is still unlocked.
  useEffect(() => {
    if (autoCheck && auditHash) {
      void checkUnlockStatus();
    }
  }, [autoCheck, auditHash, checkUnlockStatus]);

  return {
    isUnlocked,
    isSkipped,
    isLoading,
    error,
    unlock,
    skip,
    checkUnlockStatus,
    clearError,
  };
}

export default useAuditUnlock;

// Referenced so the unused-import check does not drop the cookie helper
// that callers use to reset an unlock.
export { clearUnlockCookie };
