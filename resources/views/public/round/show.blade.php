@extends('public')

@section('title')
Gitcoin Round: {{ $round->title }}
@endsection

@section('meta_description')
The {{ $round->title }} round was ran on {{ $round->round_start_time }}.
@endsection


@section('content')

<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">

        <!-- Project Details -->
        <div class="card mb-3">
            <div class="card-body">
                <h1 class="card-title">{{ $round->name }}</h1>

                <div class="mb-4">
                    The {{ $round->name }} round ran on the {{ $round->chain->name }} blockchain from {{ \Carbon\Carbon::parse($round->round_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }}.
                </div>

                @if(count($projects) > 0)
                <div>
                    <!-- <h3>Projects in the {{ $round->name }} round.</h3> -->
                    <div>
                        @foreach($projects as $project)
                        <div class="d-flex align-items-center mb-2 bg-light p-2">
                            <div>
                                <a href="{{ route('public.project.show', $project) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=50' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 50px; max-width: inherit" class="mx-auto rounded-circle" />

                                </a>

                            </div>
                            <div class="ml-2">
                                <div>
                                    <a href="{{ route('public.project.show', $project) }}" class="text-decoration-none text-dark align-items-center">
                                        <h6>{{ $project->title }}</h6>
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