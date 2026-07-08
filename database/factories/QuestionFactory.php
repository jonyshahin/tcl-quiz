<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnswerOption;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prompt' => rtrim(fake()->sentence(), '.').'?',
            'explanation' => fake()->optional()->sentence(),
            'order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    /**
     * Attach four options (A–D) with exactly one correct answer.
     */
    public function withOptions(int $correctIndex = 0): static
    {
        return $this->afterCreating(function (Question $question) use ($correctIndex) {
            $labels = ['A', 'B', 'C', 'D'];

            foreach ($labels as $i => $label) {
                AnswerOption::factory()->create([
                    'question_id' => $question->id,
                    'label' => $label,
                    'order' => $i,
                    'is_correct' => $i === $correctIndex,
                ]);
            }
        });
    }
}
