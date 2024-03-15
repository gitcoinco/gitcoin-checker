@extends('public')

@section('title')
Gitcoin Round: {{ $round->name }}
@endsection

@section('meta_description')
The {{ $round->name }} round was ran on {{ $round->donations_start_time }}.
@endsection

@section('breadcrumbExtra')
<div>
    <i class="text-muted">{{ $round->name }}</i>
</div>
@endsection


@section('content')

<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')
                <h1 class="card-title">{{ $round->name }}</h1>

                <div class="mb-4">
                    The {{ $round->name }} round ran on the {{ $round->chain->name }} blockchain from {{ \Carbon\Carbon::parse($round->donations_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->donations_end_time)->format('d M Y H:i') }}.
                </div>

                <?php
                $metadata = json_decode($round->round_metadata, true);
                ?>


                <div class="mb-4 d-flex justify-content-between">
                    <div>
                        <div class="mb-4">

                            @if (isset($metadata['quadraticFundingConfig']['matchingFundsAvailable']))
                            {{$roundToken}} {{ $metadata['quadraticFundingConfig']['matchingFundsAvailable'] }}<br />
                            (${{ number_format($round->match_amount_in_usd, 2) }})<br />
                            <span class="text-muted font-italic">Matching pool</span>
                            @else
                            No matching funds available
                            <span class="text-muted font-italic">Matching pool</span>
                            @endif
                        </div>
                        <div>
                            {{ $roundApplications->total() }}<br />
                            <span class="text-muted font-italic"> Total Projects
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="mb-4">
                            ${{ number_format($round->total_amount_donated_in_usd, 2)}}<br />
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
                            ${{ ($metadata['quadraticFundingConfig']['matchingCapAmount'] / 100) * $metadata['quadraticFundingConfig']['matchingFundsAvailable'] }} {{$roundToken}}<br />
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
                            {{ \Carbon\Carbon::parse($round->donations_end_time)->format('d M Y H:i') }}<br />
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



                @if(count($roundApplications) > 0)
                <div>
                    <!-- <h3>Projects in the {{ $round->name }} round.</h3> -->
                    <div>
                        @foreach($roundApplications as $roundApplication)
                        <?php
                        if (!$roundApplication->project) {
                            continue;
                        }
                        ?>
                        <div class="d-flex align-items-center mb-2 bg-light p-2">
                            <div class="mr-2">
                                <a href="{{ route('public.project.show', $roundApplication->project->slug) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    <img src="{{ $roundApplication->project->logoImg ? $pinataUrl.'/'.$roundApplication->project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle" />
                                </a>
                            </div>
                            <div class="flex-grow-1 mr-2">
                                <div>
                                    <a href="{{ route('public.project.show', $roundApplication->project->slug) }}" class="text-decoration-none text-dark align-items-center">
                                        <h6>{{ $roundApplication->project->title }}</h6>

                                        @if ($roundApplication->project->total_amount)
                                        ${{ number_format($roundApplication->project->total_amount, 2) }}
                                        @endif
                                    </a>
                                </div>
                                @if ($roundApplication->project->gpt_summary)
                                <div class="text-xs">
                                    {{ $roundApplication->project->gpt_summary }}
                                </div>
                                @endif
                            </div>

                            <div style="min-width: 80px;">
                                <a href="{{ route('public.application.show', $roundApplication->uuid) }}">

                                    @if($roundApplication->status == 'APPROVED')
                                    <span class="small text-success"><i class="fa fa-check-circle"></i> Approved</span>
                                    @elseif($roundApplication->status == 'PENDING')
                                    <span class="small text-warning"><i class="fa fa-clock-o"></i> Pending</span>
                                    @elseif($roundApplication->status == 'REJECTED')
                                    <span class="small text-danger"><i class="fa fa-times-circle"></i> Rejected</span>
                                    @endif
                                </a>

                                <?php
                                $totalReviews = $roundApplication->results->count() + $roundApplication->evaluationAnswers->count();
                                ?>
                                @if ($totalReviews > 0)
                                <div class="text-xs">
                                    <a href="{{ route('public.application.show', $roundApplication->uuid) }}">
                                        {{ $totalReviews }} review
                                        @if($totalReviews > 1)
                                        s
                                        @endif
                                    </a>
                                </div>
                                @endif

                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{ $roundApplications->links() }}

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