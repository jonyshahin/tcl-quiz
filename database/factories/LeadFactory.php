<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'is_winner' => fake()->boolean(),
            'score' => fake()->numberBetween(0, 5).'/5',
        ];
    }
}
