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
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        Storage::fake('public');
    }

    /**
     * Test GET /api/v1/sites/:siteId/branding returns defaults when no branding exists.
     */
    public function test_show_returns_defaults_when_no_branding_exists(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

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

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/branding");

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
        $response = $this->getJson('/api/v1/sites/999/branding');

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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
     * Test PUT /api/v1/sites/:siteId/branding updates existing branding.
     */
    public function test_update_updates_existing_branding(): void
    {
        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'primary_color' => '#2563eb',
            'company_name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
            'companyWebsite' => 'https://example.com',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test PUT validates font family max length.
     */
    public function test_update_validates_font_family_length(): void
    {
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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

        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
        $response = $this->putJson('/api/v1/sites/999/branding', [
            'primaryColor' => '#ff0000',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    /**
     * Test POST /api/v1/sites/:siteId/branding/logo uploads logo.
     */
    public function test_upload_logo_stores_file(): void
    {
        Storage::fake('public');

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 100, 100),
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
     * Test POST logo validates file type.
     */
    public function test_upload_logo_validates_file_type(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    /**
     * Test POST logo validates file size.
     */
    public function test_upload_logo_validates_file_size(): void
    {
        // Create a file larger than 2MB
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png')->size(3000), // 3MB
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

        $formats = ['png', 'jpg', 'jpeg', 'svg'];

        foreach ($formats as $format) {
            $file = $format === 'svg'
                ? \Illuminate\Http\UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml')
                : \Illuminate\Http\UploadedFile::fake()->image("logo.{$format}", 100, 100);

            $response = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
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
        Storage::fake('public');

        // Upload first logo
        $firstResponse = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo1.png', 100, 100),
        ]);
        $firstLogoFile = $firstResponse->json('data.logoFile');

        // Upload second logo
        $secondResponse = $this->postJson("/api/v1/sites/{$this->site->id}/branding/logo", [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo2.png', 100, 100),
        ]);

        $secondResponse->assertStatus(200);

        // First logo should be deleted
        Storage::disk('public')->assertMissing($firstLogoFile);
    }

    /**
     * Test DELETE /api/v1/sites/:siteId/branding removes branding and logo.
     */
    public function test_destroy_removes_branding_and_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/logos/test-logo.png', 'test');

        $branding = ReportBranding::factory()->create([
            'site_id' => $this->site->id,
            'logo_file' => 'uploads/logos/test-logo.png',
        ]);

        $response = $this->deleteJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('report_branding', ['id' => $branding->id]);
        Storage::disk('public')->assertMissing('uploads/logos/test-logo.png');
    }

    /**
     * Test DELETE returns 404 when branding doesn't exist.
     */
    public function test_destroy_returns_404_when_no_branding(): void
    {
        $response = $this->deleteJson("/api/v1/sites/{$this->site->id}/branding");

        $response->assertStatus(404);
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
        $response = $this->putJson("/api/v1/sites/{$this->site->id}/branding", [
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
