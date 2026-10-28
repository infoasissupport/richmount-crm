<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Essential system initialization
        $this->call([
            RolesAndPermissionsSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
        ]);

        // Only seed dummy records if explicitly requested
        if (env('SEED_DEMO_DATA', false)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
