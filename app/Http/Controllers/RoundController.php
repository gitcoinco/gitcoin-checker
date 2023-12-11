<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;

class RoundController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
        $rounds = Round::where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('round_addr', 'like', '%' . $search . '%');
        })->orderBy('flagged_at', 'desc')->orderBy('round_start_time', 'desc')->with('chain')->paginate();
        return $rounds;
    }

    public function show(Request $request, Round $round)
    {
        $projectsCount = Cache::remember('projectsCount', 60, function () {
            return Project::where('title', 'not like', '%test%')->count();
        });
        $roundsCount = Cache::remember('roundsCount', 60, function () {
            return Round::where('name', 'not like', '%test%')->count();
        });

        $roundApplicationController = new RoundApplicationController($this->notificationService);

        $applicationsReturn = $roundApplicationController->getApplications($request, $round, false);


        if ($request->wantsJson()) {
            return response()->json([
                'round' => $round,
                'projectsCount' => $projectsCount,
                'roundsCount' => $roundsCount,
                'applications' => $applicationsReturn['applications'],
                'selectedApplicationStatus' => $applicationsReturn['status'],
                'selectedApplicationRoundType' => $applicationsReturn['selectedApplicationRoundType'],
                'selectedApplicationRoundUuidList' => $applicationsReturn['selectedApplicationRoundUuidList'],
                'selectedApplicationRemoveTests' => $applicationsReturn['selectedApplicationRemoveTests'],
                'selectedSearchProjects' => $applicationsReturn['selectedSearchProjects'],
                'averageGPTEvaluationTime' => $applicationsReturn['averageGPTEvaluationTime'],
            ]);
        } else {
            return Inertia::render('Round/Show', [
                'round' => $round,
                'indexData' => env('INDEXER_URL'),
                'projectsCount' => $projectsCount,
                'roundsCount' => $roundsCount,
                'applications' => $applicationsReturn['applications'],
                'selectedApplicationStatus' => $applicationsReturn['status'],
                'selectedApplicationRoundType' => $applicationsReturn['selectedApplicationRoundType'],
                'selectedApplicationRoundUuidList' => $applicationsReturn['selectedApplicationRoundUuidList'],
                'selectedApplicationRemoveTests' => $applicationsReturn['selectedApplicationRemoveTests'],
                'selectedSearchProjects' => $applicationsReturn['selectedSearchProjects'],
                'averageGPTEvaluationTime' => $applicationsReturn['averageGPTEvaluationTime'],
            ]);
        }


        // $projects = $round->projects()
        //     ->with([
        //         'applications' => function ($query) use ($round) {
        //             $query->where('round_id', $round->id);
        //         },
        //         'applications.results' => function ($query) {
        //             $query->orderBy('id', 'desc');
        //         }
        //     ])
        //     ->orderByRaw('(SELECT MAX(round_applications.id) FROM round_applications WHERE round_applications.project_addr = projects.id_addr) DESC')
        //     ->paginate();

        // $latestPrompt = $round->prompt()->orderBy('id', 'desc')->first();

        // return Inertia::render('Round/Show', [
        //     'round' => $round,
        //     'projects' => $projects,
        //     'latestPrompt' => $latestPrompt,
        // ]);
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

        $projectAddr = $round->applications()->where('status', 'APPROVED')->pluck('project_addr')->toArray();

        $projects = Project::whereIn('id_addr', $projectAddr)->orderBy('id', 'desc')->paginate();

        return view('public.round.show', [
            'round' => $round,
            'projects' => $projects,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
        ]);
    }
}
