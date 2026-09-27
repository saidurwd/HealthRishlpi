<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->word()).' Department',
            'parent' => 0,
        ];
    }

    /**
     * Fill path and alias as the Yii forms did after saving.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Department $model) => $model->updatePath());
    }
}
