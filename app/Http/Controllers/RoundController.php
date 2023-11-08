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

    public static function promptDefaults()
    {
        $data = ['system_prompt' => 'Act as a Gitcoin project evaluator that needs to decide whether a specific project needs to be included in a Gitcoin round based on a set of criteria.

        The round is called {{ round.name }}.
        Eligibility: {{ round.eligibility.description }}.
        Eligibility requirements: {{ round.eligibility.requirements }}.', 'prompt' => 'Evaluate the project below based on the following scoring criteria, and give each of the scores a value of 0-100. 100 is the best score, and 0 is the worst score. You can also add comments to each score to explain your reasoning.

        {{ round.eligibility.requirements }}'];

        return $data;
    }


    public function index($search = null)
    {
        $rounds = Round::orderBy('flagged_at', 'desc')
            ->orderBy('last_application_at', 'desc')
            ->with('chain')
            ->withCount('projects')
            ->paginate();

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
        $projects = $round->projects()
            ->with([
                'applications' => function ($query) use ($round) {
                    $query->where('round_id', $round->id);
                },
                'applications.results' => function ($query) {
                    $query->orderBy('id', 'desc');
                }
            ])
            ->orderByRaw('(SELECT MAX(round_applications.id) FROM round_applications WHERE round_applications.project_addr = projects.id_addr) DESC')
            ->paginate();

        $latestPrompt = $round->prompt()->orderBy('id', 'desc')->first();

        return Inertia::render('Round/Show', [
            'round' => $round,
            'projects' => $projects,
            'latestPrompt' => $latestPrompt,
        ]);
    }



    public function flag($id)
    {
        $this->authorize('update', AccessControl::class);

        $round = Round::findOrFail($id);
        if ($round->flagged_at) {
            $round->flagged_at = null;
        } else {
            $round->flagged_at = Carbon::now();
        }
        $round->save();

        return response()->json($round);
    }

    public function showPublic(Round $round)
    {
        $round->load(['chain']);

        return view('public.round.show', [
            'round' => $round,
        ]);
    }
}
