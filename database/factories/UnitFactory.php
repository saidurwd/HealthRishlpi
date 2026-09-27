<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->randomElement(['Piece', 'Box', 'Bottle', 'Strip', 'Tube', 'Vial', 'Sachet', 'Ampoule', 'Pack', 'Jar']).' '.fake()->unique()->numberBetween(1, 9999),
            'formal_name' => fake()->lexify('???'),
            'decimal_place' => 0,
        ];
    }
}
