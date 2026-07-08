<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $quiz_attempt_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property bool $is_winner
 * @property string $score
 */
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'name',
        'email',
        'phone',
        'is_winner',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }
}
