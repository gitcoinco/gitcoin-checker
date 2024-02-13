<?php

namespace App\Console\Commands;

use App\Http\Controllers\RoundApplicationController;
use App\Models\RoundApplication;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class GPTEvaluations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gpt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run through all applications that do not have GPT results and check them against GPT.';

    private $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationService = app(NotificationService::class);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = RoundApplication::whereDoesntHave('results')->get();
        foreach ($applications as $application) {
            echo "Checking application {$application->id} against ChatGPT" . PHP_EOL;
            $roundApplicationController = new RoundApplicationController($this->notificationService);
            $roundApplicationController->checkAgainstChatGPT($application);

            if ($application->results->count() > 0) {
                echo "Application {$application->id} has been evaluated by ChatGPT" . PHP_EOL;
                $result = $application->results()->orderBy('id', 'desc')->first();
                if ($result->results_data !== '[]') {
                    echo "Application {$application->id} has valid results" . PHP_EOL;
                } else {
                    echo "Application {$application->id} has no valid results" . PHP_EOL;
                }
            }
        }
    }
}
