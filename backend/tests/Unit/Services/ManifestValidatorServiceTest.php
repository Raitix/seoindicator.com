<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ManifestValidatorService;
use App\DTO\ManifestValidationResult;
use App\DTO\ManifestError;

/**
 * Unit tests for ManifestValidatorService.
 * 
 * Tests PWA manifest validation against W3C Web App Manifest specification.
 * 
 * @see TESTS/SPEC-24-4.md
 * @see TASKS/TASK-24-4.md
 */
class ManifestValidatorServiceTest extends TestCase
{
    private ManifestValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ManifestValidatorService();
    }

    // =========================================================================
    // Category 1: validate() Method Tests (MV-001 to MV-010)
    // =========================================================================

    /** @test MV-001: Validate Complete Valid Manifest */
    public function test_validate_complete_valid_manifest(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']
            ],
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'description' => 'A sample app'
        ];

        $result = $this->service->validate($manifest);

        $this->assertInstanceOf(ManifestValidationResult::class, $result);
        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
        $this->assertTrue($result->fieldPresence['name']);
        $this->assertTrue($result->fieldPresence['short_name']);
        $this->assertTrue($result->fieldPresence['start_url']);
        $this->assertTrue($result->fieldPresence['display']);
        $this->assertTrue($result->fieldPresence['icons']);
        $this->assertTrue($result->fieldPresence['background_color']);
        $this->assertTrue($result->fieldPresence['theme_color']);
        $this->assertTrue($result->fieldPresence['description']);
    }

    /** @test MV-002: Missing Required Field - name */
    public function test_validate_missing_name(): void
    {
        $manifest = [
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        $this->assertNotEmpty($result->errors);
        $this->assertFalse($result->fieldPresence['name']);
        
        $nameError = collect($result->errors)->firstWhere('field', 'name');
        $this->assertNotNull($nameError);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $nameError->code);
    }

    /** @test MV-003: Missing Required Field - short_name */
    public function test_validate_missing_short_name(): void
    {
        $manifest = [
            'name' => 'Example App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $shortNameError = collect($result->errors)->firstWhere('field', 'short_name');
        $this->assertNotNull($shortNameError);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $shortNameError->code);
    }

    /** @test MV-004: Missing Required Field - start_url */
    public function test_validate_missing_start_url(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $startUrlError = collect($result->errors)->firstWhere('field', 'start_url');
        $this->assertNotNull($startUrlError);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $startUrlError->code);
    }

    /** @test MV-005: Missing Required Field - display */
    public function test_validate_missing_display(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $displayError = collect($result->errors)->firstWhere('field', 'display');
        $this->assertNotNull($displayError);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $displayError->code);
    }

    /** @test MV-006: Missing Required Field - icons */
    public function test_validate_missing_icons(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone'
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $iconsError = collect($result->errors)->firstWhere('field', 'icons');
        $this->assertNotNull($iconsError);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $iconsError->code);
    }

    /** @test MV-007: Empty Icons Array */
    public function test_validate_empty_icons_array(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => []
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $iconsError = collect($result->errors)->firstWhere('field', 'icons');
        $this->assertNotNull($iconsError);
        $this->assertStringContainsString('at least one', strtolower($iconsError->message));
    }

    /** @test MV-008: Invalid Display Value */
    public function test_validate_invalid_display_value(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'invalid-mode',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $displayError = collect($result->errors)->firstWhere('field', 'display');
        $this->assertNotNull($displayError);
        $this->assertEquals('INVALID_DISPLAY_VALUE', $displayError->code);
    }

    /** @test MV-009: Empty Manifest */
    public function test_validate_empty_manifest(): void
    {
        $result = $this->service->validate([]);

        $this->assertFalse($result->valid);
        $this->assertCount(5, $result->errors); // 5 required fields
    }

    /** @test MV-010: Null Input */
    public function test_validate_null_input(): void
    {
        $result = $this->service->validate(null);

        $this->assertFalse($result->valid);
        $this->assertNotEmpty($result->errors);
        
        $error = $result->errors[0] ?? null;
        $this->assertNotNull($error);
        $this->assertStringContainsString('required', strtolower($error->message));
    }

    // =========================================================================
    // Category 2: validateField() Method Tests (VF-001 to VF-012)
    // =========================================================================

    /** @test VF-001: Validate name Field - Valid */
    public function test_validate_field_name_valid(): void
    {
        $this->assertTrue($this->service->validateField('name', 'Example App'));
    }

    /** @test VF-002: Validate name Field - Empty String */
    public function test_validate_field_name_empty(): void
    {
        $this->assertFalse($this->service->validateField('name', ''));
    }

    /** @test VF-003: Validate short_name Field - Valid */
    public function test_validate_field_short_name_valid(): void
    {
        $this->assertTrue($this->service->validateField('short_name', 'Example'));
    }

    /** @test VF-004: Validate start_url Field - Valid Relative */
    public function test_validate_field_start_url_relative(): void
    {
        $this->assertTrue($this->service->validateField('start_url', '/'));
    }

    /** @test VF-005: Validate start_url Field - Valid Absolute */
    public function test_validate_field_start_url_absolute(): void
    {
        $this->assertTrue($this->service->validateField('start_url', 'https://example.com/'));
    }

    /** @test VF-006: Validate display Field - standalone */
    public function test_validate_field_display_standalone(): void
    {
        $this->assertTrue($this->service->validateField('display', 'standalone'));
    }

    /** @test VF-007: Validate display Field - fullscreen */
    public function test_validate_field_display_fullscreen(): void
    {
        $this->assertTrue($this->service->validateField('display', 'fullscreen'));
    }

    /** @test VF-008: Validate display Field - minimal-ui */
    public function test_validate_field_display_minimal_ui(): void
    {
        $this->assertTrue($this->service->validateField('display', 'minimal-ui'));
    }

    /** @test VF-009: Validate display Field - browser */
    public function test_validate_field_display_browser(): void
    {
        $this->assertTrue($this->service->validateField('display', 'browser'));
    }

    /** @test VF-010: Validate display Field - Invalid */
    public function test_validate_field_display_invalid(): void
    {
        $this->assertFalse($this->service->validateField('display', 'invalid'));
    }

    /** @test VF-011: Validate icons Field - Valid Array */
    public function test_validate_field_icons_valid(): void
    {
        $icons = [
            ['src' => '/icon.png', 'sizes' => '192x192']
        ];
        $this->assertTrue($this->service->validateField('icons', $icons));
    }

    /** @test VF-012: Validate icons Field - Not Array */
    public function test_validate_field_icons_not_array(): void
    {
        $this->assertFalse($this->service->validateField('icons', 'not-an-array'));
    }

    // =========================================================================
    // Category 3: Icon Validation Tests (IV-001 to IV-010)
    // =========================================================================

    /** @test IV-001: Valid Icon With All Required Fields */
    public function test_validate_icon_all_fields(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test IV-002: Icon Missing src Field */
    public function test_validate_icon_missing_src(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $iconError = collect($result->errors)->firstWhere('field', 'icons.0.src');
        $this->assertNotNull($iconError);
    }

    /** @test IV-003: Icon Missing sizes Field - Valid (Warning) */
    public function test_validate_icon_missing_sizes(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'type' => 'image/png'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        // sizes is recommended, not required
        $this->assertTrue($result->valid);
        $this->assertNotEmpty($result->warnings);
    }

    /** @test IV-004: Icon Missing type Field - Valid (Warning) */
    public function test_validate_icon_missing_type(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'sizes' => '192x192'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        // type is recommended, not required
        $this->assertTrue($result->valid);
    }

    /** @test IV-005: Icon With purpose Field - Valid */
    public function test_validate_icon_purpose_any(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'sizes' => '192x192',
                    'purpose' => 'any'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test IV-006: Icon With purpose Field - any maskable */
    public function test_validate_icon_purpose_any_maskable(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'sizes' => '192x192',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test IV-007: Icon With Invalid purpose */
    public function test_validate_icon_invalid_purpose(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'sizes' => '192x192',
                    'purpose' => 'invalid-purpose'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        // Invalid purpose should generate warning
        $this->assertNotEmpty($result->warnings);
    }

    /** @test IV-008: Icon With Empty src */
    public function test_validate_icon_empty_src(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [
                    'src' => '',
                    'sizes' => '192x192'
                ]
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $iconError = collect($result->errors)->firstWhere('field', 'icons.0.src');
        $this->assertNotNull($iconError);
    }

    /** @test IV-009: Icon sizes Format - Valid */
    public function test_validate_icon_sizes_formats(): void
    {
        $validSizes = ['192x192', '512x512', 'any', '192x192 512x512'];

        foreach ($validSizes as $sizes) {
            $manifest = [
                'name' => 'Example App',
                'short_name' => 'Example',
                'start_url' => '/',
                'display' => 'standalone',
                'icons' => [
                    [
                        'src' => '/icon.png',
                        'sizes' => $sizes
                    ]
                ]
            ];

            $result = $this->service->validate($manifest);
            $this->assertTrue($result->valid, "Sizes '{$sizes}' should be valid");
        }
    }

    /** @test IV-010: Icon type Valid Values */
    public function test_validate_icon_type_values(): void
    {
        $validTypes = ['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'];

        foreach ($validTypes as $type) {
            $manifest = [
                'name' => 'Example App',
                'short_name' => 'Example',
                'start_url' => '/',
                'display' => 'standalone',
                'icons' => [
                    [
                        'src' => '/icon.png',
                        'type' => $type
                    ]
                ]
            ];

            $result = $this->service->validate($manifest);
            $this->assertTrue($result->valid, "Type '{$type}' should be valid");
        }
    }

    // =========================================================================
    // Category 4: Recommended Fields Validation Tests (RF-001 to RF-006)
    // =========================================================================

    /** @test RF-001: Missing background_color - Warning */
    public function test_validate_missing_background_color_warning(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
            // No background_color
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Still valid
        $this->assertNotEmpty($result->warnings);
        
        $bgWarning = collect($result->warnings)->first(function ($w) {
            return str_contains(strtolower($w->message ?? $w), 'background_color');
        });
        $this->assertNotNull($bgWarning);
    }

    /** @test RF-002: Missing theme_color - Warning */
    public function test_validate_missing_theme_color_warning(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
        
        $themeWarning = collect($result->warnings)->first(function ($w) {
            return str_contains(strtolower($w->message ?? $w), 'theme_color');
        });
        $this->assertNotNull($themeWarning);
    }

    /** @test RF-003: Missing description - Warning */
    public function test_validate_missing_description_warning(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
        
        $descWarning = collect($result->warnings)->first(function ($w) {
            return str_contains(strtolower($w->message ?? $w), 'description');
        });
        $this->assertNotNull($descWarning);
    }

    /** @test RF-004: Invalid background_color Format - Warning */
    public function test_validate_invalid_background_color_format(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => 'invalid-color',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Invalid color is warning, not error
    }

    /** @test RF-005: Valid background_color Formats */
    public function test_validate_valid_background_color_formats(): void
    {
        $validColors = ['#ffffff', '#FFF', 'white', 'rgb(255, 255, 255)'];

        foreach ($validColors as $color) {
            $manifest = [
                'name' => 'Example App',
                'short_name' => 'Example',
                'start_url' => '/',
                'display' => 'standalone',
                'background_color' => $color,
                'icons' => [
                    ['src' => '/icon.png']
                ]
            ];

            $result = $this->service->validate($manifest);
            $this->assertTrue($result->valid, "Color '{$color}' should be valid");
        }
    }

    /** @test RF-006: Valid theme_color Formats */
    public function test_validate_valid_theme_color_formats(): void
    {
        $validColors = ['#000000', '#000', 'black'];

        foreach ($validColors as $color) {
            $manifest = [
                'name' => 'Example App',
                'short_name' => 'Example',
                'start_url' => '/',
                'display' => 'standalone',
                'theme_color' => $color,
                'icons' => [
                    ['src' => '/icon.png']
                ]
            ];

            $result = $this->service->validate($manifest);
            $this->assertTrue($result->valid, "Color '{$color}' should be valid");
        }
    }

    // =========================================================================
    // Category 5: getErrors() Method Tests (GE-001 to GE-003)
    // =========================================================================

    /** @test GE-001: Get Errors After Validation */
    public function test_get_errors_after_validation(): void
    {
        $manifest = [
            'name' => 'Example App',
            // Missing short_name, start_url, display, icons
        ];

        $result = $this->service->validate($manifest);
        $errors = $this->service->getErrors();

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertInstanceOf(ManifestError::class, $errors[0]);
    }

    /** @test GE-002: Get Empty Errors Array */
    public function test_get_errors_empty(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png']
            ]
        ];

        $this->service->validate($manifest);
        $errors = $this->service->getErrors();

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /** @test GE-003: Get Multiple Errors */
    public function test_get_errors_multiple(): void
    {
        $this->service->validate([]); // Empty manifest
        $errors = $this->service->getErrors();

        $this->assertCount(5, $errors); // 5 required fields missing
    }

    // =========================================================================
    // Category 6: ManifestValidationResult DTO Tests (MR-001 to MR-006)
    // =========================================================================

    /** @test MR-001: Result valid Property */
    public function test_result_valid_property(): void
    {
        $validManifest = [
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($validManifest);
        $this->assertTrue($result->valid);

        $invalidResult = $this->service->validate([]);
        $this->assertFalse($invalidResult->valid);
    }

    /** @test MR-002: Result errors Property */
    public function test_result_errors_property(): void
    {
        $result = $this->service->validate([]);

        $this->assertIsArray($result->errors);
        $this->assertContainsOnlyInstancesOf(ManifestError::class, $result->errors);
    }

    /** @test MR-003: Result warnings Property */
    public function test_result_warnings_property(): void
    {
        $manifest = [
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
            // Missing background_color, theme_color, description
        ];

        $result = $this->service->validate($manifest);

        $this->assertIsArray($result->warnings);
    }

    /** @test MR-004: Result fieldPresence Property */
    public function test_result_field_presence_property(): void
    {
        $manifest = [
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        $this->assertIsArray($result->fieldPresence);
        $this->assertArrayHasKey('name', $result->fieldPresence);
        $this->assertArrayHasKey('short_name', $result->fieldPresence);
        $this->assertArrayHasKey('start_url', $result->fieldPresence);
        $this->assertArrayHasKey('display', $result->fieldPresence);
        $this->assertArrayHasKey('icons', $result->fieldPresence);
    }

    /** @test MR-005: Result hasErrors Method */
    public function test_result_has_errors_method(): void
    {
        $validResult = $this->service->validate([
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ]);
        $this->assertFalse($validResult->hasErrors());

        $invalidResult = $this->service->validate([]);
        $this->assertTrue($invalidResult->hasErrors());
    }

    /** @test MR-006: Result hasWarnings Method */
    public function test_result_has_warnings_method(): void
    {
        $completeResult = $this->service->validate([
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#fff',
            'theme_color' => '#000',
            'description' => 'App',
            'icons' => [['src' => '/icon.png', 'sizes' => '192x192', 'type' => 'image/png']]
        ]);
        $this->assertFalse($completeResult->hasWarnings());

        $minimalResult = $this->service->validate([
            'name' => 'App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ]);
        $this->assertTrue($minimalResult->hasWarnings());
    }

    // =========================================================================
    // Category 7: ManifestError DTO Tests (ME-001 to ME-004)
    // =========================================================================

    /** @test ME-001: Error field Property */
    public function test_error_field_property(): void
    {
        $result = $this->service->validate([]); // Missing all required fields
        $error = $result->errors[0];

        $this->assertNotEmpty($error->field);
        $this->assertIsString($error->field);
    }

    /** @test ME-002: Error code Property */
    public function test_error_code_property(): void
    {
        $result = $this->service->validate([]);
        $error = $result->errors[0];

        $this->assertNotEmpty($error->code);
        $this->assertIsString($error->code);
        $this->assertEquals('REQUIRED_FIELD_MISSING', $error->code);
    }

    /** @test ME-003: Error message Property */
    public function test_error_message_property(): void
    {
        $result = $this->service->validate([]);
        $error = $result->errors[0];

        $this->assertNotEmpty($error->message);
        $this->assertIsString($error->message);
    }

    /** @test ME-004: Error severity Property */
    public function test_error_severity_property(): void
    {
        $result = $this->service->validate([]);
        $error = $result->errors[0];

        $this->assertContains($error->severity, ['error', 'warning']);
        $this->assertEquals('error', $error->severity);
    }

    // =========================================================================
    // Category 8: Edge Cases Tests (EC-001 to EC-010)
    // =========================================================================

    /** @test EC-001: Extra Fields in Manifest */
    public function test_edge_case_extra_fields(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']],
            'custom_property' => 'some value',
            'another_field' => ['nested' => 'data']
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Extra fields ignored
    }

    /** @test EC-002: Unicode in name Field */
    public function test_edge_case_unicode_in_name(): void
    {
        $manifest = [
            'name' => '示例应用',
            'short_name' => '示例',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test EC-003: Very Long name Field */
    public function test_edge_case_very_long_name(): void
    {
        $manifest = [
            'name' => str_repeat('x', 500),
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Valid but may have warning
    }

    /** @test EC-004: Icons With Same Sizes */
    public function test_edge_case_duplicate_icon_sizes(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon1.png', 'sizes' => '192x192'],
                ['src' => '/icon2.png', 'sizes' => '192x192']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Duplicate sizes allowed
    }

    /** @test EC-005: Icon src With Query Parameters */
    public function test_edge_case_icon_src_with_query(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon.png?v=1.2.3&theme=dark']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test EC-006: start_url With Query Parameters */
    public function test_edge_case_start_url_with_query(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/?source=pwa&utm_source=app',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid);
    }

    /** @test EC-007: Display Value Case Sensitivity */
    public function test_edge_case_display_case_sensitivity(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'STANDALONE', // Uppercase
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid); // Case-sensitive
    }

    /** @test EC-008: Empty Object in Icons Array */
    public function test_edge_case_empty_icon_object(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                [] // Empty icon object
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertFalse($result->valid);
        
        $iconError = collect($result->errors)->firstWhere('field', 'icons.0.src');
        $this->assertNotNull($iconError);
    }

    /** @test EC-009: Nested Objects in Manifest */
    public function test_edge_case_nested_objects(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']],
            'shortcuts' => [
                ['name' => 'Home', 'url' => '/']
            ],
            'screenshots' => [
                ['src' => '/screenshot.png', 'sizes' => '1280x720']
            ]
        ];

        $result = $this->service->validate($manifest);

        $this->assertTrue($result->valid); // Nested objects ignored
    }

    /** @test EC-010: Array Instead of Object */
    public function test_edge_case_array_input(): void
    {
        $result = $this->service->validate(['item1', 'item2']);

        $this->assertFalse($result->valid);
        $this->assertNotEmpty($result->errors);
    }

    // =========================================================================
    // Category 9: Security Tests (SEC-001 to SEC-003)
    // =========================================================================

    /** @test SEC-001: XSS in name Field */
    public function test_security_xss_in_name(): void
    {
        $manifest = [
            'name' => '<script>alert(1)</script>',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        // Validation passes - sanitization is caller's responsibility
        $this->assertTrue($result->valid);
        $this->assertEquals('<script>alert(1)</script>', $result->fieldPresence['name'] ? $manifest['name'] : null);
    }

    /** @test SEC-002: SQL Injection in start_url */
    public function test_security_sql_injection_in_start_url(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => "/?id=' OR '1'='1",
            'display' => 'standalone',
            'icons' => [['src' => '/icon.png']]
        ];

        $result = $this->service->validate($manifest);

        // Validation passes - SQL injection protection is caller's responsibility
        $this->assertTrue($result->valid);
    }

    /** @test SEC-003: JavaScript URL in Icon src */
    public function test_security_javascript_url_in_icon(): void
    {
        $manifest = [
            'name' => 'Example App',
            'short_name' => 'Example',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => 'javascript:alert(1)']
            ]
        ];

        $result = $this->service->validate($manifest);

        // Should generate warning for invalid URL scheme
        $this->assertNotEmpty($result->warnings);
    }
}
