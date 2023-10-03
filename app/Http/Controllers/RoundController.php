<?php

namespace App\Http\Controllers;

use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\NotificationService;

class RoundController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    public function index($search = null)
    {
        $rounds = Round::orderBy('flagged_at', 'desc')->orderBy('round_start_time', 'desc')->with('chain')->paginate();

        foreach ($rounds as $round) {
            $round->project_count = $round->projects()->count();
        }


        return Inertia::render('Round/Index', [
            'rounds' => $rounds
        ]);
    }

    public function search($search = null)
    {
        $rounds = Round::where('name', 'like', '%' . $search . '%')->orderBy('flagged_at', 'desc')->orderBy('round_start_time', 'desc')->with('chain')->paginate();
        return $rounds;
    }

    public function show(Round $round)
    {
        $projects = $round->projects()->with(['applications' => function ($query) use ($round) {
            $query->where('round_id', $round->id);
        }, 'applications.results' => function ($query) {
            $query->orderBy('id', 'desc')->limit(1);
        }])->paginate();

        return Inertia::render('Round/Show', [
            'round' => $round,
            'projects' => $projects,
        ]);
    }



    public function flag($id)
    {
        $round = Round::findOrFail($id);
        if ($round->flagged_at) {
            $round->flagged_at = null;
        } else {
            $round->flagged_at = Carbon::now();
        }
        $round->save();

        return response()->json($round);
    }
}
