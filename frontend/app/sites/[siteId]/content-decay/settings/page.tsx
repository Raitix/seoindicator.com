"use client";

import React, { useState, useEffect, useMemo } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useContentDecaySettings, useSaveContentDecaySettings } from "@/lib/hooks/useContentDecay";
import type { ContentDecayProjectSettings } from "@/lib/hooks/useContentDecay";
import { LayoutDashboard, Columns, BarChart3, Save, RotateCcw, AlertCircle } from "lucide-react";

const WINDOW_OPTIONS = [
  { value: 7, label: "7 days" },
  { value: 14, label: "14 days" },
  { value: 28, label: "28 days" },
  { value: 56, label: "56 days" },
];

const DIGEST_FREQUENCY_OPTIONS = [
  { value: "weekly", label: "Weekly" },
  { value: "monthly", label: "Monthly" },
];

export default function ContentDecaySettingsPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  const { settings, isLoading } = useContentDecaySettings(siteId);
  const { saveSettings, isSaving } = useSaveContentDecaySettings(siteId);

  const [formData, setFormData] = useState<ContentDecayProjectSettings>({
    detectionEnabled: true,
    windowDays: 28,
    minClicksThreshold: 10,
    minImpressionsThreshold: 100,
    digestEnabled: true,
    digestFrequency: "weekly",
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [hasChanges, setHasChanges] = useState(false);

  // Memoize settings to prevent infinite loops
  const settingsKey = useMemo(() => JSON.stringify(settings), [settings]);
  const initialSettings = useMemo(() => settings, [settingsKey]);

  useEffect(() => {
    if (initialSettings) {
      setFormData({
        detectionEnabled: initialSettings.detectionEnabled,
        windowDays: initialSettings.windowDays,
        minClicksThreshold: initialSettings.minClicksThreshold,
        minImpressionsThreshold: initialSettings.minImpressionsThreshold,
        digestEnabled: initialSettings.digestEnabled,
        digestFrequency: initialSettings.digestFrequency,
      });
    }
  }, [initialSettings]);

  useEffect(() => {
    if (initialSettings) {
      setHasChanges(JSON.stringify(formData) !== JSON.stringify(initialSettings));
    }
  }, [formData, initialSettings]);

  const handleChange = (field: keyof ContentDecayProjectSettings, value: unknown) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: "" }));
  };

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (formData.minClicksThreshold < 0) {
      newErrors.minClicksThreshold = "Must be 0 or greater";
    }
    if (formData.minImpressionsThreshold < 0) {
      newErrors.minImpressionsThreshold = "Must be 0 or greater";
    }
    if (formData.windowDays < 7 || formData.windowDays > 56) {
      newErrors.windowDays = "Must be between 7 and 56 days";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSave = async () => {
    if (!validate()) return;

    try {
      await saveSettings(formData);
      setHasChanges(false);
    } catch (error) {
      console.error("Failed to save settings:", error);
    }
  };

  const handleReset = () => {
    if (initialSettings) {
      setFormData({
        detectionEnabled: initialSettings.detectionEnabled,
        windowDays: initialSettings.windowDays,
        minClicksThreshold: initialSettings.minClicksThreshold,
        minImpressionsThreshold: initialSettings.minImpressionsThreshold,
        digestEnabled: initialSettings.digestEnabled,
        digestFrequency: initialSettings.digestFrequency,
      });
      setErrors({});
    }
  };

  const tabs = [
    { id: "queue", label: "Queue", href: `/sites/${siteId}/content-decay`, icon: LayoutDashboard },
    { id: "board", label: "Board", href: `/sites/${siteId}/content-decay/board`, icon: Columns },
    { id: "recovery", label: "Recovery", href: `/sites/${siteId}/content-decay/recovery`, icon: BarChart3 },
  ];

  return (
    <div className="container mx-auto px-4 py-6 space-y-6" data-testid="settings-page">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Content Decay Settings</h1>
          <p className="text-slate-400 text-sm mt-1">Configure detection and notification preferences</p>
        </div>
        {hasChanges && (
          <div className="flex items-center gap-2 text-sm text-yellow-400">
            <AlertCircle className="w-4 h-4" />
            Unsaved changes
          </div>
        )}
      </div>

      <nav className="flex gap-1 border-b border-slate-700 pb-2" aria-label="Content decay views">
        {tabs.map((tab) => (
          <Link
            key={tab.id}
            href={tab.href}
            className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-colors text-slate-400 hover:text-white hover:bg-slate-700"
          >
            <tab.icon className="w-4 h-4" />
            {tab.label}
          </Link>
        ))}
      </nav>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-32 bg-slate-800/50 animate-pulse rounded-lg" />
          ))}
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); handleSave(); }} className="space-y-6">
          <section className="bg-slate-800/50 border border-slate-700 rounded-lg p-4" aria-labelledby="detection-heading">
            <h2 id="detection-heading" className="text-lg font-medium text-white mb-4">Detection Settings</h2>

            <div className="flex items-center justify-between py-3 border-b border-slate-700">
              <div>
                <p className="text-slate-200 font-medium">Enable Detection</p>
                <p className="text-sm text-slate-400">Automatically detect decaying content</p>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={formData.detectionEnabled}
                onClick={() => handleChange("detectionEnabled", !formData.detectionEnabled)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                  formData.detectionEnabled ? "bg-blue-600" : "bg-slate-600"
                }`}
              >
                <span
                  className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                    formData.detectionEnabled ? "translate-x-6" : "translate-x-1"
                  }`}
                />
              </button>
            </div>

            <div className="py-3 border-b border-slate-700">
              <label className="block text-slate-200 font-medium mb-2">Detection Window</label>
              <p className="text-sm text-slate-400 mb-3">Number of days to analyze for decay detection</p>
              <div className="flex gap-2">
                {WINDOW_OPTIONS.map((option) => (
                  <button
                    key={option.value}
                    type="button"
                    onClick={() => handleChange("windowDays", option.value)}
                    className={`px-4 py-2 text-sm rounded-lg transition-colors ${
                      formData.windowDays === option.value
                        ? "bg-blue-600 text-white"
                        : "bg-slate-700 text-slate-300 hover:bg-slate-600"
                    }`}
                  >
                    {option.label}
                  </button>
                ))}
              </div>
              {errors.windowDays && <p className="text-red-400 text-sm mt-1">{errors.windowDays}</p>}
            </div>

            <div className="py-3">
              <p className="text-slate-200 font-medium mb-3">Thresholds</p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="minClicks" className="block text-sm text-slate-400 mb-1">Minimum Clicks</label>
                  <input
                    id="minClicks"
                    type="number"
                    value={formData.minClicksThreshold}
                    onChange={(e) => handleChange("minClicksThreshold", parseInt(e.target.value) || 0)}
                    className="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-200 focus:border-blue-500 focus:outline-none"
                    min="0"
                  />
                  {errors.minClicksThreshold && <p className="text-red-400 text-sm mt-1">{errors.minClicksThreshold}</p>}
                </div>
                <div>
                  <label htmlFor="minImpressions" className="block text-sm text-slate-400 mb-1">Minimum Impressions</label>
                  <input
                    id="minImpressions"
                    type="number"
                    value={formData.minImpressionsThreshold}
                    onChange={(e) => handleChange("minImpressionsThreshold", parseInt(e.target.value) || 0)}
                    className="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-200 focus:border-blue-500 focus:outline-none"
                    min="0"
                  />
                  {errors.minImpressionsThreshold && <p className="text-red-400 text-sm mt-1">{errors.minImpressionsThreshold}</p>}
                </div>
              </div>
            </div>
          </section>

          <section className="bg-slate-800/50 border border-slate-700 rounded-lg p-4" aria-labelledby="digest-heading">
            <h2 id="digest-heading" className="text-lg font-medium text-white mb-4">Digest Settings</h2>

            <div className="flex items-center justify-between py-3 border-b border-slate-700">
              <div>
                <p className="text-slate-200 font-medium">Enable Digest</p>
                <p className="text-sm text-slate-400">Receive periodic decay summary emails</p>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={formData.digestEnabled}
                onClick={() => handleChange("digestEnabled", !formData.digestEnabled)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                  formData.digestEnabled ? "bg-blue-600" : "bg-slate-600"
                }`}
              >
                <span
                  className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                    formData.digestEnabled ? "translate-x-6" : "translate-x-1"
                  }`}
                />
              </button>
            </div>

            {formData.digestEnabled && (
              <div className="py-3">
                <label htmlFor="digestFrequency" className="block text-sm text-slate-400 mb-2">Digest Frequency</label>
                <select
                  id="digestFrequency"
                  value={formData.digestFrequency}
                  onChange={(e) => handleChange("digestFrequency", e.target.value)}
                  className="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-200 focus:border-blue-500 focus:outline-none"
                >
                  {DIGEST_FREQUENCY_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </section>

          <div className="flex items-center gap-3 pt-4">
            <button
              type="submit"
              disabled={isSaving || !hasChanges}
              className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
            >
              <Save className="w-4 h-4" />
              {isSaving ? "Saving..." : "Save Settings"}
            </button>
            <button
              type="button"
              onClick={handleReset}
              disabled={!hasChanges}
              className="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 disabled:cursor-not-allowed text-slate-200 text-sm font-medium rounded-lg transition-colors"
            >
              <RotateCcw className="w-4 h-4" />
              Reset
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
