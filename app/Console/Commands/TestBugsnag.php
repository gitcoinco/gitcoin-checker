<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;

class TestBugsnag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bugsnag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test bugsnag';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Bugsnag::notifyException(new RuntimeException("Test error from Laravel"));

        return Command::SUCCESS;
    }
}
