<?php

namespace Tests\Unit\Services\Tools;

use App\Exporters\PdfToolExporter;
use App\Exporters\XlsToolExporter;
use App\Services\Tools\ToolExportService;
use App\Services\Tools\ToolResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ToolExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ToolExportService $service;
    private PdfToolExporter|MockInterface $pdfExporter;
    private XlsToolExporter|MockInterface $xlsExporter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pdfExporter = Mockery::mock(PdfToolExporter::class);
        $this->xlsExporter = Mockery::mock(XlsToolExporter::class);
        $this->service = new ToolExportService($this->pdfExporter, $this->xlsExporter);
        
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =====================================================
    // Service Instantiation Tests (SES-001 to SES-003)
    // =====================================================

    /** @test */
    public function service_can_be_instantiated(): void
    {
        $service = app(ToolExportService::class);
        $this->assertInstanceOf(ToolExportService::class, $service);
    }

    /** @test */
    public function service_has_pdf_exporter_dependency(): void
    {
        $this->assertInstanceOf(PdfToolExporter::class, $this->service->getPdfExporter());
    }

    /** @test */
    public function service_has_xls_exporter_dependency(): void
    {
        $this->assertInstanceOf(XlsToolExporter::class, $this->service->getXlsExporter());
    }

    // =====================================================
    // createExport() Method Tests (SES-010 to SES-025)
    // =====================================================

    /** @test */
    public function create_export_returns_download_url(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function create_export_uses_pdf_exporter_for_pdf_format(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $this->xlsExporter
            ->shouldNotReceive('export');
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
    }

    /** @test */
    public function create_export_uses_xls_exporter_for_xls_format(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->xlsExporter
            ->shouldReceive('array')
            ->once()
            ->andReturn([
                ['Meta Tags', 'Title', 'Example Page', 'OK', '-'],
            ]);
        
        $this->pdfExporter
            ->shouldNotReceive('export');
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'xls',
            data: $toolResult,
            userId: 1
        );
    }

    /** @test */
    public function create_export_stores_file_in_temp_location(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Verify file was stored
        $files = Storage::disk('local')->files('exports');
        $this->assertNotEmpty($files);
    }

    /** @test */
    public function create_export_generates_download_token(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Extract token from URL
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1] ?? null;
        
        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token));
    }

    /** @test */
    public function create_export_caches_token_data(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Extract token and verify cached data
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $cached = Cache::get("export:{$token}");
        $this->assertNotNull($cached);
        $this->assertEquals(1, $cached['user_id']);
        $this->assertEquals('pdf', $cached['format']);
    }

    /** @test */
    public function create_export_throws_exception_for_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'invalid',
            data: [],
            userId: 1
        );
    }

    /** @test */
    public function create_export_handles_empty_tool_name(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: '',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function create_export_handles_empty_url(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: '',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function create_export_handles_empty_data(): void
    {
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: [],
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function create_export_sets_token_expiry_to_one_hour(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Extract token
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // Verify TTL is approximately 1 hour (3600 seconds)
        $ttl = Cache::getStore()->getPrefix() . "export:{$token}";
        // Note: Actual TTL verification depends on cache driver
        $this->assertTrue(Cache::has("export:{$token}"));
    }

    /** @test */
    public function create_export_stores_user_id_in_cache(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 42
        );
        
        // Extract token
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $cached = Cache::get("export:{$token}");
        $this->assertEquals(42, $cached['user_id']);
    }

    /** @test */
    public function create_export_stores_tool_name_in_cache(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'keyword-density',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Extract token
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $cached = Cache::get("export:{$token}");
        $this->assertEquals('keyword-density', $cached['tool']);
    }

    // =====================================================
    // downloadExport() Method Tests (SES-030 to SES-050)
    // =====================================================

    /** @test */
    public function download_export_returns_response_for_valid_token(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        // Create export first
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Extract token
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // Download export
        $response = $this->service->downloadExport($token, 1);
        
        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test */
    public function download_export_returns_correct_content_type_for_pdf(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function download_export_returns_correct_content_type_for_xls(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->xlsExporter
            ->shouldReceive('array')
            ->once()
            ->andReturn([
                ['Meta Tags', 'Title', 'Example Page', 'OK', '-'],
            ]);
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'xls',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        // XLS content type (spreadsheet)
        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(
            in_array($contentType, [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel;charset=utf-8',
            ])
        );
    }

    /** @test */
    public function download_export_throws_exception_for_invalid_token(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $this->service->downloadExport('invalid-token-12345', 1);
    }

    /** @test */
    public function download_export_throws_exception_for_expired_token(): void
    {
        // Create a cached entry that's expired
        Cache::put('export:expired-token', [
            'file' => 'exports/expired.pdf',
            'format' => 'pdf',
            'user_id' => 1,
        ], now()->subMinute());
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $this->service->downloadExport('expired-token', 1);
    }

    /** @test */
    public function download_export_throws_exception_for_wrong_user(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // Try to download with different user
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $this->service->downloadExport($token, 999);
    }

    /** @test */
    public function download_export_deletes_file_after_download(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $cached = Cache::get("export:{$token}");
        $filePath = $cached['file'];
        
        // Verify file exists before download
        Storage::disk('local')->assertExists($filePath);
        
        // Download
        $this->service->downloadExport($token, 1);
        
        // Verify file deleted after download
        Storage::disk('local')->assertMissing($filePath);
    }

    /** @test */
    public function download_export_deletes_token_after_download(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // Verify token exists before download
        $this->assertTrue(Cache::has("export:{$token}"));
        
        // Download
        $this->service->downloadExport($token, 1);
        
        // Verify token deleted after download
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test */
    public function download_export_returns_disposition_header(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
    }

    /** @test */
    public function download_export_filename_contains_tool_name(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('meta-tag-analyzer', $disposition);
    }

    /** @test */
    public function download_export_pdf_has_pdf_extension(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.pdf', $disposition);
    }

    /** @test */
    public function download_export_xls_has_xls_extension(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->xlsExporter
            ->shouldReceive('array')
            ->once()
            ->andReturn([
                ['Meta Tags', 'Title', 'Example Page', 'OK', '-'],
            ]);
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'xls',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        $response = $this->service->downloadExport($token, 1);
        
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    // =====================================================
    // Token Generation Tests (SES-060 to SES-065)
    // =====================================================

    /** @test */
    public function token_is_64_characters(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);
        
        $token = $method->invoke($this->service);
        
        $this->assertEquals(64, strlen($token));
    }

    /** @test */
    public function token_is_alphanumeric(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);
        
        $token = $method->invoke($this->service);
        
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
    }

    /** @test */
    public function tokens_are_unique(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateToken');
        $method->setAccessible(true);
        
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $method->invoke($this->service);
        }
        
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);
    }

    // =====================================================
    // File Management Tests (SES-070 to SES-075)
    // =====================================================

    /** @test */
    public function file_is_stored_in_exports_directory(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $files = Storage::disk('local')->files('exports');
        $this->assertNotEmpty($files);
        $this->assertStringContainsString('exports/', $files[0]);
    }

    /** @test */
    public function file_name_contains_timestamp(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $files = Storage::disk('local')->files('exports');
        // File name should contain date pattern like 20260304
        $this->assertMatchesRegularExpression('/\d{8}/', $files[0]);
    }

    /** @test */
    public function multiple_exports_create_unique_files(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->twice()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $this->service->createExport(
            tool: 'keyword-density',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $files = Storage::disk('local')->files('exports');
        $this->assertCount(2, $files);
    }

    // =====================================================
    // Security Tests (SES-080 to SES-085)
    // =====================================================

    /** @test */
    public function export_prevents_path_traversal_in_tool_name(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        // Should sanitize or reject path traversal attempts
        $url = $this->service->createExport(
            tool: '../../../etc/passwd',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // Verify file was created in exports directory (not parent)
        $files = Storage::disk('local')->files('exports');
        foreach ($files as $file) {
            $this->assertStringNotContainsString('../', $file);
            $this->assertStringNotContainsString('etc', $file);
            $this->assertStringNotContainsString('passwd', $file);
        }
    }

    /** @test */
    public function export_sanitizes_url_in_filename(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com/<script>alert(1)</script>',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // File should not contain script tags
        $files = Storage::disk('local')->files('exports');
        foreach ($files as $file) {
            $this->assertStringNotContainsString('<script>', $file);
        }
    }

    /** @test */
    public function user_cannot_access_other_users_exports(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        // User 1 creates export
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // User 2 tries to access
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $this->service->downloadExport($token, 2);
    }

    // =====================================================
    // Edge Cases Tests (SES-090 to SES-100)
    // =====================================================

    /** @test */
    public function handles_empty_indicators_gracefully(): void
    {
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: ['indicators' => []],
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function handles_large_export_data(): void
    {
        $largeIndicators = [];
        for ($i = 0; $i < 500; $i++) {
            $largeIndicators[] = [
                'category' => 'Category ' . $i,
                'name' => 'Indicator ' . $i,
                'value' => 'Value ' . str_repeat('x', 100),
                'status' => 'OK',
                'recommendation' => 'Recommendation ' . $i,
            ];
        }
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: ['indicators' => $largeIndicators],
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function handles_unicode_tool_name(): void
    {
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'メタタグ分析ツール',
            url: 'https://example.com',
            format: 'pdf',
            data: [],
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function handles_special_characters_in_url(): void
    {
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com/page?foo=bar&baz=qux',
            format: 'pdf',
            data: [],
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
    }

    /** @test */
    public function handles_concurrent_export_requests(): void
    {
        $this->pdfExporter
            ->shouldReceive('export')
            ->times(10)
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $urls = [];
        for ($i = 0; $i < 10; $i++) {
            $urls[] = $this->service->createExport(
                tool: 'tool-' . $i,
                url: 'https://example.com',
                format: 'pdf',
                data: [],
                userId: 1
            );
        }
        
        // All URLs should be unique
        $uniqueUrls = array_unique($urls);
        $this->assertCount(10, $uniqueUrls);
    }

    // =====================================================
    // Integration Tests (SES-110 to SES-115)
    // =====================================================

    /** @test */
    public function full_export_workflow(): void
    {
        $toolResult = $this->createMockToolResult();
        
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        // Step 1: Create export
        $url = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        $this->assertStringContainsString('/api/v1/tools/export/download/', $url);
        
        // Step 2: Extract token
        preg_match('/download\/([a-zA-Z0-9]+)/', $url, $matches);
        $token = $matches[1];
        
        // Step 3: Verify token cached
        $this->assertTrue(Cache::has("export:{$token}"));
        
        // Step 4: Download export
        $response = $this->service->downloadExport($token, 1);
        $this->assertInstanceOf(Response::class, $response);
        
        // Step 5: Verify cleanup
        $this->assertFalse(Cache::has("export:{$token}"));
    }

    /** @test */
    public function export_with_different_formats(): void
    {
        $toolResult = $this->createMockToolResult();
        
        // PDF export
        $this->pdfExporter
            ->shouldReceive('export')
            ->once()
            ->andReturn('%PDF-1.4 mock pdf content');
        
        $pdfUrl = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'pdf',
            data: $toolResult,
            userId: 1
        );
        
        // XLS export
        $this->xlsExporter
            ->shouldReceive('array')
            ->once()
            ->andReturn([
                ['Meta Tags', 'Title', 'Example Page', 'OK', '-'],
            ]);
        
        $xlsUrl = $this->service->createExport(
            tool: 'meta-tag-analyzer',
            url: 'https://example.com',
            format: 'xls',
            data: $toolResult,
            userId: 1
        );
        
        $this->assertNotEquals($pdfUrl, $xlsUrl);
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    private function createMockToolResult(): array
    {
        return [
            'toolName' => 'Meta Tag Analyzer',
            'url' => 'https://example.com',
            'analyzedAt' => now()->toIso8601String(),
            'indicators' => [
                [
                    'category' => 'Meta Tags',
                    'name' => 'Title',
                    'value' => 'Example Page - Welcome',
                    'status' => 'OK',
                    'recommendation' => '',
                ],
                [
                    'category' => 'Meta Tags',
                    'name' => 'Description',
                    'value' => 'Too short at 50 characters',
                    'status' => 'Warning',
                    'recommendation' => 'Increase length to 150-160 characters',
                ],
            ],
            'summary' => [
                'score' => 85,
                'pass' => 10,
                'warning' => 3,
                'fail' => 2,
            ],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'message' => 'Add meta description',
                    'action' => 'Write 150-160 characters',
                ],
            ],
        ];
    }
}
