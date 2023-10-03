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
        $prompt = $round->prompt;
        return Inertia::render('Round/Prompt', [
            'round' => $round,
            'prompt' => $prompt,
        ]);
    }


    public function upsert(Round $round, Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'system_prompt' => 'required',
            'prompt' => 'required',
        ]);

        if ($validator->fails()) {
            $notificationService = app(NotificationService::class);
            $notificationService->handleValidationErrors($validator);
            return redirect()->back()->withInput();
        }

        // if the passed values are different to the current values
        if ($round->prompt->system_prompt !== $request->system_prompt || $round->prompt->prompt !== $request->prompt) {

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
