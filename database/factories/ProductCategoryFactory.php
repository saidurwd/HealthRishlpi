<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->word()).' '.fake()->numberBetween(1, 9999),
            'parent' => 0,
        ];
    }

    /**
     * Fill path and alias as the Yii forms did after saving.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (ProductCategory $model) => $model->updatePath());
    }
}
