<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $prompt
 * @property string|null $explanation
 * @property int $order
 * @property bool $is_active
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'prompt',
        'explanation',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * @return HasMany<AnswerOption, $this>
     */
    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->orderBy('order');
    }

    /**
     * @return HasMany<AnswerOption, $this>
     */
    public function correctOption(): HasMany
    {
        return $this->answerOptions()->where('is_correct', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
