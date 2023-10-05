<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index($search = null)
    {
        $projects = Project::orderBy('id', 'desc')->paginate();

        return Inertia::render('Project/Index', [
            'projects' => $projects
        ]);
    }

    public function search($search = null)
    {
        $projects = Project::where('title', 'like', '%' . $search . '%')->orderBy('id', 'desc')->paginate();
        return $projects;
    }

    public function show(Project $project)
    {
        $applications = $project->applications()->orderBy('id', 'desc')->with('round')->paginate();

        return Inertia::render('Project/Show', [
            'project' => $project,
            'applications' => $applications
        ]);
    }

    public function indexPublic($search = null)
    {
        $projects = Project::orderBy('id', 'desc')->paginate();

        return Inertia::render('Public/Project/Index', [
            'projects' => $projects,
            'canLogin' => true,

        ]);
    }

    public function showPublic(Project $project)
    {
        $applications = $project->applications()->orderBy('id', 'desc')->with('round')->paginate();

        return Inertia::render('Public/Project/Show', [
            'project' => $project,
            'applications' => $applications,
            'canLogin' => true,
        ]);
    }
}
