@extends('public')

@section('title')
Gitcoin: {{ $project->title }}
@endsection

@section('meta_description')
{{ $project->title }} is a project that applied for a grant on Gitcoin.
@endsection

@section('breadcrumbExtra')
<div>
    <i class="text-muted">{{ $project->title }}</i>
</div>
@endsection


@section('content')

<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">

        <!-- Project Details -->
        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')
                <div class="d-flex align-items-center mb-4 flex-column">
                    <div class="d-flex flex-column justify-content-center align-items-center" style="background-image: url('{{ $pinataUrl.'/'.$project->bannerImg.'?img-height=300' }}');">
                        <a href="{{ route('public.project.show', $project) }}" class="col-12 col-md-auto">

                            <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=100' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 100px; max-width: inherit" class="mx-auto rounded-circle" />
                        </a>
                    </div>
                </div>



                @if($totalProjectDonorAmount > 0 || $totalProjectMatchAmount > 0)
                <div class="highlight-green mb-4">

                    @if($totalProjectDonorAmount > 0)
                    <h3>
                        ${{ number_format($totalProjectDonorAmount, 2) }} crowdfunded from {{ $totalProjectDonorContributionsCount}} people
                    </h3>
                    @endif

                    @if($totalProjectMatchAmount > 0)
                    <h3>
                        ${{ number_format($totalProjectMatchAmount, 2) }} received from matching pools
                    </h3>
                    @endif
                </div>
                @endif

                <div class="mb-4">

                    <div class="d-flex justify-content-between">

                        <div>
                            @if(isset($project->website))
                            <div><i class="fa fa-globe" aria-hidden="true"></i> <a href="{{ $project->website }}" target="_blank">{{ $project->website }}</a></div>
                            @endif
                            @if(isset($project->projectTwitter))
                            <div><i class="fa fa-twitter" aria-hidden="true"></i> <a href="https://twitter.com/{{ $project->projectTwitter }}" target="_blank">{{ $project->projectTwitter }}</a></div>
                            @endif
                            @if($project->userGithub)
                            <div><i class="fa fa-github" aria-hidden="true"></i> <a href="https://github.com/{{ $project->userGithub }}" target="_blank">{{ $project->userGithub }}</a> <span class="text-xs">(user)</span></div>
                            @endif
                            @if($project->projectGithub)
                            <div><i class="fa fa-github" aria-hidden="true"></i> <a href="https://github.com/{{ $project->projectGithub }}" target="_blank">{{ $project->projectGithub }}</a> <span class="text-xs">(project)</span></div>
                            @endif
                        </div>

                        <div>

                            <div class="d-flex border border-grey p-2 rounded text-secondary" style="max-width: 210px;">

                                <div class="mr-1" style="min-width: 42px; font-size: 12px; border-radius: 50%;
                                            width: 42px;
                                            height: 42px;
                                            line-height: 42px;
                                            text-align: center;
                                            color: white;
                                            background-color:
                                                {{ $stats['totalAverageScore'] < 40 ? 'red' : ($stats['totalAverageScore'] < 70 ? 'orange' : 'green') }}">
                                    {{ intval($stats['totalAverageScore']) }}%
                                </div>

                                <div class="small"><em>average score over {{ $stats['totalNrScores'] }} application evaluations</em></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($project->gpt_summary && $project->gpt_summary != $project->description)
                <div class="mb-4">
                    {{ $project->gpt_summary }}
                </div>
                @endif

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
                        <div>applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> {{ $application->created_at->diffForHumans() }} which was rejected</div>
                        @elseif(strtolower($application->status) == 'pending')
                        <div>applied to the <a href="{{ route('public.round.show', $application->round) }}">{{ $application->round->name }}</a> {{ $application->created_at->diffForHumans() }} of which the application is still in a pending state</div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        @if(count($projectsInterest) > 0)

        <div>
            <div class="card mb-3">
                <div class="card-body">
                    @if ($projectsInterestType == 'donated-to')
                    <h2>People donating to {{ $project->title }}, also donated to</h2>
                    @elseif ($projectsInterestType == 'random')
                    <h2>Explore projects</h2>
                    @endif
                    <div>
                        @foreach($projectsInterest as $project)
                        <div class="mb-2 d-flex">
                            <div class="mr-2">
                                <a href="{{ route('public.project.show', $project) }}" class="">
                                    <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle" />
                                </a>
                            </div>

                            <div class="">
                                <div>
                                    <a href="{{ route('public.project.show', $project) }}">
                                        {{ $project->title }}
                                    </a>
                                </div>
                                @if($project->gpt_summary)
                                <div class="text-xs text-break">
                                    {{ $project->gpt_summary }}
                                </div>
                                @endif
                            </div>
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