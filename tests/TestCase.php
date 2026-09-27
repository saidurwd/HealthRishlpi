<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;

/**
 * Tests run against HealthRishlpi_test: the legacy schema with the
 * migrations applied and no data (see database/sql/create_test_database.sh).
 * Every test is wrapped in a transaction and rolled back.
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    /**
     * A user of group 2 unless given; the group gets a role with every
     * permission if it has none yet.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUser(array $attributes = [], string $password = 'secret'): User
    {
        $attributes += ['group_id' => 2];
        if (is_int($attributes['group_id'])) {
            $this->group($attributes['group_id']);
        }

        $user = new User;
        $user->forceFill(array_merge([
            'full_name' => 'Test User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => User::hashPassword($password),
            'status' => 1,
            'photo' => '',
        ], $attributes))->save();

        return $user;
    }

    /**
     * The role of group $id, created with every permission when missing.
     */
    protected function group(int $id, ?string $name = null): Role
    {
        $role = Role::query()->find($id);

        if ($role === null) {
            $role = new Role(['name' => $name ?? "Group $id"]);
            $role->forceFill(['id' => $id])->save();
            $role->syncPermissions(Permission::all());
        }

        return $role;
    }
}
