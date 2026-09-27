<?php

namespace Database\Factories;

use App\Models\PatientCategoryNew;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientCategoryNew>
 */
class PatientCategoryNewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->word()),
            'parent' => 0,
        ];
    }

    /**
     * Fill path and alias as the Yii forms did after saving.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (PatientCategoryNew $model) => $model->updatePath());
    }
}
