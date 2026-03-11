'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { Mail, Link, Bell, Save, X, Loader2, Check, RefreshCw, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AlertPreferences, AlertType } from '@/lib/ai-visibility/types';

interface AlertPreferencesFormProps {
  preferences?: AlertPreferences;
  onSave?: (preferences: Partial<AlertPreferences>) => Promise<void>;
  onCancel?: () => void;
  onTestWebhook?: (url: string) => Promise<{ success: boolean; message?: string }>;
  onReset?: () => void;
  isLoading?: boolean;
  isSaving?: boolean;
}

const ALERT_TYPE_OPTIONS: { value: AlertType; label: string; description: string }[] = [
  {
    value: 'mention_spike',
    label: 'Mention Spike',
    description: 'Notify when brand mentions increase significantly',
  },
  {
    value: 'mention_drop',
    label: 'Mention Drop',
    description: 'Notify when brand mentions decrease significantly',
  },
  {
    value: 'competitor_gain',
    label: 'Competitor Gain',
    description: 'Notify when a competitor gains visibility',
  },
  {
    value: 'sentiment_change',
    label: 'Sentiment Change',
    description: 'Notify when brand sentiment shifts',
  },
];

const DEFAULT_PREFERENCES: AlertPreferences = {
  email_notifications_enabled: true,
  webhook_url: null,
  alert_types: ['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'],
};

