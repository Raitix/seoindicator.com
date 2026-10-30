<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ranking history model for keyword positions.
 *
 * @property int $id
 * @property int $keyword_id
 * @property int|null $position
 * @property string|null $url_found
 * @property string|null $serp_features
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property int|null $check_duration_ms
 * @property string|null $error
 */
class Ranking extends Model
{
    use HasFactory;

    protected $table = 'ranking_history';

    protected $fillable = [
        'keyword_id',
        'position',
        'url_found',
        'serp_features',
        'checked_at',
        'check_duration_ms',
        'error',
    ];

    public $timestamps = false;

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }
}
