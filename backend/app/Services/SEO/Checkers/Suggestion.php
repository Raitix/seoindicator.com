<?php

namespace App\Services\SEO\Checkers;

/**
 * Immutable DTO representing a suggestion for improving an SEO indicator.
 *
 * @see TASK-18-3
 */
final readonly class Suggestion
{
    /**
     * Valid suggestion types.
     */
    private const VALID_TYPES = ['warning', 'error', 'info'];

    /**
     * @param string $type Type: 'warning', 'error', or 'info'
     * @param string $message Human-readable suggestion message
     * @param string|null $action Optional action to take
     * @param int $priority Priority 1-3 (1=low, 2=medium, 3=high)
     */
    public function __construct(
        public string $type,
        public string $message,
        public ?string $action,
        public int $priority = 1
    ) {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid suggestion type: %s. Must be one of: %s', $type, implode(', ', self::VALID_TYPES))
            );
        }

        if (trim($message) === '') {
            throw new \InvalidArgumentException('Suggestion message cannot be empty');
        }

        if ($priority < 1 || $priority > 3) {
            throw new \InvalidArgumentException('Priority must be between 1 and 3');
        }
    }

    /**
     * Check if this is a warning suggestion.
     */
    public function isWarning(): bool
    {
        return $this->type === 'warning';
    }

    /**
     * Check if this is an error suggestion.
     */
    public function isError(): bool
    {
        return $this->type === 'error';
    }

    /**
     * Check if this is an info suggestion.
     */
    public function isInfo(): bool
    {
        return $this->type === 'info';
    }

    /**
     * Check if this is a high priority suggestion.
     */
    public function isHighPriority(): bool
    {
        return $this->priority === 3;
    }

    /**
     * Check if this is a medium priority suggestion.
     */
    public function isMediumPriority(): bool
    {
        return $this->priority === 2;
    }

    /**
     * Check if this is a low priority suggestion.
     */
    public function isLowPriority(): bool
    {
        return $this->priority === 1;
    }

    /**
     * Get human-readable priority label.
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            default => throw new \LogicException('Invalid priority')
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'action' => $this->action,
            'priority' => $this->priority,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            message: $data['message'],
            action: $data['action'] ?? null,
            priority: $data['priority'] ?? 1
        );
    }
}