export function AlertPreferencesForm({
  preferences = DEFAULT_PREFERENCES,
  onSave,
  onCancel,
  onTestWebhook,
  onReset,
  isLoading = false,
  isSaving = false,
}: AlertPreferencesFormProps) {
  const [formData, setFormData] = useState<AlertPreferences>(preferences);
  const [hasChanges, setHasChanges] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const [webhookTestStatus, setWebhookTestStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [webhookTestMessage, setWebhookTestMessage] = useState<string | null>(null);

  // Reset form when preferences change
  useEffect(() => {
    setFormData(preferences);
    setHasChanges(false);
  }, [preferences]);

  // Check for changes
  useEffect(() => {
    const changed = JSON.stringify(formData) !== JSON.stringify(preferences);
    setHasChanges(changed);
  }, [formData, preferences]);

  const handleEmailToggle = useCallback(() => {
    setFormData((prev) => ({
      ...prev,
      email_notifications_enabled: !prev.email_notifications_enabled,
    }));
    setErrors((prev) => ({ ...prev, email: '' }));
  }, []);

  const handleWebhookChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value.trim() || null;
    setFormData((prev) => ({
      ...prev,
      webhook_url: value,
    }));
    setErrors((prev) => ({ ...prev, webhook_url: '' }));
    setWebhookTestStatus('idle');
    setWebhookTestMessage(null);
  }, []);

  const handleClearWebhook = useCallback(() => {
    setFormData((prev) => ({
      ...prev,
      webhook_url: null,
    }));
    setErrors((prev) => ({ ...prev, webhook_url: '' }));
    setWebhookTestStatus('idle');
    setWebhookTestMessage(null);
  }, []);

  const handleAlertTypeToggle = useCallback((type: AlertType) => {
    setFormData((prev) => {
      const current = prev.alert_types || [];
      const updated = current.includes(type)
        ? current.filter((t) => t !== type)
        : [...current, type];
      return { ...prev, alert_types: updated };
    });
    setErrors((prev) => ({ ...prev, alert_types: '' }));
  }, []);

  const validate = useCallback((): boolean => {
    const newErrors: Record<string, string> = {};

    // Webhook URL validation
    if (formData.webhook_url) {
      try {
        new URL(formData.webhook_url);
      } catch {
        newErrors.webhook_url = 'Invalid URL format';
      }
    }

    // At least one alert type required
    if (!formData.alert_types || formData.alert_types.length === 0) {
      newErrors.alert_types = 'At least one alert type is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  const handleSave = useCallback(async () => {
    if (!validate()) return;

    setSuccessMessage(null);
    try {
      await onSave?.(formData);
      setSuccessMessage('Preferences saved successfully!');
      setHasChanges(false);
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch {
      setErrors({ submit: 'Failed to save preferences' });
    }
  }, [validate, formData, onSave]);

  const handleCancel = useCallback(() => {
    if (hasChanges) {
      setShowConfirmDialog(true);
    } else {
      onCancel?.();
    }
  }, [hasChanges, onCancel]);

  const handleDiscard = useCallback(() => {
    setFormData(preferences);
    setShowConfirmDialog(false);
    onCancel?.();
  }, [preferences, onCancel]);

  const handleKeepEditing = useCallback(() => {
    setShowConfirmDialog(false);
  }, []);

  const handleTestWebhook = useCallback(async () => {
    if (!formData.webhook_url) return;

    setWebhookTestStatus('loading');
    setWebhookTestMessage(null);
    try {
      const result = await onTestWebhook?.(formData.webhook_url);
      if (result?.success) {
        setWebhookTestStatus('success');
        setWebhookTestMessage(result.message || 'Webhook test successful');
      } else {
        setWebhookTestStatus('error');
        setWebhookTestMessage(result?.message || 'Webhook test failed');
      }
      setTimeout(() => {
        setWebhookTestStatus('idle');
        setWebhookTestMessage(null);
      }, 3000);
    } catch (err) {
      setWebhookTestStatus('error');
      setWebhookTestMessage(err instanceof Error ? err.message : 'Webhook test failed');
      setTimeout(() => {
        setWebhookTestStatus('idle');
        setWebhookTestMessage(null);
      }, 3000);
    }
  }, [formData.webhook_url, onTestWebhook]);

  const handleReset = useCallback(() => {
    onReset?.();
  }, [onReset]);

  // Check for mobile
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;

  // Loading skeleton
  if (isLoading) {
    return (
      <div data-testid="alert-preferences-form" className="space-y-6">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} data-testid={`skeleton-${i}`} className="animate-pulse space-y-2">
            <div className="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded" />
            <div className="h-10 w-full bg-slate-200 dark:bg-slate-700 rounded" />
          </div>
        ))}
      </div>
    );
  }

  return (
    <div
      data-testid="alert-preferences-form"
      className={cn(
        'space-y-6 bg-white dark:bg-slate-800 rounded-lg p-6',
        isMobile && 'flex-col'
      )}
    >
      {/* Success Message */}
      {successMessage && (
        <div className="flex items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-lg">
          <Check className="h-4 w-4" />
          {successMessage}
        </div>
      )}

      {/* Submit Error */}
      {errors.submit && (
        <div className="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg">
          <AlertCircle className="h-4 w-4" />
          {errors.submit}
        </div>
      )}

      {/* Email Notifications Toggle */}
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <label
            htmlFor="email-notifications"
            className="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100"
          >
            <Mail className="h-4 w-4" />
            Email Notifications
          </label>
          <button
            id="email-notifications"
            role="switch"
            aria-checked={formData.email_notifications_enabled}
            onClick={handleEmailToggle}
            className={cn(
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              formData.email_notifications_enabled
                ? 'bg-blue-500'
                : 'bg-slate-200 dark:bg-slate-700'
            )}
          >
            <span
              className={cn(
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                formData.email_notifications_enabled ? 'translate-x-6' : 'translate-x-1'
              )}
            />
          </button>
        </div>
        <p className="text-xs text-slate-500 dark:text-slate-400">
          Receive email notifications for important alerts
        </p>
      </div>

      {/* Webhook URL */}
      <div className="space-y-2">
        <label
          htmlFor="webhook-url"
          className="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100"
        >
          <Link className="h-4 w-4" />
          Webhook URL
          <span className="text-xs text-slate-400">(optional)</span>
        </label>
        <div className="flex gap-2">
          <input
            id="webhook-url"
            type="url"
            value={formData.webhook_url || ''}
            onChange={handleWebhookChange}
            placeholder="https://your-webhook-url.com/endpoint"
            className={cn(
              'flex-1 px-3 py-2 text-sm border rounded-lg',
              'bg-white dark:bg-slate-900',
              'border-slate-200 dark:border-slate-700',
              'focus:outline-none focus:ring-2 focus:ring-blue-500',
              errors.webhook_url && 'border-red-500'
            )}
          />
          {formData.webhook_url && (
            <button
              onClick={handleClearWebhook}
              className="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
              aria-label="Clear webhook"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>
        {errors.webhook_url && (
          <p role="alert" className="text-xs text-red-500">
            {errors.webhook_url}
          </p>
        )}
        {formData.webhook_url && onTestWebhook && (
          <div className="space-y-1">
            <button
              onClick={handleTestWebhook}
              disabled={webhookTestStatus === 'loading'}
              className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
            >
              {webhookTestStatus === 'loading' ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Testing...
                </>
              ) : webhookTestStatus === 'success' ? (
                <>
                  <Check className="h-4 w-4 text-emerald-500" />
                  {webhookTestMessage || 'Webhook test successful'}
                </>
              ) : webhookTestStatus === 'error' ? (
                <>
                  <AlertCircle className="h-4 w-4 text-red-500" />
                  {webhookTestMessage || 'Webhook test failed'}
                </>
              ) : (
                <>
                  <RefreshCw className="h-4 w-4" />
                  Test webhook
                </>
              )}
            </button>
            {webhookTestMessage && webhookTestStatus !== 'loading' && (
              <p className={cn(
                'text-xs',
                webhookTestStatus === 'success' ? 'text-emerald-500' : 'text-red-500'
              )}>
                {webhookTestMessage}
              </p>
            )}
          </div>
        )}
      </div>

      {/* Alert Types */}
      <div className="space-y-3">
        <label className="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
          <Bell className="h-4 w-4" />
          Alert Types
        </label>
        <div className="space-y-2">
          {ALERT_TYPE_OPTIONS.map((option) => {
            const isChecked = formData.alert_types?.includes(option.value);
            return (
              <label
                key={option.value}
                className="flex items-start gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={isChecked}
                  onChange={() => handleAlertTypeToggle(option.value)}
                  className="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-500 focus:ring-blue-500"
                  aria-label={option.label}
                />
                <div className="flex-1">
                  <p className="text-sm font-medium text-slate-900 dark:text-slate-100">
                    {option.label}
                  </p>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    {option.description}
                  </p>
                </div>
              </label>
            );
          })}
        </div>
        {errors.alert_types && (
          <p role="alert" className="text-xs text-red-500">
            {errors.alert_types}
          </p>
        )}
      </div>

      {/* Actions */}
      <div className="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
        <button
          onClick={handleSave}
          disabled={!hasChanges || isSaving}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isSaving ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Saving...
            </>
          ) : (
            <>
              <Save className="h-4 w-4" />
              Save
            </>
          )}
        </button>

        {onCancel && (
          <button
            onClick={handleCancel}
            className="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg"
          >
            Cancel
          </button>
        )}

        {onReset && (
          <button
            onClick={handleReset}
            className="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
          >
            Reset to defaults
          </button>
        )}
      </div>

      {/* Confirm Dialog */}
      {showConfirmDialog && (
        <div
          role="alertdialog"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
          <div className="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-sm w-full mx-4">
            <h3 className="font-medium text-lg mb-2">Unsaved Changes</h3>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
              You have unsaved changes. Discard them and leave?
            </p>
            <div className="flex gap-2">
              <button
                onClick={handleDiscard}
                className="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg"
              >
                Discard
              </button>
              <button
                onClick={handleKeepEditing}
                className="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg"
              >
                Keep editing
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
