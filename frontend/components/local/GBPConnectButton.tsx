"use client";

import * as React from "react";
import {
  AlertCircle,
  Building2,
  Check,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Link as LinkIcon,
  Loader2,
  MapPin,
  RefreshCw,
  Unlink,
} from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from "@/lib/utils";

const API_BASE = "/api/v1";
export const GBP_OAUTH_STATE_KEY = "gbp_oauth_state";

type GBPConnectStatus = "idle" | "connecting" | "selecting" | "connected" | "error";

export interface GBPAccount {
  id: string;
  name: string;
  locationCount: number;
  profileImage?: string | null;
}

export interface GBPLocation {
  id: string;
  name: string;
  address: string;
  accountId: string;
  profileImage?: string | null;
}

export interface ConnectedGBP {
  businessName: string;
  profileImage?: string | null;
  locationId: string;
  accountId?: string;
}

export interface GBPConnectButtonProps {
  siteId: string | number;
  locationId: string | number;
  connectedGBP?: ConnectedGBP | null;
  onConnectionChange?: (connected: boolean, data?: ConnectedGBP) => void;
  className?: string;
}

type JsonRecord = Record<string, unknown>;

const GOOGLE_BUTTON_LABEL = "Connect Google Business Profile";

const toNonEmptyString = (value: unknown): string | null => {
  if (typeof value !== "string") {
    return null;
  }

  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : null;
};

