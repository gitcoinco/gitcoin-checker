<?php

namespace App\Http\Controllers;

use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoundController extends Controller
{
    public function index($search = null)
    {
        $rounds = Round::orderBy('id', 'desc')->with('chain')->paginate();

        foreach ($rounds as $round) {
            $round->project_count = $round->projects()->count();
        }


        return Inertia::render('Round/Index', [
            'rounds' => $rounds
        ]);
    }

    public function search($search = null)
    {
        $rounds = Round::where('name', 'like', '%' . $search . '%')->orderBy('id', 'desc')->with('chain')->paginate();
        return $rounds;
    }

    public function show(Round $round)
    {
        $projects = $round->projects()->paginate();


        return Inertia::render('Round/Show', [
            'round' => $round,
            'projects' => $projects,
        ]);
    }
}
