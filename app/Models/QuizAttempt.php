<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_token
 * @property int $total_questions
 * @property int $correct_count
 * @property bool $is_winner
 * @property array<int, array{selected_option_id: int, is_correct: bool}>|null $answers
 * @property Carbon|null $completed_at
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'session_token',
        'total_questions',
        'correct_count',
        'is_winner',
        'answers',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'correct_count' => 'integer',
            'is_winner' => 'boolean',
            'answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Lead, $this>
     */
    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class);
    }

    public function scoreLabel(): string
    {
        return "{$this->correct_count}/{$this->total_questions}";
    }
}
