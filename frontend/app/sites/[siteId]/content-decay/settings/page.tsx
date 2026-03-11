/**
 * Content Decay Settings Page
 * Configuration page for content decay detection settings
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 */

"use client";

import * as React from "react";
import { useParams, useRouter } from "next/navigation";
import {
  Settings,
  LayoutDashboard,
  KanbanSquare,
  BarChart3,
  Save,
  RotateCcw,
  AlertCircle,
  Check,
  Info,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Alert,
  AlertDescription,
} from "@/components/ui/alert";
import {
  useContentDecaySettings,
  useSaveContentDecaySettings,
} from "@/lib/hooks/useContentDecay";
import { cn } from "@/lib/utils";

// ============================================================
// Loading Skeleton
// ============================================================

function PageSkeleton() {
  return (
    <div className="space-y-6 p-6" data-testid="page-skeleton">
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-10 w-24" />
      </div>
      <div className="grid gap-6 lg:grid-cols-2">
        <Skeleton className="h-48" data-testid="skeleton" />
        <Skeleton className="h-48" data-testid="skeleton" />
        <Skeleton className="h-32" data-testid="skeleton" />
      </div>
    </div>
  );
}

// ============================================================
// Navigation Tabs
// ============================================================

interface NavTabsProps {
  siteId: string;
}

function NavTabs({ siteId }: NavTabsProps) {
  const router = useRouter();

  return (
    <Tabs value="settings" className="w-auto">
      <TabsList>
        <TabsTrigger
          value="queue"
          onClick={() => router.push(`/sites/${siteId}/content-decay`)}
          className="gap-1.5"
        >
          <LayoutDashboard className="h-4 w-4" />
          Queue
        </TabsTrigger>
        <TabsTrigger
          value="board"
          onClick={() => router.push(`/sites/${siteId}/content-decay/board`)}
          className="gap-1.5"
        >
          <KanbanSquare className="h-4 w-4" />
          Board
        </TabsTrigger>
        <TabsTrigger
          value="recovery"
          onClick={() => router.push(`/sites/${siteId}/content-decay/recovery`)}
          className="gap-1.5"
        >
          <BarChart3 className="h-4 w-4" />
          Recovery
        </TabsTrigger>
        <TabsTrigger value="settings" className="gap-1.5">
          <Settings className="h-4 w-4" />
          Settings
        </TabsTrigger>
      </TabsList>
    </Tabs>
  );
}

// ============================================================
// Form Field Components
// ============================================================

interface FormFieldProps {
  label: string;
  description?: string;
  htmlFor: string;
  children: React.ReactNode;
  error?: string;
}

function FormField({ label, description, htmlFor, children, error }: FormFieldProps) {
  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <Label htmlFor={htmlFor} className="text-slate-200">
          {label}
        </Label>
        {description && (
          <span className="text-xs text-slate-400">{description}</span>
        )}
      </div>
      {children}
      {error && (
        <p className="text-xs text-red-400 flex items-center gap-1">
          <AlertCircle className="h-3 w-3" />
          {error}
        </p>
      )}
    </div>
  );
}

// ============================================================
// Settings Content
// ============================================================

