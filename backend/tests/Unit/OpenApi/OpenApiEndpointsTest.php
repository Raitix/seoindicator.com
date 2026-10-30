<?php

namespace Tests\Unit\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * OpenAPI Endpoint Coverage Tests (TASK-6-17)
 * 
 * Tests for validating all API endpoints are documented in OpenAPI spec.
 */
class OpenApiEndpointsTest extends TestCase
{
    /**
     * Path to OpenAPI specification file (relative to base_path())
     */
    private const SPEC_PATH = 'src/api/openapi.yaml';

    /**
     * Parsed OpenAPI specification
     */
    private ?array $spec = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadSpec();
    }

    /**
     * Load and parse the OpenAPI specification
     */
    private function loadSpec(): void
    {
        $path = base_path(self::SPEC_PATH);
        
        if (!file_exists($path)) {
            $this->spec = null;
            return;
        }

        $content = file_get_contents($path);
        
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            $this->spec = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            $this->spec = ['_needs_yaml_parser' => true];
        }
    }

    /**
     * Get all documented paths
     */
    private function getDocumentedPaths(): array
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            return [];
        }
        
        return array_keys($this->spec['paths'] ?? []);
    }

    /**
     * Check if a path is documented (with parameter normalization)
     */
    private function isPathDocumented(string $searchPath): bool
    {
        $documentedPaths = $this->getDocumentedPaths();
        
        // Direct match
        if (in_array($searchPath, $documentedPaths)) {
            return true;
        }
        
        // Check with parameter normalization
        // /v1/users/1 -> /v1/users/{id}
        $normalizedSearch = preg_replace('/\/\d+/', '/{id}', $searchPath);
        $normalizedSearch = preg_replace('/\/\d+/', '/{id}', $normalizedSearch);
        
        foreach ($documentedPaths as $docPath) {
            $normalizedDoc = $docPath;
            // Normalize path parameters
            $normalizedDoc = preg_replace('/\{[^}]+\}/', '{id}', $normalizedDoc);
            
            if ($normalizedDoc === $normalizedSearch) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a method is documented for a path
     */
    private function isMethodDocumented(string $path, string $method): bool
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            return false;
        }
        
        $paths = $this->spec['paths'] ?? [];
        $method = strtolower($method);
        
        // Direct match
        if (isset($paths[$path][$method])) {
            return true;
        }
        
        // Try normalized match
        foreach ($paths as $docPath => $methods) {
            $normalizedDoc = preg_replace('/\{[^}]+\}/', '{id}', $docPath);
            $normalizedSearch = preg_replace('/\/\d+/', '/{id}', $path);
            
            if ($normalizedDoc === $normalizedSearch && isset($methods[$method])) {
                return true;
            }
        }
        
        return false;
    }

    // ========================================================================
    // OAS-ENDPOINTS: Authentication Endpoints
    // ========================================================================

    #[Test]
    public function ep_auth_001_register_endpoint(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/register'),
            'POST /v1/auth/register should be documented'
        );
        $this->assertTrue(
            $this->isMethodDocumented('/v1/auth/register', 'post'),
            'POST method for /v1/auth/register should be documented'
        );
    }

    #[Test]
    public function ep_auth_002_login_endpoint(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/login'),
            'POST /v1/auth/login should be documented'
        );
    }

    #[Test]
    public function ep_auth_003_logout_endpoint(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/logout'),
            'POST /v1/auth/logout should be documented'
        );
    }

    #[Test]
    public function ep_auth_004_user_endpoint(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/user'),
            'GET /v1/auth/user should be documented'
        );
    }

    #[Test]
    public function ep_auth_005_forgot_password(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/forgot-password'),
            'POST /v1/auth/forgot-password should be documented'
        );
    }

    #[Test]
    public function ep_auth_006_reset_password(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/reset-password'),
            'POST /v1/auth/reset-password should be documented'
        );
    }

    #[Test]
    public function ep_auth_007_verify_email(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/verify-email/{id}/{hash}'),
            'GET /v1/auth/verify-email/{id}/{hash} should be documented'
        );
    }

    #[Test]
    public function ep_auth_008_resend_verification(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/resend-verification'),
            'POST /v1/auth/resend-verification should be documented'
        );
    }

    #[Test]
    public function ep_auth_009_google_oauth(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/google'),
            'POST /v1/auth/google should be documented'
        );
    }

    #[Test]
    public function ep_auth_010_google_callback(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/auth/google/callback'),
            'POST /v1/auth/google/callback should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Keywords Endpoints
    // ========================================================================

    #[Test]
    public function ep_kw_001_list_keywords(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/keywords'),
            'GET /v1/keywords should be documented'
        );
    }

    #[Test]
    public function ep_kw_002_create_keyword(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/keywords', 'post'),
            'POST /v1/keywords should be documented'
        );
    }

    #[Test]
    public function ep_kw_003_get_keyword(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/keywords/{id}'),
            'GET /v1/keywords/{id} should be documented'
        );
    }

    #[Test]
    public function ep_kw_004_update_keyword(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/keywords/{id}', 'put'),
            'PUT /v1/keywords/{id} should be documented'
        );
    }

    #[Test]
    public function ep_kw_005_delete_keyword(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/keywords/{id}', 'delete'),
            'DELETE /v1/keywords/{id} should be documented'
        );
    }

    #[Test]
    public function ep_kw_006_keyword_rankings(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/keywords/{id}/rankings'),
            'GET /v1/keywords/{id}/rankings should be documented'
        );
    }

    #[Test]
    public function ep_kw_007_site_keywords(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{siteId}/keywords'),
            'GET /v1/sites/{siteId}/keywords should be documented'
        );
    }

    #[Test]
    public function ep_kw_008_keyword_history(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/keywords/{id}/history'),
            'GET /v1/keywords/{id}/history should be documented'
        );
    }

    #[Test]
    public function ep_kw_009_check_ranking(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/keywords/{id}/check'),
            'POST /v1/keywords/{id}/check should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Sites Endpoints
    // ========================================================================

    #[Test]
    public function ep_site_001_list_sites(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites'),
            'GET /v1/sites should be documented'
        );
    }

    #[Test]
    public function ep_site_006_site_dashboard(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{siteId}/dashboard'),
            'GET /v1/sites/{siteId}/dashboard should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Projects Endpoints
    // ========================================================================

    #[Test]
    public function ep_proj_001_list_projects(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/projects'),
            'GET /v1/projects should be documented'
        );
    }

    #[Test]
    public function ep_proj_002_create_project(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/projects', 'post'),
            'POST /v1/projects should be documented'
        );
    }

    #[Test]
    public function ep_proj_003_get_project(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/projects/{id}'),
            'GET /v1/projects/{id} should be documented'
        );
    }

    #[Test]
    public function ep_proj_006_archive_project(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/projects/{id}/archive'),
            'POST /v1/projects/{id}/archive should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Reports Endpoints
    // ========================================================================

    #[Test]
    public function ep_rpt_001_list_reports(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{siteId}/reports'),
            'GET /v1/sites/{siteId}/reports should be documented'
        );
    }

    #[Test]
    public function ep_rpt_002_generate_report(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{siteId}/reports/generate'),
            'POST /v1/sites/{siteId}/reports/generate should be documented'
        );
    }

    #[Test]
    public function ep_rpt_003_get_report(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/reports/{reportId}'),
            'GET /v1/reports/{reportId} should be documented'
        );
    }

    #[Test]
    public function ep_rpt_005_download_report(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/reports/{reportId}/download'),
            'GET /v1/reports/{reportId}/download should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Audit Endpoints
    // ========================================================================

    #[Test]
    public function ep_audit_001_create_audit(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{site}/audits'),
            'POST /v1/sites/{site}/audits should be documented'
        );
    }

    #[Test]
    public function ep_audit_002_list_audits(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/sites/{site}/audits', 'get'),
            'GET /v1/sites/{site}/audits should be documented'
        );
    }

    #[Test]
    public function ep_audit_003_get_audit(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/audits/{audit}'),
            'GET /v1/audits/{audit} should be documented'
        );
    }

    #[Test]
    public function ep_audit_005_audit_issues(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/audits/{auditId}/issues'),
            'GET /v1/audits/{auditId}/issues should be documented'
        );
    }

    #[Test]
    public function ep_audit_007_publish_audit(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/audits/{id}/publish'),
            'POST /v1/audits/{id}/publish should be documented'
        );
    }

    #[Test]
    public function ep_audit_008_unpublish_audit(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/audits/{id}/unpublish'),
            'DELETE /v1/audits/{id}/unpublish should be documented'
        );
    }

    #[Test]
    public function ep_audit_010_audit_analytics(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/audits/{id}/analytics'),
            'GET /v1/audits/{id}/analytics should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Competitors Endpoints
    // ========================================================================

    #[Test]
    public function ep_comp_001_list_competitors(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/competitors'),
            'GET /v1/competitors should be documented'
        );
    }

    #[Test]
    public function ep_comp_002_create_competitor(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/competitors', 'post'),
            'POST /v1/competitors should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Analysis Endpoints
    // ========================================================================

    #[Test]
    public function ep_anal_001_list_jobs(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/sites/{siteId}/analysis/jobs'),
            'GET /v1/sites/{siteId}/analysis/jobs should be documented'
        );
    }

    #[Test]
    public function ep_anal_002_create_job(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/sites/{siteId}/analysis/jobs', 'post'),
            'POST /v1/sites/{siteId}/analysis/jobs should be documented'
        );
    }

    #[Test]
    public function ep_anal_004_start_job(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/analysis/jobs/{jobId}/start'),
            'POST /v1/analysis/jobs/{jobId}/start should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: AI Visibility Endpoints
    // ========================================================================

    #[Test]
    public function ep_ai_001_list_projects(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/ai-visibility/projects'),
            'GET /v1/ai-visibility/projects should be documented'
        );
    }

    #[Test]
    public function ep_ai_002_create_project(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/ai-visibility/projects', 'post'),
            'POST /v1/ai-visibility/projects should be documented'
        );
    }

    #[Test]
    public function ep_ai_006_run_queries(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/ai-visibility/projects/{id}/run'),
            'POST /v1/ai-visibility/projects/{id}/run should be documented'
        );
    }

    #[Test]
    public function ep_ai_007_analytics(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/ai-visibility/projects/{id}/analytics'),
            'GET /v1/ai-visibility/projects/{id}/analytics should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Public Endpoints
    // ========================================================================

    #[Test]
    public function ep_pub_001_health_check(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/health'),
            'GET /health should be documented'
        );
    }

    #[Test]
    public function ep_pub_002_public_audit(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/public-audit/{hash}'),
            'GET /v1/public-audit/{hash} should be documented'
        );
    }

    #[Test]
    public function ep_pub_005_badge_impression(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/badge/impression'),
            'POST /v1/badge/impression should be documented'
        );
    }

    #[Test]
    public function ep_pub_007_pwa_guide(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/pwa/guide'),
            'GET /v1/pwa/guide should be documented'
        );
    }

    #[Test]
    public function ep_pub_008_models_list(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/models'),
            'GET /v1/models should be documented'
        );
    }

    #[Test]
    public function ep_pub_010_ai_models(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/ai/models'),
            'GET /v1/ai/models should be documented'
        );
    }

    #[Test]
    public function ep_pub_013_indicators(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/indicators'),
            'GET /indicators should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Admin Endpoints
    // ========================================================================

    #[Test]
    public function ep_adm_001_admin_stats(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/admin/stats'),
            'GET /v1/admin/stats should be documented'
        );
    }

    #[Test]
    public function ep_adm_002_user_list(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/admin/users'),
            'GET /v1/admin/users should be documented'
        );
    }

    #[Test]
    public function ep_adm_007_health_overview(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/admin/health/overview'),
            'GET /v1/admin/health/overview should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Payment Endpoints
    // ========================================================================

    #[Test]
    public function ep_pay_001_payment_methods(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/payments/methods'),
            'GET /v1/payments/methods should be documented'
        );
    }

    #[Test]
    public function ep_pay_003_paypal_order(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/payments/paypal/order'),
            'POST /v1/payments/paypal/order should be documented'
        );
    }

    #[Test]
    public function ep_pay_006_credit_transactions(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/credits/transactions'),
            'GET /v1/credits/transactions should be documented'
        );
    }

    // ========================================================================
    // OAS-ENDPOINTS: Webhook Endpoints
    // ========================================================================

    #[Test]
    public function ep_wh_001_list_webhooks(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isPathDocumented('/v1/webhooks'),
            'GET /v1/webhooks should be documented'
        );
    }

    #[Test]
    public function ep_wh_002_create_webhook(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $this->assertTrue(
            $this->isMethodDocumented('/v1/webhooks', 'post'),
            'POST /v1/webhooks should be documented'
        );
    }

    // ========================================================================
    // OAS-SECURITY: Security Documentation
    // ========================================================================

    #[Test]
    public function sec_001_security_applied(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $protectedPaths = ['/v1/keywords', '/v1/projects', '/v1/sites', '/v1/audits'];
        $hasSecurity = false;

        foreach ($protectedPaths as $protectedPath) {
            if (isset($paths[$protectedPath])) {
                $methods = $paths[$protectedPath];
                foreach ($methods as $method => $operation) {
                    if (is_array($operation) && isset($operation['security'])) {
                        $hasSecurity = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue($hasSecurity, 'Protected endpoints should have security applied');
    }

    #[Test]
    public function sec_002_public_endpoints(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $publicPaths = ['/health', '/indicators'];

        foreach ($publicPaths as $publicPath) {
            if (isset($paths[$publicPath])) {
                $methods = $paths[$publicPath];
                foreach ($methods as $method => $operation) {
                    if (is_array($operation)) {
                        // Public endpoints should either not have security or have empty security
                        $security = $operation['security'] ?? null;
                        $this->assertTrue(
                            $security === null || $security === [],
                            "Public endpoint '{$method} {$publicPath}' should not require authentication"
                        );
                    }
                }
            }
        }
        
        $this->addToAssertionCount(1);
    }

    // ========================================================================
    // OAS-ERRORS: Error Documentation
    // ========================================================================

    #[Test]
    public function err_001_400_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has400 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['400'])) {
                    $has400 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has400, 'At least one endpoint should document 400 response');
    }

    #[Test]
    public function err_002_401_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has401 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['401'])) {
                    $has401 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has401, 'At least one endpoint should document 401 response');
    }

    #[Test]
    public function err_003_403_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has403 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['403'])) {
                    $has403 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has403, 'At least one endpoint should document 403 response');
    }

    #[Test]
    public function err_004_404_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has404 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['404'])) {
                    $has404 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has404, 'At least one endpoint should document 404 response');
    }

    #[Test]
    public function err_005_422_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has422 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['422'])) {
                    $has422 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has422, 'At least one endpoint should document 422 response');
    }

    #[Test]
    public function err_007_500_response(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $has500 = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                if (isset($operation['responses']['500'])) {
                    $has500 = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($has500, 'At least one endpoint should document 500 response');
    }

    // ========================================================================
    // OAS-EXAMPLES: Example Values
    // ========================================================================

    #[Test]
    public function ex_001_request_examples(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $hasRequestExample = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $requestBody = $operation['requestBody'] ?? null;
                if ($requestBody && isset($requestBody['content'])) {
                    foreach ($requestBody['content'] as $mediaType => $media) {
                        if (isset($media['example']) || isset($media['examples'])) {
                            $hasRequestExample = true;
                            break 3;
                        }
                    }
                }
            }
        }

        $this->assertTrue($hasRequestExample, 'At least one request should have an example');
    }

    #[Test]
    public function ex_002_response_examples(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $hasResponseExample = false;

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                
                $responses = $operation['responses'] ?? [];
                foreach ($responses as $response) {
                    if (!is_array($response)) continue;
                    
                    $content = $response['content'] ?? [];
                    foreach ($content as $mediaType => $media) {
                        if (isset($media['example']) || isset($media['examples'])) {
                            $hasResponseExample = true;
                            break 4;
                        }
                    }
                }
            }
        }

        $this->assertTrue($hasResponseExample, 'At least one response should have an example');
    }

    // ========================================================================
    // Coverage Summary
    // ========================================================================

    #[Test]
    public function endpoint_coverage_summary(): void
    {
        if (!$this->spec || isset($this->spec['_needs_yaml_parser'])) {
            $this->markTestSkipped('OpenAPI spec not available');
        }

        $paths = $this->spec['paths'];
        $totalEndpoints = 0;
        
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!is_array($operation)) continue;
                if (in_array($method, ['summary', 'description', 'servers', 'parameters'])) continue;
                if (str_starts_with($method, 'x-')) continue;
                
                $totalEndpoints++;
            }
        }

        // Should have at least 50 endpoints documented
        $this->assertGreaterThanOrEqual(50, $totalEndpoints, 
            "OpenAPI spec should document at least 50 endpoints, found: {$totalEndpoints}");
    }
}
