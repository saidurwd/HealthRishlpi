<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(2, true)),
            'parent' => 0,
            'rate' => fake()->randomElement([50, 100, 150, 200, 500]),
            'discount' => 'Yes',
            'rate_status' => 'Auto',
            'service_type' => 'Service',
            'status' => 'Active',
        ];
    }

    /**
     * Fill path and alias as the Yii forms did after saving.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Service $model) => $model->updatePath());
    }
}
