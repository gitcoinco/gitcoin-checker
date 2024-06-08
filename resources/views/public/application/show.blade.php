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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.accordion-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const content = this.nextElementSibling;
                if (content.style.display === "none") {
                    content.style.display = "block";
                } else {
                    content.style.display = "none";
                }
            });
        });
    });
</script>
@endsection


@section('content')
<div class="container-fluid bg-light">
    <div class="container py-3">

        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')
                <div class="d-flex align-items-center mb-4 flex-column">
                    <div class="d-flex flex-column justify-content-center align-items-center" style="background-image: url('{{ $pinataUrl.'/'.$project->bannerImg.'?img-height=300' }}');">
                        <a href="{{ route('public.project.show', $project) }}" class="col-12 col-md-auto">
                            <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=100' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 100px; max-width: inherit" class="mx-auto rounded-circle" alt="<?php echo $project->title; ?> logo" />
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
                <h5 class="mb-3">User Review{{ count($evaluationAnswers) > 1 ? 's' : '' }}</h5>

                @if (count($evaluationAnswers) > 0)
                @foreach($evaluationAnswers as $reviewerKey => $result)
                @php
                $questions = json_decode($result->questions);
                $answers = json_decode($result->answers);
                @endphp
                @if(is_array($questions))
                <div class="review">
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2 accordion-toggle pointer">
                            <div class="rounded-circle bg-primary text-white text-center" style="width: 30px; height: 30px; line-height: 30px;">
                                R{{$reviewerKey+1}}
                            </div>
                            <div class="ml-2">
                                Reviewed on {{ $result->created_at->format('j M Y h:i A') }}
                            </div>
                        </div>
                        <div class="accordion-content" style="<?php if (count($promptResults) + count($evaluationAnswers) > 2) {
                                                                    echo 'display: none;';
                                                                } ?>">

                            @foreach($questions as $key => $question)
                            <div class="mb-3 ">
                                <div>
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
                                </div>
                            </div>
                            @endforeach
                            @if(!empty($result->notes))
                            <div class="highlight-green mb-4 text-xs" style="opacity: 0.7;">
                                {{ $result->notes }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
                @endif



                @if (count($promptResults) > 0)
                <h5 class="mb-3">AI Review{{ count($promptResults) > 1 ? 's' : '' }}</h5>

                @foreach($promptResults as $key => $result)
                @if($result->results_data)
                <div class="review">
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2 accordion-toggle pointer">
                            <div class="rounded-circle bg-primary text-white text-center" style="width: 30px; height: 30px; line-height: 30px;">

                                A{{$key+1}}
                            </div>
                            <div class="ml-2">
                                Reviewed on {{ $result->created_at->format('j M Y h:i A') }}
                            </div>
                        </div>

                        <div class="accordion-content" style="<?php if (count($promptResults) + count($evaluationAnswers) > 2) {
                                                                    echo 'display: none;';
                                                                } ?>">

                            @foreach(json_decode($result->results_data) as $data)
                            <?php
                            if (!isset($data->score)) {
                                continue;
                            }
                            ?>
                            <div class="mb-3">
                                <div>
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
                                </div>

                                @php
                                $highlightColor = 'highlight-green';
                                if ($data->score == 'No') {
                                $highlightColor = 'highlight-red';
                                } else if ($data->score == 'Uncertain') {
                                $highlightColor = 'highlight-orange';
                                }
                                @endphp

                                <div class="{{ $highlightColor }} text-xs" style="opacity: 0.7;">
                                    {{ $data->reason }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
                @endif



                <div>
                    <a href="{{ route('public.round.show', $application->round) }}" class="text-decoration-none text-primary">Back to Round</a>
                </div>


            </div>




















        </div>
    </div>
</div>
@endsection