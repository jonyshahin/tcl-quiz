<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DrawFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $note
 * @property int $eligible_count
 */
class Draw extends Model
{
    /** @use HasFactory<DrawFactory> */
    use HasFactory;

    protected $fillable = [
        'note',
        'eligible_count',
    ];

    /**
     * @return HasMany<DrawWinner, $this>
     */
    public function winners(): HasMany
    {
        return $this->hasMany(DrawWinner::class);
    }
}
