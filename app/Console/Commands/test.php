<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Very basic test to see that we can run artisan commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
