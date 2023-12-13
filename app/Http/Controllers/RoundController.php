<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDonation;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Services\AddressService;
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

    public function listPublic(Request $request)
    {
        $testIds = Round::where('name', 'like', '%test%')->pluck('id')->toArray();

        $search = $request->query('search');
        if ($search) {
            $rounds = Round::search($search)->paginate();
            $rounds->load(['chain']);
            $rounds->loadCount('applications');
        } else {
            $rounds = Round::orderBy('round_start_time', 'desc')
                ->whereNotIn('id', $testIds)
                ->whereHas('applications')
                ->with(['chain'])
                ->withCount('applications')
                ->paginate();
        }

        return view('public.round.list', [
            'rounds' => $rounds,
            'search' => $search,
        ]);
    }

    public function showPublic(Round $round)
    {
        $round->load(['chain']);

        $projectAddr = $round->applications()->where('status', 'APPROVED')->pluck('project_addr')->toArray();

        //        $projects = Project::whereIn('id_addr', $projectAddr)->orderBy('id', 'desc')->paginate();

        $totalRoundDonatators = ProjectDonation::where('round_id', $round->id)->count();
        $totalRoundDonors = ProjectDonation::where('round_id', $round->id)->distinct('voter_addr')->count('voter_addr');

        $matchingCap = 0;

        if (isset($round->metadata['quadraticFundingConfig']['matchingCapAmount'])) {
            $matchingCap = ($round->metadata['quadraticFundingConfig']['matchingCapAmount'] / 100) * $round->metadata['quadraticFundingConfig']['matchingFundsAvailable'];
        }

        $projects = RoundApplication::where('round_id', $round->id)
            ->join('projects', 'round_applications.project_addr', '=', 'projects.id_addr')
            ->selectRaw('projects.title, projects.slug, projects.logoImg, round_applications.project_addr, sum(round_applications.donor_amount_usd + round_applications.match_amount_usd) as total_amount')
            ->groupBy('projects.title', 'round_applications.project_addr')
            ->orderBy('total_amount', 'desc')
            ->paginate(100);

        $totalProjectsReachingMatchingCap = 0;
        if ($matchingCap > 0) {

            foreach ($projects as $key => $project) {
                if ($project->total_amount >= $matchingCap) {
                    $totalProjectsReachingMatchingCap++;
                }
            }
        }

        $roundToken = AddressService::getTokenFromAddress($round->token);

        return view('public.round.show', [
            'round' => $round,
            'projects' => $projects,
            'pinataUrl' => env('PINATA_CLOUDFRONT_URL'),
            'totalRoundDonatators' => $totalRoundDonatators,
            'totalRoundDonors' => $totalRoundDonors,
            'totalProjectsReachingMatchingCap' => $totalProjectsReachingMatchingCap,
            'roundToken' => $roundToken,
            'matchingCap' => $matchingCap,
        ]);
    }
}
