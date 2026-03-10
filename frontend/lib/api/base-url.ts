const DEFAULT_API_BASE = "/api/v1";
const API_PREFIX = "/api";
const API_VERSION_PATH = "/api/v1";

const isAbsoluteUrl = (value: string) => /^https?:\/\//i.test(value);

const trimTrailingSlash = (value: string) => value.replace(/\/+$/, "");

const ensureLeadingSlash = (value: string) => (value.startsWith("/") ? value : `/${value}`);

const normalizeEndpointPath = (path: string) => {
  if (!path) {
    return "";
  }

  if (isAbsoluteUrl(path)) {
    return path;
  }

  return ensureLeadingSlash(path);
};

const appendApiVersion = (path: string) => {
  const normalizedPath = trimTrailingSlash(path) || "/";

  if (normalizedPath === "/") {
    return API_VERSION_PATH;
  }

  if (normalizedPath.endsWith(API_VERSION_PATH)) {
    return normalizedPath;
  }

  if (normalizedPath.endsWith(API_PREFIX)) {
    return `${normalizedPath}/v1`;
  }

  return `${normalizedPath}${API_VERSION_PATH}`;
};

const stripApiVersion = (path: string) => {
  const normalizedPath = trimTrailingSlash(path) || "/";

  if (normalizedPath === API_VERSION_PATH || normalizedPath === API_PREFIX) {
    return "/";
  }

  if (normalizedPath.endsWith(API_VERSION_PATH)) {
    return normalizedPath.slice(0, -API_VERSION_PATH.length) || "/";
  }

  if (normalizedPath.endsWith(API_PREFIX)) {
    return normalizedPath.slice(0, -API_PREFIX.length) || "/";
  }

  return normalizedPath;
};

const formatApiRoot = (path: string) => {
  const normalizedPath = trimTrailingSlash(path);
  return normalizedPath && normalizedPath !== "/" ? normalizedPath : "";
};

export function getApiBaseUrl(): string {
  const configuredValue = process.env.NEXT_PUBLIC_API_URL?.trim();

  if (!configuredValue) {
    return DEFAULT_API_BASE;
  }

  if (isAbsoluteUrl(configuredValue)) {
    const url = new URL(configuredValue);
    return `${url.origin}${appendApiVersion(url.pathname || "/")}`;
  }

  return appendApiVersion(ensureLeadingSlash(configuredValue));
}

export function buildApiUrl(path: string): string {
  if (isAbsoluteUrl(path)) {
    return path;
  }

  const normalizedPath = normalizeEndpointPath(path);
  return normalizedPath ? `${getApiBaseUrl()}${normalizedPath}` : getApiBaseUrl();
}

export function getApiOrigin(): string {
  const apiBase = getApiBaseUrl();

  if (isAbsoluteUrl(apiBase)) {
    return new URL(apiBase).origin;
  }

  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  return "";
}

export function getApiRootUrl(): string {
  const apiBase = getApiBaseUrl();

  if (isAbsoluteUrl(apiBase)) {
    const url = new URL(apiBase);
    return `${url.origin}${formatApiRoot(stripApiVersion(url.pathname || "/"))}`;
  }

  return formatApiRoot(stripApiVersion(apiBase));
}

export function buildSanctumUrl(path = "/sanctum/csrf-cookie"): string {
  if (isAbsoluteUrl(path)) {
    return path;
  }

  const normalizedPath = normalizeEndpointPath(path);
  const apiRoot = getApiRootUrl();

  return apiRoot ? `${apiRoot}${normalizedPath}` : normalizedPath;
}

export { DEFAULT_API_BASE };
