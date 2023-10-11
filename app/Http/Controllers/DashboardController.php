<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $projectsCount = Cache::remember('projectsCount', 60, function () {
            return Project::where('title', 'not like', '%test%')->count();
        });
        $roundsCount = Cache::remember('roundsCount', 60, function () {
            return Round::where('name', 'not like', '%test%')->count();
        });


        return Inertia::render('Dashboard', [
            'indexData' => env('INDEXER_URL'),
            'projectsCount' => $projectsCount,
            'roundsCount' => $roundsCount,
        ]);
    }
}
