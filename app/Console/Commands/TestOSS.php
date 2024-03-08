<?php

namespace App\Console\Commands;

use App\Http\Controllers\GithubController;
use App\Http\Controllers\OpensourceObserverController;
use Illuminate\Console\Command;

class TestOSS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:oss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test an Opensource Observer Call';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $repo = 'bobjiang';

        $ossController = new OpensourceObserverController();
        $data = ($ossController->getProjectStatistics($repo));

        $this->info('Data: ' . json_encode($data));

        // $githubController = new GithubController();
        // $response = $githubController->getGithubRepoDetails($repo);
        // dd($response);
    }
}
