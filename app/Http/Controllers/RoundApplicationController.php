<?php

namespace App\Http\Controllers;

use App\Models\RoundApplication;
use App\Models\RoundApplicationPromptResult;
use Illuminate\Http\Request;
use Inertia\Inertia;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;


class RoundApplicationController extends Controller
{
    public function evaluate(RoundApplication $application)
    {
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

    public function checkAgainstChatGPT(RoundApplication $application)
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

        $result = $application->results()->orderBy('id', 'desc')->first();


        return redirect()->route('round.application.evaluate', ['application' => $application->id, 'result' => $result]);


        // $results = ApplicationGPTAction::make($result)->send($result->prompt_data);

        // dd($results);
    }

    // load the prompt for the application
    public static function getPrompt(RoundApplication $application)
    {

        $round = $application->round;
        $prompt = RoundPromptController::ensurePromptExists($round);

        $project = $application->project;

        $data = [
            'system_prompt' => $prompt->system_prompt,
            'prompt' => $prompt->prompt . PHP_EOL . PHP_EOL . 'Project name: ' . $project->title . PHP_EOL . 'Project website: ' . $project->website . PHP_EOL . 'Project description: ' . $project->description . PHP_EOL . 'Project twitter: ' . $project->twitter . PHP_EOL . 'Project github: ' . $project->github . PHP_EOL . PHP_EOL . 'Results should be an array of comma separated objects and returned in json format:' . PHP_EOL . PHP_EOL . '[{
                "score": 15,
                "reason": "A specific reason for the score",
                "criteria": "A specific bit of evaluation criteria"
            }]',
        ];

        return $data;
    }
}
