<?php

namespace App\Http\Controllers;

use App\Models\Round;
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

    public function show(Round $round)
    {
        $prompt = RoundPromptController::ensurePromptExists($round);

        return Inertia::render('Round/Prompt', [
            'round' => $round,
            'prompt' => $prompt,
        ]);
    }

    public static function ensurePromptExists(Round $round)
    {
        $prompt = $round->prompt;
        if (!$prompt) {
            $prompt = $round->prompt()->create([
                'system_prompt' => 'Act as a Gitcoin project evaluator that needs to decide whether a specific project needs to be included in a Gitcoin round based on a set of criteria.',
                'prompt' => 'Evaluate the project below based on the following scoring criteria, and give each of the scores a value of 0-100. 100 is the best score, and 0 is the worst score. You can also add comments to each score to explain your reasoning.' . PHP_EOL . PHP_EOL . '1. Be an open-source project with meaningful Github activity in the prior 3 months that has demonstrated work completed towards the project’s mission.' . PHP_EOL . '2. Primarily focused on developing on top of or advancing the broader Ethereum and/or Web3 industry.
                ',
            ]);
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
