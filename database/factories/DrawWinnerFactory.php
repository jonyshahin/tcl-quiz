<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Draw;
use App\Models\DrawWinner;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DrawWinner>
 */
class DrawWinnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'draw_id' => Draw::factory(),
            'lead_id' => Lead::factory(),
            'position' => fake()->numberBetween(1, 3),
        ];
    }
}
