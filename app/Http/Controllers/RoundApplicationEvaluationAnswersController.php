<?php

namespace App\Http\Controllers;

use App\Models\RoundApplication;
use App\Models\RoundApplicationEvaluationAnswers;
use App\Models\RoundApplicationEvaluationQuestions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoundApplicationEvaluationAnswersController extends Controller
{
    public function upsert(RoundApplication $application)
    {
        $this->authorize('update', AccessControl::class);

        $this->validate(request(), [
            'answers' => 'required|array',
            'answers.*' => 'required',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $answers = RoundApplicationEvaluationAnswers::firstOrCreate(
            [
                'round_id' => $application->round_id,
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]
        );

        $answers->questions = $application->round->evaluationQuestions->questions;
        $answers->answers = request()->input('answers');
        $answers->notes = request()->input('notes');

        // Work out the score by checking for 'yes' answers, and using the question.weighting for the particular question
        $score = 0;
        foreach (json_decode($answers->questions, true) as $questionKey => $question) {
            $answer = $answers->answers[$questionKey];
            if ($question['type'] == 'radio') {
                if (Str::lower($answer) === 'yes') {
                    $score += $question['weighting'];
                }
            }
        }

        $answers->score = $score;
        $answers->save();

        return response()->json($answers);
    }

    public function index(RoundApplication $application)
    {
        // Show the user answers if they exist
        $loggedInUser = auth()->user();

        $answers = RoundApplicationEvaluationAnswers::where([
            'round_id' => $application->round_id,
            'application_id' => $application->id,
        ])->orderBy('created_at', 'desc')->with(['user'])->get();

        return response()->json([
            'answers' => $answers,
            'loggedInUser' => $loggedInUser,
        ]);
    }
}
