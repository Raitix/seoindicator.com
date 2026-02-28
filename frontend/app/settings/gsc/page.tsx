"use client";

import { useState, useEffect, useCallback } from "react";
import {
  fetchGSCProperties,
  disconnectGSCProperty,
  formatDate,
} from "@/lib/api/gsc";
import { GSCProperty } from "@/lib/types/gsc";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  ExternalLink,
  Link2,
  Unlink2,
  Globe,
  AlertCircle,
  CheckCircle2,
  Loader2,
} from "lucide-react";

/**
 * GSC Connection Page
 * TASK-12-9: React component for users to connect/disconnect Google Search Console properties via OAuth flow.
 */
export default function GSCConnectionPage() {
  const [properties, setProperties] = useState<GSCProperty[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  
  // Disconnect modal state
  const [disconnectModalOpen, setDisconnectModalOpen] = useState(false);
  const [propertyToDisconnect, setPropertyToDisconnect] = useState<GSCProperty | null>(null);
  const [isDisconnecting, setIsDisconnecting] = useState(false);

  // OAuth callback handling
  const [oauthStatus, setOauthStatus] = useState<{
    type: 'success' | 'error' | null;
    message: string;
  }>({ type: null, message: '' });

  // Fetch properties on mount
  const loadProperties = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await fetchGSCProperties();
      setProperties(response.data || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load GSC properties");
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadProperties();
  }, [loadProperties]);

  // Handle OAuth callback from URL params
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const oauthSuccess = urlParams.get("oauth_success");
    const oauthError = urlParams.get("oauth_error");
    const propertyUrl = urlParams.get("property_url");

    if (oauthSuccess === "true" && propertyUrl) {
      setOauthStatus({
        type: "success",
        message: `Successfully connected ${decodeURIComponent(propertyUrl)}`,
      });
      // Clean up URL
      window.history.replaceState({}, "", window.location.pathname);
      // Refresh properties list
      loadProperties();
    } else if (oauthError) {
      setOauthStatus({
        type: "error",
        message: decodeURIComponent(oauthError),
      });
      // Clean up URL
      window.history.replaceState({}, "", window.location.pathname);
    }

    // Clear OAuth status after 5 seconds
    if (oauthSuccess || oauthError) {
      const timer = setTimeout(() => {
        setOauthStatus({ type: null, message: "" });
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [loadProperties]);

  // Handle connect button click - redirect to OAuth
  const handleConnect = () => {
    // Redirect to backend OAuth endpoint
    // The backend will redirect to Google OAuth, then back to our callback
    window.location.href = "/api/gsc/oauth/redirect";
  };

  // Open disconnect confirmation modal
  const openDisconnectModal = (property: GSCProperty) => {
    setPropertyToDisconnect(property);
    setDisconnectModalOpen(true);
  };

  // Handle disconnect confirmation
  const handleDisconnect = async () => {
    if (!propertyToDisconnect) return;

    setIsDisconnecting(true);
    setError(null);

    try {
      await disconnectGSCProperty(propertyToDisconnect.id);
      setSuccess(`Successfully disconnected ${propertyToDisconnect.property_url}`);
      setProperties((prev) => prev.filter((p) => p.id !== propertyToDisconnect.id));
      setDisconnectModalOpen(false);
      setPropertyToDisconnect(null);
      
      // Clear success message after 3 seconds
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to disconnect property");
    } finally {
      setIsDisconnecting(false);
    }
  };

  // Get status badge for property
  const getStatusBadge = (status?: string) => {
    switch (status) {
      case "active":
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-400">
            <CheckCircle2 className="h-3 w-3" />
            Active
          </span>
        );
      case "expired":
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/20 text-amber-400">
            <AlertCircle className="h-3 w-3" />
            Expired
          </span>
        );
      case "revoked":
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400">
            <Unlink2 className="h-3 w-3" />
            Revoked
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-400">
            <CheckCircle2 className="h-3 w-3" />
            Connected
          </span>
        );
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-2xl">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">
            Google Search Console
          </h1>
          <p className="text-sm text-slate-400 mt-1">
            Connect your Google Search Console properties to enable advanced SEO insights
          </p>
        </div>

        {/* OAuth Status Banner */}
        {oauthStatus.type && (
          <div
            className={`mb-6 p-4 rounded-lg border ${
              oauthStatus.type === "success"
                ? "bg-emerald-500/10 border-emerald-500/30 text-emerald-400"
                : "bg-red-500/10 border-red-500/30 text-red-400"
            }`}
          >
            <div className="flex items-center gap-2">
              {oauthStatus.type === "success" ? (
                <CheckCircle2 className="h-5 w-5" />
              ) : (
                <AlertCircle className="h-5 w-5" />
              )}
              <p className="font-medium">{oauthStatus.message}</p>
            </div>
          </div>
        )}

        {/* Error Banner */}
        {error && (
          <div className="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400">
            <div className="flex items-center gap-2">
              <AlertCircle className="h-5 w-5" />
              <p className="font-medium">{error}</p>
            </div>
          </div>
        )}

        {/* Success Banner */}
        {success && (
          <div className="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
            <div className="flex items-center gap-2">
              <CheckCircle2 className="h-5 w-5" />
              <p className="font-medium">{success}</p>
            </div>
          </div>
        )}

        {/* Main Card */}
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
          {/* Connect Section */}
          <div className="pb-6 border-b border-slate-800">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-white font-medium">Connect Property</h2>
                <p className="text-sm text-slate-400 mt-1">
                  Link a Google Search Console property to import search analytics
                </p>
              </div>
              <Button
                onClick={handleConnect}
                className="bg-blue-600 hover:bg-blue-700 text-white"
              >
                <Link2 className="mr-2 h-4 w-4" />
                Connect with Google
              </Button>
            </div>
          </div>

          {/* Properties List */}
          <div className="pt-6">
            <h2 className="text-white font-medium mb-4">
              Connected Properties
            </h2>

            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="h-6 w-6 animate-spin text-slate-400" />
                <span className="ml-2 text-slate-400">Loading properties...</span>
              </div>
            ) : properties.length === 0 ? (
              <div className="text-center py-8 text-slate-400">
                <Globe className="h-12 w-12 mx-auto mb-3 opacity-50" />
                <p>No properties connected yet</p>
                <p className="text-sm mt-1">
                  Click "Connect with Google" to add your first property
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {properties.map((property) => (
                  <div
                    key={property.id}
                    className="flex items-center justify-between p-4 rounded-lg border border-slate-800 bg-slate-800/50 hover:bg-slate-800/70 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex-shrink-0">
                        <Globe className="h-5 w-5 text-slate-400" />
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <a
                            href={property.property_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-white hover:text-blue-400 transition-colors"
                          >
                            {property.property_url}
                          </a>
                          <ExternalLink className="h-3 w-3 text-slate-500" />
                        </div>
                        <div className="flex items-center gap-2 mt-1">
                          {getStatusBadge(property.status)}
                          <span className="text-xs text-slate-500">
                            Connected {formatDate(property.connected_at)}
                          </span>
                        </div>
                      </div>
                    </div>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => openDisconnectModal(property)}
                      className="text-slate-400 hover:text-red-400 hover:bg-red-500/10"
                    >
                      <Unlink2 className="h-4 w-4 mr-1" />
                      Disconnect
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Info Section */}
        <div className="mt-6 p-4 rounded-lg bg-slate-800/30 border border-slate-800">
          <h3 className="text-sm font-medium text-white mb-2">
            What happens when you connect?
          </h3>
          <ul className="text-sm text-slate-400 space-y-1">
            <li>• We'll import your search analytics data for the past 16 months</li>
            <li>• Daily automatic sync keeps your data up-to-date</li>
            <li>• Opportunity keywords are identified based on CTR gaps</li>
            <li>• You can disconnect at any time - we'll revoke all access</li>
          </ul>
        </div>
      </div>

      {/* Disconnect Confirmation Modal */}
      <Dialog open={disconnectModalOpen} onOpenChange={setDisconnectModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Disconnect Property</DialogTitle>
            <DialogDescription>
              Are you sure you want to disconnect{" "}
              <span className="text-white font-medium">
                {propertyToDisconnect?.property_url}
              </span>
              ?
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <p className="text-sm text-slate-400">
              This will revoke our access to your Google Search Console data for this
              property. You can reconnect at any time.
            </p>
          </div>
          <DialogFooter>
            <Button
              variant="ghost"
              onClick={() => setDisconnectModalOpen(false)}
              disabled={isDisconnecting}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleDisconnect}
              disabled={isDisconnecting}
            >
              {isDisconnecting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Disconnecting...
                </>
              ) : (
                <>
                  <Unlink2 className="mr-2 h-4 w-4" />
                  Disconnect
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
