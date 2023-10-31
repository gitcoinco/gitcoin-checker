<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundEvaluationQuestions;
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
        return Inertia::render('Round/Evaluation', [
            'round' => $round,
        ]);
    }

    public function showQA(Round $round)
    {
        if (!$round->evaluationQuestions) {
            $questions = RoundEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
            $questions->save();
        }

        $round->load('evaluationQuestions');

        return Inertia::render('Round/EvaluationQA', [
            'round' => $round,
        ]);
    }

    public function upsert(Round $round)
    {
        $this->authorize('update', AccessControl::class);

        $validator = Validator::make(request()->all(), [
            'questions.*.text' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:select',
            'questions.*.options' => 'required|array',
            'questions.*.options.*' => 'string|max:100',
            'questions.*.weighting' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            $notificationService = app(NotificationService::class);
            $notificationService->handleValidationErrors($validator);
            return redirect()->back()->withInput();
        }

        $questions = RoundEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
        $questions->questions = request()->input('questions');
        $questions->save();

        $this->notificationService->success('Successfully updated.');

        return redirect()->route('round.evaluation.show.qa', ['round' => $round]);
    }
}
