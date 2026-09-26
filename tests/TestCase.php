<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Tests run against HealthRishlpi_test, an empty schema-only copy of the
 * legacy database (see database/sql/create_test_database.sh). Every test is
 * wrapped in a transaction and rolled back.
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUser(array $attributes = [], string $password = 'secret'): User
    {
        $user = new User;
        $user->forceFill(array_merge([
            'full_name' => 'Test User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => User::hashPassword($password),
            'group_id' => 2,
            'status' => 1,
            'photo' => '',
        ], $attributes))->save();

        return $user;
    }
}
