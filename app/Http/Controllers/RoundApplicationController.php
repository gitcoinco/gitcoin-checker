<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use App\Models\RoundPrompt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;
use App\Services\NotificationService;

class RoundApplicationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $applications = RoundApplication::with([
            'round',
            'project',
            'latestPrompt' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
            },
            'results' => function ($query) {
                $query->orderBy('id', 'desc');
            }
        ])
            ->orderBy('id', 'desc')
            ->paginate();

        // No need for the foreach loop, since we are already eager loading the latest prompt.

        return Inertia::render('Application/Index', [
            'applications' => $applications
        ]);
    }


    public function evaluateAllShow(Round $round)

    {
        $maxProjects = 50;
        $this->notificationService->info('While this app is in testing, we are only evaluating ' . $maxProjects . ' projects at a time.');

        $latestPrompt = $round->prompt()->orderBy('id', 'desc')->first();
        $projectsIdsWithEvaluatedPrompts = RoundApplicationPromptResult::where('prompt_id', $latestPrompt->id)
            ->distinct('project_id')
            ->pluck('project_id');

        $projects = $round->projects()->whereNotIn('projects.id', $projectsIdsWithEvaluatedPrompts)->with(['applications' => function ($query) use ($round) {
            $query->where('round_id', $round->id);
        }, 'applications.results' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->paginate();



        return Inertia::render('Round/EvaluateAll', [
            'round' => $round,
            'projects' => $projects,
            'latestPrompt' => $latestPrompt,
        ]);
    }

    public function evaluate(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $round = $application->round;

        $application->load('project');

        $result = $application->results()->orderBy('id', 'desc')->first();

        return Inertia::render('Application/Evaluate', [
            'round' => $round,
            'application' => $application,
            'prompt' => RoundApplicationController::getPrompt($application),
            'result' => $result ? $result : '',
        ]);
    }

    private function chatGPT(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        // If the application has already been evaluated against the latest prompt, don't do it again
        $latestPrompt = $application->round->prompt()->orderBy('id', 'desc')->first();

        $latestResult = $application->results()->orderBy('id', 'desc')->first();
        if ($latestResult && $latestResult->prompt_id == $latestPrompt->id) {
            return $latestResult;
        }


        $result = new RoundApplicationPromptResult();
        $result->application_id = $application->id;
        $result->round_id = $application->round_id;

        $project = $application->project;
        $result->project_id = $project->id;

        $result->prompt_id = $latestPrompt->id;
        $result->prompt_type = 'chatgpt';

        $promptData = RoundApplicationController::getPrompt($application);
        $result->prompt_data = $promptData['prompt'];
        $result->save();

        $chatGPT = new ChatGPTV2(env('OPENAI_API_KEY'), 'https://api.openai.com/');
        $chatGPT->addMessage($promptData['system_prompt'], 'system');
        $answers = $chatGPT->ask($result->prompt_data, 'user');

        $answer = null;
        foreach ($answers as $item) {
            $answer = $item['answer'];
        }

        $result->results_data = $answer;
        $result->save();

        return $result;;
    }

    public function checkAgainstChatGPT(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
        $result = $application->results()->orderBy('id', 'desc')->first();
        return redirect()->route('round.application.evaluate', ['application' => $application->id, 'result' => $result]);
    }


    public function checkAgainstChatGPTList(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $result = $this->chatGPT($application);
        $result = $application->results()->orderBy('id', 'desc')->first();
        $round = $application->round;
        $project = $round->projects()->where('projects.id', $application->project->id)->with(['applications' => function ($query) use ($round, $application) {
            $query->where('round_id', $round->id);
            $query->where('id', $application->id);
        }, 'applications.results' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->first();


        return response()->json(['project' => $project]);
    }



    // load the prompt for the application
    public static function getPrompt(RoundApplication $application)
    {

        $round = $application->round;
        $prompt = RoundPromptController::ensurePromptExists($round);

        $project = $application->project;

        $returnedFormat = 'Your response should only contain an array of comma separated objects for the evaluation criteria and returned in json format with each score being between 0 and 100:' . PHP_EOL . PHP_EOL . '[{
            "score": 15,
            "reason": "A specific reason for the score",
            "criteria": "A specific bit of evaluation criteria"
        }]';

        $projectTwitter = 'Project Twitter: ' . ($project->projectTwitter ? 'https://twitter.com/' . $project->projectTwitter : 'N/A');
        $projectGithub = 'Project Github: ' . ($project->projectGithub ? 'https://github.com/' . $project->projectGithub : 'N/A');
        $userGithub = 'User Github: ' . ($project->userGithub ? 'https://github.com/' . $project->userGithub : 'N/A');

        $data = [
            'system_prompt' => $prompt->system_prompt . PHP_EOL . PHP_EOL . $returnedFormat,
            'prompt' => $prompt->prompt . PHP_EOL . PHP_EOL . 'Project name: ' . $project->title . PHP_EOL . 'Project website: ' . $project->website . PHP_EOL . 'Project description: ' . $project->description . PHP_EOL . $projectTwitter . PHP_EOL . $projectGithub . PHP_EOL . $userGithub . PHP_EOL,
        ];

        return $data;
    }
}
