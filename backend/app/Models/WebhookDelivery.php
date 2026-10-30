<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDelivery model for tracking webhook delivery attempts.
 *
 * @property int $id
 * @property int $webhook_id
 * @property string $event_type
 * @property array $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int $attempt_count
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WebhookDelivery extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'attempt_count',
        'delivered_at',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the webhook that this delivery belongs to.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if the delivery was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->response_status !== null
            && $this->response_status >= 200
            && $this->response_status < 300;
    }

    /**
     * Mark the delivery as delivered.
     */
    public function markDelivered(int $status, ?string $body = null): void
    {
        $this->update([
            'response_status' => $status,
            'response_body' => $body,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Get the delivery status.
     * 
     * @return string pending|delivered|retrying|failed
     */
    public function getStatus(): string
    {
        // If delivered_at is set and response is success, it's delivered
        if ($this->delivered_at && $this->wasSuccessful()) {
            return 'delivered';
        }

        // If next_retry_at is set, it's in retry queue
        if ($this->next_retry_at !== null) {
            return 'retrying';
        }

        // If response_status is set but not successful and no retry, it's failed
        if ($this->response_status !== null && !$this->wasSuccessful() && $this->next_retry_at === null) {
            return 'failed';
        }

        // Otherwise, it's pending
        return 'pending';
    }

    /**
     * Mark the delivery as retrying.
     */
    public function markAsRetrying(\Illuminate\Support\Carbon $nextRetryAt): void
    {
        $this->update([
            'next_retry_at' => $nextRetryAt,
            'attempt_count' => $this->attempt_count + 1,
        ]);
    }

    /**
     * Mark the delivery as permanently failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'next_retry_at' => null,
            'delivered_at' => now(),
        ]);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to get deliveries pending retry.
     */
    public function scopePendingRetry($query)
    {
        return $query->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }

    /**
     * Scope to get failed deliveries.
     */
    public function scopeFailedDeliveries($query)
    {
        return $query->whereNotNull('response_status')
            ->where('response_status', '>=', 400)
            ->whereNull('next_retry_at');
    }
}
