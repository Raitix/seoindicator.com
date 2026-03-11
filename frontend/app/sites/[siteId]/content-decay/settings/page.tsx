/**
 * Content Decay Settings Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Settings page for configuring content decay detection and notifications
 */

'use client';

import * as React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { Save, RotateCcw, Info, Bell, Clock, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
  useContentDecaySettings,
  useSaveContentDecaySettings,
} from '@/lib/hooks/useContentDecay';

// ============================================================
// Types
// ============================================================

interface SettingsForm {
  detectionEnabled: boolean;
  windowDays: number;
  minClicksThreshold: number;
  minImpressionsThreshold: number;
  digestEnabled: boolean;
  digestFrequency: 'weekly' | 'monthly';
}

const DEFAULT_SETTINGS: SettingsForm = {
  detectionEnabled: true,
  windowDays: 28,
  minClicksThreshold: 10,
  minImpressionsThreshold: 100,
  digestEnabled: true,
  digestFrequency: 'weekly',
};

// ============================================================
// Page Component
// ============================================================

export default function ContentDecaySettingsPage() {
  const params = useParams();
  const router = useRouter();
  const siteId = params.siteId as string;

  // Data hooks
  const { settings, isLoading, error, mutate } = useContentDecaySettings(siteId);
  const { saveSettings, isSaving } = useSaveContentDecaySettings(siteId);

  // Form state
  const [form, setForm] = React.useState<SettingsForm>(DEFAULT_SETTINGS);
  const [hasChanges, setHasChanges] = React.useState(false);

  // Initialize form when settings load
  React.useEffect(() => {
    if (settings) {
      setForm({
        detectionEnabled: settings.detectionEnabled ?? true,
        windowDays: settings.windowDays ?? 28,
        minClicksThreshold: settings.minClicksThreshold ?? 10,
        minImpressionsThreshold: settings.minImpressionsThreshold ?? 100,
        digestEnabled: settings.digestEnabled ?? true,
        digestFrequency: settings.digestFrequency ?? 'weekly',
      });
    }
  }, [settings]);

  // Update field
  const updateField = React.useCallback(<K extends keyof SettingsForm>(
    key: K,
    value: SettingsForm[K]
  ) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setHasChanges(true);
  }, []);

  // Reset to defaults
  const handleReset = React.useCallback(() => {
    setForm(DEFAULT_SETTINGS);
    setHasChanges(true);
    toast.info('Settings reset to defaults');
  }, []);

  // Save settings
  const handleSave = React.useCallback(async () => {
    const result = await saveSettings(form);
    if (result.success) {
      toast.success('Settings saved successfully');
      setHasChanges(false);
      mutate();
    } else {
      toast.error('Failed to save settings');
    }
  }, [form, saveSettings, mutate]);

  // Cancel changes
  const handleCancel = React.useCallback(() => {
    if (settings) {
      setForm({
        detectionEnabled: settings.detectionEnabled ?? true,
        windowDays: settings.windowDays ?? 28,
        minClicksThreshold: settings.minClicksThreshold ?? 10,
        minImpressionsThreshold: settings.minImpressionsThreshold ?? 100,
        digestEnabled: settings.digestEnabled ?? true,
        digestFrequency: settings.digestFrequency ?? 'weekly',
      });
      setHasChanges(false);
    }
  }, [settings]);

  // Loading state
  if (isLoading) {
    return (
      <main className="container mx-auto px-4 py-8">
        <div className="max-w-2xl mx-auto">
          <Skeleton className="h-8 w-48 mb-2" />
          <Skeleton className="h-4 w-64 mb-6" />
          <Skeleton className="h-96" />
        </div>
      </main>
    );
  }

  // Error state
  if (error) {
    return (
      <main className="container mx-auto px-4 py-8">
        <div className="max-w-2xl mx-auto">
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              Failed to load settings. Please try again.
            </AlertDescription>
          </Alert>
        </div>
      </main>
    );
  }

  return (
    <main className="container mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-4 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol className="flex items-center gap-2">
          <li>
            <Link href="/sites" className="hover:text-foreground">Sites</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}`} className="hover:text-foreground">Site {siteId}</Link>
          </li>
          <li>/</li>
          <li>
            <Link href={`/sites/${siteId}/content-decay`} className="hover:text-foreground">
              Content Decay
            </Link>
          </li>
          <li>/</li>
          <li className="text-foreground font-medium">Settings</li>
        </ol>
      </nav>

      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Content Decay Settings</h1>
        <p className="text-muted-foreground">
          Configure detection thresholds and notifications
        </p>
      </div>

      {/* Form */}
      <form
        data-testid="settings-form"
        className="space-y-6 max-w-2xl"
        onSubmit={(e) => {
          e.preventDefault();
          handleSave();
        }}
      >
        {/* Detection Section */}
        <section
          data-testid="detection-section"
          className="bg-white rounded-lg border p-6 shadow-sm"
        >
          <h2
            data-testid="section-detection-title"
            className="text-lg font-semibold mb-4"
          >
            Detection Settings
          </h2>

          {/* Detection Toggle */}
          <div
            data-testid="detection-toggle-group"
            className="flex items-center justify-between mb-6"
          >
            <div className="space-y-0.5">
              <Label htmlFor="detection-enabled" className="font-medium">
                Enable Detection
              </Label>
              <p className="text-sm text-muted-foreground">
                Automatically scan for content decay
              </p>
            </div>
            <Switch
              id="detection-enabled"
              data-testid="detection-toggle"
              checked={form.detectionEnabled}
              onCheckedChange={(checked) => updateField('detectionEnabled', checked)}
              aria-label="Enable content decay detection"
            />
          </div>

          {/* Window Size Selector */}
          <div data-testid="window-size-group" className="space-y-2">
            <Label htmlFor="window-size">Comparison Window</Label>
            <Select
              value={form.windowDays.toString()}
              onValueChange={(v) => updateField('windowDays', parseInt(v))}
            >
              <SelectTrigger id="window-size" data-testid="window-size-select">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7">7 days</SelectItem>
                <SelectItem value="14">14 days</SelectItem>
                <SelectItem value="28">28 days (recommended)</SelectItem>
                <SelectItem value="56">56 days</SelectItem>
              </SelectContent>
            </Select>
            <p className="text-xs text-muted-foreground">
              Number of days to compare for decay detection
            </p>
          </div>
        </section>

        {/* Thresholds Section */}
        <section
          data-testid="thresholds-section"
          className="bg-white rounded-lg border p-6 shadow-sm"
        >
          <h2
            data-testid="section-thresholds-title"
            className="text-lg font-semibold mb-4"
          >
            Thresholds
          </h2>

          {/* Min Clicks Threshold */}
          <div data-testid="min-clicks-group" className="space-y-2 mb-4">
            <Label htmlFor="min-clicks">Minimum Clicks Threshold</Label>
            <Input
              id="min-clicks"
              type="number"
              min="0"
              value={form.minClicksThreshold}
              onChange={(e) => updateField('minClicksThreshold', parseInt(e.target.value) || 0)}
              data-testid="min-clicks-input"
            />
            <p className="text-xs text-muted-foreground">
              Pages with fewer clicks are excluded from detection
            </p>
          </div>

          {/* Min Impressions Threshold */}
          <div data-testid="min-impressions-group" className="space-y-2">
            <Label htmlFor="min-impressions">Minimum Impressions Threshold</Label>
            <Input
              id="min-impressions"
              type="number"
              min="0"
              value={form.minImpressionsThreshold}
              onChange={(e) => updateField('minImpressionsThreshold', parseInt(e.target.value) || 0)}
              data-testid="min-impressions-input"
            />
            <p className="text-xs text-muted-foreground">
              Pages with fewer impressions are excluded from detection
            </p>
          </div>
        </section>

        {/* Digest Section */}
        <section
          data-testid="digest-section"
          className="bg-white rounded-lg border p-6 shadow-sm"
        >
          <h2
            data-testid="section-digest-title"
            className="text-lg font-semibold mb-4 flex items-center gap-2"
          >
            <Bell className="h-5 w-5" />
            Email Digest
          </h2>

          {/* Digest Toggle */}
          <div
            data-testid="digest-toggle-group"
            className="flex items-center justify-between mb-6"
          >
            <div className="space-y-0.5">
              <Label htmlFor="digest-enabled" className="font-medium">
                Enable Weekly Digest
              </Label>
              <p className="text-sm text-muted-foreground">
                Receive weekly summary of decay and recovery
              </p>
            </div>
            <Switch
              id="digest-enabled"
              data-testid="digest-toggle"
              checked={form.digestEnabled}
              onCheckedChange={(checked) => updateField('digestEnabled', checked)}
              aria-label="Enable email digest"
            />
          </div>

          {/* Digest Frequency */}
          <div data-testid="digest-frequency-group" className="space-y-2">
            <Label htmlFor="digest-frequency">Digest Frequency</Label>
            <Select
              value={form.digestFrequency}
              onValueChange={(v) => updateField('digestFrequency', v as 'weekly' | 'monthly')}
            >
              <SelectTrigger id="digest-frequency" data-testid="digest-frequency-select">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="weekly">Weekly (recommended)</SelectItem>
                <SelectItem value="monthly">Monthly</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </section>

        {/* Form Actions */}
        <div
          data-testid="form-actions"
          className="flex justify-between items-center pt-4"
        >
          <Button
            type="button"
            variant="outline"
            onClick={handleReset}
            data-testid="reset-btn"
          >
            <RotateCcw className="h-4 w-4 mr-2" />
            Reset to Defaults
          </Button>
          <div className="flex gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={handleCancel}
              disabled={!hasChanges}
              data-testid="cancel-btn"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isSaving || !hasChanges}
              data-testid="save-btn"
            >
              <Save className="h-4 w-4 mr-2" />
              {isSaving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </div>
      </form>

      {/* Back to Queue */}
      <div className="mt-8">
        <Link href={`/sites/${siteId}/content-decay`}>
          <Button variant="ghost">
            ← Back to Content Decay
          </Button>
        </Link>
      </div>
    </main>
  );
}
