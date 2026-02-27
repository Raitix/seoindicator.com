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
}
