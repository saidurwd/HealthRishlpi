<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An active user of group 2 whose password is "secret". The group's role
 * must exist for permission checks (tests: TestCase::group()).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => User::hashPassword('secret'),
            'register_date' => now()->format('Y-m-d G:i:s'),
            'activation' => md5(fake()->uuid()),
            'group_id' => 2,
            'status' => 1,
            'photo' => '',
        ];
    }

    public function password(string $plain): static
    {
        return $this->state(['password' => User::hashPassword($plain)]);
    }

    public function super(): static
    {
        return $this->state(['group_id' => User::SUPER_GROUP]);
    }
}
