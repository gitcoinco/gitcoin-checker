<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;


class RoundPromptController extends Controller
{

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    private function getRandomApplicationPrompt(Round $round)
    {
        $randomApplication = $round->applications()->inRandomOrder()->first();
        if (!$randomApplication) {
            return null;
        }

        $randomApplication->generated_prompt = RoundApplicationController::getPrompt($randomApplication);

        return $randomApplication;
    }

    public function show(Round $round)
    {
        $prompt = $round->prompt;
        if (!$prompt) {
            $this->notificationService->info('We have populated the prompt with some default text.');
        }

        $prompt = RoundPromptController::ensurePromptExists($round);

        return Inertia::render('Round/Prompt', [
            'round' => $round,
            'prompt' => $prompt,
            'randomApplication' => $this->getRandomApplicationPrompt($round),
        ]);
    }

    public static function promptDefaults()
    {
        $data = ['system_prompt' => 'Act as a Gitcoin project evaluator that needs to decide whether a specific project needs to be included in a Gitcoin round based on a set of criteria.' . PHP_EOL . PHP_EOL . 'The round is called {{ round.name }}.' . PHP_EOL . PHP_EOL . 'Eligibility: {{ round.eligibility.description }}.' . PHP_EOL . 'Eligibility requirements:' . PHP_EOL . '{{ round.eligibility.requirements }}', 'prompt' => 'Evaluate the project below based on the following scoring criteria, and give each of the scores a value of 0-100. 100 is the best score, and 0 is the worst score. You can also add comments to each score to explain your reasoning.' . PHP_EOL . PHP_EOL . '{{ project.details }}' . PHP_EOL . PHP_EOL . '{{ application.answers }}' . PHP_EOL . PHP_EOL . 'Historic project applications (if the project was approved in the past, this counts in their favour):' . PHP_EOL . '{{ project.historic_applications }}.'];
        return $data;
    }

    public static function ensurePromptExists(Round $round)
    {
        $prompt = $round->prompt;
        if (!$prompt) {
            $data = RoundPromptController::promptDefaults();
            $prompt = $round->prompt()->create($data);
        }

        return $prompt;
    }


    public function upsert(Round $round, Request $request)
    {
        $this->authorize('update', AccessControl::class);

        $validator = Validator::make(request()->all(), [
            'system_prompt' => 'required',
            'prompt' => 'required',
        ]);

        if ($validator->fails()) {
            $notificationService = app(NotificationService::class);
            $notificationService->handleValidationErrors($validator);
            return redirect()->back()->withInput();
        }

        $prompt = $round->prompt;

        // if the passed values are different to the current values
        if ($prompt->system_prompt !== $request->system_prompt || $prompt->prompt !== $request->prompt) {

            // delete the current prompt if it exists and create a new prompt
            $round->prompt()->delete();

            $round->prompt()->create($request->all());


            $this->notificationService->success('New round criteria specified.');
        } else {
            $this->notificationService->info('Round criteria not changed.');
        }

        return redirect()->route('round.prompt.show', $round);
    }
}
