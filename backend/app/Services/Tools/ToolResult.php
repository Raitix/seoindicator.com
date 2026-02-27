<?php

namespace App\Services\Tools;

/**
 * Data Transfer Object for tool analysis results.
 * 
 * TASK-16-1: Tools Infrastructure
 */
class ToolResult
{
    /**
     * @param bool $success Whether the analysis was successful
     * @param string $toolName Name of the tool that performed the analysis
     * @param string $url URL that was analyzed
     * @param array|null $data Analysis data (on success)
     * @param string|null $error Error message (on failure)
     * @param string|null $errorCode Error code for programmatic handling
     * @param string $analyzedAt ISO timestamp of when analysis was performed
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $toolName,
        public readonly string $url,
        public readonly ?array $data = null,
        public readonly ?string $error = null,
        public readonly ?string $errorCode = null,
        public readonly string $analyzedAt = ''
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'tool' => $this->toolName,
            'url' => $this->url,
            'analyzedAt' => $this->analyzedAt,
        ];

        if ($this->success) {
            $result['data'] = $this->data;
        } else {
            $result['error'] = $this->error;
            $result['errorCode'] = $this->errorCode;
        }

        return $result;
    }

    /**
     * Create a successful result.
     *
     * @param string $toolName
     * @param string $url
     * @param array $data
     * @return self
     */
    public static function ok(string $toolName, string $url, array $data): self
    {
        return new self(
            success: true,
            toolName: $toolName,
            url: $url,
            data: $data,
            analyzedAt: now()->toISOString()
        );
    }

    /**
     * Create a failed result.
     *
     * @param string $toolName
     * @param string $url
     * @param string $error
     * @param string|null $errorCode
     * @return self
     */
    public static function fail(string $toolName, string $url, string $error, ?string $errorCode = null): self
    {
        return new self(
            success: false,
            toolName: $toolName,
            url: $url,
            error: $error,
            errorCode: $errorCode ?? 'ANALYSIS_ERROR',
            analyzedAt: now()->toISOString()
        );
    }
}