const toNumber = (value: unknown, fallback = 0): number => {
  if (typeof value === "number" && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === "string") {
    const parsed = Number(value);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  return fallback;
};

const sanitizeErrorMessage = (value: unknown, fallback = "Connection failed. Please try again.") => {
  const raw = toNonEmptyString(value);

  if (!raw) {
    return fallback;
  }

  const normalized = raw.toLowerCase();

  if (normalized.includes("already connected")) {
    return "This location is already connected to another record.";
  }

  if (normalized.includes("popup") && normalized.includes("block")) {
    return "Popup blocked. Please allow popups for this site.";
  }

  if (normalized.includes("no google business profile accounts") || normalized.includes("no gbp accounts")) {
    return "No Google Business Profile accounts found.";
  }

  return raw;
};

const getErrorMessageFromPayload = (payload: unknown, fallback?: string) => {
  if (typeof payload === "string") {
    return sanitizeErrorMessage(payload, fallback);
  }

  if (payload && typeof payload === "object") {
    const record = payload as JsonRecord;

    return sanitizeErrorMessage(
      record.error ?? record.message ?? record.detail ?? record.title,
      fallback
    );
  }

  return fallback ?? "Connection failed. Please try again.";
};

const normalizeAccount = (input: unknown): GBPAccount | null => {
  if (!input || typeof input !== "object") {
    return null;
  }

  const item = input as JsonRecord;
  const id = toNonEmptyString(item.id ?? item.account_id ?? item.accountId ?? item.name);
  const name = toNonEmptyString(item.name ?? item.account_name ?? item.accountName);

  if (!id || !name) {
    return null;
  }

  return {
    id,
    name,
    locationCount: Math.max(0, toNumber(item.location_count ?? item.locationCount ?? item.locations_count ?? item.locationsCount, 0)),
    profileImage:
      toNonEmptyString(item.profile_image ?? item.profileImage ?? item.logo_url ?? item.logoUrl) ?? undefined,
  };
};

const normalizeLocation = (input: unknown, accountId: string): GBPLocation | null => {
  if (!input || typeof input !== "object") {
    return null;
  }

  const item = input as JsonRecord;
  const id = toNonEmptyString(item.id ?? item.location_id ?? item.locationId ?? item.name);
  const name = toNonEmptyString(item.name ?? item.title ?? item.location_name ?? item.locationName);

  if (!id || !name) {
    return null;
  }

  const addressParts = [
    toNonEmptyString(item.address),
    toNonEmptyString(item.address_line_1 ?? item.addressLine1),
    toNonEmptyString(item.locality ?? item.city),
    toNonEmptyString(item.region ?? item.state),
    toNonEmptyString(item.postal_code ?? item.postalCode),
    toNonEmptyString(item.country),
  ].filter(Boolean) as string[];

  return {
    id,
    name,
    accountId,
    address:
      toNonEmptyString(item.formatted_address ?? item.formattedAddress) ??
      addressParts.join(", ") ??
      "Address unavailable",
    profileImage:
      toNonEmptyString(item.profile_image ?? item.profileImage ?? item.photo_url ?? item.photoUrl) ?? undefined,
  };
};

const pluralizeLocations = (count: number) => `${count} ${count === 1 ? "location" : "locations"}`;

export function GBPConnectButton({
  siteId,
  locationId,
  connectedGBP,
  onConnectionChange,
  className,
}: GBPConnectButtonProps) {
  const [status, setStatus] = React.useState<GBPConnectStatus>(connectedGBP ? "connected" : "idle");
  const [error, setError] = React.useState<string | null>(null);
  const [availableAccounts, setAvailableAccounts] = React.useState<GBPAccount[]>([]);
  const [availableLocations, setAvailableLocations] = React.useState<GBPLocation[]>([]);
  const [selectedAccount, setSelectedAccount] = React.useState<GBPAccount | null>(null);
  const [currentConnection, setCurrentConnection] = React.useState<ConnectedGBP | null>(connectedGBP ?? null);
  const [showDisconnectConfirm, setShowDisconnectConfirm] = React.useState(false);
  const [isFetchingLocations, setIsFetchingLocations] = React.useState(false);

  const callbackHandlerRef = React.useRef<((event: MessageEvent) => void) | null>(null);

  React.useEffect(() => {
    setCurrentConnection(connectedGBP ?? null);
    setStatus(connectedGBP ? "connected" : "idle");
  }, [connectedGBP]);

  const clearOAuthListener = React.useCallback(() => {
    if (callbackHandlerRef.current) {
      window.removeEventListener("message", callbackHandlerRef.current);
      callbackHandlerRef.current = null;
    }
  }, []);

  React.useEffect(() => () => clearOAuthListener(), [clearOAuthListener]);

  const apiRequest = React.useCallback(async (path: string, init?: RequestInit) => {
    const response = await fetch(path, {
      ...init,
      headers: {
        Accept: "application/json",
        ...(init?.body ? { "Content-Type": "application/json" } : {}),
        ...(init?.headers ?? {}),
      },
    });

    let payload: unknown = null;
    try {
      payload = await response.json();
    } catch {
      payload = null;
    }

    if (!response.ok) {
      throw new Error(getErrorMessageFromPayload(payload, "Connection failed. Please try again."));
    }

    return payload;
  }, []);

  const resetSelectionState = React.useCallback(() => {
    setSelectedAccount(null);
    setAvailableLocations([]);
    setAvailableAccounts([]);
  }, []);

  const handleOAuthCompleted = React.useCallback(
    async (payload: JsonRecord) => {
      const storedState = sessionStorage.getItem(GBP_OAUTH_STATE_KEY);
      const returnedState = toNonEmptyString(payload.state);

      if (returnedState && storedState && returnedState !== storedState) {
        sessionStorage.removeItem(GBP_OAUTH_STATE_KEY);
        setError("OAuth validation failed. Please try again.");
        setStatus("error");
        return;
      }

      if (payload.error) {
        sessionStorage.removeItem(GBP_OAUTH_STATE_KEY);
        setError(getErrorMessageFromPayload(payload.error, "Connection failed. Please try again."));
        setStatus("error");
        return;
      }

      try {
        const query = new URLSearchParams();
        const code = toNonEmptyString(payload.code);

        if (code) {
          query.set("code", code);
        }

        if (returnedState) {
          query.set("state", returnedState);
        }

        const suffix = query.toString() ? `?${query.toString()}` : "";
        const response = await apiRequest(`${API_BASE}/locations/${locationId}/gbp/accounts${suffix}`);
        const rawAccounts = Array.isArray((response as JsonRecord)?.accounts)
          ? ((response as JsonRecord).accounts as unknown[])
          : Array.isArray((response as JsonRecord)?.data)
            ? ((response as JsonRecord).data as unknown[])
            : Array.isArray(response)
              ? (response as unknown[])
              : [];

        const accounts = rawAccounts.map(normalizeAccount).filter(Boolean) as GBPAccount[];
        sessionStorage.removeItem(GBP_OAUTH_STATE_KEY);

        if (accounts.length === 0) {
          setError("No Google Business Profile accounts found.");
          setStatus("error");
          return;
        }

        setAvailableAccounts(accounts);
        setAvailableLocations([]);
        setSelectedAccount(null);
        setError(null);
        setStatus("selecting");
      } catch (requestError) {
        sessionStorage.removeItem(GBP_OAUTH_STATE_KEY);
        setError(
          sanitizeErrorMessage(
            requestError instanceof Error ? requestError.message : requestError,
            "Connection failed. Please try again."
          )
        );
        setStatus("error");
      }
    },
    [apiRequest, locationId]
  );

  const initiateOAuth = React.useCallback(async () => {
    if (status === "connecting") {
      return;
    }

    clearOAuthListener();
    resetSelectionState();
    setCurrentConnection(null);
    setError(null);
    setIsFetchingLocations(false);
    setStatus("connecting");

    try {
      const oauthState = `${siteId}-${locationId}-${Date.now()}`;
      sessionStorage.setItem(GBP_OAUTH_STATE_KEY, oauthState);

      const response = await apiRequest(`${API_BASE}/locations/${locationId}/gbp/oauth-url?state=${encodeURIComponent(oauthState)}`);
      const oauthUrl = toNonEmptyString((response as JsonRecord)?.oauth_url ?? (response as JsonRecord)?.url);

      if (!oauthUrl) {
        throw new Error("Connection failed. Please try again.");
      }

      const popup = window.open(
        oauthUrl,
        "GBP OAuth",
        "popup=yes,width=600,height=700,resizable=yes,scrollbars=yes"
      );

      if (!popup) {
        throw new Error("Popup blocked. Please allow popups for this site.");
      }

      const handleMessage = (event: MessageEvent) => {
        const payload = event.data;

        if (!payload || typeof payload !== "object") {
          return;
        }

        const record = payload as JsonRecord;

        if (record.type !== "gbp_oauth_callback") {
          return;
        }

        clearOAuthListener();
        void handleOAuthCompleted(record);
      };

      callbackHandlerRef.current = handleMessage;
      window.addEventListener("message", handleMessage);
    } catch (oauthError) {
      sessionStorage.removeItem(GBP_OAUTH_STATE_KEY);
      clearOAuthListener();
      setError(
        sanitizeErrorMessage(
          oauthError instanceof Error ? oauthError.message : oauthError,
          "Connection failed. Please try again."
        )
      );
      setStatus("error");
    }
  }, [apiRequest, clearOAuthListener, handleOAuthCompleted, locationId, resetSelectionState, siteId, status]);

  const selectAccount = React.useCallback(
    async (account: GBPAccount) => {
      setSelectedAccount(account);
      setAvailableLocations([]);
      setError(null);
      setIsFetchingLocations(true);

      try {
        const response = await apiRequest(`${API_BASE}/locations/${locationId}/gbp/accounts/${account.id}/locations`);
        const rawLocations = Array.isArray((response as JsonRecord)?.locations)
          ? ((response as JsonRecord).locations as unknown[])
          : Array.isArray((response as JsonRecord)?.data)
            ? ((response as JsonRecord).data as unknown[])
            : Array.isArray(response)
              ? (response as unknown[])
              : [];

        const locations = rawLocations
          .map((item) => normalizeLocation(item, account.id))
          .filter(Boolean) as GBPLocation[];

        setAvailableLocations(locations);
      } catch (requestError) {
        setError(
          sanitizeErrorMessage(
            requestError instanceof Error ? requestError.message : requestError,
            "Failed to fetch locations."
          )
        );
        setStatus("error");
      } finally {
        setIsFetchingLocations(false);
      }
    },
    [apiRequest, locationId]
  );

  const connectLocation = React.useCallback(
    async (gbpLocation: GBPLocation) => {
      setError(null);
      setStatus("connecting");

      try {
        await apiRequest(`${API_BASE}/locations/${locationId}/connect-gbp`, {
          method: "POST",
          body: JSON.stringify({
            site_id: siteId,
            gbp_account_id: gbpLocation.accountId,
            gbp_location_id: gbpLocation.id,
          }),
        });

        const nextConnection: ConnectedGBP = {
          businessName: gbpLocation.name,
          locationId: gbpLocation.id,
          accountId: gbpLocation.accountId,
          profileImage: gbpLocation.profileImage ?? selectedAccount?.profileImage ?? undefined,
        };

        setCurrentConnection(nextConnection);
        setStatus("connected");
        resetSelectionState();
        onConnectionChange?.(true, nextConnection);
      } catch (requestError) {
        setError(
          sanitizeErrorMessage(
            requestError instanceof Error ? requestError.message : requestError,
            "Connection failed. Please try again."
          )
        );
        setStatus("error");
      }
    },
    [apiRequest, locationId, onConnectionChange, resetSelectionState, selectedAccount?.profileImage, siteId]
  );

  const disconnect = React.useCallback(async () => {
    setError(null);
    setStatus("connecting");

    try {
      await apiRequest(`${API_BASE}/locations/${locationId}/disconnect-gbp`, {
        method: "POST",
        body: JSON.stringify({ site_id: siteId }),
      });

      setCurrentConnection(null);
      setShowDisconnectConfirm(false);
      resetSelectionState();
      setStatus("idle");
      onConnectionChange?.(false);
    } catch (requestError) {
      setShowDisconnectConfirm(false);
      setError(
        sanitizeErrorMessage(
          requestError instanceof Error ? requestError.message : requestError,
          "Disconnect failed. Please try again."
        )
      );
      setStatus("connected");
    }
  }, [apiRequest, locationId, onConnectionChange, resetSelectionState, siteId]);

  const retry = React.useCallback(() => {
    setError(null);
    setIsFetchingLocations(false);
    resetSelectionState();
    setStatus(currentConnection ? "connected" : "idle");
  }, [currentConnection, resetSelectionState]);

  if (status === "connected" && currentConnection) {
    return (
      <div
        className={cn(
          "gbp-connected rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-slate-100",
          className
        )}
        data-testid="gbp-connected"
      >
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-start gap-3">
            {currentConnection.profileImage ? (
              <img
                src={currentConnection.profileImage}
                alt=""
                className="h-12 w-12 rounded-full border border-emerald-500/20 object-cover"
                data-testid="gbp-profile-image"
              />
            ) : (
              <div
                className="flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/20 bg-slate-900/80 text-emerald-200"
                data-testid="building-icon"
              >
                <Building2 className="h-5 w-5" aria-hidden="true" />
              </div>
            )}

            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <p className="truncate text-sm font-semibold text-white">
                  Connected to {currentConnection.businessName}
                </p>
                <span
                  className="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-100"
                  data-testid="check-icon"
                >
                  <Check className="h-3.5 w-3.5" aria-hidden="true" />
                  Connected
                </span>
              </div>
              <p className="mt-1 text-xs text-emerald-100/80">Google Business Profile</p>
            </div>
          </div>

          <Button
            type="button"
            variant="outline"
            className="border-red-500/30 bg-red-500/10 text-red-100 hover:bg-red-500/20 hover:text-white"
            onClick={() => setShowDisconnectConfirm(true)}
            data-testid="disconnect-button"
            aria-label="Disconnect Google Business Profile"
          >
            <Unlink className="h-4 w-4" aria-hidden="true" />
            Disconnect
          </Button>
        </div>

        {error ? (
          <p className="mt-3 text-sm text-red-100" role="alert">
            {error}
          </p>
        ) : null}

        <Dialog open={showDisconnectConfirm} onOpenChange={setShowDisconnectConfirm}>
          <DialogContent data-testid="disconnect-modal">
            <DialogHeader>
              <DialogTitle>Disconnect Google Business Profile?</DialogTitle>
              <DialogDescription>
                This will remove the connection to {currentConnection.businessName}. You can reconnect it later.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowDisconnectConfirm(false)}
                data-testid="cancel-disconnect"
              >
                Cancel
              </Button>
              <Button
                type="button"
                variant="destructive"
                onClick={() => void disconnect()}
                data-testid="confirm-disconnect"
              >
                Disconnect
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    );
  }

  if (status === "selecting") {
    return (
      <div
        className={cn(
          "gbp-selecting rounded-2xl border border-slate-800 bg-slate-950/70 p-4 text-slate-100",
          className
        )}
        data-testid="gbp-selecting"
      >
        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h3 className="text-base font-semibold text-white">Select a Google Business Profile</h3>
            <p className="text-sm text-slate-400">
              Choose the Google account and location to link with this business record.
            </p>
          </div>
          <Button
            type="button"
            variant="ghost"
            className="justify-start text-slate-300 hover:bg-slate-800 hover:text-white sm:justify-center"
            onClick={retry}
          >
            <ChevronLeft className="h-4 w-4" aria-hidden="true" />
            Start over
          </Button>
        </div>

        {!selectedAccount ? (
          <ScrollArea className="mt-4 max-h-80 pr-1" data-testid="account-list">
            <div className="space-y-3">
              {availableAccounts.map((account) => (
                <button
                  key={account.id}
                  type="button"
                  onClick={() => void selectAccount(account)}
                  className="flex w-full items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-left transition hover:border-slate-700 hover:bg-slate-900"
                  data-testid={`account-${account.id}`}
                >
                  {account.profileImage ? (
                    <img src={account.profileImage} alt="" className="h-11 w-11 rounded-full object-cover" />
                  ) : (
                    <div className="flex h-11 w-11 items-center justify-center rounded-full border border-slate-700 bg-slate-950 text-slate-300" data-testid="building-icon">
                      <Building2 className="h-5 w-5" aria-hidden="true" />
                    </div>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium text-white">{account.name}</p>
                    <p className="text-sm text-slate-400">{pluralizeLocations(account.locationCount)}</p>
                  </div>
                  <ChevronRight className="h-4 w-4 text-slate-500" aria-hidden="true" data-testid="chevron-down-icon" />
                </button>
              ))}
            </div>
          </ScrollArea>
        ) : (
          <div className="mt-4 space-y-4" data-testid="location-list">
            <Button
              type="button"
              variant="ghost"
              className="justify-start px-0 text-sky-300 hover:bg-transparent hover:text-sky-200"
              onClick={() => {
                setSelectedAccount(null);
                setAvailableLocations([]);
                setError(null);
              }}
              data-testid="back-to-accounts"
            >
              <ChevronLeft className="h-4 w-4" aria-hidden="true" />
              Back to accounts
            </Button>

            <div className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
              <p className="text-sm font-medium text-white">{selectedAccount.name}</p>
              <p className="mt-1 text-sm text-slate-400">Pick the location that should sync with this record.</p>
            </div>

            {isFetchingLocations ? (
              <div className="flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-sm text-slate-300">
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
                Loading locations...
              </div>
            ) : availableLocations.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-4 text-sm text-slate-400">
                No locations found for this account.
              </div>
            ) : (
              <ScrollArea className="max-h-80 pr-1">
                <div className="space-y-3">
                  {availableLocations.map((location) => (
                    <button
                      key={location.id}
                      type="button"
                      onClick={() => void connectLocation(location)}
                      className="w-full rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-left transition hover:border-slate-700 hover:bg-slate-900"
                      data-testid={`location-${location.id}`}
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 flex-1">
                          <p className="truncate font-medium text-white">{location.name}</p>
                          <p className="mt-2 flex items-start gap-2 text-sm text-slate-400">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" data-testid="map-pin-icon" />
                            <span>{location.address}</span>
                          </p>
                        </div>
                        <ExternalLink className="mt-0.5 h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
                      </div>
                    </button>
                  ))}
                </div>
              </ScrollArea>
            )}
          </div>
        )}
      </div>
    );
  }

  if (status === "error") {
    return (
      <div
        className={cn(
          "gbp-error flex flex-col gap-3 rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-red-50",
          className
        )}
        data-testid="gbp-error"
        role="alert"
      >
        <div className="flex items-start gap-3">
          <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-red-300" aria-hidden="true" data-testid="alert-icon" />
          <div>
            <p className="text-sm font-medium">{error ?? "Connection failed. Please try again."}</p>
            <p className="mt-1 text-xs text-red-100/80">OAuth and API errors are safe to retry. Sensitive tokens never leave the backend.</p>
          </div>
        </div>

        <Button
          type="button"
          variant="outline"
          className="w-full justify-center border-red-500/30 bg-red-500/10 text-red-50 hover:bg-red-500/20 sm:w-auto"
          onClick={retry}
          data-testid="retry-button"
        >
          <RefreshCw className="h-4 w-4" aria-hidden="true" data-testid="refresh-icon" />
          Try again
        </Button>
      </div>
    );
  }

  if (status === "connecting") {
    return (
      <div
        className={cn(
          "gbp-connecting flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 p-4 text-slate-100",
          className
        )}
        data-testid="gbp-connecting"
      >
        <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" data-testid="loader-icon" />
        <div>
          <p className="text-sm font-medium text-white">Connecting...</p>
          <p className="text-xs text-slate-400">Finishing Google Business Profile authorization.</p>
        </div>
      </div>
    );
  }

  return (
    <Button
      type="button"
      onClick={() => void initiateOAuth()}
      className={cn(
        "gbp-connect-button google-branding h-auto w-full justify-between rounded-2xl border border-slate-700 bg-white px-4 py-3 text-left text-slate-900 shadow-sm hover:bg-slate-50",
        className
      )}
      data-testid="gbp-connect-button"
      aria-label={GOOGLE_BUTTON_LABEL}
    >
      <span className="flex min-w-0 items-center gap-3">
        <span className="shrink-0" data-testid="google-logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" className="h-5 w-5">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
          </svg>
        </span>
        <span className="min-w-0">
          <span className="block truncate text-sm font-semibold text-slate-900">{GOOGLE_BUTTON_LABEL}</span>
          <span className="block text-xs text-slate-500">Secure OAuth link for site {siteId}</span>
        </span>
      </span>
      <span className="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
        <LinkIcon className="h-4 w-4" aria-hidden="true" />
        Connect
      </span>
    </Button>
  );
}

export default GBPConnectButton;
