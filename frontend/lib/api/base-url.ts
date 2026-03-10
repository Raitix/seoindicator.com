const DEFAULT_API_BASE = "/api/v1";

const normalizePath = (path: string) => (path.startsWith("/") ? path : `/${path}`);

export function getApiBaseUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL || DEFAULT_API_BASE;
}

export function buildApiUrl(path: string): string {
  return `${getApiBaseUrl()}${normalizePath(path)}`;
}

export function getApiOrigin(): string {
  const apiBase = getApiBaseUrl();

  if (apiBase.startsWith("http://") || apiBase.startsWith("https://")) {
    return new URL(apiBase).origin;
  }

  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  return "";
}

export function buildSanctumUrl(path = "/sanctum/csrf-cookie"): string {
  return `${getApiOrigin()}${normalizePath(path)}`;
}

export { DEFAULT_API_BASE };
