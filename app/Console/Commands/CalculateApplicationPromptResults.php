<?php

namespace App\Console\Commands;

use App\Http\Controllers\RoundApplicationPromptResultController;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use Illuminate\Console\Command;

class CalculateApplicationPromptResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate-application-prompt-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate all the application score results from ChatGPT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = RoundApplicationPromptResult::all();


        foreach ($applications as $application) {
            RoundApplicationPromptResultController::calculateScore($application);
        }
    }
}
