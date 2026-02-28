"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  fetchGSCProperties,
  getGSCOAuthUrl,
} from "@/lib/api/gsc";
import { Button } from "@/components/ui/button";
import {
  Globe,
  Loader2,
  AlertCircle,
  CheckCircle2,
} from "lucide-react";

/**
 * GSC Property Selection Page
 * Shown when a user has multiple GSC properties after OAuth authorization.
 */
export default function GSCSelectPropertyPage() {
  const router = useRouter();
  const [properties, setProperties] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedProperty, setSelectedProperty] = useState<string | null>(null);
  const [isConnecting, setIsConnecting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Fetch available properties from session
    const fetchProperties = async () => {
      try {
        const response = await fetch("/api/gsc/oauth/available-properties");
        if (!response.ok) {
          throw new Error("Failed to fetch available properties");
        }
        const data = await response.json();
        setProperties(data.properties || []);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load properties");
      } finally {
        setIsLoading(false);
      }
    };

    fetchProperties();
  }, []);

  const handleSelectProperty = async () => {
    if (!selectedProperty) return;

    setIsConnecting(true);
    setError(null);

    try {
      const response = await fetch("/api/gsc/oauth/select-property", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ property_url: selectedProperty }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData?.message || "Failed to connect property");
      }

      // Redirect to GSC settings page with success message
      const successUrl = encodeURIComponent(selectedProperty);
      router.push(`/settings/gsc?oauth_success=true&property_url=${successUrl}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to connect property");
      setIsConnecting(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-950 to-slate-900">
        <div className="text-center">
          <Loader2 className="h-8 w-8 animate-spin text-blue-500 mx-auto mb-4" />
          <p className="text-slate-400">Loading your properties...</p>
        </div>
      </div>
    );
  }

  if (properties.length === 0) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-950 to-slate-900 px-4">
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-amber-500 mx-auto mb-4" />
          <h2 className="text-xl font-semibold text-white mb-2">
            No Properties Found
          </h2>
          <p className="text-slate-400 mb-6">
            We couldn't find any Google Search Console properties for your account.
            Make sure you have verified properties in Google Search Console.
          </p>
          <Button
            onClick={() => router.push("/settings/gsc")}
            variant="outline"
            className="border-slate-700 text-slate-300"
          >
            Back to Settings
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-2xl">
        <div className="mb-6">
          <h1 className="text-2xl font-semibold text-white">
            Select a Property
          </h1>
          <p className="text-sm text-slate-400 mt-1">
            Choose which Google Search Console property to connect
          </p>
        </div>

        {error && (
          <div className="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400">
            <div className="flex items-center gap-2">
              <AlertCircle className="h-5 w-5" />
              <p className="font-medium">{error}</p>
            </div>
          </div>
        )}

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
          <div className="space-y-3">
            {properties.map((property) => (
              <button
                key={property.siteUrl}
                onClick={() => setSelectedProperty(property.siteUrl)}
                className={`w-full flex items-center gap-3 p-4 rounded-lg border transition-all ${
                  selectedProperty === property.siteUrl
                    ? "border-blue-500 bg-blue-500/10"
                    : "border-slate-800 bg-slate-800/50 hover:bg-slate-800/70"
                }`}
              >
                <div
                  className={`flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                    selectedProperty === property.siteUrl
                      ? "border-blue-500"
                      : "border-slate-600"
                  }`}
                >
                  {selectedProperty === property.siteUrl && (
                    <div className="w-2.5 h-2.5 rounded-full bg-blue-500" />
                  )}
                </div>
                <div className="flex items-center gap-2 flex-1 min-w-0">
                  <Globe className="h-5 w-5 text-slate-400 flex-shrink-0" />
                  <span className="text-white truncate">{property.siteUrl}</span>
                </div>
                {selectedProperty === property.siteUrl && (
                  <CheckCircle2 className="h-5 w-5 text-blue-500 flex-shrink-0" />
                )}
              </button>
            ))}
          </div>

          <div className="mt-6 pt-6 border-t border-slate-800 flex justify-end gap-3">
            <Button
              variant="ghost"
              onClick={() => router.push("/settings/gsc")}
              className="text-slate-400"
            >
              Cancel
            </Button>
            <Button
              onClick={handleSelectProperty}
              disabled={!selectedProperty || isConnecting}
              className="bg-blue-600 hover:bg-blue-700 text-white"
            >
              {isConnecting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Connecting...
                </>
              ) : (
                "Connect Property"
              )}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
