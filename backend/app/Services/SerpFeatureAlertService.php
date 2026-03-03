<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\SerpFeature;
use App\Models\AlertSettings;
use App\Models\SiteNotificationSettings;
use App\Models\Notification;
use App\Mail\SerpFeatureAlertEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

/**
 * SerpFeatureAlertService for TASK-7-14.
 *
 * Handles SERP feature alert generation, templates, and delivery.
 * Integrates with the existing alert system (FEAT-002).
 */
class SerpFeatureAlertService
{
    /**
     * Alert types for SERP feature changes.
     */
    public const TYPE_SERP_FEATURE_GAINED = 'serp_feature_gained';
    public const TYPE_SERP_FEATURE_LOST = 'serp_feature_lost';

    /**
     * Deduplication window in seconds (1 minute).
     */
    public const DEDUPE_WINDOW_SECONDS = 60;

    /**
     * Alert templates for SERP feature changes.
     */
    public const ALERT_TEMPLATES = [
        'serp_feature_gained' => [
            'subject' => '🎉 New SERP Feature: {{featureName}}',
            'body' => 'Your page now appears in the {{featureName}} for "{{keyword}}". This can increase CTR by {{ctrImpact}}.',
            'short' => 'Gained {{featureName}} for "{{keyword}}"',
        ],
        'serp_feature_lost' => [
            'subject' => '⚠️ Lost SERP Feature: {{featureName}}',
            'body' => 'Your page no longer appears in the {{featureName}} for "{{keyword}}". Check your page and competitor activity.',
            'short' => 'Lost {{featureName}} for "{{keyword}}"',
        ],
    ];

    /**
     * Generate alert for SERP feature change.
     *
     * @param int $keywordId The keyword ID
     * @param string $featureType The SERP feature type (e.g., 'featured_snippet')
     * @param string $action 'gained' or 'lost'
     * @param array $context Additional context (url, detectedAt, etc.)
     * @return array|null Generated alert data or null if disabled
     */
    public function generateAlert(int $keywordId, string $featureType, string $action, array $context = []): ?array
    {
        // Validate inputs
        if (!in_array($action, ['gained', 'lost'])) {
            Log::warning('SerpFeatureAlertService: Invalid action', ['action' => $action]);
            return null;
        }

        if (!in_array($featureType, SerpFeature::FEATURE_TYPES)) {
            Log::warning('SerpFeatureAlertService: Invalid feature type', ['feature_type' => $featureType]);
            return null;
        }

        // Load keyword with relationships
        $keyword = Keyword::with(['user', 'site'])->find($keywordId);
        if (!$keyword || !$keyword->user) {
            Log::debug('SerpFeatureAlertService: Keyword or user not found', ['keyword_id' => $keywordId]);
            return null;
        }

        // Check deduplication
        if ($this->isDuplicate($keywordId, $featureType, $action)) {
            Log::info('SerpFeatureAlertService: Duplicate alert suppressed', [
                'keyword_id' => $keywordId,
                'feature_type' => $featureType,
                'action' => $action,
            ]);
            return null;
        }

        // Check if alerts are enabled
        $alertSettings = AlertSettings::firstOrCreate(
            ['user_id' => $keyword->user_id],
            AlertSettings::getDefaults()
        );

        if (!$alertSettings->global_enabled) {
            Log::debug('SerpFeatureAlertService: Global alerts disabled', ['user_id' => $keyword->user_id]);
            return null;
        }

        if (!$alertSettings->serp_feature_alerts_enabled) {
            Log::debug('SerpFeatureAlertService: SERP feature alerts disabled', ['user_id' => $keyword->user_id]);
            return null;
        }

        // Check if this specific feature type is enabled
        if (!$alertSettings->isSerpFeatureAlertEnabled($featureType)) {
            Log::debug('SerpFeatureAlertService: Feature type not enabled for alerts', [
                'feature_type' => $featureType,
                'user_id' => $keyword->user_id,
            ]);
            return null;
        }

        // Check site notification settings
        if ($keyword->site_id && !$this->isSiteAlertEnabled($keyword->site_id)) {
            Log::debug('SerpFeatureAlertService: Site SERP feature alerts disabled', ['site_id' => $keyword->site_id]);
            return null;
        }

        // Generate alert content
        $alertType = $action === 'gained' ? self::TYPE_SERP_FEATURE_GAINED : self::TYPE_SERP_FEATURE_LOST;
        $featureMeta = SerpFeature::FEATURE_META[$featureType] ?? [
            'name' => ucfirst(str_replace('_', ' ', $featureType)),
            'ctrImpact' => 'varies',
        ];

        $alert = [
            'type' => $alertType,
            'keyword_id' => $keywordId,
            'keyword' => $keyword->keyword,
            'feature_type' => $featureType,
            'feature_name' => $featureMeta['name'],
            'ctr_impact' => $featureMeta['ctrImpact'] ?? 'varies',
            'action' => $action,
            'url' => $context['url'] ?? $context['current_url'] ?? null,
            'previous_url' => $context['previous_url'] ?? null,
            'detected_at' => $context['detected_at'] ?? now(),
            'user_id' => $keyword->user_id,
            'site_id' => $keyword->site_id,
        ];

        // Apply templates
        $alert['subject'] = $this->renderTemplate(
            self::ALERT_TEMPLATES[$alertType]['subject'],
            $alert
        );
        $alert['body'] = $this->renderTemplate(
            self::ALERT_TEMPLATES[$alertType]['body'],
            $alert
        );
        $alert['short'] = $this->renderTemplate(
            self::ALERT_TEMPLATES[$alertType]['short'],
            $alert
        );

        // Record for deduplication
        $this->recordAlert($keywordId, $featureType, $action);

        Log::info('SerpFeatureAlertService: Alert generated', [
            'type' => $alertType,
            'keyword_id' => $keywordId,
            'feature_type' => $featureType,
        ]);

        return $alert;
    }

