<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the 'php artisan ingest:data' command
        Artisan::call('ingest:data');

        // Capture the output
        $output = Artisan::output();

        // Display the output in the console
        $this->command->info($output);
    }
}
