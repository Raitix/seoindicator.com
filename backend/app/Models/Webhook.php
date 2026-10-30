<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Webhook model for storing webhook configurations.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property array $events
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Webhook extends Model
{
    use HasFactory;

    /**
     * Valid webhook event types.
     */
    public const VALID_EVENTS = [
        'ranking.changed',
        'alert.triggered',
        'report.completed',
        'keyword.added',
        'keyword.removed',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'events' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the user that owns the webhook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the deliveries for this webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Generate a new webhook secret.
     */
    public static function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }

    /**
     * Check if the webhook is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * Check if the webhook subscribes to a given event.
     */
    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