    /**
     * Deliver alert through configured channels.
     *
     * @param array $alert Alert data from generateAlert()
     * @return array Delivery results
     */
    public function deliverAlert(array $alert): array
    {
        $results = [
            'delivered' => false,
            'channels' => [],
        ];

        $user = \App\Models\User::find($alert['user_id']);
        if (!$user) {
            Log::error('SerpFeatureAlertService: User not found for delivery', ['user_id' => $alert['user_id']]);
            return $results;
        }

        // Get delivery channels
        $channels = $this->getDeliveryChannels($alert);

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $results['channels']['email'] = $this->deliverEmail($user, $alert);
                    break;

                case 'in_app':
                    $results['channels']['in_app'] = $this->deliverInApp($user, $alert);
                    break;
            }
        }

        $results['delivered'] = !empty($results['channels']);

        Log::info('SerpFeatureAlertService: Alert delivered', [
            'alert_type' => $alert['type'],
            'keyword_id' => $alert['keyword_id'],
            'channels' => array_keys($results['channels']),
        ]);

        return $results;
    }

    /**
     * Process SERP feature change event.
     *
     * @param array $event Event data from SerpFeatureChangeTracker
     * @return array|null Generated and delivered alert
     */
    public function processChangeEvent(array $event): ?array
    {
        $keywordId = $event['keyword_id'] ?? null;
        $featureType = $event['feature_type'] ?? null;
        $action = $event['action'] ?? null;

        if (!$keywordId || !$featureType || !$action) {
            Log::warning('SerpFeatureAlertService: Invalid event data', ['event' => $event]);
            return null;
        }

        $alert = $this->generateAlert($keywordId, $featureType, $action, $event);

        if ($alert) {
            $delivery = $this->deliverAlert($alert);
            $alert['delivery'] = $delivery;
        }

        return $alert;
    }

    /**
     * Render template with variable substitution.
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    protected function renderTemplate(string $template, array $variables): string
    {
        $rendered = $template;

        foreach ($variables as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $rendered = str_replace(
                    '{{' . $key . '}}',
                    (string) $value,
                    $rendered
                );
            }
        }

        return $rendered;
    }

    /**
     * Check if this alert is a duplicate within the deduplication window.
     *
     * @param int $keywordId
     * @param string $featureType
     * @param string $action
     * @return bool
     */
    protected function isDuplicate(int $keywordId, string $featureType, string $action): bool
    {
        $key = $this->getDedupeKey($keywordId, $featureType, $action);
        return Cache::has($key);
    }

    /**
     * Record alert for deduplication.
     *
     * @param int $keywordId
     * @param string $featureType
     * @param string $action
     */
    protected function recordAlert(int $keywordId, string $featureType, string $action): void
    {
        $key = $this->getDedupeKey($keywordId, $featureType, $action);
        Cache::put($key, true, self::DEDUPE_WINDOW_SECONDS);
    }

    /**
     * Get deduplication cache key.
     *
     * @param int $keywordId
     * @param string $featureType
     * @param string $action
     * @return string
     */
    protected function getDedupeKey(int $keywordId, string $featureType, string $action): string
    {
        return "serp_feature_alert:{$keywordId}:{$featureType}:{$action}";
    }

    /**
     * Check if site has SERP feature alerts enabled.
     *
     * @param int $siteId
     * @return bool
     */
    protected function isSiteAlertEnabled(int $siteId): bool
    {
        $settings = SiteNotificationSettings::where('site_id', $siteId)->first();

        if (!$settings) {
            return true; // Default to enabled if no settings
        }

        return $settings->serp_feature_alerts;
    }

    /**
     * Get delivery channels for alert.
     *
     * @param array $alert
     * @return array
     */
    protected function getDeliveryChannels(array $alert): array
    {
        $channels = [];

        // Check site settings first
        if ($alert['site_id']) {
            $siteSettings = SiteNotificationSettings::where('site_id', $alert['site_id'])->first();

            if ($siteSettings) {
                if ($siteSettings->email_notifications) {
                    $channels[] = 'email';
                }
                if ($siteSettings->in_app_notifications) {
                    $channels[] = 'in_app';
                }
                return $channels;
            }
        }

        // Default channels
        return ['email', 'in_app'];
    }

    /**
     * Deliver alert via email.
     *
     * @param \App\Models\User $user
     * @param array $alert
     * @return array
     */
    protected function deliverEmail(\App\Models\User $user, array $alert): array
    {
        try {
            Mail::to($user->email)
                ->queue(new SerpFeatureAlertEmail($user, $alert));

            Log::info('SerpFeatureAlertService: Email queued', [
                'user_id' => $user->id,
                'email' => $user->email,
                'alert_type' => $alert['type'],
            ]);

            return [
                'sent' => true,
                'queued_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('SerpFeatureAlertService: Email delivery failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deliver alert via in-app notification.
     *
     * @param \App\Models\User $user
     * @param array $alert
     * @return array
     */
    protected function deliverInApp(\App\Models\User $user, array $alert): array
    {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $alert['type'],
                'keyword_id' => $alert['keyword_id'],
                'data' => [
                    'keyword' => $alert['keyword'],
                    'feature_type' => $alert['feature_type'],
                    'feature_name' => $alert['feature_name'],
                    'action' => $alert['action'],
                    'url' => $alert['url'],
                    'previous_url' => $alert['previous_url'],
                    'ctr_impact' => $alert['ctr_impact'],
                    'short' => $alert['short'],
                    'subject' => $alert['subject'],
                    'body' => $alert['body'],
                ],
            ]);

            Log::info('SerpFeatureAlertService: In-app notification created', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'alert_type' => $alert['type'],
            ]);

            return [
                'sent' => true,
                'notification_id' => $notification->id,
            ];
        } catch (\Exception $e) {
            Log::error('SerpFeatureAlertService: In-app notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get alert templates.
     *
     * @return array
     */
    public static function getTemplates(): array
    {
        return self::ALERT_TEMPLATES;
    }

    /**
     * Get alert types.
     *
     * @return array
     */
    public static function getAlertTypes(): array
    {
        return [
            self::TYPE_SERP_FEATURE_GAINED,
            self::TYPE_SERP_FEATURE_LOST,
        ];
    }
}
