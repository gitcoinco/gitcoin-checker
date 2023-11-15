<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundApplicationEvaluationQuestions;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class RoundEvaluationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    public function show(Round $round)
    {
        $round->load(['chain']);

        return Inertia::render('Round/Evaluation', [
            'round' => $round,
        ]);
    }

    public function showQA(Round $round)
    {
        if (!$round->evaluationQuestions) {
            $questions = RoundApplicationEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
            $questions->save();
        }

        $round->load(['evaluationQuestions', 'chain']);

        return Inertia::render('Round/EvaluationQA', [
            'round' => $round,
        ]);
    }

    public function upsert(Round $round)
    {
        $this->authorize('update', AccessControl::class);

        $validator = Validator::make(request()->all(), [
            'questions.*.text' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:radio',
            'questions.*.options' => 'required|array',
            'questions.*.options.*' => 'string|max:100',
            'questions.*.weighting' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            $notificationService = app(NotificationService::class);
            $notificationService->handleValidationErrors($validator);
            return redirect()->back()->withInput();
        }

        $questions = RoundApplicationEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
        $questions->questions = request()->input('questions');
        $questions->save();

        $this->notificationService->success('Successfully updated.');

        return redirect()->route('round.evaluation.show.qa', ['round' => $round]);
    }
}
