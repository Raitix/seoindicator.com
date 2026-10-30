"use client";

import { useState, useEffect } from "react";
import { useAlertSettings, updateAlertSettings } from "@/lib/api/alerts";
import type { AlertSettingsUpdateRequest } from "@/lib/types/alerts";

export default function AlertSettingsPage() {
  const { settings, isLoading, isError, mutate } = useAlertSettings();
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [globalEnabled, setGlobalEnabled] = useState(true);
  const [defaultThreshold, setDefaultThreshold] = useState(3);
  const [quietHoursStart, setQuietHoursStart] = useState<string>("");
  const [quietHoursEnd, setQuietHoursEnd] = useState<string>("");
  const [digestMode, setDigestMode] = useState<"instant" | "daily" | "weekly">("instant");
  const [serpFeatureAlertsEnabled, setSerpFeatureAlertsEnabled] = useState(true);

  // Update form when settings load
  useEffect(() => {
    if (settings) {
      setGlobalEnabled(settings.global_enabled);
      setDefaultThreshold(settings.default_threshold);
      setQuietHoursStart(settings.quiet_hours_start || "");
      setQuietHoursEnd(settings.quiet_hours_end || "");
      setDigestMode(settings.digest_mode);
      setSerpFeatureAlertsEnabled(settings.serp_feature_alerts_enabled);
    }
  }, [settings]);

  const handleSave = async () => {
    setSaving(true);
    setError(null);
    setSaved(false);

    const data: AlertSettingsUpdateRequest = {
      global_enabled: globalEnabled,
      default_threshold: defaultThreshold,
      quiet_hours_start: quietHoursStart || null,
      quiet_hours_end: quietHoursEnd || null,
      digest_mode: digestMode,
      serp_feature_alerts_enabled: serpFeatureAlertsEnabled,
    };

    try {
      await updateAlertSettings(data);
      setSaved(true);
      mutate();
      setTimeout(() => setSaved(false), 3000);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to save settings");
    } finally {
      setSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-950 to-slate-900">
        <div className="text-slate-400">Loading...</div>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-950 to-slate-900">
        <div className="text-red-400">Failed to load alert settings</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10 text-slate-300">
      <div className="mx-auto w-full max-w-2xl">
        <h1 className="text-2xl font-semibold text-white mb-6">Alert Settings</h1>

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-6">
          {/* Global Enable Toggle */}
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-white font-medium">Enable Alerts</h2>
              <p className="text-sm text-slate-400">Receive notifications for ranking changes</p>
            </div>
            <button
              onClick={() => setGlobalEnabled(!globalEnabled)}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                globalEnabled ? "bg-blue-600" : "bg-slate-700"
              }`}
            >
              <span
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                  globalEnabled ? "translate-x-6" : "translate-x-1"
                }`}
              />
            </button>
          </div>

          {/* Default Threshold */}
          <div>
            <label className="block text-white font-medium mb-2">
              Position Change Threshold
            </label>
            <p className="text-sm text-slate-400 mb-3">
              Alert when position changes by this many places or more
            </p>
            <div className="flex items-center gap-4">
              <input
                type="range"
                min="1"
                max="20"
                value={defaultThreshold}
                onChange={(e) => setDefaultThreshold(Number(e.target.value))}
                className="flex-1 h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <span className="text-white font-medium w-8 text-center">
                {defaultThreshold}
              </span>
            </div>
          </div>

          {/* Digest Mode */}
          <div>
            <label className="block text-white font-medium mb-2">Notification Frequency</label>
            <div className="grid grid-cols-3 gap-3">
              {(["instant", "daily", "weekly"] as const).map((mode) => (
                <button
                  key={mode}
                  onClick={() => setDigestMode(mode)}
                  className={`px-4 py-2 rounded-lg border text-sm font-medium transition-colors ${
                    digestMode === mode
                      ? "border-blue-500 bg-blue-500/20 text-blue-400"
                      : "border-slate-700 bg-slate-800 text-slate-300 hover:border-slate-600"
                  }`}
                >
                  {mode.charAt(0).toUpperCase() + mode.slice(1)}
                </button>
              ))}
            </div>
          </div>

          {/* Quiet Hours */}
          <div>
            <label className="block text-white font-medium mb-2">Quiet Hours</label>
            <p className="text-sm text-slate-400 mb-3">
              No email notifications during these hours
            </p>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm text-slate-400 mb-1">Start</label>
                <input
                  type="time"
                  value={quietHoursStart}
                  onChange={(e) => setQuietHoursStart(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm text-slate-400 mb-1">End</label>
                <input
                  type="time"
                  value={quietHoursEnd}
                  onChange={(e) => setQuietHoursEnd(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>
          </div>

          {/* SERP Feature Alerts */}
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-white font-medium">SERP Feature Alerts</h2>
              <p className="text-sm text-slate-400">Get notified when you gain or lose SERP features</p>
            </div>
            <button
              onClick={() => setSerpFeatureAlertsEnabled(!serpFeatureAlertsEnabled)}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                serpFeatureAlertsEnabled ? "bg-blue-600" : "bg-slate-700"
              }`}
            >
              <span
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                  serpFeatureAlertsEnabled ? "translate-x-6" : "translate-x-1"
                }`}
              />
            </button>
          </div>

          {/* Error Message */}
          {error && (
            <div className="p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
              {error}
            </div>
          )}

          {/* Success Message */}
          {saved && (
            <div className="p-3 rounded-lg bg-green-500/20 border border-green-500/50 text-green-400 text-sm">
              Settings saved successfully
            </div>
          )}

          {/* Save Button */}
          <div className="pt-4 border-t border-slate-800">
            <button
              onClick={handleSave}
              disabled={saving}
              className="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-600/50 text-white font-medium rounded-lg transition-colors"
            >
              {saving ? "Saving..." : "Save Settings"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
