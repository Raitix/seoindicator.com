<?php

namespace App\Services;

use App\Models\PWAAnalysis;

/**
 * Service for generating PWA improvement suggestions.
 *
 * Transforms PWA analysis gaps into actionable user guidance.
 * Generates suggestions sorted by priority (high → medium → low).
 *
 * @see TASK-24-7
 * @see SPEC-024
 * @see TEST-24-7
 */
class PWASuggestionService
{
    /**
     * Suggestion templates for manifest fields.
     *
     * Structure: [field => [priority, message, guide]]
     */
    private const FIELD_SUGGESTIONS = [
        // Required fields (high priority)
        'name' => [
            'priority' => 'high',
            'message' => 'Add a "name" field to your manifest for the full app name.',
            'guide' => '#manifest-name',
        ],
        'short_name' => [
            'priority' => 'high',
            'message' => 'Add a "short_name" field to your manifest for home screen display.',
            'guide' => '#manifest-short-name',
        ],
        'start_url' => [
            'priority' => 'high',
            'message' => 'Add a "start_url" field to your manifest to define the app entry point.',
            'guide' => '#manifest-start-url',
        ],
        'display' => [
            'priority' => 'high',
            'message' => 'Add a "display" field to your manifest (standalone recommended).',
            'guide' => '#manifest-display',
        ],
        // Recommended fields (medium priority)
        'theme_color' => [
            'priority' => 'medium',
            'message' => 'Add a "theme_color" to your manifest for browser UI theming.',
            'guide' => '#manifest-theme-color',
        ],
        'background_color' => [
            'priority' => 'medium',
            'message' => 'Add a "background_color" to your manifest for the splash screen background.',
            'guide' => '#manifest-background-color',
        ],
        // Optional fields (low priority)
        'description' => [
            'priority' => 'low',
            'message' => 'Add a "description" to your manifest for better app store presence.',
            'guide' => '#manifest-description',
        ],
        // Manifest validation
        'manifest' => [
            'priority' => 'high',
            'message' => 'Add a web app manifest to enable PWA features.',
            'guide' => '#manifest-basics',
        ],
        'manifest_valid' => [
            'priority' => 'high',
            'message' => 'Fix your manifest - it contains invalid JSON or structure.',
            'guide' => '#manifest-basics',
        ],
    ];

    /**
     * Suggestion templates for icons.
     *
     * Structure: [size => [priority, message, guide]]
     */
    private const ICON_SUGGESTIONS = [
        // Required icons (high priority)
        '192' => [
            'priority' => 'high',
            'message' => 'Add a 192x192 icon to your manifest for Android home screen.',
            'guide' => '#icon-requirements',
        ],
        '512' => [
            'priority' => 'high',
            'message' => 'Add a 512x512 icon to your manifest for Android splash screen.',
            'guide' => '#icon-requirements',
        ],
        // Recommended icons (medium priority)
        '180' => [
            'priority' => 'medium',
            'message' => 'Add a 180x180 icon to your manifest for iOS devices.',
            'guide' => '#ios-icons',
        ],
    ];

    /**
     * Generate all suggestions for a PWA analysis.
     *
     * Analyzes the PWAAnalysis model and returns prioritized suggestions
     * for all missing or invalid elements.
     *
     * @param PWAAnalysis $analysis The PWA analysis result
     * @return array<array{priority: string, field: string, message: string, guide: string}>
     */
    public function generateSuggestions(PWAAnalysis $analysis): array
    {
        $suggestions = [];

        // Check manifest presence
        if (!$analysis->has_manifest) {
            $suggestions[] = $this->createSuggestion('manifest', 'manifest');
        }

        // Check manifest validity (only if manifest is present but invalid)
        if ($analysis->has_manifest && !$analysis->manifest_valid) {
            $suggestions[] = $this->createSuggestion('manifest_valid', 'manifest_valid');
        }

        // Check required fields
        $requiredFields = ['name', 'short_name', 'start_url', 'display'];
        foreach ($requiredFields as $field) {
            if (!$analysis->{"has_$field"}) {
                $suggestions[] = $this->createSuggestion($field, $field);
            }
        }

        // Check recommended fields
        $recommendedFields = ['theme_color', 'background_color', 'description'];
        foreach ($recommendedFields as $field) {
            if (!$analysis->{"has_$field"}) {
                $suggestions[] = $this->createSuggestion($field, $field);
            }
        }

        // Check icons
        $iconFields = [
            '192' => 'icons_192_present',
            '512' => 'icons_512_present',
            '180' => 'icons_180_present',
        ];

        foreach ($iconFields as $size => $field) {
            if (!$analysis->$field) {
                $suggestions[] = $this->createIconSuggestion($size);
            }
        }

        return $this->sortByPriority($suggestions);
    }

    /**
     * Get suggestion for a specific manifest field.
     *
     * Returns null if the field is present or the field name is invalid.
     *
     * @param string $field The field name (name, short_name, start_url, etc.)
     * @param bool $present Whether the field is present in the manifest
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForField(string $field, bool $present): ?array
    {
        // Return null if field is present
        if ($present) {
            return null;
        }

        // Check if we have a suggestion template for this field
        if (!isset(self::FIELD_SUGGESTIONS[$field])) {
            return null;
        }

        return $this->createSuggestion($field, $field);
    }

    /**
     * Get suggestion for a specific icon size.
     *
     * Returns null if the icon is present or the size is invalid.
     *
     * @param string $size The icon size (192, 512, or 180)
     * @param bool $present Whether the icon is present in the manifest
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForIcon(string $size, bool $present): ?array
    {
        // Return null if icon is present
        if ($present) {
            return null;
        }

        // Check if we have a suggestion template for this size
        if (!isset(self::ICON_SUGGESTIONS[$size])) {
            return null;
        }

        return $this->createIconSuggestion($size);
    }

    /**
     * Create a suggestion array from template.
     *
     * @param string $templateKey The key in FIELD_SUGGESTIONS
     * @param string $field The field name to use in the suggestion
     * @return array{priority: string, field: string, message: string, guide: string}
     */
    private function createSuggestion(string $templateKey, string $field): array
    {
        $template = self::FIELD_SUGGESTIONS[$templateKey];

        return [
            'priority' => $template['priority'],
            'field' => $field,
            'message' => $template['message'],
            'guide' => $template['guide'],
        ];
    }

    /**
     * Create an icon suggestion array from template.
     *
     * @param string $size The icon size
     * @return array{priority: string, field: string, message: string, guide: string}
     */
    private function createIconSuggestion(string $size): array
    {
        $template = self::ICON_SUGGESTIONS[$size];

        return [
            'priority' => $template['priority'],
            'field' => "icon_{$size}",
            'message' => $template['message'],
            'guide' => $template['guide'],
        ];
    }

    /**
     * Sort suggestions by priority (high → medium → low).
     *
     * Maintains stable ordering for suggestions with the same priority.
     *
     * @param array $suggestions The unsorted suggestions
     * @return array The sorted suggestions
     */
    private function sortByPriority(array $suggestions): array
    {
        $priorityOrder = [
            'high' => 0,
            'medium' => 1,
            'low' => 2,
        ];

        usort($suggestions, function (array $a, array $b) use ($priorityOrder): int {
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return $suggestions;
    }
}
