<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnswerOption;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnswerOption>
 */
class AnswerOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'text' => fake()->sentence(4),
            'is_correct' => false,
            'order' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }
}
