<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Draw;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Draw>
 */
class DrawFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note' => null,
            'eligible_count' => fake()->numberBetween(0, 100),
        ];
    }
}
