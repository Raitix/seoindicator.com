<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Site model for tracked websites.
 *
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property string|null $name
 */
class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function keywordSuggestions(): HasMany
    {
        return $this->hasMany(KeywordSuggestion::class);
    }
}
