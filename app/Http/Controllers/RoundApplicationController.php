<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use Illuminate\Http\Request;
use Inertia\Inertia;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;


class RoundApplicationController extends Controller
{
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
        $result = new RoundApplicationPromptResult();
        $result->application_id = $application->id;
        $result->round_id = $application->round_id;

        $project = $application->project;
        $result->project_id = $project->id;

        $result->prompt_id = $application->round->prompt->id;
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

        $data = [
            'system_prompt' => $prompt->system_prompt,
            'prompt' => $prompt->prompt . PHP_EOL . PHP_EOL . 'Project name: ' . $project->title . PHP_EOL . 'Project website: ' . $project->website . PHP_EOL . 'Project description: ' . $project->description . PHP_EOL . 'Project twitter: ' . $project->projectTwitter . PHP_EOL . 'Project github: ' . $project->userGithub . PHP_EOL . PHP_EOL . 'Your response should only contain an array of comma separated objects for the evaluation criteria and returned in json format with each score being between 0 and 100:' . PHP_EOL . PHP_EOL . '[{
                "score": 15,
                "reason": "A specific reason for the score",
                "criteria": "A specific bit of evaluation criteria"
            }]',
        ];

        return $data;
    }
}
