@extends('public')

@section('title')
{{ $project->title }}
@endsection

@section('content')

<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">

    <div class="max-w-7xl mx-auto p-6 lg:p-8">

        <!-- Project Details -->
        <div class="flex flex-col bg-white p-4 rounded-lg mb-4">
            <h1>{{ $project->title }}</h1>
            @if(isset($project->website))
            <div><i class="fa fa-globe" aria-hidden="true"></i> Website: <a href="{{ $project->website }}" target="_blank">{{ $project->website }}</a></div>
            @endif
            @if(isset($project->projectTwitter))
            <div><i class="fa fa-twitter" aria-hidden="true"></i> Twitter: <a href="https://twitter.com/{{ $project->projectTwitter }}" target="_blank">{{ $project->projectTwitter }}</a></div>
            @endif
            @if($project->userGithub)
            <div><i class="fa fa-github" aria-hidden="true"></i> User Github: <a href="https://github.com/{{ $project->userGithub }}" target="_blank">{{ $project->userGithub }}</a></div>
            @endif
            @if($project->projectGithub)
            <div><i class="fa fa-github" aria-hidden="true"></i> Project Github: <a href="https://github.com/{{ $project->projectGithub }}" target="_blank">{{ $project->projectGithub }}</a></div>
            @endif

            @if($project->description)
            <div class="text-xs">
                {!! nl2br(e($project->description)) !!}
            </div>
            @endif


        </div>

        <!-- Applications -->
        @foreach($applications as $application)
        <div class="flex flex-col bg-white p-4 rounded-lg mb-4">
            <h2 class="text-xl">Applications</h2>
            <div>Round: {{ $application->round->name }}</div>
            <div>Status: {{ $application->status }}</div>
            <div>Applied On: {{ $application->created_at }}</div>
        </div>
        @endforeach

        <!-- Back to projects -->
        <div class="flex flex-col bg-white p-4 rounded-lg mb-4">
            <a href="{{ route('public.projects.index') }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Back to Projects</a>
        </div>
    </div>
</div>


@endsection