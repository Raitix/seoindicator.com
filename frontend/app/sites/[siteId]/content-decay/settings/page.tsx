"use client";

import { Suspense, useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { Metadata } from "next";
import { ArrowLeft, Save, RotateCcw, AlertCircle, Check } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  useContentDecaySettings,
  useSaveContentDecaySettings,
} from "@/lib/hooks/useContentDecay";
import { cn } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Content Decay Settings | SEOIndicator",
  description: "Configure content decay detection settings for your site.",
};

interface SettingsState {
  detectionEnabled: boolean;
  windowDays: number;
  minClicksThreshold: number;
  minImpressionsThreshold: number;
  digestEnabled: boolean;
  digestFrequency: "weekly" | "monthly";
}

const WINDOW_OPTIONS = [
  { value: 7, label: "7 days" },
  { value: 14, label: "14 days" },
  { value: 28, label: "28 days" },
  { value: 56, label: "56 days" },
];

const FREQUENCY_OPTIONS = [
  { value: "weekly", label: "Weekly" },
  { value: "monthly", label: "Monthly" },
];

function SettingsContent({ siteId }: { siteId: string }) {
  const router = useRouter();
  const [settings, setSettings] = useState<SettingsState>({
    detectionEnabled: true,
    windowDays: 28,
    minClicksThreshold: 100,
    minImpressionsThreshold: 1000,
    digestEnabled: true,
    digestFrequency: "weekly",
  });
  const [hasChanges, setHasChanges] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  const { settings: loadedSettings, isLoading, error } = useContentDecaySettings(siteId);
  const { saveSettings, isSaving } = useSaveContentDecaySettings(siteId);

  // Load settings when data arrives
  useEffect(() => {
    if (loadedSettings) {
      setSettings({
        detectionEnabled: loadedSettings.detectionEnabled ?? true,
        windowDays: loadedSettings.windowDays ?? 28,
        minClicksThreshold: loadedSettings.minClicksThreshold ?? 100,
        minImpressionsThreshold: loadedSettings.minImpressionsThreshold ?? 1000,
        digestEnabled: loadedSettings.digestEnabled ?? true,
        digestFrequency: loadedSettings.digestFrequency ?? "weekly",
      });
    }
  }, [loadedSettings]);

  const handleChange = <K extends keyof SettingsState>(key: K, value: SettingsState[K]) => {
    setSettings((prev) => ({ ...prev, [key]: value }));
    setHasChanges(true);
    setSaveSuccess(false);
    setValidationErrors((prev) => ({ ...prev, [key]: "" }));
  };

  const validateSettings = (): boolean => {
    const errors: Record<string, string> = {};

    if (settings.minClicksThreshold < 0) {
      errors.minClicksThreshold = "Minimum clicks must be 0 or greater";
    }
    if (settings.minImpressionsThreshold < 0) {
      errors.minImpressionsThreshold = "Minimum impressions must be 0 or greater";
    }
    if (settings.windowDays < 7 || settings.windowDays > 90) {
      errors.windowDays = "Window must be between 7 and 90 days";
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSave = async () => {
    if (!validateSettings()) return;

    const result = await saveSettings(settings);
    if (result.success) {
      setHasChanges(false);
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    }
  };

  const handleReset = () => {
    if (loadedSettings) {
      setSettings({
        detectionEnabled: loadedSettings.detectionEnabled ?? true,
        windowDays: loadedSettings.windowDays ?? 28,
        minClicksThreshold: loadedSettings.minClicksThreshold ?? 100,
        minImpressionsThreshold: loadedSettings.minImpressionsThreshold ?? 1000,
        digestEnabled: loadedSettings.digestEnabled ?? true,
        digestFrequency: loadedSettings.digestFrequency ?? "weekly",
      });
    } else {
      setSettings({
        detectionEnabled: true,
        windowDays: 28,
        minClicksThreshold: 100,
        minImpressionsThreshold: 1000,
        digestEnabled: true,
        digestFrequency: "weekly",
      });
    }
    setHasChanges(false);
    setValidationErrors({});
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.push(`/sites/${siteId}/content-decay`)}
            data-testid="back-button"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Queue
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-white">Settings</h1>
            <p className="text-sm text-slate-400">
              Configure content decay detection
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {hasChanges && (
            <Button
              variant="outline"
              size="sm"
              onClick={handleReset}
              data-testid="reset-button"
            >
              <RotateCcw className="h-4 w-4 mr-2" />
              Reset
            </Button>
          )}
          <Button
            size="sm"
            onClick={handleSave}
            disabled={!hasChanges || isSaving}
            data-testid="save-button"
          >
            {isSaving ? (
              <RotateCcw className="h-4 w-4 mr-2 animate-spin" />
            ) : saveSuccess ? (
              <Check className="h-4 w-4 mr-2 text-green-500" />
            ) : (
              <Save className="h-4 w-4 mr-2" />
            )}
            {saveSuccess ? "Saved!" : "Save Changes"}
          </Button>
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive" data-testid="error-alert">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Failed to load settings. Please try again.
          </AlertDescription>
        </Alert>
      )}

      {/* Settings Cards */}
      <div className="space-y-6">
        {/* Detection Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Detection Settings</CardTitle>
            <CardDescription>
              Configure how content decay is detected on your site
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Detection Toggle */}
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label htmlFor="detection-enabled" className="text-base">
                  Detection Enabled
                </Label>
                <p className="text-sm text-muted-foreground">
                  Automatically scan for content decay on your site
                </p>
              </div>
              <Switch
                id="detection-enabled"
                checked={settings.detectionEnabled}
                onCheckedChange={(checked) => handleChange("detectionEnabled", checked)}
                data-testid="detection-toggle"
              />
            </div>

            {/* Window Size */}
            <div className="space-y-2">
              <Label htmlFor="window-days" className="text-base">
                Comparison Window
              </Label>
              <p className="text-sm text-muted-foreground">
                Time window for comparing current vs previous performance
              </p>
              <Select
                value={settings.windowDays.toString()}
                onValueChange={(value) => handleChange("windowDays", parseInt(value))}
                disabled={!settings.detectionEnabled}
              >
                <SelectTrigger
                  id="window-days"
                  className="w-40"
                  data-testid="window-select"
                >
                  <SelectValue placeholder="Select window" />
                </SelectTrigger>
                <SelectContent>
                  {WINDOW_OPTIONS.map((opt) => (
                    <SelectItem key={opt.value} value={opt.value.toString()}>
                      {opt.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Thresholds */}
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="min-clicks" className="text-base">
                  Minimum Clicks Threshold
                </Label>
                <p className="text-sm text-muted-foreground">
                  Only analyze pages with at least this many clicks
                </p>
                <Input
                  id="min-clicks"
                  type="number"
                  min={0}
                  value={settings.minClicksThreshold}
                  onChange={(e) => handleChange("minClicksThreshold", parseInt(e.target.value) || 0)}
                  disabled={!settings.detectionEnabled}
                  className={cn(
                    "w-40",
                    validationErrors.minClicksThreshold && "border-red-500"
                  )}
                  data-testid="min-clicks-input"
                />
                {validationErrors.minClicksThreshold && (
                  <p className="text-xs text-red-500" data-testid="error-min-clicks">
                    {validationErrors.minClicksThreshold}
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="min-impressions" className="text-base">
                  Minimum Impressions Threshold
                </Label>
                <p className="text-sm text-muted-foreground">
                  Only analyze pages with at least this many impressions
                </p>
                <Input
                  id="min-impressions"
                  type="number"
                  min={0}
                  value={settings.minImpressionsThreshold}
                  onChange={(e) => handleChange("minImpressionsThreshold", parseInt(e.target.value) || 0)}
                  disabled={!settings.detectionEnabled}
                  className={cn(
                    "w-40",
                    validationErrors.minImpressionsThreshold && "border-red-500"
                  )}
                  data-testid="min-impressions-input"
                />
                {validationErrors.minImpressionsThreshold && (
                  <p className="text-xs text-red-500" data-testid="error-min-impressions">
                    {validationErrors.minImpressionsThreshold}
                  </p>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Notification Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Notification Settings</CardTitle>
            <CardDescription>
              Configure how you receive content decay updates
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Digest Toggle */}
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label htmlFor="digest-enabled" className="text-base">
                  Email Digest
                </Label>
                <p className="text-sm text-muted-foreground">
                  Receive periodic email summaries of content decay
                </p>
              </div>
              <Switch
                id="digest-enabled"
                checked={settings.digestEnabled}
                onCheckedChange={(checked) => handleChange("digestEnabled", checked)}
                data-testid="digest-toggle"
              />
            </div>

            {/* Digest Frequency */}
            <div className="space-y-2">
              <Label htmlFor="digest-frequency" className="text-base">
                Digest Frequency
              </Label>
              <p className="text-sm text-muted-foreground">
                How often to receive email digests
              </p>
              <Select
                value={settings.digestFrequency}
                onValueChange={(value) => handleChange("digestFrequency", value as "weekly" | "monthly")}
                disabled={!settings.digestEnabled}
              >
                <SelectTrigger
                  id="digest-frequency"
                  className="w-40"
                  data-testid="frequency-select"
                >
                  <SelectValue placeholder="Select frequency" />
                </SelectTrigger>
                <SelectContent>
                  {FREQUENCY_OPTIONS.map((opt) => (
                    <SelectItem key={opt.value} value={opt.value}>
                      {opt.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Info Card */}
        <Card className="border-slate-800 bg-slate-900/50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-blue-400 mt-0.5" />
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">
                  <strong className="text-white">How detection works:</strong> The system compares
                  your page performance between two consecutive time windows. Pages showing
                  significant decline in clicks, impressions, or position are flagged as
                  decay candidates.
                </p>
                <p className="text-sm text-muted-foreground">
                  <strong className="text-white">Recommendation:</strong> Use a 28-day window
                  for most sites. Shorter windows may detect decay faster but with more
                  noise. Longer windows provide more stable signals.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Unsaved Changes Warning */}
      {hasChanges && (
        <div className="fixed bottom-4 right-4 z-50">
          <Badge
            variant="outline"
            className="bg-yellow-500/10 border-yellow-500/50 text-yellow-500 px-3 py-2"
            data-testid="unsaved-changes-badge"
          >
            You have unsaved changes
          </Badge>
        </div>
      )}
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <Skeleton className="h-10 w-48" data-testid="skeleton" />
        <Skeleton className="h-10 w-32" data-testid="skeleton" />
      </div>
      <Skeleton className="h-48 w-full" data-testid="skeleton" />
      <Skeleton className="h-48 w-full" data-testid="skeleton" />
    </div>
  );
}

export default function SettingsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 px-4 py-10">
      <div className="mx-auto w-full max-w-3xl">
        <Suspense fallback={<LoadingSkeleton />}>
          <SettingsContent siteId={siteId} />
        </Suspense>
      </div>
    </div>
  );
}
