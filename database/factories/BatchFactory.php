<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => strtoupper(fake()->unique()->bothify('B-####??')),
            'manufacturing' => fake()->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
            'expiry' => fake()->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
        ];
    }
}
