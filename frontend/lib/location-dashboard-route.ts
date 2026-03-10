const PREVIEW_SEGMENT = "preview";

type RouteParamValue = string | string[] | null | undefined;

const normalizeRouteParam = (value: RouteParamValue): string | null => {
  if (Array.isArray(value)) {
    return normalizeRouteParam(value[0]);
  }

  if (typeof value !== "string") {
    return null;
  }

  const normalized = value.trim();
  return normalized.length > 0 ? normalized : null;
};

const extractPathSegments = (pathname?: string | null): string[] => {
  if (typeof pathname !== "string") {
    return [];
  }

  const normalizedPath = pathname.split(/[?#]/, 1)[0]?.trim() ?? "";

  return normalizedPath
    .split("/")
    .map((segment) => segment.trim())
    .filter(Boolean);
};

const resolveRouteSegment = (routeSegment: RouteParamValue, pathSegment?: string | null): string => {
  const normalizedRouteSegment = normalizeRouteParam(routeSegment);
  const normalizedPathSegment = normalizeRouteParam(pathSegment);

  if (normalizedRouteSegment && normalizedRouteSegment !== PREVIEW_SEGMENT) {
    return normalizedRouteSegment;
  }

  if (normalizedPathSegment && normalizedPathSegment !== PREVIEW_SEGMENT) {
    return normalizedPathSegment;
  }

  return normalizedPathSegment ?? normalizedRouteSegment ?? PREVIEW_SEGMENT;
};

export function resolveStandaloneLocationDashboardId(routeId: RouteParamValue, pathname?: string | null): string {
  const [locationSegment, locationIdSegment] = extractPathSegments(pathname);
  const pathLocationId = locationSegment === "locations" ? locationIdSegment : null;

  return resolveRouteSegment(routeId, pathLocationId);
}

export function resolveSiteLocationDashboardIds(
  routeSiteId: RouteParamValue,
  routeLocationId: RouteParamValue,
  pathname?: string | null
): { siteId: string; locationId: string } {
  const [sitesSegment, siteIdSegment, localSegment, locationIdSegment] = extractPathSegments(pathname);
  const pathSiteId = sitesSegment === "sites" && localSegment === "local" ? siteIdSegment : null;
  const pathLocationId = sitesSegment === "sites" && localSegment === "local" ? locationIdSegment : null;

  return {
    siteId: resolveRouteSegment(routeSiteId, pathSiteId),
    locationId: resolveRouteSegment(routeLocationId, pathLocationId),
  };
}

export { PREVIEW_SEGMENT };
