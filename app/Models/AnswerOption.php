<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnswerOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $question_id
 * @property string $label
 * @property string $text
 * @property bool $is_correct
 * @property int $order
 */
class AnswerOption extends Model
{
    /** @use HasFactory<AnswerOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'label',
        'text',
        'is_correct',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
