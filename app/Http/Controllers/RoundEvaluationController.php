<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundEvaluationQuestions;
use App\Services\NotificationService;
use Illuminate\Http\Request;
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

        $questions = RoundEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
        $questions->questions = request()->input('questions');
        $questions->save();

        $this->notificationService->success('Successfully updated.');

        return redirect()->route('round.evaluation.show.qa', ['round' => $round]);
    }
}
