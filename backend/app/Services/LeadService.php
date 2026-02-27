<?php

namespace App\Services;

use App\Models\ToolLead;
use App\Repositories\LeadRepository;
use Illuminate\Support\Facades\Log;

/**
 * Lead Service for managing tool leads.
 * 
 * TASK-16-5: Email Capture Modal & API
 * TASK-16-6: Lead Storage Service
 */
class LeadService
{
    protected LeadRepository $repository;
    protected int $duplicateThresholdHours;

    public function __construct(LeadRepository $repository)
    {
        $this->repository = $repository;
        $this->duplicateThresholdHours = config('tools.leads.duplicate_threshold_hours', 24);
    }

    /**
     * Capture a lead from tool usage.
     * 
     * @param array $data Lead data (email, tool, url, ip_address, user_agent)
     * @return array{success: bool, lead: ToolLead|null, is_duplicate: bool}
     */
    public function capture(array $data): array
    {
        $email = strtolower($data['email'] ?? '');
        $tool = $data['tool'] ?? '';

        // Validate tool
        if (!ToolLead::isValidTool($tool)) {
            return [
                'success' => false,
                'lead' => null,
                'is_duplicate' => false,
                'error' => 'Invalid tool name',
            ];
        }

        // Validate email
        if (!$this->isValidEmail($email)) {
            return [
                'success' => false,
                'lead' => null,
                'is_duplicate' => false,
                'error' => 'Invalid email address',
            ];
        }

        // Check for duplicate within threshold period
        $existing = $this->repository->findRecentDuplicate($email, $tool, $this->duplicateThresholdHours);
        
        if ($existing) {
            Log::info('Lead capture: duplicate detected', [
                'email' => $email,
                'tool' => $tool,
                'existing_id' => $existing->id,
            ]);

            return [
                'success' => true,
                'lead' => $existing,
                'is_duplicate' => true,
            ];
        }

        // Create new lead
        $lead = $this->repository->create([
            'email' => $email,
            'tool' => $tool,
            'url' => $data['url'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        Log::info('Lead captured', [
            'lead_id' => $lead->id,
            'email' => $email,
            'tool' => $tool,
        ]);

        return [
            'success' => true,
            'lead' => $lead,
            'is_duplicate' => false,
        ];
    }

    /**
     * Validate email address.
     */
    protected function isValidEmail(string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        if (strlen($email) > 255) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get lead statistics.
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get leads with pagination.
     */
    public function getLeads(int $perPage = 15, array $filters = []): array
    {
        $paginator = $this->repository->paginate($perPage, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Export leads as CSV.
     */
    public function exportCsv(array $filters = []): string
    {
        $csv = [];
        $csv[] = ['ID', 'Email', 'Tool', 'URL', 'IP Address', 'Created At'];

        foreach ($this->repository->getForExport($filters) as $row) {
            $csv[] = [
                $row['id'],
                $row['email'],
                $row['tool'],
                $row['url'] ?? '',
                $row['ip_address'] ?? '',
                $row['created_at'],
            ];
        }

        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Delete a lead (soft delete for GDPR compliance).
     */
    public function delete(int $id): bool
    {
        return $this->repository->softDelete($id);
    }

    /**
     * Restore a deleted lead.
     */
    public function restore(int $id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Permanently delete a lead.
     */
    public function permanentDelete(int $id): bool
    {
        return $this->repository->forceDelete($id);
    }
}
