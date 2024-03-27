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


                    @php
                    $now = \Carbon\Carbon::now();
                    $start = \Carbon\Carbon::parse($round->donations_start_time);
                    $end = \Carbon\Carbon::parse($round->donations_end_time);
                    @endphp

                    @if($now->lt($start))
                    The {{ $round->name }} round will run on the {{ $round->chain->name }} blockchain from {{ $start->format('d M Y H:i') }} to {{ $end->format('d M Y H:i') }} (UTC).
                    @elseif($now->gt($end))
                    The {{ $round->name }} round ran on the {{ $round->chain->name }} blockchain from {{ $start->format('d M Y H:i') }} to {{ $end->format('d M Y H:i') }} (UTC).
                    @else
                    The {{ $round->name }} round is currently running on the {{ $round->chain->name }} blockchain from {{ $start->format('d M Y H:i') }} to {{ $end->format('d M Y H:i') }} (UTC).
                    @endif

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
                            @if (\Carbon\Carbon::parse($round->donations_end_time)->isPast())
                            <span class="text-muted font-italic"> Round ended on </span>
                            @else
                            <span class="text-muted font-italic"> Round will end on </span>
                            @endif
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


                        $totalReviews = $roundApplication->results->count() + $roundApplication->evaluationAnswers->count();

                        $score = 0;
                        $count = 0;

                        if ($totalReviews > 0) {
                            foreach ($roundApplication->results as $result) {
                                $score += $result->score;
                                $count++;
                            }

                            foreach ($roundApplication->evaluationAnswers as $evaluationAnswer) {
                                $score += $evaluationAnswer->score;
                                $count++;
                            }
                            if ($count > 0) {
                                $score = intval($score / $count);
                            }
                        }



                        ?>
                        <div class="d-flex align-items-center mb-5 bg-light p-2">
                            <div class="mr-2">
                                <a href="{{ route('public.project.show', $roundApplication->project->slug) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    <img src="{{ $roundApplication->project->logoImg ? $pinataUrl.'/'.$roundApplication->project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle" />
                                </a>
                            </div>
                            <div class="flex-grow-1 mr-2">
                                <div class="mb-2">
                                    <div>
                                        <a href="{{ route('public.project.show', $roundApplication->project->slug) }}" class="text-decoration-none text-dark align-items-center">
                                            <h6 class="mb-0">{{ $roundApplication->project->title }}</h6>
                                        </a>
                                    </div>
                                    @if ($roundApplication->project->gpt_summary)
                                    <div class="text-xs">{{ $roundApplication->project->gpt_summary }}</div>
                                    @endif
                                </div>

                                <div class="text-xs">
                                    <div>

                                        <a href="{{ route('public.application.show', $roundApplication->uuid) }}">
                                            {{ $totalReviews }} application review<?php if ($totalReviews > 1) {
                                                                                        echo 's';
                                                                                    } ?>
                                        </a>
                                    </div>

                                    <div>

                                        <a href="{{ route('public.application.show', $roundApplication->uuid) }}">
                                            @if($roundApplication->status == 'APPROVED')
                                            <span class="text-success"><i class="fa fa-check-circle"></i>Application approved</span>
                                            @elseif($roundApplication->status == 'PENDING')
                                            <span class="text-warning"><i class="fa fa-clock-o"></i> Pending application</span>
                                            @elseif($roundApplication->status == 'REJECTED')
                                            <span class="text-danger"><i class="fa fa-times-circle"></i>Application rejected</span>
                                            @endif
                                        </a>
                                    </div>
                                </div>




                                @if ($roundApplication->project->total_amount)
                                <div class="text-xs">
                                    Total amount: ${{ number_format($roundApplication->project->total_amount, 2) }}
                                </div>
                                @endif


                                @if ($roundApplication->donor_amount_usd > 0)
                                <div class="text-xs">
                                    Donor amount: ${{ $roundApplication->donor_amount_usd }}
                                </div>
                                @endif

                                @if ($roundApplication->match_amount_usd > 0)
                                <div class="text-xs">
                                    Match amount: ${{ $roundApplication->match_amount_usd }}
                                </div>
                                @endif

                                @if ($roundApplication->donor_contributions_count > 0)
                                <div class="text-xs">
                                    {{ $roundApplication->donor_contributions_count }} contribution<?php if ($roundApplication->donor_contributions_count > 1) {
                                                                                                        echo 's';
                                                                                                    } ?>
                                </div>
                                @endif
                            </div>

                            <div style="min-width: 80px;">
                                @if ($totalReviews > 0)
                                <a href="{{ route('public.application.show', $roundApplication->uuid) }}">
                                    <div style="font-size: 12px; border-radius: 50%;
                                            width: 42px;
                                            height: 42px;
                                            line-height: 42px;
                                            text-align: center;
                                            color: white;
                                            background-color:
                                                {{ $score < 40 ? 'red' : ($score < 70 ? 'orange' : 'green') }}">
                                        {{ $score }}%
                                    </div>
                                </a>

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