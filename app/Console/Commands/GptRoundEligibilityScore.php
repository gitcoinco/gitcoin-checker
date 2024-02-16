<?php

namespace App\Console\Commands;

use App\Http\Controllers\GptRoundEligibilityScoreController;
use Illuminate\Console\Command;

class GptRoundEligibilityScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:roundscore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'How well does a round score in terms of how it is setup, e.g. does the eligibility criteria match the questions asked in the round applications?';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new GptRoundEligibilityScoreController();
        $controller->scoreRounds();
    }
}
