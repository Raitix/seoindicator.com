<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Project Model (placeholder for testing).
 *
 * @property int $id
 * @property string $name
 * @property string|null $domain
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
    ];

    /**
     * Get the GSC data for the project.
     */
    public function gscData(): HasMany
    {
        return $this->hasMany(GSCData::class);
    }

    /**
     * Get the GSC connections for the project.
     */
    public function gscConnections(): HasMany
    {
        return $this->hasMany(GSCConnection::class);
    }
}