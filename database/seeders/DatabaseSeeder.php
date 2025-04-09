<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersTableSeeder::class,
            SubmissionTypeSeeder::class,
            DocumentRequirementSeeder::class,
            WorkflowStageSeeder::class,
            
            // Optional seeders for development/testing
            // Demo data seeders (only use in development)
        ]);
    }
}