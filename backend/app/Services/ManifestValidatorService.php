<?php

namespace App\Services;

use App\DTO\ManifestError;
use App\DTO\ManifestValidationResult;

/**
 * Manifest Validator Service for TASK-24-4.
 *
 * Validates PWA manifest structure and required fields against
 * W3C Web App Manifest specification.
 *
 * Features:
 * - Validates required fields: name, short_name, start_url, display, icons
 * - Validates recommended fields: background_color, theme_color, description
 * - Validates display enum values: standalone, fullscreen, minimal-ui, browser
 * - Validates icons array structure (src, sizes, type, purpose)
 * - Returns detailed validation results with errors and warnings
 *
 * @see https://www.w3.org/TR/appmanifest/
 * @see TASKS/TASK-24-4.md
 * @see TESTS/SPEC-24-4.md
 */
class ManifestValidatorService
{
    /**
     * Required manifest fields.
     */
    private const REQUIRED_FIELDS = [
        'name',
        'short_name',
        'start_url',
        'display',
        'icons',
    ];

    /**
     * Recommended manifest fields.
     */
    private const RECOMMENDED_FIELDS = [
        'background_color',
        'theme_color',
        'description',
    ];

    /**
     * Valid display mode values (case-sensitive).
     */
    private const VALID_DISPLAY_VALUES = [
        'standalone',
        'fullscreen',
        'minimal-ui',
        'browser',
    ];

    /**
     * Valid icon purpose values.
     */
    private const VALID_PURPOSE_VALUES = [
        'any',
        'maskable',
    ];

