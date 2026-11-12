/**
 * Shared SWR options for requests that may be answered with 401.
 *
 * A guest-viewable page asks the API for data that belongs to an account.
 * The API answers 401, which is the expected response - not a failure to
 * retry. SWR retries indefinitely on error by default, so without this the
 * request would be reissued forever with exponential backoff, and every
 * attempt would trip the 401 interceptor again.
 *
 * A 401 is therefore terminal: the page shows its sign-in prompt instead of
 * hammering the API.
 */

/**
 * True when the error is the API refusing to serve account data.
 */
export function isUnauthorizedError(error: unknown): boolean {
  if (!error) return false;
  const err = error as { status?: number; message?: string };
  if (err.status === 401) return true;
  return typeof err.message === "string" && /401|unauthenticated|unauthorized/i.test(err.message);
}

/**
 * SWR options for an endpoint that may return 401.
 *
 * Retries other errors a few times (a transient 500 is worth another go),
 * but never retries a 401.
 */
export const swrAuthOptions = {
  shouldRetryOnError: (error: unknown) => !isUnauthorizedError(error),
  errorRetryCount: 3,
  revalidateOnFocus: false,
  revalidateIfStale: false,
} as const;
