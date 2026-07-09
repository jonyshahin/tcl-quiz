<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DrawWinnerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $draw_id
 * @property int $lead_id
 * @property int $position
 */
class DrawWinner extends Model
{
    /** @use HasFactory<DrawWinnerFactory> */
    use HasFactory;

    protected $fillable = [
        'draw_id',
        'lead_id',
        'position',
    ];

    /**
     * @return BelongsTo<Draw, $this>
     */
    public function draw(): BelongsTo
    {
        return $this->belongsTo(Draw::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
