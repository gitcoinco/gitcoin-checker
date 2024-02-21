<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function indexRO(Request $request)
    {
        return Inertia::render('RO/Dashboard', [
            'indexData' => env('GRAPHQL_ENDPOINT'),
        ]);
    }

    public function index(Request $request)
    {
        // $user = $request->user();

        // if (!$user->is_admin && $user->is_round_operator) {
        //     return redirect()->route('round.index');
        // }

        $projectsCount = Cache::remember('projectsCount', 60, function () {
            return Project::where('title', 'not like', '%test%')->count();
        });
        $roundsCount = Cache::remember('roundsCount', 60, function () {
            return Round::where('name', 'not like', '%test%')->count();
        });

        $roundApplicationController = new RoundApplicationController($this->notificationService);

        // $applicationsReturn = $roundApplicationController->getApplications($request);

        if ($request->wantsJson()) {
            return response()->json([
                'projectsCount' => $projectsCount,
                'roundsCount' => $roundsCount,
                // 'applications' => $applicationsReturn['applications'],
                // 'selectedApplicationStatus' => $applicationsReturn['status'],
                // 'selectedApplicationRoundType' => $applicationsReturn['selectedApplicationRoundType'],
                // 'selectedApplicationRoundUuidList' => $applicationsReturn['selectedApplicationRoundUuidList'],
                // 'selectedApplicationRemoveTests' => $applicationsReturn['selectedApplicationRemoveTests'],
                // 'selectedSearchProjects' => $applicationsReturn['selectedSearchProjects'],
                // 'averageGPTEvaluationTime' => $applicationsReturn['averageGPTEvaluationTime'],
                // 'orderBy' => $applicationsReturn['orderBy'],
                // 'orderByDirection' => $applicationsReturn['orderDirection'],
            ]);
        } else {
            return Inertia::render('Dashboard', [
                'indexData' => env('GRAPHQL_ENDPOINT'),
                'projectsCount' => $projectsCount,
                'roundsCount' => $roundsCount,
                // 'applications' => $applicationsReturn['applications'],
                // 'selectedApplicationStatus' => $applicationsReturn['status'],
                // 'selectedApplicationRoundType' => $applicationsReturn['selectedApplicationRoundType'],
                // 'selectedApplicationRoundUuidList' => $applicationsReturn['selectedApplicationRoundUuidList'],
                // 'selectedApplicationRemoveTests' => $applicationsReturn['selectedApplicationRemoveTests'],
                // 'selectedSearchProjects' => $applicationsReturn['selectedSearchProjects'],
                // 'averageGPTEvaluationTime' => $applicationsReturn['averageGPTEvaluationTime'],
                // 'orderBy' => $applicationsReturn['orderBy'],
                // 'orderByDirection' => $applicationsReturn['orderDirection'],
            ]);
        }
    }
}
