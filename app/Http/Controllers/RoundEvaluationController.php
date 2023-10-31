<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundEvaluationQuestions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoundEvaluationController extends Controller
{
    public function show(Round $round)
    {
        return Inertia::render('Round/Evaluation', [
            'round' => $round,
        ]);
    }

    public function showQA(Round $round)
    {
        return Inertia::render('Round/EvaluationQA', [
            'round' => $round,
        ]);
    }

    public function upsert(Round $round)
    {
        $questions = RoundEvaluationQuestions::firstOrCreate(['round_id' => $round->id]);
        $questions->questions = request()->input('questions');
        $questions->save();

        return redirect()->route('round.evaluation.show', ['round' => $round]);
    }
}
