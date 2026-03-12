<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\ReportBranding;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ReportBrandingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase trait handles migrations automatically
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns defaults when no branding exists.
     */
    public function test_show_returns_defaults_when_no_branding_exists(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'siteId' => $this->site->id,
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#64748b',
                    'fontFamily' => 'Inter',
                ],
            ]);
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns 401 when not authenticated.
     */
    public function test_show_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(401);
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns 403 when user does not own site.
     */
    public function test_show_returns_403_when_user_does_not_own_site(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns existing branding.
     */
    public function test_show_returns_existing_branding(): void
    {
        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'primary_color' => '#10b981',
            'secondary_color' => '#f59e0b',
            'font_family' => 'Roboto',
            'company_name' => 'ACME Corp',
            'company_website' => 'https://acme.com',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'siteId' => $this->site->id,
                    'primaryColor' => '#10b981',
                    'secondaryColor' => '#f59e0b',
                    'fontFamily' => 'Roboto',
                    'companyName' => 'ACME Corp',
                    'companyWebsite' => 'https://acme.com',
                ],
            ]);
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns 404 for non-existent site.
     */
    public function test_show_returns_404_for_nonexistent_site(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/999/branding');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    /**
     * Test PUT /api/v1/sites/:siteId/branding creates new branding.
     */
    public function test_update_creates_new_branding(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#ef4444',
                'secondaryColor' => '#3b82f6',
                'fontFamily' => 'Open Sans',
                'companyName' => 'Test Company',
                'companyWebsite' => 'https://test.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'primaryColor' => '#ef4444',
                    'secondaryColor' => '#3b82f6',
                    'fontFamily' => 'Open Sans',
                    'companyName' => 'Test Company',
                    'companyWebsite' => 'https://test.com',
                ],
            ]);

        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
            'primary_color' => '#ef4444',
            'company_name' => 'Test Company',
        ]);
    }

    /**
     * Test PUT /api/v1/sites/:siteId/branding returns 401 when not authenticated.
     */
    public function test_update_returns_401_when_not_authenticated(): void
    {
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'primaryColor' => '#ef4444',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test PUT /api/v1/sites/:siteId/branding returns 403 when user does not own site.
     */
    public function test_update_returns_403_when_user_does_not_own_site(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#ef4444',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    /**
     * Test PUT /api/v1/sites/:siteId/branding updates existing branding.
     */
    public function test_update_updates_existing_branding(): void
    {
        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'primary_color' => '#2563eb',
            'company_name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#10b981',
                'companyName' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'primaryColor' => '#10b981',
                    'companyName' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('report_branding', [
            'id' => $branding->id,
            'primary_color' => '#10b981',
            'company_name' => 'New Name',
        ]);
    }

    /**
     * Test PUT validates primary color must be valid hex.
     */
    public function test_update_validates_primary_color_hex_format(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => 'invalid-color',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['primaryColor']);
    }

    /**
     * Test PUT validates secondary color must be valid hex.
     */
    public function test_update_validates_secondary_color_hex_format(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'secondaryColor' => 'not-a-hex',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['secondaryColor']);
    }

    /**
     * Test PUT accepts valid hex colors (lowercase).
     */
    public function test_update_accepts_lowercase_hex_colors(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#ff0000',
                'secondaryColor' => '#00ff00',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'primaryColor' => '#ff0000',
                    'secondaryColor' => '#00ff00',
                ],
            ]);
    }

    /**
     * Test PUT accepts valid hex colors (uppercase).
     */
    public function test_update_accepts_uppercase_hex_colors(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#FF0000',
                'secondaryColor' => '#00FF00',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'primaryColor' => '#FF0000',
                    'secondaryColor' => '#00FF00',
                ],
            ]);
    }

    /**
     * Test PUT validates company website must be valid URL.
     */
    public function test_update_validates_company_website_url(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'companyWebsite' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['companyWebsite']);
    }

    /**
     * Test PUT accepts valid company website.
     */
    public function test_update_accepts_valid_company_website(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'companyWebsite' => 'https://example.com',
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PUT validates font family max length.
     */
    public function test_update_validates_font_family_length(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'fontFamily' => str_repeat('a', 101),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fontFamily']);
    }

    /**
     * Test PUT validates company name max length.
     */
    public function test_update_validates_company_name_length(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'companyName' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['companyName']);
    }

    /**
     * Test PUT strips HTML from company name.
     */
    public function test_update_strips_html_from_company_name(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'companyName' => '<script>alert("xss")</script>Safe Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
            'company_name' => 'Safe Name',
        ]);
    }

    /**
     * Test PUT strips HTML from font family.
     */
    public function test_update_strips_html_from_font_family(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'fontFamily' => '<b>Bold</b> Font',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
            'font_family' => 'Bold Font',
        ]);
    }

    /**
     * Test PUT allows setting company_name to null.
     */
    public function test_update_allows_null_company_name(): void
    {
        // First create branding with a company name
        ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'company_name' => 'Existing Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'companyName' => null,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
            'company_name' => null,
        ]);
    }

    /**
     * Test PUT returns 404 for non-existent site.
     */
    public function test_update_returns_404_for_nonexistent_site(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/sites/999/branding', [
                'primaryColor' => '#ff0000',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    /**
     * Create a fake image file without GD dependency.
     */
    private function createFakeImage(string $name, string $mime = 'image/png'): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create($name, 100, $mime);
    }

    /**
     * Test POST /api/v1/sites/:siteId/branding/logo uploads logo.
     */
    public function test_upload_logo_stores_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                'logo' => $this->createFakeImage('logo.png'),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['logoUrl', 'logoFile'],
            ]);

        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
        ]);
    }

    /**
     * Test POST logo returns 401 when not authenticated.
     */
    public function test_upload_logo_returns_401_when_not_authenticated(): void
    {
        Storage::fake('public');

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => $this->createFakeImage('logo.png'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test POST logo validates file type.
     */
    public function test_upload_logo_validates_file_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                'logo' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => [
                        'logo' => [
                            'The logo field must be a file of type: png, jpg, jpeg, svg.',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test POST logo validates file size.
     */
    public function test_upload_logo_validates_file_size(): void
    {
        // Create a file larger than 2MB (using create() which doesn't need GD)
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                'logo' => \Illuminate\Http\UploadedFile::fake()->create('logo.png', 3000), // 3MB
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    /**
     * Test POST logo accepts valid formats: PNG, JPG, JPEG, SVG.
     */
    public function test_upload_logo_accepts_valid_formats(): void
    {
        Storage::fake('public');

        $formats = [
            ['ext' => 'png', 'mime' => 'image/png'],
            ['ext' => 'jpg', 'mime' => 'image/jpeg'],
            ['ext' => 'jpeg', 'mime' => 'image/jpeg'],
            ['ext' => 'svg', 'mime' => 'image/svg+xml'],
        ];

        foreach ($formats as $format) {
            $file = $this->createFakeImage("logo.{$format['ext']}", $format['mime']);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                    'logo' => $file,
                ]);

            $response->assertStatus(200);
        }
    }

    /**
     * Test POST logo replaces existing logo.
     */
    public function test_upload_logo_replaces_existing(): void
    {
        Storage::fake('local');

        // Upload first logo
        $firstResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                'logo' => $this->createFakeImage('logo1.png'),
            ]);
        $firstLogoFile = $firstResponse->json('data.logoFile');

        // Upload second logo
        $secondResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
                'logo' => $this->createFakeImage('logo2.png'),
            ]);

        $secondResponse->assertStatus(200);

        // First logo should be deleted
        Storage::disk('local')->assertMissing($firstLogoFile);
    }

    /**
     * Test DELETE /api/v1/sites/:siteId/branding removes branding and logo.
     */
    public function test_destroy_removes_branding_and_logo(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('logos/test-logo.png', 'test');

        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'logo_file' => 'logos/test-logo.png',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('report_branding', ['id' => $branding->id]);
        Storage::disk('local')->assertMissing('logos/test-logo.png');
    }

    /**
     * Test DELETE returns 404 when branding doesn't exist.
     */
    public function test_destroy_returns_404_when_no_branding(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(404);
    }

    /**
     * Test DELETE returns 401 when not authenticated.
     */
    public function test_destroy_returns_401_when_not_authenticated(): void
    {
        $response = $this->deleteJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(401);
    }

    /**
     * Test one branding record per site (unique constraint).
     */
    public function test_one_branding_per_site(): void
    {
        ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'primary_color' => '#111111',
        ]);

        // Attempt to create another branding for same site
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sites/{$this->site->id}/branding", [
                'primaryColor' => '#222222',
            ]);

        $response->assertStatus(200);

        // Should still only have one record
        $this->assertEquals(1, ReportBranding::where('site_id', $this->site->id)->count());
        $this->assertDatabaseHas('report_branding', [
            'site_id' => $this->site->id,
            'primary_color' => '#222222',
        ]);
    }

    /**
     * Test branding is deleted when site is deleted (cascade).
     */
    public function test_branding_deleted_with_site(): void
    {
        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->site->delete();

        $this->assertDatabaseMissing('report_branding', ['id' => $branding->id]);
    }
}
