@extends('public')

@section('head')
<title>Welcome</title>
@endsection

@section('content')

<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">

    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <!-- <div class="flex flex-col mb-5">
            <div class="flex flex-col">
                One of the projects that has moved through the <a href="https://gitcoin.co/grants/" class="text-blue-500 hover:underline">Gitcoin Grants</a> process.
            </div>
        </div> -->

        <!-- Project Details -->
        <div class="flex flex-col bg-white p-4 rounded-lg mb-4">
            <h1>{{ $project->title }}</h1>
            @if(isset($project->website))
            <div>Website: {{ $project->website }}</div>
            @endif

            @if(isset($project->metadata['projectTwitter']))
            <div>Twitter: {{ $project->metadata['projectTwitter'] }}</div>
            @endif
            @if(isset($project->metadata['userGithub']))
            <div>Github: {{ $project->metadata['userGithub'] }}</div>
            @endif
            @if(isset($project->metadata['description']))
            <div class="text-xs">{{ $project->metadata['description'] }}</div>
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