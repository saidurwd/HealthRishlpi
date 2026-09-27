<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => ProductCategory::factory(),
            'unit' => Unit::factory(),
            'title' => ucfirst(fake()->unique()->word()).' '.fake()->randomElement(['500mg', '250mg', '10ml', '5%']),
        ];
    }
}
