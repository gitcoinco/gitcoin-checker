@extends('public')

@section('title')
Gitcoin: {{ $project->title }}
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

                <div class="d-flex align-items-center mb-4">
                    <a href="{{ route('public.project.show', $project) }}">
                        <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=100' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 100px; max-width: inherit" class="mx-auto rounded-circle" />
                    </a>
                    <h1 class="card-title ml-2">{{ $project->title }}</h1>
                </div>



                @if($totalProjectDonorAmount > 0 || $totalProjectMatchAmount > 0)
                <div class="highlight-green mb-4">

                    @if($totalProjectDonorAmount > 0)
                    <h3>
                        ${{ number_format($totalProjectDonorAmount, 2) }} in donations received from {{ $totalProjectDonorContributionsCount}} donors
                    </h3>
                    @endif

                    @if($totalProjectMatchAmount > 0)
                    <h3>
                        ${{ number_format($totalProjectMatchAmount, 2) }} in donations received from match pools
                    </h3>
                    @endif
                </div>
                @endif

                <div class="mb-4">

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
                <div class="text-xs descriptionHTML">
                    {!! ($descriptionHTML) !!}
                </div>
                @endif

            </div>
        </div>

        <!-- Applications -->
        @if(count($applications) > 0)
        <div class="card mb-3">
            <div class="card-body">
                <h2>{{ $project->title }} History</h2>
                <ul>

                    @foreach($applications as $application)
                    <li class="mb-3">
                        @if(strtolower($application->status) == 'approved')
                        <div>accepted into <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> {{ $application->created_at->diffForHumans() }}.
                            @if ($application->donor_contributions_count)
                            {{ $application->donor_contributions_count }} people contributed ${{ number_format($application->donor_amount_usd) }} to the project, and ${{ number_format($application->match_amount_usd) }} of match funding was provided.
                            @endif
                        </div>
                        @elseif(strtolower($application->status) == 'rejected')
                        <div>applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> on {{ $application->created_at->diffForHumans() }} which was rejected</div>
                        @elseif(strtolower($application->status) == 'pending')
                        <div>applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> on {{ $application->created_at->diffForHumans() }} of which the application is still in a pending state</div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        @if(count($projectsAlsoDonatedTo) > 0)
        <div>
            <div class="card mb-3">
                <div class="card-body">
                    <h2>People donating to {{ $project->title }}, also donated to</h2>
                    <div>
                        @foreach($projectsAlsoDonatedTo as $project)
                        <div class="mb-2">
                            <a href="{{ route('public.project.show', $project) }}" onmouseover="this.children[0].classList.remove('grayscale');" onmouseout="this.children[0].classList.add('grayscale');">
                                <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle hover:grayscale-0 grayscale" />

                                {{ $project->title }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Back to projects -->
        <div class="card mb-3">
            <div class="card-body">
                <a href="{{ route('public.projects.list') }}" class="text-decoration-none text-primary">Back to Projects</a>
            </div>
        </div>
    </div>

    <style>
        .grayscale {
            filter: grayscale(100%);
        }

        .hover\:grayscale-0:hover {
            filter: grayscale(0%);
        }
    </style>



    @endsection