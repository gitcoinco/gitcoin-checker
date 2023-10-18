<?php

namespace App\Http\Controllers;

use App\Models\RoundApplication;
use Illuminate\Http\Request;

class RoundApplicationUserScoreController extends Controller
{
    public function index(RoundApplication $application)
    {
        $userScores = $application->userScores()->with('user')->get();
        return response()->json([
            'userScores' => $userScores,
            'loggedInUser' => auth()->user(),
        ]);
    }

    public function upsert(RoundApplication $application)
    {
        // check that score is between 0 and 100
        $score = request()->input('score');
        if ($score < 0 || $score > 100) {
            return response()->json([
                'message' => 'Score must be between 0 and 100',
            ], 422);
        }

        $user = auth()->user();
        $score = $application->userScores()->where('user_id', $user->id)->first();
        if (!$score) {
            $score = $application->userScores()->create([
                'user_id' => $user->id,
                'application_id' => $application->id,
                'round_id' => $application->round_id,
                'score' => request()->input('score'),
                'notes' => request()->input('notes'),
            ]);
        } else {
            $score->score = request()->input('score');
            $score->notes = request()->input('notes');
            $score->save();
        }
        return response()->json($score);
    }

    public function delete(RoundApplication $application)
    {
        $user = auth()->user();
        $score = $application->userScores()->where('user_id', $user->id)->first();
        if ($score) {
            $score->delete();
        }
        return $score;
    }
}