function SettingsContent({ siteId }: { siteId: string }) {
  const router = useRouter();

  // Form state
  const [formData, setFormData] = React.useState({
    detectionEnabled: true,
    windowDays: 28,
    minClicksThreshold: 100,
    minImpressionsThreshold: 1000,
    digestEnabled: false,
    digestFrequency: "weekly" as "weekly" | "monthly",
  });

  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const [hasChanges, setHasChanges] = React.useState(false);

  // Hooks
  const {
    settings,
    isLoading,
  } = useContentDecaySettings(siteId);

  const { saveSettings, isSaving } = useSaveContentDecaySettings(siteId);

  // Memoize settings to prevent infinite loops when mock returns new object each render
  const settingsKey = React.useMemo(() => {
    if (!settings) return null;
    return JSON.stringify({
      detectionEnabled: settings.detectionEnabled,
      windowDays: settings.windowDays,
      minClicksThreshold: settings.minClicksThreshold,
      minImpressionsThreshold: settings.minImpressionsThreshold,
      digestEnabled: settings.digestEnabled,
      digestFrequency: settings.digestFrequency,
    });
  }, [settings]);

  // Load existing settings into form
  React.useEffect(() => {
    if (settings) {
      setFormData({
        detectionEnabled: settings.detectionEnabled ?? true,
        windowDays: settings.windowDays ?? 28,
        minClicksThreshold: settings.minClicksThreshold ?? 100,
        minImpressionsThreshold: settings.minImpressionsThreshold ?? 1000,
        digestEnabled: settings.digestEnabled ?? false,
        digestFrequency: settings.digestFrequency ?? "weekly",
      });
      setHasChanges(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settingsKey]);

  // Update form field
  const updateField = <K extends keyof typeof formData>(
    field: K,
    value: (typeof formData)[K]
  ) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setHasChanges(true);
    // Clear error for this field
    if (errors[field]) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
  };

  // Validate form
  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (formData.minClicksThreshold < 0) {
      newErrors.minClicksThreshold = "Must be 0 or greater";
    }
    if (formData.minImpressionsThreshold < 0) {
      newErrors.minImpressionsThreshold = "Must be 0 or greater";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Save handler
  const handleSave = async () => {
    if (!validate()) return;

    const result = await saveSettings(formData);
    if (result.success) {
      setHasChanges(false);
    }
  };

  // Reset handler
  const handleReset = () => {
    if (settings) {
      setFormData({
        detectionEnabled: settings.detectionEnabled ?? true,
        windowDays: settings.windowDays ?? 28,
        minClicksThreshold: settings.minClicksThreshold ?? 100,
        minImpressionsThreshold: settings.minImpressionsThreshold ?? 1000,
        digestEnabled: settings.digestEnabled ?? false,
        digestFrequency: settings.digestFrequency ?? "weekly",
      });
      setHasChanges(false);
      setErrors({});
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
        <PageSkeleton />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900">
      <div className="container mx-auto px-4 py-6 max-w-4xl">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white flex items-center gap-2">
              <Settings className="h-6 w-6 text-slate-400" />
              Content Decay Settings
            </h1>
            <p className="text-sm text-slate-400">
              Configure content decay detection for this site
            </p>
          </div>

          <div className="flex items-center gap-3">
            <NavTabs siteId={siteId} />
          </div>
        </div>

        {/* Unsaved Changes Indicator */}
        {hasChanges && (
          <Alert className="mb-6 bg-amber-500/10 border-amber-500/30">
            <AlertCircle className="h-4 w-4 text-amber-500" />
            <AlertDescription className="text-amber-200">
              You have unsaved changes. Remember to save your settings.
            </AlertDescription>
          </Alert>
        )}

        {/* Detection Settings */}
        <Card className="bg-slate-800/50 border-slate-700 mb-6">
          <CardHeader>
            <CardTitle className="text-slate-200">Detection Settings</CardTitle>
            <CardDescription className="text-slate-400">
              Configure how content decay is detected for this site
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Detection Toggle */}
            <div className="flex items-center justify-between">
              <div>
                <Label htmlFor="detection-enabled" className="text-slate-200">
                  Enable Content Decay Detection
                </Label>
                <p className="text-xs text-slate-400 mt-1">
                  Automatically scan for pages with declining performance
                </p>
              </div>
              <Switch
                id="detection-enabled"
                checked={formData.detectionEnabled}
                onCheckedChange={(checked) => updateField("detectionEnabled", checked)}
                data-testid="detection-toggle"
              />
            </div>

            {/* Window Size */}
            <FormField
              label="Detection Window"
              description="Time period for comparison"
              htmlFor="window-days"
            >
              <Select
                value={formData.windowDays.toString()}
                onValueChange={(v) => updateField("windowDays", parseInt(v, 10))}
              >
                <SelectTrigger className="w-full bg-slate-700 border-slate-600 text-slate-200">
                  <SelectValue placeholder="Select window size" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="7">7 days</SelectItem>
                  <SelectItem value="14">14 days</SelectItem>
                  <SelectItem value="28">28 days</SelectItem>
                  <SelectItem value="56">56 days</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-xs text-slate-500 mt-1">
                Compare current performance to the previous window
              </p>
            </FormField>

            {/* Thresholds */}
            <div className="grid gap-4 md:grid-cols-2">
              <FormField
                label="Minimum Clicks Threshold"
                htmlFor="min-clicks"
                error={errors.minClicksThreshold}
              >
                <Input
                  id="min-clicks"
                  type="number"
                  value={formData.minClicksThreshold}
                  onChange={(e) =>
                    updateField("minClicksThreshold", parseInt(e.target.value, 10) || 0)
                  }
                  className="bg-slate-700 border-slate-600 text-slate-200"
                  data-testid="min-clicks-input"
                />
                <p className="text-xs text-slate-500 mt-1">
                  Pages below this threshold are excluded from detection
                </p>
              </FormField>

              <FormField
                label="Minimum Impressions Threshold"
                htmlFor="min-impressions"
                error={errors.minImpressionsThreshold}
              >
                <Input
                  id="min-impressions"
                  type="number"
                  value={formData.minImpressionsThreshold}
                  onChange={(e) =>
                    updateField("minImpressionsThreshold", parseInt(e.target.value, 10) || 0)
                  }
                  className="bg-slate-700 border-slate-600 text-slate-200"
                  data-testid="min-impressions-input"
                />
                <p className="text-xs text-slate-500 mt-1">
                  Pages below this threshold are excluded from detection
                </p>
              </FormField>
            </div>
          </CardContent>
        </Card>

        {/* Digest Settings */}
        <Card className="bg-slate-800/50 border-slate-700 mb-6">
          <CardHeader>
            <CardTitle className="text-slate-200">Notification Digest</CardTitle>
            <CardDescription className="text-slate-400">
              Receive periodic updates about content decay
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Digest Toggle */}
            <div className="flex items-center justify-between">
              <div>
                <Label htmlFor="digest-enabled" className="text-slate-200">
                  Enable Email Digest
                </Label>
                <p className="text-xs text-slate-400 mt-1">
                  Receive regular summaries of content decay activity
                </p>
              </div>
              <Switch
                id="digest-enabled"
                checked={formData.digestEnabled}
                onCheckedChange={(checked) => updateField("digestEnabled", checked)}
                data-testid="digest-toggle"
              />
            </div>

            {/* Digest Frequency */}
            {formData.digestEnabled && (
              <FormField
                label="Digest Frequency"
                htmlFor="digest-frequency"
              >
                <Select
                  value={formData.digestFrequency}
                  onValueChange={(v) => updateField("digestFrequency", v as "weekly" | "monthly")}
                >
                  <SelectTrigger className="w-full bg-slate-700 border-slate-600 text-slate-200">
                    <SelectValue placeholder="Select frequency" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="weekly">Weekly</SelectItem>
                    <SelectItem value="monthly">Monthly</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
            )}
          </CardContent>
        </Card>

        {/* Info Card */}
        <Card className="bg-slate-800/30 border-slate-700 mb-6">
          <CardContent className="p-4">
            <div className="flex gap-3">
              <Info className="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5" />
              <div className="text-sm text-slate-300">
                <p className="font-medium mb-1">How Detection Works</p>
                <p className="text-slate-400">
                  Content decay detection compares current page performance to a previous period.
                  Pages showing significant declines in clicks, impressions, or rankings are flagged
                  as candidates for refresh. Adjust thresholds to focus on pages that matter most to
                  your traffic.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Action Buttons */}
        <div className="flex items-center justify-end gap-3">
          {hasChanges && (
            <Button
              variant="outline"
              onClick={handleReset}
              disabled={isSaving}
              className="border-slate-700 text-slate-300"
            >
              <RotateCcw className="h-4 w-4 mr-2" />
              Reset
            </Button>
          )}
          <Button
            onClick={handleSave}
            disabled={isSaving || !hasChanges}
            className="bg-primary text-primary-foreground"
          >
            {isSaving ? (
              <>
                <span className="animate-spin mr-2">ΓÅ│</span>
                Saving...
              </>
            ) : (
              <>
                <Save className="h-4 w-4 mr-2" />
                Save Settings
              </>
            )}
          </Button>
        </div>

        {/* Status Badge */}
        <div className="mt-6 flex items-center gap-2">
          <Badge
            variant={formData.detectionEnabled ? "default" : "secondary"}
            className={cn(
              formData.detectionEnabled
                ? "bg-green-500/20 text-green-400 border-green-500/30"
                : "bg-slate-700 text-slate-400"
            )}
          >
            {formData.detectionEnabled ? (
              <>
                <Check className="h-3 w-3 mr-1" />
                Detection Active
              </>
            ) : (
              "Detection Paused"
            )}
          </Badge>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Page Component
// ============================================================

export default function SettingsPage() {
  const params = useParams();
  const siteId = params.siteId as string;

  if (!siteId) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <p className="text-slate-400">Site not found</p>
      </div>
    );
  }

  return <SettingsContent siteId={siteId} />;
}
