<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Role Seeder.
 *
 * Seeds roles if using a role-based system with a separate roles table.
 * Currently, roles are stored as a column on the users table, so this seeder
 * is a placeholder for tests that expect it to exist.
 *
 * @see TASK-27-5
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles are stored as a column on the users table (default: 'user')
        // No seeding needed - this is a placeholder for test compatibility
    }
}
