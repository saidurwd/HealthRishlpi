<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->company().' Store',
            'parent' => 0,
        ];
    }

    /**
     * Fill path and alias as the Yii forms did after saving.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Store $model) => $model->updatePath());
    }
}