    /**
     * Valid image MIME types for icons.
     */
    private const VALID_ICON_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/x-icon',
    ];

    /**
     * Collection of validation errors.
     *
     * @var array<ManifestError>
     */
    private array $errors = [];

    /**
     * Collection of validation warnings.
     *
     * @var array<ManifestError>
     */
    private array $warnings = [];

    /**
     * Field presence tracking.
     *
     * @var array<string, bool>
     */
    private array $fieldPresence = [];

    /**
     * Validate a PWA manifest array.
     *
     * @param array|null $manifest The manifest to validate
     * @return ManifestValidationResult The validation result
     */
    public function validate(?array $manifest): ManifestValidationResult
    {
        // Reset state for fresh validation
        $this->errors = [];
        $this->warnings = [];
        $this->fieldPresence = [];

        // Handle null input
        if ($manifest === null) {
            $this->addError('manifest', 'MANIFEST_REQUIRED', 'Manifest is required');
            
            return new ManifestValidationResult(
                valid: false,
                errors: $this->errors,
                warnings: $this->warnings,
                fieldPresence: $this->fieldPresence
            );
        }

        // Initialize field presence for all tracked fields
        $allFields = array_merge(self::REQUIRED_FIELDS, self::RECOMMENDED_FIELDS);
        foreach ($allFields as $field) {
            $this->fieldPresence[$field] = array_key_exists($field, $manifest);
        }

        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $manifest)) {
                $this->addError($field, 'REQUIRED_FIELD_MISSING', "The {$field} field is required");
            } elseif ($manifest[$field] === null || $manifest[$field] === '') {
                $this->addError($field, 'REQUIRED_FIELD_EMPTY', "The {$field} field cannot be empty");
            }
        }

        // Validate display field value
        if (isset($manifest['display']) && is_string($manifest['display'])) {
            if (!in_array($manifest['display'], self::VALID_DISPLAY_VALUES, true)) {
                $this->addError(
                    'display',
                    'INVALID_DISPLAY_VALUE',
                    'Display must be one of: ' . implode(', ', self::VALID_DISPLAY_VALUES)
                );
            }
        }

        // Validate icons array
        if (array_key_exists('icons', $manifest)) {
            if (!is_array($manifest['icons'])) {
                $this->addError('icons', 'ICONS_NOT_ARRAY', 'The icons field must be an array');
            } elseif (empty($manifest['icons'])) {
                $this->addError('icons', 'ICONS_EMPTY', 'The icons array must contain at least one icon');
            } else {
                $this->validateIcons($manifest['icons']);
            }
        }

        // Check recommended fields (warnings only)
        foreach (self::RECOMMENDED_FIELDS as $field) {
            if (!array_key_exists($field, $manifest)) {
                $this->addWarning(
                    $field,
                    'RECOMMENDED_FIELD_MISSING',
                    "The {$field} field is recommended for a complete PWA manifest"
                );
            }
        }

        // Validate color formats if present
        if (isset($manifest['background_color'])) {
            $this->validateColor('background_color', $manifest['background_color']);
        }
        if (isset($manifest['theme_color'])) {
            $this->validateColor('theme_color', $manifest['theme_color']);
        }

        // Check for very long name (warning)
        if (isset($manifest['name']) && is_string($manifest['name']) && strlen($manifest['name']) > 100) {
            $this->addWarning(
                'name',
                'NAME_TOO_LONG',
                'The name field exceeds recommended length (100 characters)'
            );
        }

        return new ManifestValidationResult(
            valid: empty($this->errors),
            errors: $this->errors,
            warnings: $this->warnings,
            fieldPresence: $this->fieldPresence
        );
    }

    /**
     * Validate a single field.
     *
     * @param string $field The field name to validate
     * @param mixed $value The field value
     * @return bool True if valid, false otherwise
     */
    public function validateField(string $field, mixed $value): bool
    {
        // Reset errors for field validation
        $previousErrors = $this->errors;
        $this->errors = [];

        $isValid = match ($field) {
            'name' => $this->validateName($value),
            'short_name' => $this->validateShortName($value),
            'start_url' => $this->validateStartUrl($value),
            'display' => $this->validateDisplay($value),
            'icons' => $this->validateIconsField($value),
            default => true, // Unknown fields are considered valid (ignored)
        };

        // If validation failed, keep the error; otherwise restore previous errors
        if (!$isValid) {
            // Keep the error that was added
        } else {
            $this->errors = $previousErrors;
        }

        return $isValid;
    }

    /**
     * Get all validation errors from the last validate() call.
     *
     * @return array<ManifestError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate the name field.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateName(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            $this->addError('name', 'INVALID_NAME', 'The name field must be a non-empty string');
            return false;
        }
        return true;
    }

    /**
     * Validate the short_name field.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateShortName(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            $this->addError('short_name', 'INVALID_SHORT_NAME', 'The short_name field must be a non-empty string');
            return false;
        }
        return true;
    }

    /**
     * Validate the start_url field.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateStartUrl(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            $this->addError('start_url', 'INVALID_START_URL', 'The start_url field must be a non-empty string');
            return false;
        }
        return true;
    }

    /**
     * Validate the display field.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateDisplay(mixed $value): bool
    {
        if (!is_string($value)) {
            $this->addError('display', 'INVALID_DISPLAY', 'The display field must be a string');
            return false;
        }

        if (!in_array($value, self::VALID_DISPLAY_VALUES, true)) {
            $this->addError(
                'display',
                'INVALID_DISPLAY_VALUE',
                'Display must be one of: ' . implode(', ', self::VALID_DISPLAY_VALUES)
            );
            return false;
        }

        return true;
    }

    /**
     * Validate the icons field (array check only).
     *
     * @param mixed $value
     * @return bool
     */
    private function validateIconsField(mixed $value): bool
    {
        if (!is_array($value)) {
            $this->addError('icons', 'ICONS_NOT_ARRAY', 'The icons field must be an array');
            return false;
        }

        if (empty($value)) {
            $this->addError('icons', 'ICONS_EMPTY', 'The icons array must contain at least one icon');
            return false;
        }

        // Validate each icon
        foreach ($value as $index => $icon) {
            if (!is_array($icon)) {
                $this->addError(
                    "icons.{$index}",
                    'ICON_NOT_OBJECT',
                    "Icon at index {$index} must be an object"
                );
                return false;
            }

            if (!isset($icon['src']) || $icon['src'] === '') {
                $this->addError(
                    "icons.{$index}.src",
                    'ICON_SRC_REQUIRED',
                    "Icon at index {$index} must have a src field"
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the icons array.
     *
     * @param array $icons
     * @return void
     */
    private function validateIcons(array $icons): void
    {
        foreach ($icons as $index => $icon) {
            if (!is_array($icon)) {
                $this->addError(
                    "icons.{$index}",
                    'ICON_NOT_OBJECT',
                    "Icon at index {$index} must be an object"
                );
                continue;
            }

            // src is required
            if (!isset($icon['src']) || $icon['src'] === '') {
                $this->addError(
                    "icons.{$index}.src",
                    'ICON_SRC_REQUIRED',
                    "Icon at index {$index} must have a src field"
                );
            } else {
                // Check for dangerous URL schemes (warning only)
                $src = $icon['src'];
                if (preg_match('/^javascript:/i', $src)) {
                    $this->addWarning(
                        "icons.{$index}.src",
                        'UNSAFE_URL_SCHEME',
                        "Icon src uses an unsafe URL scheme"
                    );
                }
            }

            // sizes is recommended
            if (!isset($icon['sizes']) || $icon['sizes'] === '') {
                $this->addWarning(
                    "icons.{$index}.sizes",
                    'ICON_SIZES_RECOMMENDED',
                    "Icon at index {$index} should have a sizes field"
                );
            }

            // type is recommended
            if (!isset($icon['type']) || $icon['type'] === '') {
                $this->addWarning(
                    "icons.{$index}.type",
                    'ICON_TYPE_RECOMMENDED',
                    "Icon at index {$index} should have a type field"
                );
            }

            // purpose validation if present
            if (isset($icon['purpose'])) {
                $this->validateIconPurpose($index, $icon['purpose']);
            }
        }
    }

    /**
     * Validate icon purpose value.
     *
     * @param int $index
     * @param string $purpose
     * @return void
     */
    private function validateIconPurpose(int $index, string $purpose): void
    {
        // Purpose can be a space-separated list
        $purposes = preg_split('/\s+/', $purpose);

        foreach ($purposes as $p) {
            $p = strtolower(trim($p));
            if (!in_array($p, self::VALID_PURPOSE_VALUES, true)) {
                $this->addWarning(
                    "icons.{$index}.purpose",
                    'INVALID_ICON_PURPOSE',
                    "Invalid purpose value: {$p}. Must be one of: " . implode(', ', self::VALID_PURPOSE_VALUES)
                );
                break;
            }
        }
    }

    /**
     * Validate a color value.
     *
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateColor(string $field, mixed $value): void
    {
        if (!is_string($value)) {
            $this->addWarning($field, 'INVALID_COLOR_FORMAT', "The {$field} field must be a string");
            return;
        }

        // Valid color formats:
        // - Hex: #RGB, #RRGGBB, #RGBA, #RRGGBBAA
        // - Named colors
        // - rgb(), rgba()
        
        $isValid = $this->isValidColor($value);

        if (!$isValid) {
            $this->addWarning(
                $field,
                'INVALID_COLOR_FORMAT',
                "The {$field} field has an invalid color format"
            );
        }
    }

    /**
     * Check if a color value is valid.
     *
     * @param string $value
     * @return bool
     */
    private function isValidColor(string $value): bool
    {
        // Hex color (3, 4, 6, or 8 digits)
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{4}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $value)) {
            return true;
        }

        // RGB/RGBA
        if (preg_match('/^rgba?\s*\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)$/', $value)) {
            return true;
        }

        // Named colors (common ones)
        $namedColors = [
            'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
            'beige', 'bisque', 'black', 'blanchedalmond', 'blue', 'blueviolet', 'brown', 'burlywood',
            'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan',
            'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen', 'darkgrey', 'darkkhaki', 'darkmagenta',
            'darkolivegreen', 'darkorange', 'darkorchid', 'darkred', 'darksalmon', 'darkseagreen', 'darkslateblue',
            'darkslategray', 'darkslategrey', 'darkturquoise', 'darkviolet', 'deeppink', 'deepskyblue', 'dimgray',
            'dimgrey', 'dodgerblue',
            'firebrick', 'floralwhite', 'forestgreen', 'fuchsia',
            'gainsboro', 'ghostwhite', 'gold', 'goldenrod', 'gray', 'green', 'greenyellow', 'grey',
            'honeydew', 'hotpink',
            'indianred', 'indigo', 'ivory',
            'khaki',
            'lavender', 'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan',
            'lightgoldenrodyellow', 'lightgray', 'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon',
            'lightseagreen', 'lightskyblue', 'lightslategray', 'lightslategrey', 'lightsteelblue', 'lightyellow',
            'lime', 'limegreen', 'linen',
            'magenta', 'maroon', 'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple', 'mediumseagreen',
            'mediumslateblue', 'mediumspringgreen', 'mediumturquoise', 'mediumvioletred', 'midnightblue', 'mintcream',
            'mistyrose', 'moccasin',
            'navajowhite', 'navy',
            'oldlace', 'olive', 'olivedrab', 'orange', 'orangered', 'orchid',
            'palegoldenrod', 'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff', 'peru', 'pink',
            'plum', 'powderblue', 'purple',
            'rebeccapurple', 'red', 'rosybrown', 'royalblue',
            'saddlebrown', 'salmon', 'sandybrown', 'seagreen', 'seashell', 'sienna', 'silver', 'skyblue', 'slateblue',
            'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue',
            'tan', 'teal', 'thistle', 'tomato', 'transparent', 'turquoise',
            'violet',
            'wheat', 'white', 'whitesmoke',
            'yellow', 'yellowgreen',
        ];

        if (in_array(strtolower($value), $namedColors, true)) {
            return true;
        }

        return false;
    }

    /**
     * Add a validation error.
     *
     * @param string $field
     * @param string $code
     * @param string $message
     * @return void
     */
    private function addError(string $field, string $code, string $message): void
    {
        $this->errors[] = ManifestError::error($field, $code, $message);
    }

    /**
     * Add a validation warning.
     *
     * @param string $field
     * @param string $code
     * @param string $message
     * @return void
     */
    private function addWarning(string $field, string $code, string $message): void
    {
        $this->warnings[] = ManifestError::warning($field, $code, $message);
    }
}
