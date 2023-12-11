@extends('public')

@section('title')
{{ $project->title }}
@endsection

@section('meta_description')
{{ $project->title }} is a project that applied for a grant on Gitcoin.
@endsection


@section('content')

<div class="container-fluid bg-light">

    <div class="container py-3">

        <!-- Project Details -->
        <div class="card mb-3">
            <div class="card-body">
                <h1 class="card-title">{{ $project->title }}</h1>

                @if($totalProjectDonorAmount > 0)
                <h2>
                    ${{ $totalProjectDonorAmount }} in donations received from donors
                </h2>
                @endif
                @if($totalProjectMatchAmount > 0)
                <h2>
                    ${{ $totalProjectMatchAmount }} in donations received from match pools
                </h2>
                @endif

                <div class="mb-3">

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
                </div>

                @if($descriptionHTML)
                <div class="text-xs">
                    {!! ($descriptionHTML) !!}
                </div>
                @endif

            </div>
        </div>

        <!-- Applications -->
        @if(count($applications) > 0)

        @foreach($applications as $application)
        <div class="card mb-3">
            <div class="card-body">

                @if(strtolower($application->status) == 'approved')
                <div>{{ $project->title }} applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> on {{ $application->created_at }} which was accepted</div>
                @elseif(strtolower($application->status) == 'rejected')
                <div>{{ $project->title }} applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> on {{ $application->created_at }} which was rejected</div>
                @elseif(strtolower($application->status) == 'pending')
                <div>{{ $project->title }} applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> on {{ $application->created_at }} of which the application is still in a pending state</div>
                @endif

            </div>
        </div>
        @endforeach
        @endif

        <!-- Back to projects -->
        <div class="card mb-3">
            <div class="card-body">
                <a href="{{ route('public.projects.list') }}" class="text-decoration-none text-primary">Back to Projects</a>
            </div>
        </div>
    </div>
</div>


@endsection