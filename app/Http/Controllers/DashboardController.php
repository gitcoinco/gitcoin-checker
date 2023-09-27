<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $projectsCount = Project::where('title', 'not like', '%test%')->count();
        $roundsCount = Round::where('name', 'not like', '%test%')->count();


        return Inertia::render('Dashboard', [
            'indexData' => env('INDEXER_URL'),
            'projectsCount' => $projectsCount,
            'roundsCount' => $roundsCount,
        ]);
    }
}
