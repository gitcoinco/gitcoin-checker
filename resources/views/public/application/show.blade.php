@extends('public')

@section('title')
{{ $project->title }} application on {{ $application->round->name }}
@endsection

@section('meta_description')

{{ $project->title }} application on {{ $application->round->name }} round. Current status: {{ $application->status }}. Date: {{ $application->created_at }}

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

                @if($project->gpt_summary && $project->gpt_summary != $project->description)
                <div class="mb-4">
                    <h2><a href="{{ route('public.project.show', $project) }}">{{ $project->title }}</a></h2>

                    {{ $project->gpt_summary }}
                </div>
                @endif

                <div>
                    <h5>Application</h5>

                    <div>Applied on: {{ $application->created_at->format('j M Y h:i A') }}</div>

                    <div>Round: <a href="{{ route('public.round.show', $application->round->uuid) }}">{{ $application->round->name }}</a></div>

                    <div>
                        @if($application->status == 'APPROVED')
                        <i class="fa fa-check-circle text-success"></i> Approved
                        @elseif($application->status == 'PENDING')
                        <i class="fa fa-clock-o text-warning"></i> Pending
                        @elseif($application->status == 'REJECTED')
                        <i class="fa fa-times-circle text-danger"></i> Rejected
                        @endif
                    </div>

                </div>

            </div>
        </div>


        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">User Review<?php if (count($evaluationAnswers) > 1) {
                                                echo 's';
                                            } ?> </h5>

                @if (count($evaluationAnswers) > 0)
                @foreach($evaluationAnswers as $reviewerKey => $result)
                @php
                $questions = json_decode($result->questions);
                $answers = json_decode($result->answers);
                @endphp

                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-primary text-white text-center" style="width: 30px; height: 30px; line-height: 30px;">
                            R{{$reviewerKey+1}}
                        </div>
                        <div class="ml-2">
                            Reviewed on {{ $result->created_at->format('j M Y h:i A') }}
                        </div>
                    </div>

                    @foreach($questions as $key => $question)
                    <div class="mb-3 text-xs">
                        <h6>
                            <div>
                                @if($answers[$key] == 'Yes')
                                <i class="fa fa-check-circle text-success"></i>
                                @elseif($answers[$key] == 'No')
                                <i class="fa fa-times-circle text-danger"></i>
                                @else
                                <i class="fa fa-question-circle text-warning"></i>
                                @endif

                                <span class="text-muted" style="{{ str_word_count($question->text) > 20 ? 'font-size: 0.8em;' : '' }}">

                                    {{ $question->text }}
                                </span>
                            </div>
                        </h6>
                    </div>
                    @endforeach
                    @if(!empty($result->notes))
                    <div class="highlight-green mb-4 text-xs" style="opacity: 0.7;">
                        {{ $result->notes }}
                    </div>
                    @endif
                </div>
                @endforeach
                @else
                <div>
                    No manual reviews yet.
                </div>
                @endif

                <div>
                    <a href="{{ route('public.round.show', $application->round) }}" class="text-decoration-none text-primary">Back to Round</a>
                </div>
            </div>


        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">AI Review<?php if (count($promptResults) > 1) {
                                                echo 's';
                                            } ?></h5>

                @foreach($promptResults as $result)
                @foreach(json_decode($result->results_data) as $data)
                <div class="mb-3 text-xs">
                    <h6 class="mb-0">
                        <div>
                            @if($data->score == 'Yes')
                            <i class="fa fa-check-circle text-success"></i>
                            @elseif($data->score == 'No')
                            <i class="fa fa-times-circle text-danger"></i>
                            @else
                            <i class="fa fa-question-circle text-warning"></i>
                            @endif

                            <span class="text-muted" style="{{ str_word_count($data->criteria) > 20 ? 'font-size: 0.8em;' : '' }}">
                                {{ $data->criteria }}
                            </span>
                        </div>
                    </h6>

                    <?php
                    $highlightColor = 'highlight-green';
                    if ($data->score == 'No') {
                        $highlightColor = 'highlight-red';
                    } else if ($data->score == 'Uncertain') {
                        $highlightColor = 'highlight-orange';
                    }

                    ?>

                    <div class="{{ $highlightColor }} text-xs" style="opacity: 0.7;">
                        {{ $data->reason }}
                    </div>
                </div>
                @endforeach
                @endforeach
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