<?php

namespace App\Console\Commands;

use App\Http\Controllers\GithubController;
use Illuminate\Console\Command;

class TestGithub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:github';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Github API...');

        $githubController = new GithubController();
        dd($githubController->checkGitHubActivity('NFTGrowing', true));

        $this->info('Done!');
    }
}
