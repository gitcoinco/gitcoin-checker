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

    public function reset(Round $round)
    {
        $this->authorize('update', AccessControl::class);

        // get defaults
        $data = RoundPromptController::promptDefaults();

        $prompt = $round->prompt;

        $prompt->system_prompt = $data['system_prompt'];
        $prompt->prompt = $data['prompt'];
        $prompt->save();

        $this->notificationService->success('Round criteria reset.');

        return redirect()->route('round.prompt.show', $round);
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

        $round->load('chain');

        return Inertia::render('Round/Prompt', [
            'round' => $round,
            'prompt' => $prompt,
            'randomApplication' => $this->getRandomApplicationPrompt($round),
        ]);
    }

    public static function promptDefaults()
    {
        $data = ['system_prompt' => file_get_contents(config_path('prompts/system.txt')), 'prompt' => file_get_contents(config_path('prompts/user.txt'))];
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
