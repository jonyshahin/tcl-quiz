<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_token' => (string) Str::uuid(),
            'total_questions' => 0,
            'correct_count' => 0,
            'is_winner' => false,
            'answers' => [],
            'completed_at' => null,
        ];
    }

    public function completed(int $total, int $correct): static
    {
        return $this->state(function (array $attributes) use ($total, $correct) {
            // Build an answers map consistent with the score so finalize() agrees.
            $answers = [];
            for ($i = 1; $i <= $total; $i++) {
                $answers[(string) $i] = [
                    'selected_option_id' => $i,
                    'is_correct' => $i <= $correct,
                ];
            }

            return [
                'total_questions' => $total,
                'correct_count' => $correct,
                'is_winner' => $correct === $total && $total > 0,
                'answers' => $answers,
                'completed_at' => now(),
            ];
        });
    }
}
