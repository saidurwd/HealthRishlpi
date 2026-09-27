<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_new' => PatientCategoryNew::factory(),
            'category' => PatientCategory::factory(),
            'name' => fake()->name(),
            'age' => fake()->numberBetween(1, 80),
            'age_type' => 'Year',
            'sex' => fake()->randomElement(['Male', 'Female']),
            'marital_status' => 'Unmarried',
            'mobile' => fake()->numerify('01#########'),
            'address' => fake()->streetAddress(),
            'admission' => 'No',
            'created_on' => now(),
        ];
    }
}
