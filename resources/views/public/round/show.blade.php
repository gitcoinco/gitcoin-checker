@extends('public')

@section('title')
Gitcoin Round: {{ $round->name }}
@endsection

@section('meta_description')
The {{ $round->name }} round was ran on {{ $round->round_start_time }}.
@endsection


@section('content')

<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">

        <!-- Project Details -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="mb-4">
                    <a href="{{ route('public.projects.home') }}" title="View a list of projects that have applied for funding via Gitcoin">Home</a> |
                    <a href="{{ route('public.rounds.list') }}" title="View a list of rounds">Rounds</a>
                    | <span>{{ $round->name }}</span>
                </div>


                <h1 class="card-title">{{ $round->name }}</h1>

                <div class="mb-4">
                    The {{ $round->name }} round ran on the {{ $round->chain->name }} blockchain from {{ \Carbon\Carbon::parse($round->round_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }}.
                </div>

                <div class="mb-4 d-flex justify-content-between">
                    <div>
                        <div class="mb-4">
                            @if (isset($round->metadata['quadraticFundingConfig']['matchingFundsAvailable']))
                            {{$roundToken}} {{ $round->metadata['quadraticFundingConfig']['matchingFundsAvailable'] }}<br />
                            (${{ number_format($round->match_amount_usd, 2) }})<br />
                            <span class="text-muted font-italic">Matching pool</span>
                            @else
                            No matching funds available
                            <span class="text-muted font-italic">Matching pool</span>
                            @endif
                        </div>
                        <div>
                            {{ $projects->total() }}<br />
                            <span class="text-muted font-italic"> Total Projects
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="mb-4">
                            ${{ number_format($round->amount_usd, 2)}}<br />
                            <span class="text-muted font-italic"> Total USD Crowdfunded
                            </span>
                        </div>
                        <div>
                            {{ $totalRoundDonatators }}<br />
                            <span class="text-muted font-italic"> Total Donations
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            @if ($matchingCap > 0)
                            ${{ ($round->metadata['quadraticFundingConfig']['matchingCapAmount'] / 100) * $round->metadata['quadraticFundingConfig']['matchingFundsAvailable'] }} {{$roundToken}}<br />
                            <span class="text-muted font-italic"> Matching Cap
                            </span>
                            @else
                            0 matching cap<br />
                            <span class="text-muted font-italic"> No Matching Cap

                            </span>
                            @endif
                        </div>
                        <div>
                            {{ $totalRoundDonors }}<br />
                            <span class="text-muted font-italic"> Total Donors
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }}<br />
                            <span class="text-muted font-italic"> Round ended on
                            </span>
                        </div>
                        <div>
                            {{ $totalProjectsReachingMatchingCap }}<br />
                            <span class="text-muted font-italic"> Projects Reaching Matching Cap
                            </span>
                        </div>
                    </div>
                </div>



                @if(count($projects) > 0)
                <div>
                    <!-- <h3>Projects in the {{ $round->name }} round.</h3> -->
                    <div>
                        @foreach($projects as $project)
                        <div class="d-flex align-items-center mb-2 bg-light p-2">
                            <div>
                                <a href="{{ route('public.project.show', $project->slug) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle" />
                                </a>
                            </div>
                            <div class="ml-2">
                                <div>
                                    <a href="{{ route('public.project.show', $project->slug) }}" class="text-decoration-none text-dark align-items-center">
                                        <h6>{{ $project->title }}</h6>${{ number_format($project->total_amount, 2) }}
                                    </a>
                                </div>
                                @if ($project->gpt_summary)
                                <div class="text-xs">
                                    {{ $project->gpt_summary }}
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{ $projects->links() }}

                @endif
            </div>
        </div>


        <!-- Back to projects -->
        <div class="card mb-3">
            <div class="card-body">
                <a href="{{ route('public.projects.list') }}" class="text-decoration-none text-primary">Back to Projects</a>
            </div>
        </div>
    </div>
</div>


@endsection