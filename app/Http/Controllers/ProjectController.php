<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use League\CommonMark\CommonMarkConverter;

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

        return view('public.project.index', [
            'projects' => $projects,
            'canLogin' => true,
        ]);
    }

    public function sitemapPublic()
    {
        $projects = Project::orderBy('id', 'desc')->get();

        return view('public.project.sitemap', [
            'projects' => $projects,
        ]);
    }

    public function showPublic(Project $project)
    {
        $applications = $project->applications()->orderBy('id', 'desc')->with('round')->paginate();

        $converter = new CommonMarkConverter();
        $descriptionHTML = null;

        if ($project->description) {
            $descriptionHTML = $converter->convertToHTML($project->description)->getContent();
        }

        return view('public.project.show', [
            'project' => $project,
            'applications' => $applications,
            'descriptionHTML' => $descriptionHTML,
        ]);
    }
}
